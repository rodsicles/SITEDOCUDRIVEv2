<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EquipmentItem;
use App\Models\EquipmentBorrow;
use App\Services\EquipmentService;

class EquipmentController extends Controller
{
    public function __construct(
        protected EquipmentService $equipmentService
    ) {}

    public function index()
    {
        $user = auth()->user();
        $items = $this->equipmentService->getAllItems();
        $currentlyBorrowed = $this->equipmentService->getCurrentlyBorrowedCount();
        $overdueCount = $this->equipmentService->getOverdueCount();

        if ($user->isFaculty()) {
            $borrows = $this->equipmentService->getUserBorrows($user->id);
        } else {
            $borrows = $this->equipmentService->getAllBorrows();
        }

        return view('equipment.index', compact('items', 'borrows', 'currentlyBorrowed', 'overdueCount'));
    }

    public function borrow()
    {
        $items = $this->equipmentService->getAvailableItems();
        return view('equipment.borrow', compact('items'));
    }

    public function storeBorrow(Request $request)
    {
        $validated = $request->validate([
            'equipment_item_id' => 'required|exists:equipment_items,equipment_item_id',
            'purpose' => 'required|string|max:255',
            'borrow_date' => 'required|date',
            'borrow_time' => 'required|date_format:H:i',
            'return_date' => 'required|date|after_or_equal:borrow_date',
            'return_time' => 'required|date_format:H:i',
        ]);

        $this->equipmentService->createBorrow($validated, auth()->user());
        return redirect()->route('equipment.index')->with('success', 'Equipment borrowed successfully. This serves as your documentation.');
    }

    public function returnItem($id)
    {
        $borrow = EquipmentBorrow::findOrFail($id);
        $this->equipmentService->returnItem($borrow, auth()->id());
        return redirect()->back()->with('success', 'Equipment returned successfully.');
    }

    // --- Dean-only item management ---

    public function storeItem(Request $request)
    {
        $validated = $request->validate([
            'item_name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'quantity' => 'required|integer|min:1',
            'status' => 'required|in:Available,Unavailable',
        ]);

        $this->equipmentService->createItem($validated, auth()->id());
        return redirect()->back()->with('success', 'Equipment item added.');
    }

    public function updateItem(Request $request, $id)
    {
        $item = EquipmentItem::findOrFail($id);

        $validated = $request->validate([
            'item_name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'quantity' => 'required|integer|min:1',
            'status' => 'required|in:Available,Unavailable',
        ]);

        $this->equipmentService->updateItem($item, $validated, auth()->id());
        return redirect()->back()->with('success', 'Equipment item updated.');
    }

    public function destroyItem($id)
    {
        $item = EquipmentItem::findOrFail($id);
        $this->equipmentService->deleteItem($item, auth()->id());
        return redirect()->back()->with('success', 'Equipment item deleted.');
    }
}
