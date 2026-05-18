@extends('layouts.dashboard')

@section('title', 'Analytics - Faculty')

@section('page-title', 'Data Analytics')
@section('page-subtitle', 'Your submission activity and trends')

@section('sidebar')
    @include('partials.faculty-sidebar')
@endsection

@section('content')
    @include('partials.submission-analytics')
@endsection
