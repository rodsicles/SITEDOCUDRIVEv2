<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\Document;
use App\Models\Employee;
use App\Models\Folder;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class GlobalSearchService
{
    public function search(User $user, string $query, int $limitPerGroup = 6): array
    {
        $query = trim($query);
        if (mb_strlen($query) < 3) {
            return [];
        }

        $results = [];

        foreach ($this->searchDocuments($user, $query, $limitPerGroup) as $row) {
            $results[] = $row;
        }

        foreach ($this->searchAnnouncements($user, $query, $limitPerGroup) as $row) {
            $results[] = $row;
        }

        foreach ($this->searchUsers($user, $query, $limitPerGroup) as $row) {
            $results[] = $row;
        }

        if ($user->isDean() || $user->isProgramCoordinator()) {
            foreach ($this->searchEmployees($user, $query, $limitPerGroup) as $row) {
                $results[] = $row;
            }
        }

        foreach ($this->searchTasks($user, $query, $limitPerGroup) as $row) {
            $results[] = $row;
        }

        return $results;
    }

    private function searchDocuments(User $user, string $query, int $limit): Collection
    {
        $documentsRoute = match (true) {
            $user->isDean() => 'dean.documents',
            $user->isProgramCoordinator() => 'coordinator.documents',
            default => 'faculty.documents',
        };

        $items = Document::query()
            ->with(['folder.parent.parent.parent', 'uploader.employee'])
            ->visibleTo($user)
            ->onlyApprovedShareable()
            ->where(function ($q) use ($query) {
                $q->where('document_title', 'like', "%{$query}%")
                    ->orWhere('document_type', 'like', "%{$query}%")
                    ->orWhere('tags', 'like', "%{$query}%")
                    ->orWhere('subject', 'like', "%{$query}%");
            })
            ->latest()
            ->limit($limit)
            ->get();

        return $items->map(function (Document $document) use ($documentsRoute, $query) {
            $path = $this->folderBreadcrumb($document->folder);
            $tab = $this->tabSlugForFolder($document->folder);

            $params = array_filter([
                'tab' => $tab,
                'folder' => $document->folder_id,
                'search' => $query,
            ]);

            return [
                'title' => $document->document_title,
                'subtitle' => $path ?: 'Uncategorized',
                'type' => 'Document',
                'url' => route($documentsRoute, $params),
            ];
        });
    }

    private function searchAnnouncements(User $user, string $query, int $limit): Collection
    {
        return Announcement::query()
            ->active()
            ->visibleTo($user)
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('body', 'like', "%{$query}%");
            })
            ->ordered()
            ->limit($limit)
            ->get()
            ->map(fn (Announcement $announcement) => [
                'title' => $announcement->title,
                'subtitle' => Str::limit(strip_tags((string) $announcement->body), 80) ?: 'Announcement',
                'type' => 'Announcement',
                'url' => route('announcements.index', ['highlight' => $announcement->announcement_id]),
            ]);
    }

    private function searchUsers(User $user, string $query, int $limit): Collection
    {
        $usersQuery = User::query()
            ->where('status', 'Active')
            ->with(['employee', 'role'])
            ->where(function ($q) use ($query) {
                $q->where('username', 'like', "%{$query}%")
                    ->orWhere('name', 'like', "%{$query}%")
                    ->orWhereHas('employee', fn ($e) => $e->where('full_name', 'like', "%{$query}%"));
            });

        if ($user->isFaculty()) {
            $usersQuery->where('id', $user->id);
        } elseif ($user->isProgramCoordinator()) {
            $dept = optional($user->employee)->department;
            if ($dept) {
                $usersQuery->whereHas('employee', fn ($e) => $e->where('department', $dept));
            } else {
                $usersQuery->where('id', $user->id);
            }
        }

        return $usersQuery
            ->limit($limit)
            ->get()
            ->map(function (User $found) use ($user) {
                $name = $found->employee->full_name ?? $found->username;
                $roleName = $found->role->role_name ?? 'User';
                $dept = $found->employee->department ?? null;

                $url = match (true) {
                    $user->isDean() => route('dean.employees'),
                    $user->isProgramCoordinator() => route('coordinator.faculty'),
                    default => route('profile.edit'),
                };

                return [
                    'title' => $name,
                    'subtitle' => trim($roleName.($dept ? " · {$dept}" : '')),
                    'type' => 'User',
                    'url' => $url,
                ];
            });
    }

    private function searchEmployees(User $user, string $query, int $limit): Collection
    {
        $employeeQuery = Employee::query()
            ->whereHas('user', fn ($q) => $q->where('status', 'Active'))
            ->where(function ($q) use ($query) {
                $q->where('full_name', 'like', "%{$query}%")
                    ->orWhere('employee_no', 'like', "%{$query}%")
                    ->orWhere('department', 'like', "%{$query}%");
            });

        if ($user->isProgramCoordinator()) {
            $dept = optional($user->employee)->department;
            if ($dept) {
                $employeeQuery->where('department', $dept);
            }
        }

        $url = $user->isDean() ? route('dean.employees') : route('coordinator.faculty');

        return $employeeQuery
            ->limit($limit)
            ->get()
            ->map(fn (Employee $employee) => [
                'title' => $employee->full_name,
                'subtitle' => $employee->department ?? 'Employee',
                'type' => 'Employee',
                'url' => $url,
            ]);
    }

    private function searchTasks(User $user, string $query, int $limit): Collection
    {
        $tasksQuery = Task::query()->where(function ($q) use ($query) {
            $q->where('task_title', 'like', "%{$query}%")
                ->orWhere('task_description', 'like', "%{$query}%");
        });

        if ($user->isFaculty()) {
            $tasksQuery->where('assigned_to', $user->id);
        } elseif ($user->isProgramCoordinator()) {
            $tasksQuery->where('assigned_by', $user->id);
        }

        $url = match (true) {
            $user->isDean() => route('dean.dashboard'),
            $user->isProgramCoordinator() => route('coordinator.tasks'),
            $user->isFaculty() => route('faculty.tasks'),
            default => '#',
        };

        return $tasksQuery
            ->limit($limit)
            ->get()
            ->map(fn (Task $task) => [
                'title' => $task->task_title,
                'subtitle' => 'Status: '.$task->status,
                'type' => 'Task',
                'url' => $url,
            ]);
    }

    private function folderBreadcrumb(?Folder $folder): string
    {
        if (!$folder) {
            return '';
        }

        $names = array_map(
            fn (Folder $f) => $f->folder_name,
            $folder->getAncestors()
        );
        $names[] = $folder->folder_name;

        return implode(' › ', $names);
    }

    private function tabSlugForFolder(?Folder $folder): string
    {
        if (!$folder) {
            return 'accreditation-and-certifications';
        }

        $top = $folder;
        while ($top->parent_id !== null) {
            if (!$top->relationLoaded('parent')) {
                $top->load('parent');
            }
            $parent = $top->parent;
            if (!$parent) {
                break;
            }
            $top = $parent;
        }

        return Str::slug($top->folder_name);
    }
}
