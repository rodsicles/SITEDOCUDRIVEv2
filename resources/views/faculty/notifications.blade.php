@extends('layouts.dashboard')

@section('title', 'Notifications - Faculty')

@section('page-title', 'Notifications')
@section('page-subtitle', 'View all your notifications')

@section('sidebar')
    @include('partials.faculty-sidebar')
@endsection

@section('content')
    @include('partials.notifications-list', ['routePrefix' => 'faculty'])
@endsection
