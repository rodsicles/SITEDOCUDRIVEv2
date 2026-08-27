<?php

namespace App\Http\Controllers;

use App\Models\DashboardLog;
use App\Models\Document;
use Illuminate\Http\Request;

class DocumentCommentController extends Controller
{
    public function store(Request $request, int $id)
    {
        $document = Document::findOrFail($id);
        $user = auth()->user();

        if (!$document->canView($user)) {
            abort(403, 'Unauthorized access');
        }

        $validated = $request->validate([
            'comment' => 'required|string|max:2000',
        ]);

        $comment = $document->comments()->create([
            'user_id' => $user->id,
            'comment' => $validated['comment'],
        ]);

        DashboardLog::create([
            'user_id' => $user->id,
            'document_id' => $document->document_id,
            'activity' => 'Commented on document: ' . $document->document_title,
            'activity_type' => 'document_commented',
            'visibility' => 'own',
        ]);

        return back()->with('success', 'Comment added.');
    }

    public function destroy(int $id, int $commentId)
    {
        $document = Document::findOrFail($id);
        $user = auth()->user();

        $comment = $document->comments()->where('comment_id', $commentId)->firstOrFail();

        $canDelete = (int) $comment->user_id === (int) $user->id || $user->isDeanOrSecretary();

        if (!$canDelete) {
            abort(403, 'You are not allowed to delete this comment.');
        }

        $comment->delete();

        DashboardLog::create([
            'user_id' => $user->id,
            'document_id' => $document->document_id,
            'activity' => 'Deleted a comment on document: ' . $document->document_title,
            'activity_type' => 'document_comment_deleted',
            'visibility' => 'own',
        ]);

        return back()->with('success', 'Comment deleted.');
    }
}
