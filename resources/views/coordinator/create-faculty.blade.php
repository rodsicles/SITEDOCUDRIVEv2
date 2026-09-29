@extends('layouts.dashboard')

@section('title', 'Create Faculty Account')

@section('page-title', 'Add Faculty Member')
@section('page-subtitle', 'Create a faculty account for any SITE program')

@section('sidebar')
    @include('partials.coordinator-sidebar')
@endsection

@section('content')
    @include('partials.account-create-form', [
        'formKey' => 'faculty',
        'action' => route('coordinator.store-faculty'),
        'coursesUrl' => route('coordinator.courses.by-program'),
        'numberPreview' => $facultyNumberPreview ?? [],
        'defaultProgram' => $defaultProgram ?? null,
        'title' => 'Create faculty account',
        'intro' => 'Choose the faculty member\'s program, set sign-in credentials, and assign that program\'s subjects.',
        'submitLabel' => 'Create Faculty Account',
        'submitIcon' => 'fa-user-plus',
        'cancelUrl' => route('coordinator.faculty'),
        'numberExample' => 'SITE-IT-FAC001',
    ])

    @include('partials.course-assignment-guide-script')
@endsection
