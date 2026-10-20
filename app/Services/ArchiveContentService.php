<?php

namespace App\Services;

use App\Models\DashboardLog;
use App\Models\Document;
use App\Models\Folder;
use App\Models\SchoolYear;
use App\Models\User;
use App\Support\IteSubjects;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ArchiveContentService
{
    public function __construct(private AcademicHierarchyService $hierarchy) {}

    public function canAdd(User $user): bool
    {
        return $user->isDeanOrSecretary() || $user->isProgramCoordinator() || $user->isFaculty();
    }

    public function folderOrFail(SchoolYear $year, int $folderId, User $user): Folder
    {
        abort_unless($year->isArchived() && $this->canAdd($user), 403);
        $folder = Folder::query()->where('school_year_id', $year->id)->findOrFail($folderId);
        foreach ([...$folder->getAncestors(), $folder] as $ancestor) {
            abort_unless($ancestor->canBeViewedBy($user), 404);
            if ($ancestor->managedCategory) {
                abort_unless($ancestor->managedCategory->owner_id === null
                    || (int) $ancestor->managedCategory->owner_id === (int) $user->id, 404);
                abort_unless($ancestor->managedCategory->is_active, 422, 'This category is inactive.');
            }
            if ($this->hierarchy->isTgSubjectFolder($ancestor) || $this->hierarchy->isEqSubjectFolder($ancestor)) {
                abort_unless(in_array($ancestor->folder_name, IteSubjects::labelsForUser($user), true), 404);
            }
        }

        return $folder;
    }

    public function isAcademic(Folder $folder): bool
    {
        return in_array(app(DocumentService::class)->resolveCategoryForFolder($folder->folder_id), Document::SHAREABLE_CATEGORIES, true);
    }

    public function isUploadable(Folder $folder): bool
    {
        return $this->isAcademic($folder)
            ? $this->hierarchy->isTgUploadLeafFolder($folder) || $this->hierarchy->isEqUploadLeafFolder($folder)
            : ! $folder->isCustomFoldersCategory();
    }

    public function label(Folder $folder, SchoolYear $year): string
    {
        $parent = $folder->parent;
        return $parent && (int) $parent->school_year_id !== (int) $year->id
            ? $parent->folder_name.' / '.$folder->folder_name
            : $folder->folder_name;
    }

    public function folders(SchoolYear $year, User $user, ?int $parentId = null): Collection
    {
        $query = Folder::query()->where('school_year_id', $year->id)->visibleTo($user);
        if ($parentId) {
            $query->where('parent_id', $parentId);
        } else {
            $query->where(function ($q) use ($year) {
                $q->whereNull('parent_id')->orWhereHas('parent', fn ($parent) => $parent->where('school_year_id', '!=', $year->id)->orWhereNull('school_year_id'));
            });
        }

        return $query->orderBy('sort_order')->orderBy('folder_name')->get()
            ->filter(function (Folder $folder) use ($year, $user) {
                try {
                    $this->folderOrFail($year, $folder->folder_id, $user);
                    return true;
                } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
                    return false;
                }
            })->values();
    }

    public function describe(SchoolYear $year, User $user, ?int $folderId): array
    {
        $folder = $folderId ? $this->folderOrFail($year, $folderId, $user) : null;
        $children = $this->folders($year, $user, $folderId);
        $semester = $folder && ($this->hierarchy->isTgSemesterFolder($folder) || $this->hierarchy->isEqSemesterFolder($folder));
        $serialize = fn (Folder $item) => ['id' => $item->folder_id, 'name' => $this->label($item, $year)];
        $breadcrumbs = $folder ? collect($folder->getAncestors())
            ->filter(fn (Folder $ancestor) => (int) $ancestor->school_year_id === (int) $year->id)
            ->push($folder)->map($serialize)->values() : collect();

        return [
            'folder' => $folder ? $serialize($folder) : null,
            'breadcrumbs' => $breadcrumbs,
            'folders' => $children->map($serialize)->values(),
            'uploadable' => $folder && $this->isUploadable($folder),
            'academic' => $folder && $this->isAcademic($folder),
            'subjects' => $semester ? IteSubjects::labelsForUser($user) : [],
            'url' => route(($user->isDeanOrSecretary() ? 'dean' : ($user->isProgramCoordinator() ? 'coordinator' : 'faculty')).'.archives.show', $year),
        ];
    }

    public function createFolder(SchoolYear $year, User $user, string $name, ?int $parentId): Folder
    {
        abort_unless($year->isArchived() && $this->canAdd($user), 403);
        $parent = $parentId ? $this->folderOrFail($year, $parentId, $user) : null;
        if ($parent) {
            abort_if($this->isAcademic($parent), 422, 'Use the provided academic folders.');
            abort_unless($parent->is_system || (int) $parent->user_id === (int) $user->id, 403);
            abort_if($parent->isCustomSubfolder(), 422, 'Only one custom subfolder level is allowed.');
        }

        return DB::transaction(function () use ($year, $user, $name, $parent) {
            $folder = Folder::create([
                'user_id' => $user->id,
                'folder_name' => $name,
                'color' => '#0d5c3b',
                'parent_id' => $parent?->folder_id,
                'is_system' => false,
                'is_private' => (bool) $parent?->is_private,
                'privacy_owner_id' => $parent?->privacy_owner_id,
                'level' => $parent ? (int) $parent->level + 1 : 0,
                'sort_order' => (int) Folder::where('parent_id', $parent?->folder_id)->max('sort_order') + 1,
                'slug' => 'archive-'.$year->id.'-'.$user->id.'-'.Str::slug($name).'-'.Str::random(8),
                'school_year_id' => $year->id,
                'document_category_id' => $parent?->document_category_id,
            ]);
            $this->log($year, $user, 'archive_folder_created', "Created archive folder: {$name}");
            return $folder;
        });
    }

    public function renameFolder(SchoolYear $year, User $user, Folder $folder, string $name): void
    {
        $this->folderOrFail($year, $folder->folder_id, $user);
        abort_unless(! $folder->is_system && (int) $folder->user_id === (int) $user->id, 403);
        $old = $folder->folder_name;
        DB::transaction(function () use ($folder, $name, $old, $year, $user) {
            $folder->update(['folder_name' => $name]);
            $this->log($year, $user, 'archive_folder_renamed', "Renamed archive folder from \"{$old}\" to \"{$name}\"");
        });
    }

    public function log(SchoolYear $year, User $user, string $type, string $activity): void
    {
        DashboardLog::create([
            'user_id' => $user->id,
            'activity' => $activity.' ('.$year->name.')',
            'activity_type' => $type,
            'visibility' => $user->isDeanOrSecretary() ? 'dean' : 'own',
        ]);
    }
}
