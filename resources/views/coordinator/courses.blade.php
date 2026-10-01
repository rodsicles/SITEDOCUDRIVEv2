@extends('layouts.dashboard')

@section('title', 'Course Catalog - Program Coordinator')

@section('page-title', 'Course Catalog')
@section('page-subtitle', 'Manage ' . (count($departments) > 1 ? implode(', ', array_keys($departments)) : ($department ?? 'program')) . ' courses for faculty uploads')

@section('sidebar')
    @include('partials.coordinator-sidebar')
@endsection

@section('content')
    @include('partials.course-catalog', [
        'routePrefix' => 'coordinator',
        'lockedDepartment' => count($departments) > 1 ? null : $department,
        'departments' => $departments,
        'deptSlug' => $deptSlug,
        'courses' => $courses,
        'departmentFilter' => $departmentFilter,
        'search' => $search,
    ])
@endsection
