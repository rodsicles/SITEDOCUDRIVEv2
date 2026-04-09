@extends('layouts.dashboard')

@section('title', 'Borrow Equipment')
@section('page-title', 'Borrow Equipment')
@section('page-subtitle', 'File an equipment borrow record')

@section('sidebar')
    @if(auth()->user()->isFaculty())
        @include('partials.faculty-sidebar')
    @elseif(auth()->user()->isProgramCoordinator())
        @include('partials.coordinator-sidebar')
    @else
        @include('partials.dean-sidebar')
    @endif
@endsection

@section('content')
    <div class="mb-5">
        <a href="{{ route('equipment.index') }}" class="btn btn-secondary border-0">
            <i class="fas fa-arrow-left"></i> Back to Equipment
        </a>
    </div>

    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-hand-holding mr-2"></i>Borrow Equipment</h3>
        </div>

        <form action="{{ route('equipment.store-borrow') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                <div class="form-group mb-0">
                    <label class="form-label">Equipment Item *</label>
                    <select name="equipment_item_id" class="form-control" required>
                        <option value="">Select Equipment</option>
                        @foreach($items as $item)
                            <option value="{{ $item->equipment_item_id }}">{{ $item->item_name }} (Qty: {{ $item->quantity }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Purpose *</label>
                    <input type="text" name="purpose" class="form-control" required maxlength="255" placeholder="e.g. Class presentation, Meeting">
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Borrow Date *</label>
                    <input type="date" name="borrow_date" class="form-control" required value="{{ date('Y-m-d') }}">
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Borrow Time *</label>
                    <input type="time" name="borrow_time" class="form-control" required>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Return Date *</label>
                    <input type="date" name="return_date" class="form-control" required>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Return Time *</label>
                    <input type="time" name="return_time" class="form-control" required>
                </div>
            </div>

            <div class="backup-info">
                <i class="fas fa-info-circle mr-1"></i>
                This borrow record serves as documentation and proof that you are borrowing this equipment.
            </div>

            <button type="submit" class="btn btn-primary border-0">
                <i class="fas fa-check"></i> Confirm Borrow
            </button>
        </form>
    </div>
@endsection
