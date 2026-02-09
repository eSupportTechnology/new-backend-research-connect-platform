<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CardController extends Controller
{
    // List all cards for logged-in user
    public function index(Request $request) {
        $cards = $request->user()->cards()->get();
        return response()->json($cards);
    }

    // Add a new card
    public function store(Request $request) {
        $request->validate([
            'card_number'   => 'required|string|max:19',
            'expiry'        => 'required|string|max:5',
            'security_code' => 'required|string|max:4',
            'holder_name'   => 'required|string|max:255',
        ]);

        // If user wants this to be default, reset previous defaults
        if ($request->is_default) {
            $request->user()->card()->update(['is_default' => false]);
        }

        $card = $request->user()->cards()->create([
            'card_number'   => $request->card_number,
            'expiry'        => $request->expiry,
            'security_code' => $request->security_code,
            'holder_name'   => $request->holder_name,
            'is_default'    => $request->is_default ?? false,
        ]);

        return response()->json($card, 201);
    }

    // Remove card
    public function destroy(Request $request, $id) {
        $card = $request->user()->cards()->findOrFail($id);
        $card->delete();
        return response()->json(['message' => 'Card removed successfully']);
    }

    // Set default card
    public function setDefault(Request $request, $id) {
        $request->user()->cards()->update(['is_default' => false]);
        $card = $request->user()->cards()->findOrFail($id);
        $card->is_default = true;
        $card->save();

        return response()->json(['message' => 'Default card updated']);
    }
}
