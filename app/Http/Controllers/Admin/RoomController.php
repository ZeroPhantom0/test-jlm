<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoomController extends Controller
{
    public function index()
    {
        $rooms = Room::orderBy('floor')->orderBy('room_number')->get();

        $metrics = [
            'total'     => Room::count(),
            'occupied'  => Room::where('status', 'Occupied')->count(),
            'reserved'  => Room::where('status', 'Reserved')->count(),
            'available' => Room::where('status', 'Available')->count(),
        ];

        return view('admin.rooms', compact('rooms', 'metrics'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $room = Room::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Room created successfully.',
            'room'    => $room,
        ]);
    }

    public function show(Room $room)
    {
        return response()->json(['room' => $room]);
    }

    public function update(Request $request, Room $room)
    {
        $data = $this->validated($request, $room->id);
        $room->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Room updated successfully.',
            'room'    => $room->fresh(),
        ]);
    }

    public function destroy(Room $room)
    {
        $room->delete();

        return response()->json([
            'success' => true,
            'message' => 'Room deleted successfully.',
        ]);
    }

    protected function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'room_number' => ['required', 'string', 'max:20'],
            'floor'       => ['required', 'integer', 'min:1'],
            'room_type'   => ['required', Rule::in(['Solo Room', 'Bedspace', 'Barkada Room'])],
            'description' => ['nullable', 'string'],
            'total_beds'  => ['required', 'integer', 'min:0'],
            'upper_deck'  => ['nullable', 'integer', 'min:0'],
            'lower_deck'  => ['nullable', 'integer', 'min:0'],
            'daily_rent'  => ['required', 'numeric', 'min:0'],
            'status'      => ['required', Rule::in(['Available', 'Occupied', 'Reserved'])],
            'room_bed_id' => ['nullable', 'string', 'max:50'],
            'tenant_name' => ['nullable', 'string', 'max:120'],
        ]);
    }
}