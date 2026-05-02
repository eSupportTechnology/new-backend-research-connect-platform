<?php

namespace App\Http\Controllers;

use App\Models\SavedItem;
use App\Models\Innovation\Innovation;
use App\Models\Research\Research;
use Illuminate\Http\Request;

class SavedItemController extends Controller
{
    /** List all saved items for the current user */
    public function index()
    {
        $saved = SavedItem::where('user_id', auth()->id())
            ->latest()
            ->get();

        $result = $saved->map(function ($item) {
            if ($item->item_type === 'innovation') {
                $record = Innovation::with('user:id,first_name,last_name')
                    ->find($item->item_id);
                if (!$record) return null;
                return [
                    'saved_id'   => $item->id,
                    'item_type'  => 'innovation',
                    'item_id'    => $item->item_id,
                    'title'      => $record->title,
                    'thumbnail'  => $record->thumbnail,
                    'category'   => $record->category,
                    'author'     => $record->user
                        ? $record->user->first_name . ' ' . $record->user->last_name
                        : '—',
                    'created_at' => $item->created_at->toDateString(),
                ];
            }

            if ($item->item_type === 'research') {
                $record = Research::with('user:id,first_name,last_name')
                    ->find($item->item_id);
                if (!$record) return null;
                return [
                    'saved_id'   => $item->id,
                    'item_type'  => 'research',
                    'item_id'    => $item->item_id,
                    'title'      => $record->title,
                    'thumbnail'  => $record->thumbnail ?? null,
                    'category'   => $record->category ?? $record->field_of_study ?? '—',
                    'author'     => $record->user
                        ? $record->user->first_name . ' ' . $record->user->last_name
                        : '—',
                    'created_at' => $item->created_at->toDateString(),
                ];
            }

            return null;
        })->filter()->values();

        return response()->json(['success' => true, 'data' => $result]);
    }

    /** Toggle save/unsave — returns new saved state */
    public function toggle(Request $request)
    {
        $request->validate([
            'item_type' => 'required|in:innovation,research',
            'item_id'   => 'required',
        ]);

        $userId  = auth()->id();
        $itemId  = (string) $request->item_id;

        $existing = SavedItem::where('user_id',   $userId)
            ->where('item_type', $request->item_type)
            ->where('item_id',   $itemId)
            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json(['success' => true, 'saved' => false]);
        }

        SavedItem::create([
            'user_id'   => $userId,
            'item_type' => $request->item_type,
            'item_id'   => $itemId,
        ]);

        return response()->json(['success' => true, 'saved' => true]);
    }

    /** Check if a specific item is saved by the current user */
    public function check(Request $request)
    {
        $request->validate([
            'item_type' => 'required|in:innovation,research',
            'item_id'   => 'required',
        ]);

        $saved = SavedItem::where('user_id',   auth()->id())
            ->where('item_type', $request->item_type)
            ->where('item_id',   (string) $request->item_id)
            ->exists();

        return response()->json(['success' => true, 'saved' => $saved]);
    }
}