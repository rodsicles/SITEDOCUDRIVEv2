<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Support\CoordinatorDepartment;
use Illuminate\Http\Request;

class CourseHistoryController extends Controller
{
    public function __invoke(Request $request, Course $course)
    {
        $user = $request->user();
        abort_unless($user->isDeanOrSecretary() || ($user->isProgramCoordinator() && CoordinatorDepartment::handles($user, $course->program)), 403);
        $changes = $course->changes()->with('actor.employee')->paginate(20);
        return view('courses.history', compact('course', 'changes'));
    }
}
