<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use App\Models\TeachingGuide;
use App\Models\User;
use App\Services\NotificationService;
use App\Support\UploadStorage;
use Illuminate\Http\Request;

class TeachingGuideController extends Controller
{
    public function __construct(protected NotificationService $notificationService) {}

    public function index(Request $request)
    {
        $user = auth()->user();
        $search = $request->query('search');
        $semesterFilter = $request->query('semester');
        $folderFilter = $request->query('folder_id');

        $query = TeachingGuide::with('uploader.employee', 'folder')
            ->forUser($user)
            ->latest();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        if ($folderFilter) {
            $query->where('folder_id', $folderFilter);
        }

        $guides = $query->paginate(15);

        // Get TG folder tree for filter/upload folder selection
        $tgRoot = Folder::where('slug', 'tg-category')->with(['children.children'])->first();

        $role = $this->getViewRole($user);
        return view("{$role}.teaching-guides", compact('guides', 'search', 'semesterFilter', 'folderFilter', 'tgRoot'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        // Only Dean, Secretary, Coordinator can upload
        if ($user->isFaculty()) {
            abort(403);
        }

        $validated = $request->validate([
            'title'     => 'required|string|max:150',
            'subject'   => 'required|string|max:100',
            'folder_id' => 'required|exists:folders,folder_id',
            'files'     => 'required|array|min:1',
            'files.*'   => 'required|file|max:10240|mimes:pdf,doc,docx|mimetypes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);

        $folder = Folder::findOrFail($validated['folder_id']);
        $quotaService = app(\App\Services\StorageQuotaService::class);
        $uploadedCount = 0;

        foreach ($request->file('files') as $file) {
            // Block double-extension attacks
            if (preg_match('/\.(php|phtml|exe|sh|bat|cmd|com|vbs|js|jsp|asp|aspx)(\.|$)/i', $file->getClientOriginalName())) {
                continue;
            }

            if (!$quotaService->hasQuotaForBytes($user->id, (int) ($file->getSize() ?? 0))) {
                return back()->with('error', 'Storage quota exceeded (limit: ' . $quotaService->formatBytes(\App\Services\StorageQuotaService::DEFAULT_QUOTA_BYTES) . ').');
            }

            $extension = strtolower($file->getClientOriginalExtension());
            $fileType  = $extension === 'pdf' ? 'pdf' : 'word';
            $filename  = time() . '_' . $file->hashName();
            $storedPath = UploadStorage::storeAs($file, 'teaching-guides', $filename);

            $guide = TeachingGuide::create([
                'user_id'   => $user->id,
                'title'     => $validated['title'],
                'file_path' => $storedPath,
                'file_type' => $fileType,
                'subject'   => $validated['subject'],
                'folder_id' => $validated['folder_id'],
                'semester'  => $this->semesterFromFolder($folder),
                'academic_year' => $this->academicYearFromFolder($folder),
            ]);

            $uploadedCount++;
        }

        if ($uploadedCount === 0) {
            return back()->with('error', 'No valid files were uploaded.');
        }

        // Notify all active Faculty users
        $facultyIds = User::whereHas('role', fn($q) => $q->where('role_name', 'Faculty Employee'))
            ->where('status', 'Active')
            ->pluck('id')
            ->toArray();

        if (!empty($facultyIds)) {
            $label = $uploadedCount === 1
                ? "\"{$validated['title']}\" ({$folder->folder_name})"
                : "{$uploadedCount} teaching guides in \"{$folder->folder_name}\"";
            $this->notificationService->notifyMany(
                $facultyIds,
                "New teaching guide uploaded: {$label}. Check the Teaching Guides section."
            );
        }

        $msg = $uploadedCount === 1
            ? 'Teaching guide uploaded and faculty notified.'
            : "{$uploadedCount} teaching guides uploaded and faculty notified.";

        return back()->with('success', $msg);
    }

    public function download($id)
    {
        $user = auth()->user();
        $guide = TeachingGuide::findOrFail($id);

        UploadStorage::assertResolvedPath($guide->file_path, 'teaching-guides');

        return UploadStorage::downloadResponse($guide->file_path, basename($guide->file_path));
    }

    public function destroy($id)
    {
        $user = auth()->user();

        // Faculty cannot delete
        if ($user->isFaculty()) abort(403);

        $guide = TeachingGuide::findOrFail($id);
        UploadStorage::delete($guide->file_path);
        $guide->delete();

        return back()->with('success', 'Teaching guide deleted.');
    }

    private function getViewRole($user): string
    {
        if ($user->isDean() || $user->isSecretary()) return 'dean';
        if ($user->isProgramCoordinator()) return 'coordinator';
        return 'faculty';
    }

    /** Derive semester label from folder name (e.g. "1st Semester AY...") */
    private function semesterFromFolder(Folder $folder): string
    {
        $name = $folder->folder_name . ($folder->parent ? ' ' . $folder->parent->folder_name : '');
        if (str_contains($name, '1st')) return '1st';
        if (str_contains($name, '2nd')) return '2nd';
        return '1st';
    }

    private function academicYearFromFolder(Folder $folder): string
    {
        // Try to extract "2025-2026" pattern from folder name or parent
        $text = $folder->folder_name . ' ' . ($folder->parent?->folder_name ?? '');
        if (preg_match('/(\d{4})-(\d{4})/', $text, $m)) {
            return $m[1] . '-' . $m[2];
        }
        $y = now()->month >= 8 ? now()->year : now()->year - 1;
        return $y . '-' . ($y + 1);
    }
}
