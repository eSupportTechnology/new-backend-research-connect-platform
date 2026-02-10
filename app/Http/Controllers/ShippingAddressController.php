<?php

namespace App\Http\Controllers;


use App\Models\Profile\ShippingAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
class ShippingAddressController extends Controller
{
    public function index()
    {
        $addresses = ShippingAddress::byUser(Auth::id())
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $addresses
        ]);
    }

    // Get single address
    public function show($id)
    {
        $address = ShippingAddress::byUser(Auth::id())->find($id);

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $address
        ]);
    }

    // Create new address
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'country' => 'required|string|max:100',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'address' => 'required|string|max:255',
            'apartment' => 'nullable|string|max:100',
            'city' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'phone' => 'required|string|max:20',
            'is_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // If this is set as default, unset other defaults
        if ($request->is_default) {
            ShippingAddress::byUser(Auth::id())
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $address = ShippingAddress::create([
            'user_id' => Auth::id(),
            'country' => $request->country,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'address' => $request->address,
            'apartment' => $request->apartment,
            'city' => $request->city,
            'postal_code' => $request->postal_code,
            'phone' => $request->phone,
            'is_default' => $request->is_default ?? false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Address added successfully',
            'data' => $address
        ], 201);
    }

    // Update address
    public function update(Request $request, $id)
    {
        $address = ShippingAddress::byUser(Auth::id())->find($id);

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'country' => 'string|max:100',
            'first_name' => 'string|max:100',
            'last_name' => 'string|max:100',
            'address' => 'string|max:255',
            'apartment' => 'nullable|string|max:100',
            'city' => 'string|max:100',
            'postal_code' => 'string|max:20',
            'phone' => 'string|max:20',
            'is_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // If setting as default, unset other defaults
        if ($request->has('is_default') && $request->is_default) {
            ShippingAddress::byUser(Auth::id())
                ->where('is_default', true)
                ->where('id', '!=', $id)
                ->update(['is_default' => false]);
        }

        $address->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Address updated successfully',
            'data' => $address
        ]);
    }

    // Delete address
    public function destroy($id)
    {
        $address = ShippingAddress::byUser(Auth::id())->find($id);

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found'
            ], 404);
        }

        // If deleting default address, set another as default
        if ($address->is_default) {
            $newDefault = ShippingAddress::byUser(Auth::id())
                ->where('id', '!=', $id)
                ->first();

            if ($newDefault) {
                $newDefault->update(['is_default' => true]);
            }
        }

        $address->delete();

        return response()->json([
            'success' => true,
            'message' => 'Address deleted successfully'
        ]);
    }

    // Set address as default
    public function setDefault($id)
    {
        $address = ShippingAddress::byUser(Auth::id())->find($id);

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found'
            ], 404);
        }

        // Unset all other defaults
        ShippingAddress::byUser(Auth::id())
            ->where('is_default', true)
            ->update(['is_default' => false]);

        // Set this as default
        $address->update(['is_default' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Default address updated successfully',
            'data' => $address
        ]);
    }

    // Get default address
    public function getDefault()
    {
        $address = ShippingAddress::byUser(Auth::id())
            ->default()
            ->first();

        return response()->json([
            'success' => true,
            'data' => $address
        ]);
    }
}
