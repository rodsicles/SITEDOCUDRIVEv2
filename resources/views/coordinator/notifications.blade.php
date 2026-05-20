@extends('layouts.dashboard')

@section('title', 'Notifications - Program Coordinator')

@section('page-title', 'Notifications')
@section('page-subtitle', 'View all your notifications')

@section('sidebar')
    @include('partials.coordinator-sidebar')
@endsection

@section('content')
    @include('partials.notifications-list', ['routePrefix' => 'coordinator'])
@endsection
