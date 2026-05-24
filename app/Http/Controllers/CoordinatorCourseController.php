<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Services\CourseService;
use App\Support\CoordinatorDepartment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CoordinatorCourseController extends Controller
{
    public function __construct(
        protected CourseService $courseService,
    ) {}

    public function index(Request $request)
    {
        $department = $this->coordinatorDepartment();
        $deptSlug = CourseService::departmentToSlug($department);
        $allowedFilters = ['all', 'inactive', $deptSlug];
        $requested = $request->query('department');
        $departmentFilter = $requested === null
            ? $deptSlug
            : (in_array($requested, $allowedFilters, true) ? $requested : $deptSlug);

        $filter = $departmentFilter === 'inactive' ? 'inactive' : 'all';
        $search = $request->query('search');

        $courses = $this->courseService->listForDepartment($department, $filter, $search);

        return view('coordinator.courses', compact(
            'courses',
            'department',
            'departmentFilter',
            'deptSlug',
            'search',
        ));
    }

    public function store(Request $request)
    {
        $department = $this->coordinatorDepartment();

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z]{2,4}\d{2,4}$/'],
            'title' => 'required|string|max:150',
        ]);

        $validated['department'] = $department;

        $exists = Course::where('code', strtoupper($validated['code']))
            ->where('department', $department)
            ->exists();

        if ($exists) {
            return back()->withInput()->with('error', 'This course code already exists for your department.');
        }

        $this->courseService->create($validated, auth()->id());

        return back()->with('success', 'Course added. Faculty in your department can now select it when uploading.');
    }

    public function update(Request $request, Course $course)
    {
        $this->authorizeCourse($course);

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
        $this->authorizeCourse($course);
        $this->courseService->deactivate($course, auth()->id());

        return back()->with('success', 'Course removed from upload choices.');
    }

    public function restore(Course $course)
    {
        $this->authorizeCourse($course);
        $this->courseService->reactivate($course, auth()->id());

        return back()->with('success', 'Course restored to upload choices.');
    }

    private function coordinatorDepartment(): string
    {
        $dept = CoordinatorDepartment::require(auth()->user());

        if (!in_array($dept, [Course::DEPT_IT, Course::DEPT_ENGINEERING], true)) {
            abort(403, 'Your account must be assigned to Information Technology or Engineering to manage the course catalog.');
        }

        return $dept;
    }

    private function authorizeCourse(Course $course): void
    {
        if ($course->department !== $this->coordinatorDepartment()) {
            abort(403, 'You do not have access to courses outside your department.');
        }
    }
}
