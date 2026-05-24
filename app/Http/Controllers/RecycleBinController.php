<?php

namespace App\Http\Controllers;

use App\Services\RecycleBinService;
use Illuminate\Http\Request;

class RecycleBinController extends Controller
{
    public function __construct(
        protected RecycleBinService $recycleBin,
    ) {}

    public function index()
    {
        $documents = $this->recycleBin->paginateForUser(auth()->user());
        $canForceDelete = auth()->user()->isDean();

        return view($this->viewName(), compact('documents', 'canForceDelete'));
    }

    public function restore(Request $request, int $id)
    {
        $result = $this->recycleBin->restore($id, auth()->user());

        $message = 'Document restored successfully.';
        if ($result['folder_missing']) {
            $message .= ' The original folder no longer exists, so it was placed in Uncategorized.';
        }

        return redirect()
            ->route($this->routePrefix() . '.recycle-bin.index')
            ->with('success', $message);
    }

    public function forceDelete(Request $request, int $id)
    {
        $this->recycleBin->forceDelete($id, auth()->user());

        return redirect()
            ->route($this->routePrefix() . '.recycle-bin.index')
            ->with('success', 'Document permanently deleted.');
    }

    public function bulkForceDelete(Request $request)
    {
        $validated = $request->validate([
            'document_ids' => 'required|array|min:1|max:50',
            'document_ids.*' => 'integer|min:1',
        ]);

        $count = $this->recycleBin->bulkForceDelete($validated['document_ids'], auth()->user());

        return redirect()
            ->route($this->routePrefix() . '.recycle-bin.index')
            ->with('success', $count === 1
                ? '1 document permanently deleted.'
                : "{$count} documents permanently deleted.");
    }

    protected function routePrefix(): string
    {
        $user = auth()->user();

        if ($user->isDeanOrSecretary()) {
            return 'dean';
        }

        if ($user->isProgramCoordinator()) {
            return 'coordinator';
        }

        return 'faculty';
    }

    protected function viewName(): string
    {
        return $this->routePrefix() . '.recycle-bin';
    }
}
