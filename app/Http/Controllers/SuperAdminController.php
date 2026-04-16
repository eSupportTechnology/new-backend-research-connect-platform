<?php

namespace App\Http\Controllers;

use App\Models\RegisterUsers\User;
use App\Models\Innovation\Innovation;
use App\Models\Research\Research;
use App\Models\Innovation\SellingItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SuperAdminController extends Controller
{
    /**
     * Get aggregate statistics for the super admin dashboard
     */
    public function getDashboardStats()
    {
        try {
            // 1. Basic Counts
            $totalUsers = User::count();
            $totalInnovations = Innovation::count();
            $totalResearch = Research::count();
            $totalUploads = $totalInnovations + $totalResearch;
            
            // 2. Revenue Calculation
            $totalRevenue = SellingItem::sum('total_revenue');
            $activeSubscriptions = User::whereIn('role', ['Admin', 'Manager'])->count(); // Heuristic for now
            
            // 3. Views Calculation
            $totalInnovationViews = Innovation::sum('views');
            $totalResearchViews = Research::sum('views');
            $totalViews = $totalInnovationViews + $totalResearchViews;

            // 4. Users by Role
            $usersByRoleRaw = User::select('role', DB::raw('count(*) as count'))
                ->groupBy('role')
                ->get();

            $usersByRole = $usersByRoleRaw->map(function($item) {
                $colors = [
                    'GENERAL_USER' => '#DC2626', // Red
                    'Manager' => '#3B82F6',      // Blue
                    'Admin' => '#10B981',        // Emerald
                    'superadmin' => '#7C3AED'    // Purple
                ];
                
                $labels = [
                    'GENERAL_USER' => 'General Users',
                    'Manager' => 'Managers',
                    'Admin' => 'Admins',
                    'superadmin' => 'Super Admins'
                ];

                return [
                  'name' => $labels[$item->role] ?? $item->role,
                  'value' => $item->count,
                  'color' => $colors[$item->role] ?? '#9CA3AF'
                ];
            });

            // 5. Recent Activity
            $recentUsers = User::latest()->limit(5)->get()->map(function($u) {
                return [
                    'id' => 'u-' . $u->id,
                    'action' => 'New user registered',
                    'user' => $u->email,
                    'time' => $u->created_at->diffForHumans(),
                    'type' => 'user'
                ];
            });

            $recentInnovations = Innovation::latest()->limit(5)->get()->map(function($i) {
                return [
                    'id' => 'i-' . $i->id,
                    'action' => 'Innovation uploaded',
                    'user' => $i->title,
                    'time' => $i->created_at->diffForHumans(),
                    'type' => 'content'
                ];
            });

            $recentResearch = Research::latest()->limit(5)->get()->map(function($r) {
                return [
                    'id' => 'r-' . $r->id,
                    'action' => 'Research uploaded',
                    'user' => $r->title,
                    'time' => $r->created_at->diffForHumans(),
                    'type' => 'content'
                ];
            });

            $realtimeActivity = $recentUsers->concat($recentInnovations)->concat($recentResearch)
                ->sortByDesc('time')
                ->values()
                ->take(10);

            // 6. Revenue Data (Chart helper)
            // Since we don't have a transaction log yet, we generate a 6 month series aggregated by listed_at
            $months = collect(range(0, 5))->map(function($i) {
                $date = Carbon::now()->subMonths($i);
                return [
                    'month' => $date->format('M'),
                    'revenue' => SellingItem::whereMonth('created_at', $date->month)
                        ->whereYear('created_at', $date->year)
                        ->sum('total_revenue')
                ];
            })->reverse()->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => [
                        ['label' => 'Total Users', 'value' => number_format($totalUsers), 'change' => '+0%', 'icon' => '👥', 'color' => 'purple'],
                        ['label' => 'Total Revenue', 'value' => '$' . number_format($totalRevenue, 2), 'change' => '+0%', 'icon' => '💰', 'color' => 'blue'],
                        ['label' => 'Total Uploads', 'value' => number_format($totalUploads), 'change' => '+0%', 'icon' => '📄', 'color' => 'green'],
                        ['label' => 'Total Views', 'value' => number_format($totalViews), 'change' => '+0%', 'icon' => '👁️', 'color' => 'pink'],
                        ['label' => 'Staff Accounts', 'value' => number_format($activeSubscriptions), 'change' => '+0%', 'icon' => '⭐', 'color' => 'yellow']
                    ],
                    'revenueData' => $months,
                    'usersByRole' => $usersByRole,
                    'realtimeActivity' => $realtimeActivity
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard stats: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get users based on their contributions (Innovation or Research) for Admin management
     */
    public function getUsersByContribution(Request $request)
    {
        try {
            $type = $request->query('type', 'innovator'); // innovator or researcher
            
            $query = User::with(['profile'])
                ->withCount(['innovations', 'researches']);

            if ($type === 'innovator') {
                $query->has('innovations');
            } else {
                $query->has('researches');
            }

            $users = $query->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $users
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching contributors: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle the "Best" status of a user
     */
    public function toggleBestStatus(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            $type = $request->input('type'); // innovator or researcher

            if ($type === 'innovator') {
                $user->is_best_innovator = !$user->is_best_innovator;
            } elseif ($type === 'researcher') {
                $user->is_best_researcher = !$user->is_best_researcher;
            } else {
                return response()->json(['success' => false, 'message' => 'Invalid type'], 400);
            }

            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'User status updated successfully',
                'is_best' => $type === 'innovator' ? $user->is_best_innovator : $user->is_best_researcher
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get featured performers for public display
     */
    public function getFeaturedPerformers(Request $request)
    {
        try {
            $type = $request->query('type', 'innovator');
            
            $query = User::with(['profile'])
                ->withCount(['innovations', 'researches']);

            if ($type === 'innovator') {
                $query->where('is_best_innovator', true);
            } else {
                $query->where('is_best_researcher', true);
            }

            $users = $query->get();

            return response()->json([
                'success' => true,
                'data' => $users
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching featured performers: ' . $e->getMessage()
            ], 500);
        }
    }
}
