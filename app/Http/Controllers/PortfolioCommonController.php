<?php

namespace App\Http\Controllers;

use App\Models\Portfolio\Education;
use App\Models\Portfolio\Experience;
use App\Models\Portfolio\Profile;
use Illuminate\Http\Request;

class PortfolioCommonController extends Controller
{
    public function getUserPublicView($userId)
    {
        try {

            // Get Profile
            $profile = Profile::where('user_id', $userId)->first();

            // Get Experience
            $experience = Experience::where('user_id', $userId)
                ->orderBy('start_year', 'desc')
                ->get();

            // Get Education
            $education = Education::where('user_id', $userId)
                ->orderBy('start_year', 'desc')
                ->get();

            return response()->json([
                'status' => true,
                'data' => [
                    'profile' => $profile,
                    'experience' => $experience,
                    'education' => $education,
                ]
            ]);

        } catch (\Exception $exception) {

            return response()->json([
                'status' => false,
                'message' => $exception->getMessage()
            ], 500);
        }
    }
}
