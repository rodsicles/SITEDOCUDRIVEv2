@extends('layouts.dashboard')

@section('title', 'Course Catalog - Program Coordinator')

@section('page-title', 'Course Catalog')
@section('page-subtitle', 'Manage ' . ($department ?? 'department') . ' courses for faculty uploads')

@section('sidebar')
    @include('partials.coordinator-sidebar')
@endsection

@section('content')
    @include('partials.course-catalog', [
        'routePrefix' => 'coordinator',
        'lockedDepartment' => $department,
        'deptSlug' => $deptSlug,
        'courses' => $courses,
        'departmentFilter' => $departmentFilter,
        'search' => $search,
    ])
@endsection
