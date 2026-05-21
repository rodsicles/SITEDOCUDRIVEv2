@extends('layouts.dashboard')

@section('title', 'Performance Analytics - Faculty')

@section('page-title', 'Performance Analytics')
@section('page-subtitle', 'Your activity and submission insights')

@section('sidebar')
    @include('partials.faculty-sidebar')
@endsection

@section('content')
    @include('partials.engagement-analytics')

    @include('partials.submission-analytics')
@endsection
