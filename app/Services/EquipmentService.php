<?php

namespace App\Services;

use App\Models\EquipmentItem;
use App\Models\EquipmentBorrow;
use App\Models\DashboardLog;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EquipmentService
{
    // --- Equipment Item Management (Dean) ---

    public function getAllItems(): Collection
    {
        return EquipmentItem::orderBy('item_name')->get();
    }

    public function getAvailableItems(): Collection
    {
        return EquipmentItem::where('status', 'Available')->orderBy('item_name')->get();
    }

    public function createItem(array $validated, int $userId): EquipmentItem
    {
        $item = EquipmentItem::create(array_merge($validated, ['created_by' => $userId]));

        DashboardLog::create([
            'user_id' => $userId,
            'activity' => 'Added equipment: ' . $validated['item_name'],
            'activity_type' => 'equipment_added',
            'log_date' => now(),
        ]);

        return $item;
    }

    public function updateItem(EquipmentItem $item, array $validated, int $userId): EquipmentItem
    {
        $item->update($validated);

        DashboardLog::create([
            'user_id' => $userId,
            'activity' => 'Updated equipment: ' . $item->item_name,
            'activity_type' => 'equipment_updated',
            'log_date' => now(),
        ]);

        return $item;
    }

    public function deleteItem(EquipmentItem $item, int $userId): void
    {
        $itemName = $item->item_name;
        $item->delete();

        DashboardLog::create([
            'user_id' => $userId,
            'activity' => 'Deleted equipment: ' . $itemName,
            'activity_type' => 'equipment_deleted',
            'log_date' => now(),
        ]);
    }

    // --- Borrow Management (All Roles) ---

    public function getUserBorrows(int $userId): LengthAwarePaginator
    {
        return EquipmentBorrow::with('equipmentItem')
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->paginate(15);
    }

    public function getAllBorrows(): LengthAwarePaginator
    {
        return EquipmentBorrow::with(['user.employee', 'equipmentItem'])
            ->orderByDesc('created_at')
            ->paginate(15);
    }

    public function createBorrow(array $validated, User $user): EquipmentBorrow
    {
        DB::beginTransaction();

        try {
            $borrow = EquipmentBorrow::create(array_merge($validated, [
                'user_id' => $user->id,
                'status' => 'Borrowed',
            ]));

            $item = EquipmentItem::find($validated['equipment_item_id']);
            $employeeName = $user->employee->full_name ?? $user->username;

            DashboardLog::create([
                'user_id' => $user->id,
                'activity' => "Borrowed equipment: {$item->item_name}",
                'activity_type' => 'equipment_borrowed',
                'log_date' => now(),
            ]);

            // Notify Dean
            $deans = User::where('role_id', 1)->get();
            foreach ($deans as $dean) {
                Notification::create([
                    'user_id' => $dean->id,
                    'message' => "{$employeeName} borrowed: {$item->item_name}",
                    'is_read' => false,
                ]);
            }

            DB::commit();
            return $borrow;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function returnItem(EquipmentBorrow $borrow, int $userId): void
    {
        if ($borrow->user_id !== $userId) {
            abort(403, 'Unauthorized');
        }

        $borrow->update([
            'status' => 'Returned',
            'actual_return_date' => now(),
        ]);

        $item = $borrow->equipmentItem;

        DashboardLog::create([
            'user_id' => $userId,
            'activity' => "Returned equipment: {$item->item_name}",
            'activity_type' => 'equipment_returned',
            'log_date' => now(),
        ]);
    }

    public function getCurrentlyBorrowedCount(): int
    {
        return EquipmentBorrow::where('status', 'Borrowed')->count();
    }

    public function getOverdueCount(): int
    {
        return EquipmentBorrow::where('status', 'Borrowed')
            ->where('return_date', '<', now()->startOfDay())
            ->count();
    }
}
