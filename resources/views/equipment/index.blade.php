@extends('layouts.dashboard')

@section('title', 'Equipment Borrowing')
@section('page-title', 'Equipment')
@section('page-subtitle', 'Borrow and track equipment')

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
    {{-- Stats --}}
    <div class="stats-grid-horizontal">
        <div class="stat-item-horizontal">
            <div class="stat-icon-horizontal"><i class="fas fa-tools"></i></div>
            <div class="stat-content-horizontal">
                <div class="stat-number-label"><strong>{{ $items->count() }}</strong> Equipment Items</div>
            </div>
        </div>
        <div class="stat-item-horizontal">
            <div class="stat-icon-horizontal"><i class="fas fa-hand-holding"></i></div>
            <div class="stat-content-horizontal">
                <div class="stat-number-label"><strong>{{ $currentlyBorrowed }}</strong> Currently Borrowed</div>
            </div>
        </div>
        <div class="stat-item-horizontal">
            <div class="stat-icon-horizontal"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="stat-content-horizontal">
                <div class="stat-number-label"><strong>{{ $overdueCount }}</strong> Overdue Returns</div>
            </div>
        </div>
    </div>

    {{-- Dean: Manage Equipment Items --}}
    @if(auth()->user()->isDean())
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-cog mr-2"></i>Manage Equipment</h3>
        </div>
        <form action="{{ route('equipment.store-item') }}" method="POST" class="mb-5">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-4 gap-5 mb-5">
                <div class="form-group mb-0">
                    <label class="form-label">Item Name *</label>
                    <input type="text" name="item_name" class="form-control" required maxlength="100">
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Description</label>
                    <input type="text" name="description" class="form-control" maxlength="500">
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Quantity *</label>
                    <input type="number" name="quantity" class="form-control" required min="1" value="1">
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Status *</label>
                    <select name="status" class="form-control" required>
                        <option value="Available">Available</option>
                        <option value="Unavailable">Unavailable</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary border-0">
                <i class="fas fa-plus"></i> Add Equipment
            </button>
        </form>

        @if($items->count() > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th>Item Name</th>
                    <th>Description</th>
                    <th>Qty</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                <tr>
                    <td><strong>{{ $item->item_name }}</strong></td>
                    <td>{{ $item->description ?? '-' }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>
                        <span class="{{ $item->isAvailable() ? 'badge-available' : 'badge-unavailable' }}">
                            {{ $item->status }}
                        </span>
                    </td>
                    <td>
                        <form action="{{ route('equipment.destroy-item', $item->equipment_item_id) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger py-1 px-2.5 text-xs border-0" onclick="return confirm('Delete this item?')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
    @endif

    {{-- Borrow Button --}}
    <div class="mb-5">
        <a href="{{ route('equipment.borrow') }}" class="btn btn-primary border-0">
            <i class="fas fa-hand-holding"></i> Borrow Equipment
        </a>
    </div>

    {{-- Borrowed Items --}}
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-list mr-2"></i>{{ auth()->user()->isFaculty() ? 'My Borrowed Items' : 'All Borrowed Items' }}</h3>
            <span class="badge badge-info">{{ $borrows->total() }} Records</span>
        </div>
        @if($borrows->count() > 0)
            <table class="data-table">
                <thead>
                    <tr>
                        @if(!auth()->user()->isFaculty())
                            <th>Borrower</th>
                        @endif
                        <th>Equipment</th>
                        <th>Purpose</th>
                        <th>Borrow Date/Time</th>
                        <th>Return Date/Time</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($borrows as $borrow)
                    <tr>
                        @if(!auth()->user()->isFaculty())
                            <td><strong>{{ $borrow->user->employee->full_name ?? $borrow->user->username }}</strong></td>
                        @endif
                        <td><strong>{{ $borrow->equipmentItem->item_name }}</strong></td>
                        <td>{{ $borrow->purpose }}</td>
                        <td>{{ $borrow->borrow_date->format('M d, Y') }} {{ \Carbon\Carbon::parse($borrow->borrow_time)->format('h:i A') }}</td>
                        <td>{{ $borrow->return_date->format('M d, Y') }} {{ \Carbon\Carbon::parse($borrow->return_time)->format('h:i A') }}</td>
                        <td>
                            @if($borrow->isReturned())
                                <span class="badge-returned"><i class="fas fa-check"></i> Returned</span>
                            @elseif($borrow->isOverdue())
                                <span class="badge-overdue"><i class="fas fa-exclamation-triangle"></i> Overdue</span>
                            @else
                                <span class="badge-borrowed"><i class="fas fa-hand-holding"></i> Borrowed</span>
                            @endif
                        </td>
                        <td>
                            @if($borrow->isBorrowed() && $borrow->user_id === auth()->id())
                                <form action="{{ route('equipment.return', $borrow->equipment_borrow_id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-success py-1 px-2.5 text-xs border-0" onclick="return confirm('Confirm return of this equipment?')">
                                        <i class="fas fa-undo"></i> Return
                                    </button>
                                </form>
                            @elseif($borrow->isReturned())
                                <span class="text-gray-400 text-xs">{{ $borrow->actual_return_date->format('M d, Y h:i A') }}</span>
                            @else
                                <span class="text-gray-400 text-xs">-</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="mt-5">{{ $borrows->links() }}</div>
        @else
            <div class="text-center py-10 text-gray-500 dark:text-gray-400">
                <i class="fas fa-tools text-5xl mb-4 opacity-50"></i>
                <p>No borrow records yet.</p>
            </div>
        @endif
    </div>
@endsection
