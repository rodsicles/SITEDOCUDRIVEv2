@extends('layouts.dashboard')

@section('title', 'Performance Analytics - Faculty')

@section('page-title', 'Performance Analytics')
@section('page-subtitle', 'Your submission activity for the selected school year')

@section('sidebar')
    @include('partials.faculty-sidebar')
@endsection

@section('content')
    @include('partials.submission-analytics')
@endsection
