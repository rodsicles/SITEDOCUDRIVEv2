<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PreventsDuplicateUpload;
use App\Http\Controllers\Concerns\ValidatesDocumentUpload;
use App\Http\Requests\RenameDocumentRequest;
use App\Http\Requests\StoreFolderRequest;
use App\Models\Document;
use App\Models\ExamQuestionnaire;
use App\Models\Folder;
use App\Models\SchoolYear;
use App\Models\TeachingGuide;
use App\Models\User;
use App\Services\AcademicHierarchyService;
use App\Services\ArchiveContentService;
use App\Services\DocumentService;
use App\Services\StorageQuotaService;
use App\Support\IteSubjects;
use App\Support\CoordinatorDepartment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ArchiveContentController extends Controller
{
    use PreventsDuplicateUpload;
    use ValidatesDocumentUpload;

    public function __construct(private ArchiveContentService $archives) {}

    public function destinations(Request $request, SchoolYear $year)
    {
        $request->validate(['folder' => 'nullable|integer']);
        abort_unless($year->isArchived() && $this->archives->canAdd($request->user()), 403);
        return response()->json($this->archives->describe($year, $request->user(), $request->filled('folder') ? (int) $request->folder : null));
    }

    public function subject(Request $request, SchoolYear $year, AcademicHierarchyService $hierarchy)
    {
        $data = $request->validate([
            'folder' => 'required|integer',
            'subject' => ['required', Rule::in(IteSubjects::labelsForUser($request->user()))],
        ]);
        $folder = $this->archives->folderOrFail($year, (int) $data['folder'], $request->user());
        abort_unless($hierarchy->isTgSemesterFolder($folder) || $hierarchy->isEqSemesterFolder($folder), 422);
        $subjectFolder = $hierarchy->isTgSemesterFolder($folder)
            ? $hierarchy->ensureSubjectWithTgLb($folder, $data['subject'])
            : $hierarchy->ensureSubjectWithEqStructure($folder, $data['subject']);
        abort_unless((int) $subjectFolder->school_year_id === (int) $year->id, 409);
        return response()->json(['id' => $subjectFolder->folder_id]);
    }

    public function upload(Request $request, SchoolYear $year, DocumentService $documents, StorageQuotaService $quota)
    {
        $folder = $this->archives->folderOrFail($year, (int) $request->input('folder_id'), $request->user());
        abort_unless($this->archives->isUploadable($folder), 422, 'Choose a valid archive destination.');
        $validated = $this->validateDocumentUpload($request);
        if (!empty($validated['recipient_ids'])) {
            $recipientIds = array_values(array_unique(array_map('intval', $validated['recipient_ids'])));
            $eligible = User::query()->whereIn('id', $recipientIds)->where('status', 'Active')
                ->whereHas('role', fn ($q) => $q->where('role_name', 'Faculty Employee'));
            if ($request->user()->isProgramCoordinator()) {
                $programs = CoordinatorDepartment::requirePrograms($request->user());
                $eligible->whereHas('employee', fn ($q) => $q->whereIn('program', $programs));
            }
            abort_unless($eligible->count() === count($recipientIds), 422, 'Choose eligible faculty recipients.');
        }
        $files = $request->file('documents');
        foreach ($files as $file) {
            abort_if(preg_match('/\.(php|phtml|phar|exe|sh|bat|cmd|com|cgi|pl|py|jsp|asp|aspx|htaccess)/i', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)), 422, 'File contains a forbidden extension in its name.');
        }
        abort_unless($quota->hasQuotaForBytes($request->user()->id, $quota->sumUploadedSizes($files)), 422, 'Storage quota exceeded.');
        if ($duplicate = $this->rejectDuplicateUpload($request)) {
            return $duplicate;
        }

        $result = $documents->uploadDocuments($validated, $files, $request->user()->id, $validated['recipient_ids'] ?? [], $year->id);
        if ($result['count'] === 0) {
            throw ValidationException::withMessages(['documents' => 'No files were added. Check the selected files and try again.']);
        }
        $this->archives->log($year, $request->user(), 'archive_files_added', 'Added '.$result['count'].' file(s) to archive');
        $message = $result['submitted_for_approval']
            ? $result['count'].' file(s) submitted for approval in '.$year->name.'.'
            : $result['count'].' file(s) added to '.$year->name.'.';
        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => $message])
            : back()->with('success', $message);
    }

    public function createFolder(StoreFolderRequest $request, SchoolYear $year)
    {
        $data = $request->validated();
        $this->archives->createFolder($year, $request->user(), $data['folder_name'], $data['parent_id'] ?? null);
        return back()->with('success', 'Folder added to '.$year->name.'.');
    }

    public function renameFolder(Request $request, SchoolYear $year, Folder $folder)
    {
        $data = $request->validate(['folder_name' => ['required', 'string', 'max:13', 'regex:/^[a-zA-Z0-9\s\-_]+$/']]);
        $this->archives->renameFolder($year, $request->user(), $folder, $data['folder_name']);
        return back()->with('success', 'Archive folder renamed.');
    }

    public function renameDocument(RenameDocumentRequest $request, SchoolYear $year, Document $document, DocumentService $documents)
    {
        abort_unless($year->isArchived() && (int) $document->school_year_id === (int) $year->id, 404);
        $old = $document->document_title;
        $title = $request->validated('document_title');
        $documents->renameDocument($document->document_id, $request->user(), $title);
        $this->archives->log($year, $request->user(), 'archive_document_renamed', "Renamed archive file from \"{$old}\" to \"{$title}\"");
        return back()->with('success', 'Archive file renamed.');
    }

    public function renameGuide(RenameDocumentRequest $request, SchoolYear $year, TeachingGuide $guide, DocumentService $documents)
    {
        abort_unless($year->isArchived() && (int) $guide->school_year_id === (int) $year->id, 404);
        abort_unless($guide->isVisibleTo($request->user()), 404);
        abort_unless(! $guide->folder || $guide->folder->canBeViewedBy($request->user()), 404);
        abort_unless($request->user()->isDeanOrSecretary() || $request->user()->isProgramCoordinator(), 403);
        $old = $guide->title;
        $title = $request->validated('document_title');
        if ($guide->document_id) {
            $documents->renameDocument($guide->document_id, $request->user(), $title);
        } else {
            $guide->update(['title' => $title]);
        }
        $this->archives->log($year, $request->user(), 'archive_guide_renamed', "Renamed archived teaching guide from \"{$old}\" to \"{$title}\"");
        return back()->with('success', 'Teaching guide renamed.');
    }

    public function renameQuestionnaire(RenameDocumentRequest $request, SchoolYear $year, ExamQuestionnaire $questionnaire, DocumentService $documents)
    {
        abort_unless($year->isArchived() && (int) $questionnaire->school_year_id === (int) $year->id, 404);
        abort_unless($questionnaire->isVisibleTo($request->user()), 404);
        abort_unless(! $questionnaire->document || $questionnaire->document->canView($request->user()), 404);
        abort_unless($request->user()->isDeanOrSecretary() || $request->user()->isProgramCoordinator(), 403);
        $old = $questionnaire->title;
        $title = $request->validated('document_title');
        if ($questionnaire->document_id) {
            $documents->renameDocument($questionnaire->document_id, $request->user(), $title);
        } else {
            $questionnaire->update(['title' => $title]);
        }
        $this->archives->log($year, $request->user(), 'archive_questionnaire_renamed', "Renamed archived exam questionnaire from \"{$old}\" to \"{$title}\"");
        return back()->with('success', 'Exam questionnaire renamed.');
    }
}
