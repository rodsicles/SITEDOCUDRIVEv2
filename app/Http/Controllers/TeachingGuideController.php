<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use App\Models\TeachingGuide;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
            'file'      => 'required|file|max:10240|mimes:pdf,doc,docx',
        ]);

        $file = $request->file('file');

        // Block double-extension attacks
        if (preg_match('/\.(php|phtml|exe|sh|bat|cmd|com|vbs|js|jsp|asp|aspx)(\.|$)/i', $file->getClientOriginalName())) {
            return back()->with('error', 'Invalid file type.');
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $fileType  = $extension === 'pdf' ? 'pdf' : 'word';
        $filename  = time() . '_' . $file->hashName();
        $file->storeAs('teaching-guides', $filename, 'local');

        $folder = Folder::findOrFail($validated['folder_id']);

        $guide = TeachingGuide::create([
            'user_id'   => $user->id,
            'title'     => $validated['title'],
            'file_path' => 'teaching-guides/' . $filename,
            'file_type' => $fileType,
            'subject'   => $validated['subject'],
            'folder_id' => $validated['folder_id'],
            'semester'  => $this->semesterFromFolder($folder),
            'academic_year' => $this->academicYearFromFolder($folder),
        ]);

        // Notify all active Faculty users
        $facultyIds = User::whereHas('role', fn($q) => $q->where('role_name', 'Faculty Employee'))
            ->where('status', 'Active')
            ->pluck('id')
            ->toArray();

        if (!empty($facultyIds)) {
            $this->notificationService->notifyMany(
                $facultyIds,
                "A new teaching guide has been uploaded: \"{$guide->title}\" ({$folder->folder_name}). Check the Teaching Guides section."
            );
        }

        return back()->with('success', 'Teaching guide uploaded and faculty notified.');
    }

    public function download($id)
    {
        $user = auth()->user();
        $guide = TeachingGuide::findOrFail($id);

        $path = Storage::disk('local')->path($guide->file_path);
        if (!file_exists($path)) abort(404, 'File not found.');

        return response()->download($path, basename($guide->file_path));
    }

    public function destroy($id)
    {
        $user = auth()->user();

        // Faculty cannot delete
        if ($user->isFaculty()) abort(403);

        $guide = TeachingGuide::findOrFail($id);
        Storage::disk('local')->delete($guide->file_path);
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
