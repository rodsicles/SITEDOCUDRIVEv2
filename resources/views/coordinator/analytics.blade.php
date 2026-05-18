@extends('layouts.dashboard')

@section('title', 'Analytics - Coordinator')

@section('page-title', 'Data Analytics')
@section('page-subtitle', 'Department submission insights')

@section('sidebar')
    @include('partials.coordinator-sidebar')
@endsection

@section('content')
    @include('partials.submission-analytics')
@endsection
