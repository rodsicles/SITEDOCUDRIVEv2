<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Services\CourseService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeanCourseController extends Controller
{
    public function __construct(
        protected CourseService $courseService,
    ) {}

    public function index(Request $request)
    {
        $departmentFilter = $request->query('department', 'all');
        $search = $request->query('search');

        $courses = $this->courseService->listAll($departmentFilter, $search);
        $departments = CourseService::departments();

        return view('dean.courses', compact('courses', 'departments', 'departmentFilter', 'search'));
    }

    public function store(Request $request)
    {
        $departments = array_keys(CourseService::departments());

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z]{2,4}\d{2,4}$/'],
            'title' => 'required|string|max:150',
            'department' => ['required', Rule::in($departments)],
        ]);

        $exists = Course::where('code', strtoupper($validated['code']))
            ->where('department', $validated['department'])
            ->exists();

        if ($exists) {
            return back()->withInput()->with('error', 'This course code already exists for that department.');
        }

        $this->courseService->create($validated, auth()->id());

        return back()->with('success', 'Course added. Faculty and coordinators in that department can now select it when uploading.');
    }

    public function update(Request $request, Course $course)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z]{2,4}\d{2,4}$/'],
            'title' => 'required|string|max:150',
        ]);

        $newCode = strtoupper(trim($validated['code']));

        $duplicate = Course::where('code', $newCode)
            ->where('department', $course->department)
            ->where('id', '!=', $course->id)
            ->exists();

        if ($duplicate) {
            return back()->with('error', "Course code {$newCode} already exists in {$course->department}.");
        }

        $this->courseService->rename($course, $newCode, trim($validated['title']), auth()->id());

        return back()->with('success', 'Course updated successfully.');
    }

    public function destroy(Course $course)
    {
        $this->courseService->deactivate($course, auth()->id());

        return back()->with('success', 'Course removed from upload choices.');
    }

    public function restore(Course $course)
    {
        $this->courseService->reactivate($course, auth()->id());

        return back()->with('success', 'Course restored to upload choices.');
    }
}
