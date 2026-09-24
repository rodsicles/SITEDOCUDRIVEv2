@extends('layouts.dashboard')

@section('title', 'Course Catalog - Dean')

@section('page-title', 'Course Catalog')
@section('page-subtitle', 'Manage courses for BLIS, BSEnSE, BSIT and BSCpE')

@section('sidebar')
    @include('partials.dean-sidebar')
@endsection

@section('content')
    @include('partials.course-catalog', [
        'routePrefix' => 'dean',
        'courses' => $courses,
        'programs' => $departments,
        'departmentFilter' => $departmentFilter,
        'search' => $search,
    ])
@endsection
