<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SkillTagService;

class SkillTagController extends Controller
{
    public function __construct(
        protected SkillTagService $skillTagService
    ) {}

    public function index()
    {
        $user = auth()->user();

        if ($user->isFaculty()) {
            $tags = $this->skillTagService->getTagsForUser($user->id);
            return view('skill-tags.index', compact('tags'));
        }

        $summary = $this->skillTagService->getAllFacultySkillsSummary();
        return view('skill-tags.index', compact('summary'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tag_name' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9\s\/\.\+\#\-]+$/'],
        ], [
            'tag_name.regex' => 'Tag may only contain letters, numbers, spaces, and basic symbols (+#/.-)',
        ]);

        try {
            $this->skillTagService->addTag(auth()->id(), $validated['tag_name']);
            return redirect()->back()->with('success', 'Skill tag added successfully.');
        } catch (\Illuminate\Database\QueryException $e) {
            return redirect()->back()->with('error', 'This tag already exists.');
        }
    }

    public function destroy($id)
    {
        $this->skillTagService->deleteTag($id, auth()->id());
        return redirect()->back()->with('success', 'Skill tag removed.');
    }
}
