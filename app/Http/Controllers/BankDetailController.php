<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Profile\BankDetail;

class BankDetailController extends Controller
{
    // List all bank details for logged-in user
    public function index(Request $request) {
        $details = $request->user()->bankDetails()->get();
        return response()->json($details);
    }

    // Add a new bank detail
    public function store(Request $request) {
        $request->validate([
            'bank_name'             => 'required|string|max:255',
            'account_holder_name'   => 'required|string|max:255',
            'account_number'        => 'required|string|max:255',
            'branch_name'           => 'required|string|max:255',
        ]);

        if ($request->is_default) {
            $request->user()->bankDetails()->update(['is_default' => false]);
        }

        $detail = $request->user()->bankDetails()->create([
            'bank_name'             => $request->bank_name,
            'account_holder_name'   => $request->account_holder_name,
            'account_number'        => $request->account_number,
            'branch_name'           => $request->branch_name,
            'is_default'            => $request->is_default ?? false,
        ]);

        return response()->json($detail, 201);
    }

    // Remove bank detail
    public function destroy(Request $request, $id) {
        $detail = $request->user()->bankDetails()->findOrFail($id);
        $detail->delete();
        return response()->json(['message' => 'Bank detail removed successfully']);
    }

    // Set default bank detail
    public function setDefault(Request $request, $id) {
        $request->user()->bankDetails()->update(['is_default' => false]);
        $detail = $request->user()->bankDetails()->findOrFail($id);
        $detail->is_default = true;
        $detail->save();

        return response()->json(['message' => 'Default bank account updated']);
    }
}
