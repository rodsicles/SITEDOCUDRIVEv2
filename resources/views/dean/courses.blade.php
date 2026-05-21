@extends('layouts.dashboard')

@section('title', 'Course Catalog - Dean')

@section('page-title', 'Course Catalog')
@section('page-subtitle', 'Manage ITE and Engineering courses for faculty uploads')

@section('sidebar')
    @include('partials.dean-sidebar')
@endsection

@section('content')
    @include('partials.course-catalog', [
        'routePrefix' => 'dean',
        'courses' => $courses,
        'departments' => $departments,
        'departmentFilter' => $departmentFilter,
        'search' => $search,
    ])
@endsection
