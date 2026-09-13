<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\DashboardLog;
use App\Models\Document;
use App\Models\DocumentRequest;
use App\Models\DocumentRequestRecipient;
use App\Models\DocumentSearchIndex;
use App\Models\Notification;
use App\Models\SchoolYear;
use App\Models\User;
use App\Jobs\IndexDocumentContentJob;
use App\Support\UploadStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DocumentRequestController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $tab = $request->string('tab')->value() === 'sent' ? 'sent' : 'received';
        $query = DocumentRequest::query()->with(['requester.employee', 'course', 'schoolYear', 'recipients.recipient.employee', 'recipients.document']);

        if ($tab === 'sent') {
            $query->where('requested_by', $user->id);
        } else {
            $query->whereHas('recipients', fn ($q) => $q->where('user_id', $user->id));
        }

        if ($request->filled('status')) {
            $status = $request->string('status')->value();
            if ($tab === 'sent') {
                $query->whereHas('recipients', fn ($q) => $q->where('status', $status));
            } else {
                $query->whereHas('recipients', fn ($q) => $q->where('user_id', $user->id)->where('status', $status));
            }
        }

        $requests = $query->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END')->orderBy('due_at')->latest('id')->paginate(20)->withQueryString();
        $people = User::query()->with(['employee', 'role'])->where('status', 'Active')->whereKeyNot($user->id)->orderBy('username')->get();

        return view('document-requests.index', [
            'requests' => $requests,
            'tab' => $tab,
            'people' => $people,
            'courses' => Course::active()->ordered()->get(),
            'schoolYears' => SchoolYear::orderByDesc('start_year')->get(),
            'pendingCount' => DocumentRequestRecipient::where('user_id', $user->id)->whereIn('status', ['pending', 'changes_requested'])->count(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'instructions' => ['nullable', 'string', 'max:2000'],
            'document_type' => ['required', Rule::in(['any', 'pdf', 'word', 'image'])],
            'recipient_ids' => ['required', 'array', 'min:1'],
            'recipient_ids.*' => ['integer', Rule::exists('users', 'id')->where('status', 'Active')],
            'course_id' => ['nullable', 'exists:courses,id'],
            'department' => ['nullable', Rule::in(['Information Technology', 'Engineering'])],
            'school_year_id' => ['nullable', 'exists:school_years,id'],
            'semester' => ['nullable', Rule::in(['1st', '2nd'])],
            'due_at' => ['nullable', 'date', 'after:now'],
            'allow_late_submission' => ['nullable', 'boolean'],
        ]);

        $recipientIds = collect($validated['recipient_ids'])->map(fn ($id) => (int) $id)->unique()->reject(fn ($id) => $id === (int) $request->user()->id);
        abort_if($recipientIds->isEmpty(), 422, 'Choose at least one other employee.');

        $documentRequest = DB::transaction(function () use ($validated, $recipientIds, $request) {
            $item = DocumentRequest::create([
                ...collect($validated)->except('recipient_ids')->all(),
                'requested_by' => $request->user()->id,
                'allow_late_submission' => $request->boolean('allow_late_submission', true),
            ]);
            foreach ($recipientIds as $userId) {
                $item->recipients()->create(['user_id' => $userId]);
                Notification::create([
                    'user_id' => $userId,
                    'message' => 'Document requested: "'.$item->title.'"'.($item->due_at ? ' — due '.$item->due_at->format('M d, Y g:i A') : ''),
                    'tone' => Notification::TONE_SUCCESS,
                    'action_url' => route('document-requests.index', ['tab' => 'received']),
                    'is_read' => false,
                ]);
            }
            DashboardLog::create([
                'user_id' => $request->user()->id,
                'activity' => 'Requested "'.$item->title.'" from '.$recipientIds->count().' employee(s)',
                'activity_type' => 'document_request_created',
            ]);
            return $item;
        });

        return redirect()->route('document-requests.index', ['tab' => 'sent'])->with('success', 'Document request sent to '.$recipientIds->count().' employee(s).');
    }

    public function submit(Request $request, DocumentRequestRecipient $recipient)
    {
        abort_unless((int) $recipient->user_id === (int) $request->user()->id, 403);
        $recipient->load('request.course');
        abort_if($recipient->request->status !== 'open', 422, 'This request is closed.');
        abort_if($recipient->request->due_at?->isPast() && !$recipient->request->allow_late_submission, 422, 'The submission deadline has passed.');

        $rules = ['file' => ['required', 'file', 'max:10240']];
        $rules['file'][] = match ($recipient->request->document_type) {
            'pdf' => 'mimes:pdf', 'word' => 'mimes:doc,docx',
            'image' => 'mimes:jpg,jpeg,png,gif,webp', default => 'mimes:pdf,doc,docx,jpg,jpeg,png,gif,webp',
        };
        $validated = $request->validate($rules);
        $file = $validated['file'];
        $path = UploadStorage::storeAs($file, 'documents/requests', Str::uuid().'.'.strtolower($file->getClientOriginalExtension()));

        try {
            $document = DB::transaction(function () use ($recipient, $request, $file, $path) {
                $document = Document::create([
                    'uploaded_by' => $request->user()->id,
                    'document_title' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                    'file_path' => $path,
                    'file_size' => $file->getSize() ?: 0,
                    'document_type' => strtolower($file->getClientOriginalExtension()),
                    'category' => 'Other',
                    'school_year_id' => $recipient->request->school_year_id,
                    'subject' => $recipient->request->course?->code,
                    'tags' => 'document-request,compliance',
                    'version' => 1,
                ]);
                $document->recipients()->syncWithoutDetaching([$recipient->request->requested_by]);
                $recipient->update([
                    'submitted_document_id' => $document->document_id,
                    'status' => 'submitted',
                    'submitted_at' => now(),
                    'review_note' => null,
                ]);
                Notification::create([
                    'user_id' => $recipient->request->requested_by,
                    'message' => ($request->user()->employee->full_name ?? $request->user()->username).' submitted "'.$recipient->request->title.'".',
                    'tone' => Notification::TONE_SUCCESS,
                    'action_url' => route('document-requests.index', ['tab' => 'sent']),
                    'is_read' => false,
                ]);
                return $document;
            });
        } catch (\Throwable $e) {
            UploadStorage::delete($path);
            throw $e;
        }

        $realPath = $file->getRealPath();
        DocumentSearchIndex::updateOrCreate(
            ['document_id' => $document->document_id],
            ['file_hash' => $realPath ? (hash_file('sha256', $realPath) ?: null) : null, 'index_status' => 'pending']
        );
        IndexDocumentContentJob::dispatch($document->document_id)->afterResponse();
        return back()->with('success', 'Document submitted successfully.');
    }

    public function review(Request $request, DocumentRequestRecipient $recipient)
    {
        $recipient->load('request');
        abort_unless((int) $recipient->request->requested_by === (int) $request->user()->id, 403);
        $validated = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'changes_requested'])],
            'review_note' => ['nullable', 'string', 'max:1000', Rule::requiredIf($request->input('decision') === 'changes_requested')],
        ]);
        abort_unless($recipient->status === 'submitted', 422, 'Only submitted documents can be reviewed.');

        $recipient->update([
            'status' => $validated['decision'],
            'review_note' => $validated['review_note'] ?? null,
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
        ]);
        $recipient->request->update([
            'status' => $recipient->request->recipients()->where('status', '!=', 'approved')->exists() ? 'open' : 'completed',
        ]);
        Notification::create([
            'user_id' => $recipient->user_id,
            'message' => 'Your submission for "'.$recipient->request->title.'" was '.str_replace('_', ' ', $validated['decision']).'.'.(!empty($validated['review_note']) ? ' Note: '.$validated['review_note'] : ''),
            'tone' => $validated['decision'] === 'approved' ? Notification::TONE_SUCCESS : Notification::TONE_DANGER,
            'action_url' => route('document-requests.index'),
            'is_read' => false,
        ]);
        return back()->with('success', $validated['decision'] === 'approved' ? 'Submission approved.' : 'Correction request sent.');
    }
}
