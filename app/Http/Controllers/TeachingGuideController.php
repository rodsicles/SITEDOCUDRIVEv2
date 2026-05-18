<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use App\Models\SchoolYear;
use App\Models\TeachingGuide;
use App\Services\AcademicHierarchyService;
use App\Services\NotificationService;
use App\Services\TeachingGuideSyncService;
use App\Support\AcademicYear;
use App\Support\IteSubjects;
use App\Support\UploadStorage;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TeachingGuideController extends Controller
{
    use \App\Http\Controllers\Concerns\HandlesUploadExceptions;

    public function __construct(
        protected NotificationService $notificationService,
        protected TeachingGuideSyncService $teachingGuideSync,
        protected AcademicHierarchyService $hierarchy,
    ) {}

    public function index(Request $request)
    {
        $user = auth()->user();
        $search = $request->query('search');
        $semesterFilter = $request->query('semester');
        $academicYearStart = AcademicYear::startYearFromQuery($request->query('academic_year'));

        $query = TeachingGuide::with('uploader.employee', 'folder')
            ->forUser($user)
            ->latest();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        if ($semesterFilter) {
            $query->where('semester', $semesterFilter);
        }

        if ($academicYearStart) {
            $range = AcademicYear::rangeString($academicYearStart);
            $query->where('academic_year', $range);
        }

        $guides = $query->paginate(15)->appends($request->query());
        $archiveYears = array_filter(
            AcademicYear::availableStartYears(),
            fn ($y) => AcademicYear::isArchived($y)
        );

        $role = $this->getViewRole($user);

        return view("{$role}.teaching-guides", compact(
            'guides', 'search', 'semesterFilter', 'academicYearStart', 'archiveYears'
        ));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        if ($user->isFaculty()) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'subject' => ['required', 'string', Rule::in(IteSubjects::labels())],
            'academic_year_start' => 'required|integer',
            'semester' => 'required|in:1st,2nd',
            'assessment_period' => 'required|in:prelims,midterms,finals',
            'guide_type' => 'required|in:teaching-guides,lesson,lab-manual',
            'version_type' => 'required|in:revisions,final',
            'files' => 'required|array|min:1',
            'files.*' => 'required|file|max:10240|mimes:pdf,doc,docx|mimetypes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'recipient_ids' => 'required|array|min:1',
            'recipient_ids.*' => 'integer|exists:users,id',
        ]);

        $startYear = (int) $validated['academic_year_start'];
        $folder = $this->hierarchy->resolveTeachingGuideFolder(
            $startYear,
            $validated['semester'],
            $validated['subject'],
            $validated['assessment_period'],
            $validated['guide_type'],
            $validated['version_type'],
        );

        if (!$folder) {
            return back()->with('error', 'Could not create folder structure. Please contact the administrator.');
        }

        $recipientIds = array_values(array_unique(array_map('intval', $validated['recipient_ids'])));
        $quotaService = app(\App\Services\StorageQuotaService::class);
        $uploadedCount = 0;
        $academicYear = AcademicYear::rangeString($startYear);

        try {
            foreach ($request->file('files') as $file) {
                if (preg_match('/\.(php|phtml|exe|sh|bat|cmd|com|vbs|js|jsp|asp|aspx)(\.|$)/i', $file->getClientOriginalName())) {
                    continue;
                }

                if (!$quotaService->hasQuotaForBytes($user->id, (int) ($file->getSize() ?? 0))) {
                    return back()->with('error', 'Storage quota exceeded (limit: ' . $quotaService->formatBytes(\App\Services\StorageQuotaService::DEFAULT_QUOTA_BYTES) . ').');
                }

                $extension = strtolower($file->getClientOriginalExtension());
                $fileType = $extension === 'pdf' ? 'pdf' : 'word';
                $filename = time() . '_' . $file->hashName();
                $storedPath = UploadStorage::storeAs($file, 'teaching-guides', $filename);

                $guide = TeachingGuide::create([
                    'user_id' => $user->id,
                    'title' => $validated['title'],
                    'file_path' => $storedPath,
                    'file_type' => $fileType,
                    'subject' => $validated['subject'],
                    'folder_id' => $folder->folder_id,
                    'school_year_id' => SchoolYear::activeId(),
                    'semester' => $validated['semester'],
                    'academic_year' => $academicYear,
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

        if ($user->isFaculty()) {
            abort(403);
        }

        $guide = TeachingGuide::findOrFail($id);
        UploadStorage::delete($guide->file_path);
        $guide->delete();

        return back()->with('success', 'Teaching guide deleted.');
    }

    private function getViewRole($user): string
    {
        if ($user->isDean() || $user->isSecretary()) {
            return 'dean';
        }
        if ($user->isProgramCoordinator()) {
            return 'coordinator';
        }

        return 'faculty';
    }
}
