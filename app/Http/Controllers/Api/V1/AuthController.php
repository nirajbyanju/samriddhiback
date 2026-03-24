<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\V1\BaseController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Password;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\RegisterUserRequest;
use Illuminate\Support\Facades\Hash;
use App\Services\RegistrationService;
use App\Services\UserMenuService;
use Carbon\Carbon;
use App\Models\RefreshToken;
use Illuminate\Support\Facades\DB;

class AuthController extends BaseController
{
    protected RegistrationService $registrationService;
    protected UserMenuService $userMenuService;


    public function __construct(
        RegistrationService $registrationService,
        UserMenuService $userMenuService,
    ) {
        $this->registrationService = $registrationService;
        $this->userMenuService = $userMenuService;
    }


    public function register(RegisterUserRequest $request): JsonResponse
    {
        // Use the service to register the user
        $userData = $this->registrationService->registerUser($request->all());

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $userData['token'],
                'name' => $userData['name'],
            ],
            'message' => 'User registered successfully.',
        ], 201);
    }

    public function adminRegister(RegisterUserRequest $request): JsonResponse
    {
        // Use the service to register the user
        $userData = $this->registrationService->registerAdmin($request->all());
        $userData['roles'] = ['admin'];
        return response()->json([
            'success' => true,
            'data' => [
                'token' => $userData['token'],
                'name' => $userData['name'],
            ],
            'message' => 'User registered successfully.',
        ], 201);
    }


    /**
     * Login api
     *
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!User::where('email', $request->email)->first()) {
            return response()->json([
                'error' => [
                    'status' => 'error',
                    'validationErrors' => [
                        'email' => ['The email not found.']
                    ],
                ],
            ], 422);
        }

        // First check authentication without loading relationships
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'error' => [
                    'status' => 'error',
                    'validationErrors' => [
                        'password' => ['The password is incorrect.']
                    ],
                ],
            ], 422);
        }

        $user = Auth::user();

        // Now check status and verification
        // if ($user->status == 0) {
        //     Auth::logout();
        //     return response()->json([
        //         'error' => [
        //             'status' => 'error',
        //             'validationErrors' => [
        //                 'email' => ['The account is inactive or disabled.']
        //             ],
        //         ],
        //     ], 404);
        // }

        if ($user->email_verified_at == null) {
            Auth::logout();
            return response()->json([
                'data' => 'error',
                'validationErrors' => [
                    'email' => $request->email,
                    'message' => 'email was not verify'
                ],
            ], 200);
        }

        // Load relationships only when needed
        $user->load('roles');

        $accessToken = $user->createToken('auth-token', ['*'], Carbon::now()->addHour())->plainTextToken;
        $refreshToken = RefreshToken::createForUser($user);

        $result = [
            'token' => $accessToken,
            'refresh_token' => $refreshToken->token,
            'expires_in' => 3600,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'firstName' => $user->first_name,
                'lastName' => $user->last_name,
                'userName' => $user->username,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name'),
            ],
            'menus' => $this->userMenuService->getForUser($user),
        ];

        return response()->json([
            'success' => true,
            'data' => $result,
            'message' => 'User logged in successfully.',
        ], 200);
    }

    public function refreshToken(Request $request): JsonResponse
    {
        $refreshToken = $request->input('refresh_token');

        if (!$refreshToken) {
            return response()->json([
                'success' => false,
                'message' => 'Refresh token is required'
            ], 422);
        }

        $tokenModel = RefreshToken::where('token', $refreshToken)->first();

        if (!$tokenModel) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired refresh token'
            ], 401);
        }

        if ($tokenModel->isExpired()) {
            $tokenModel->delete();

            return response()->json([
                'success' => false,
                'message' => 'Refresh token has expired'
            ], 401);
        }

        $user = $tokenModel->user;

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        DB::beginTransaction();

        try {
            $user->loadMissing('roles');

            // Delete old access tokens
            $user->tokens()->delete();

            // Delete used refresh token (important for security)
            $tokenModel->delete();

            // Create new access token (1 hour expiry recommended)
            $accessToken = $user->createToken(
                'auth-token',
                ['*'],
                Carbon::now()->addHour()
            )->plainTextToken;

            // Create new refresh token
            $newRefreshToken = RefreshToken::createForUser($user);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'access_token' => $accessToken,
                    'refresh_token' => $newRefreshToken->token,
                    'expires_in' => 3600,
                    'token_type' => 'Bearer',
                    'user' => [
                        'id' => $user->id,
                        'firstName' => $user->first_name,
                        'lastName' => $user->last_name,
                        'userName' => $user->username,
                        'email' => $user->email,
                        'roles' => $user->roles->pluck('name'),
                    ],
                    'menus' => $this->userMenuService->getForUser($user),
                ],
                'message' => 'Token refreshed successfully.'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Token refresh failed'
            ], 500);
        }
    }
    public function sendResetLinkEmail(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json(['error' => 'Email does not exist.'], 404);
            }


            $response = Password::sendResetLink(
                $request->only('email')
            );

            if ($response == Password::RESET_LINK_SENT) {
                return response()->json(['message' => 'Reset link sent to your email.'], 200);
            } else {
                return response()->json(['error' => 'Unable to send reset link. Please try again later.'], 500);
            }
            // dispatch(new SenEmailJob($request->only('email')));

            return response()->json(['message' => 'Reset link queued to be sent to your email.'], 200);
        } catch (\Exception $e) {
            // Return a generic error response
            return response()->json(['error' => 'An unexpected error occurred. Please try again later.'], 500);
        }
    }

    public function resetPassword(Request $request)
    {
        try {
            // Validate the input
            $validator = Validator::make($request->all(), [
                'token' => 'required|string',
                'email' => 'required|email',
                'password' => 'required|string|min:8',
                'cPassword' => 'required|string|same:password',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // Perform password reset
            $status = Password::reset(
                [
                    'email' => $request->email,
                    'password' => $request->password,
                    'password_confirmation' => $request->cPassword,
                    'token' => $request->token,
                ],
                function ($user, $password) {
                    $user->forceFill([
                        'password' => Hash::make($password),
                    ])->save();
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return response()->json(['message' => 'Password reset successfully.'], 200);
            } else {
                return response()->json(['error' => __($status)], 400);
            }
        } catch (\Exception $e) {
            // Return a generic error response
            return response()->json(['error' => 'An unexpected error occurred. Please try again later.'], 500);
        }
    }

    public function emailVerify(Request $request)
    {
        try {
            // Validate the request parameters
            $request->validate([
                'token' => 'required|string',
                'email' => 'required|email',
            ]);

            // Find the user by token and email
            $user = User::where('userCode', $request->token)
                ->where('email', $request->email)
                ->first();

            if (!$user) {
                return response()->json([
                    'success' => true,
                    'status' => '201',
                    'message' => 'Invalid token or email.',
                ], 201);
            }
            if ($user->email_verified_at) {
                return response()->json([
                    'success' => true,
                    'status' => '201',
                    'message' => 'Email already verified.',
                ], 202);
            }

            // Update the user's email verification timestamp
            $user->update(['email_verified_at' => now()]);

            return response()->json(['message' => 'Email verification successful.'], 200);
        } catch (\Exception $e) {
            // Return a generic error response
            return response()->json(['error' => 'An unexpected error occurred. Please try again later.'], 500);
        }
    }


    public function resendVerification(Request $request): JsonResponse
    {
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'status' => 422,
                    'message' => 'Invalid input',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Fetch user by email
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'status' => 404,
                    'message' => 'User not found',
                ], 404);
            }

            // Check if already verified
            if ($user->email_verified_at) {
                return response()->json([
                    'success' => false,
                    'status' => 409,
                    'message' => 'Email is already verified',
                ], 409);
            }

            // Notify user and send verification email
            // $user->notify(new UserNotification("Verification email resent to {$user->first_name} {$user->last_name}"));
            $user->sendEmailVerificationNotification();

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Verification email resent successfully.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'An unexpected error occurred.',
                'error' => $e->getMessage() // Optional: useful for debugging
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $user = $request->user();

            // Check if user is authenticated
            if (!$user) {
                return $this->sendError('Unauthenticated user', 401);
            }

            // Delete user's personal access tokens if they exist
            if (method_exists($user, 'tokens')) {
                $user->tokens()->delete();
            }

            // Delete refresh tokens from DB if they exist

            RefreshToken::where('user_id', $user->id)->delete();

            return $this->sendResponse([], 'Logged out successfully');
        } catch (\Exception $e) {
            // Optional: Log the error for debugging
            return $this->sendError('Error logging out', 500);
        }
    }
}
