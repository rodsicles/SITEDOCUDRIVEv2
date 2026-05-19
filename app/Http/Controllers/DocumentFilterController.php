<?php

namespace App\Http\Controllers;

use App\Models\DocumentFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DocumentFilterController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'category' => 'nullable|string|max:50',
            'folder' => 'nullable',
            'tab' => 'nullable|string|max:50',
            'search' => 'nullable|string|max:100',
            'file_type' => 'nullable|in:pdf,word',
            'size_range' => 'nullable|in:small,medium,large',
            'sort' => 'nullable|in:size,date,title,author,category',
            'sort_dir' => 'nullable|in:asc,desc',
            'title' => 'nullable|string|max:100',
            'tag' => 'nullable|string|max:100',
            'uploaded_by' => 'nullable|integer|exists:users,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'academic_year' => 'nullable|string|max:10',
        ]);

        $filters = collect([
            'category' => $validated['category'] ?? null,
            'folder' => $validated['folder'] ?? null,
            'tab' => $validated['tab'] ?? null,
            'search' => $validated['search'] ?? null,
            'file_type' => $validated['file_type'] ?? null,
            'size_range' => $validated['size_range'] ?? null,
            'sort' => $validated['sort'] ?? null,
            'sort_dir' => $validated['sort_dir'] ?? null,
            'title' => $validated['title'] ?? null,
            'tag' => $validated['tag'] ?? null,
            'uploaded_by' => $validated['uploaded_by'] ?? null,
            'date_from' => $validated['date_from'] ?? null,
            'date_to' => $validated['date_to'] ?? null,
            'academic_year' => $validated['academic_year'] ?? null,
        ])->filter(static fn ($value) => $value !== null && $value !== '')->all();

        DocumentFilter::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'name' => $validated['name'],
            ],
            [
                'filters' => $filters,
            ]
        );

        return back()->with('success', 'Filter saved successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $filter = DocumentFilter::where('document_filter_id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $filter->delete();

        return back()->with('success', 'Saved document filter deleted successfully.');
    }
}