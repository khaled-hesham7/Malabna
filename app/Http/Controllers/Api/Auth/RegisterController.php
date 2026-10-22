<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\Auth\RegisterResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class RegisterController extends Controller
{
    /**
     * Handle Customer (Player) registration.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name'     => $request->name,
            'phone'    => $request->phone,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'status'   => 'active',
        ]);

        // Assign 'player' role via Spatie Laravel Permission
        $playerRole = Role::firstOrCreate(['name' => 'player']);
        $user->assignRole($playerRole);

        // Generate Sanctum access token
        $token = $user->createToken('web_session_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Player account registered successfully.',
            'data'    => new RegisterResource([
                'user'  => $user,
                'token' => $token,
            ]),
        ], 201);
    }
}
