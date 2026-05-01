<?php

namespace App\Http\Controllers;

use App\Models\AdminNotification;
use Illuminate\Http\Request;

class AdminNotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = AdminNotification::orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        $unreadCount = AdminNotification::where('is_read', false)->count();

        return response()->json([
            'success'      => true,
            'data'         => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    public function markRead($id)
    {
        AdminNotification::findOrFail($id)->update(['is_read' => true]);

        return response()->json([
            'success'      => true,
            'unread_count' => AdminNotification::where('is_read', false)->count(),
        ]);
    }

    public function markAllRead()
    {
        AdminNotification::where('is_read', false)->update(['is_read' => true]);

        return response()->json(['success' => true, 'unread_count' => 0]);
    }

    public function destroy($id)
    {
        AdminNotification::findOrFail($id)->delete();

        return response()->json([
            'success'      => true,
            'unread_count' => AdminNotification::where('is_read', false)->count(),
        ]);
    }

    public function clearRead()
    {
        AdminNotification::where('is_read', true)->delete();

        return response()->json(['success' => true]);
    }
}