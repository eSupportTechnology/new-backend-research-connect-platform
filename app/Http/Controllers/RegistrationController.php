<?php

namespace App\Http\Controllers;

use App\Models\RegisterUsers\Investor;
use App\Models\RegisterUsers\ParentModel;
use App\Models\RegisterUsers\Student;
use App\Models\RegisterUsers\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
class RegistrationController extends Controller
{
    public function registerInvestor(Request $request)
    {
        $validated = $request->validate([
            'investorDetails.firstName' => 'required|string|max:255',
            'investorDetails.lastName' => 'required|string|max:255',
            'investorDetails.email' => 'required|email|unique:users,email',
            'investorDetails.password' => 'required|string|min:8',
            'investorDetails.phone' => 'required|string',
            'investorDetails.investmentPreferences' => 'required|string',
        ]);
        $user = User::create([
            'first_name' => $validated['investorDetails']['firstName'],
            'last_name' => $validated['investorDetails']['lastName'],
            'email' => $validated['investorDetails']['email'],
            'password' => Hash::make($validated['investorDetails']['password']),
            'role' => 'INVESTOR'
        ]);
        $investor = Investor::create([
            'user_id' => $user->id,
            'phone' => $validated['investorDetails']['phone'],
            'address' => $request->input('investorDetails.address', ''),
            'investment_preferences' => $validated['investorDetails']['investmentPreferences']
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'investor' => $investor,
            'token' => $token
        ]);
    }

    public function registerGeneralUser(Request $request)
    {
        $validated = $request->validate([
            'generalUserDetails.firstName' => 'required|string|max:255',
            'generalUserDetails.lastName' => 'required|string|max:255',
            'generalUserDetails.email' => 'required|email|unique:users,email',
            'generalUserDetails.password' => 'required|string|min:8',
            'generalUserDetails.phone' => 'required|string',
            'isSchoolStudent' => 'required|boolean'
        ]);

        $user = User::create([
            'first_name' => $validated['generalUserDetails']['firstName'],
            'last_name' => $validated['generalUserDetails']['lastName'],
            'email' => $validated['generalUserDetails']['email'],
            'password' => Hash::make($validated['generalUserDetails']['password']),
            'role' => 'GENERAL_USER',
            'user_type' => $request->input('userType')
        ]);

        // School student
        if ($request->input('isSchoolStudent')) {
            $student = Student::create([
                'user_id' => $user->id,
                'school_name' => $request->input('studentDetails.schoolName'),
                'grade_level' => $request->input('studentDetails.gradeLevel'),
                'student_id' => $request->input('studentDetails.studentId'),
            ]);

            ParentModel::create([
                'student_id' => $student->id,
                'first_name' => $request->input('parentDetails.parentFirstName'),
                'last_name' => $request->input('parentDetails.parentLastName'),
                'email' => $request->input('parentDetails.parentEmail'),
                'phone' => $request->input('parentDetails.parentPhone'),
                'relation' => $request->input('parentDetails.relation'),
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }
    public function registerBoth(Request $request)
    {
        $validated = $request->validate([
            'coreDetails.firstName' => 'required|string|max:255',
            'coreDetails.lastName' => 'required|string|max:255',
            'coreDetails.email' => 'required|email|unique:users,email',
            'coreDetails.password' => 'required|string|min:8',
            'investorDetails.phone' => 'required|string',
            'investorDetails.investmentPreferences' => 'required|string',
            'isSchoolStudent' => 'required|boolean'
        ]);

        $user = User::create([
            'first_name' => $validated['coreDetails']['firstName'],
            'last_name' => $validated['coreDetails']['lastName'],
            'email' => $validated['coreDetails']['email'],
            'password' => Hash::make($validated['coreDetails']['password']),
            'role' => 'BOTH',
            'user_type' => $request->input('userType')
        ]);

        $investor = Investor::create([
            'user_id' => $user->id,
            'phone' => $validated['investorDetails']['phone'],
            'address' => $request->input('investorDetails.address', ''),
            'investment_preferences' => $validated['investorDetails']['investmentPreferences']
        ]);

        if ($request->input('isSchoolStudent')) {
            $student = Student::create([
                'user_id' => $user->id,
                'school_name' => $request->input('studentDetails.schoolName'),
                'grade_level' => $request->input('studentDetails.gradeLevel'),
                'student_id' => $request->input('studentDetails.studentId'),
            ]);

            ParentModel::create([
                'student_id' => $student->id,
                'first_name' => $request->input('parentDetails.parentFirstName'),
                'last_name' => $request->input('parentDetails.parentLastName'),
                'email' => $request->input('parentDetails.parentEmail'),
                'phone' => $request->input('parentDetails.parentPhone'),
                'relation' => $request->input('parentDetails.relation'),
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }
}
