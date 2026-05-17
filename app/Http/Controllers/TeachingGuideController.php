<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use App\Models\TeachingGuide;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\TeachingGuideSyncService;
use App\Support\UploadStorage;
use Illuminate\Http\Request;

class TeachingGuideController extends Controller
{
    use \App\Http\Controllers\Concerns\HandlesUploadExceptions;

    public function __construct(
        protected NotificationService $notificationService,
        protected TeachingGuideSyncService $teachingGuideSync,
    ) {}

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
            'recipient_ids' => 'required|array|min:1',
            'recipient_ids.*' => 'integer|exists:users,id',
        ]);

        $recipientIds = array_values(array_unique(array_map('intval', $validated['recipient_ids'])));

        $folder = Folder::findOrFail($validated['folder_id']);
        $quotaService = app(\App\Services\StorageQuotaService::class);
        $uploadedCount = 0;

        try {
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
                    'semester'  => $this->teachingGuideSync->semesterFromFolder($folder),
                    'academic_year' => $this->teachingGuideSync->academicYearFromFolder($folder),
                ]);

                $this->teachingGuideSync->syncRecipients($guide, $recipientIds);
                $this->teachingGuideSync->syncDocumentFromGuide($guide, $user, $recipientIds);

                $uploadedCount++;
            }
        } catch (\Throwable $e) {
            return $this->uploadFailedResponse($request, $e);
        }

        if ($uploadedCount === 0) {
            return back()->with('error', 'No valid files were uploaded.');
        }

        if (!empty($recipientIds)) {
            $label = $uploadedCount === 1
                ? "\"{$validated['title']}\" ({$folder->folder_name})"
                : "{$uploadedCount} teaching guides in \"{$folder->folder_name}\"";
            $this->notificationService->notifyMany(
                $recipientIds,
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
        $guide = TeachingGuide::forUser($user)->findOrFail($id);

        $storageDir = str_starts_with($guide->file_path, 'documents/') ? 'documents' : 'teaching-guides';
        UploadStorage::assertResolvedPath($guide->file_path, $storageDir);

        if (!UploadStorage::exists($guide->file_path)) {
            return back()->with('error', 'This file is no longer available. It was uploaded to a previous storage provider and no longer exists in the current storage.');
        }

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

}
