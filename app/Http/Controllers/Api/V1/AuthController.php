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
                'roles' => $userData['roles'],
            ],
            'message' => 'User registered successfully.',
        ], 201);
    }

    public function adminRegister(RegisterUserRequest $request): JsonResponse
    {
        // Use the service to register the user
        $userData = $this->registrationService->registerAdmin($request->all());
        return response()->json([
            'success' => true,
            'data' => [
                'token' => $userData['token'],
                'name' => $userData['name'],
                'roles' => $userData['roles'],
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
            'device_name' => 'nullable|string|max:100',
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
        if ((int) $user->status === 0) {
            Auth::logout();

            return response()->json([
                'error' => [
                    'status' => 'error',
                    'validationErrors' => [
                        'email' => ['The account is inactive or disabled.']
                    ],
                ],
            ], 403);
        }

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

        $deviceName = $this->resolveDeviceName($request);

        RefreshToken::revokeDeviceSessionsForUser($user, $deviceName);

        [$accessToken, $refreshToken, $accessTokenExpiresAt] = $this->createSessionTokens(
            $user,
            $deviceName,
            $request->userAgent(),
            $request->ip(),
        );

        return response()->json([
            'success' => true,
            'data' => $this->buildTokenPayload($user, $accessToken, $refreshToken, $accessTokenExpiresAt),
            'message' => 'User logged in successfully.',
        ], 200);
    }

    public function refreshToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'refresh_token' => 'required|string',
            'device_name' => 'nullable|string|max:100',
        ]);

        $tokenModel = RefreshToken::where('token', $validated['refresh_token'])->first();

        if (!$tokenModel) {
            return $this->tokenErrorResponse(
                'refresh_token_invalid',
                'Invalid or expired refresh token.',
                401
            );
        }

        if ($tokenModel->isExpired()) {
            $tokenModel->delete();

            return $this->tokenErrorResponse(
                'refresh_token_expired',
                'Refresh token has expired.',
                401
            );
        }

        $user = $tokenModel->user;

        if (!$user) {
            return $this->tokenErrorResponse(
                'user_not_found',
                'User not found.',
                404
            );
        }

        if ((int) $user->status === 0) {
            $tokenModel->delete();

            return $this->tokenErrorResponse(
                'account_inactive',
                'The account is inactive or disabled.',
                403
            );
        }

        if ($user->email_verified_at === null) {
            $tokenModel->delete();

            return $this->tokenErrorResponse(
                'email_unverified',
                'Email is not verified.',
                403
            );
        }

        DB::beginTransaction();

        try {
            $user->loadMissing('roles');
            $deviceName = $this->resolveDeviceName($request, $tokenModel);
            $userAgent = $request->userAgent() ?: $tokenModel->user_agent;
            $ipAddress = $request->ip() ?: $tokenModel->ip_address;

            $tokenModel->accessToken?->delete();
            $tokenModel->delete();

            [$accessToken, $newRefreshToken, $accessTokenExpiresAt] = $this->createSessionTokens(
                $user,
                $deviceName,
                $userAgent,
                $ipAddress,
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $this->buildTokenPayload($user, $accessToken, $newRefreshToken, $accessTokenExpiresAt),
                'message' => 'Token refreshed successfully.'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->tokenErrorResponse(
                'refresh_failed',
                'Token refresh failed.',
                500
            );
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

            $logoutAllDevices = $request->boolean('all_devices');
            $currentAccessToken = method_exists($user, 'currentAccessToken')
                ? $user->currentAccessToken()
                : null;

            if ($logoutAllDevices || !$currentAccessToken) {
                $user->tokens()->delete();
                RefreshToken::where('user_id', $user->id)->delete();
            } else {
                RefreshToken::where('personal_access_token_id', $currentAccessToken->id)->delete();
                $currentAccessToken->delete();
            }

            return $this->sendResponse([], 'Logged out successfully');
        } catch (\Exception $e) {
            // Optional: Log the error for debugging
            return $this->sendError('Error logging out', 500);
        }
    }

    private function createSessionTokens(
        User $user,
        string $deviceName,
        ?string $userAgent,
        ?string $ipAddress,
    ): array {
        $accessTokenExpiresAt = Carbon::now()->addHour();
        $newAccessToken = $user->createToken($deviceName, ['*'], $accessTokenExpiresAt);
        $refreshToken = RefreshToken::createForUser(
            $user,
            $newAccessToken->accessToken,
            $deviceName,
            $userAgent,
            $ipAddress,
        );

        return [$newAccessToken->plainTextToken, $refreshToken, $accessTokenExpiresAt];
    }

    private function buildTokenPayload(
        User $user,
        string $accessToken,
        RefreshToken $refreshToken,
        Carbon $accessTokenExpiresAt,
    ): array {
        $user->loadMissing('roles');

        return [
            'token' => $accessToken,
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken->token,
            'expires_in' => now()->diffInSeconds($accessTokenExpiresAt, false),
            'access_token_expires_at' => $accessTokenExpiresAt->toISOString(),
            'refresh_token_expires_at' => $refreshToken->expires_at?->toISOString(),
            'token_type' => 'Bearer',
            'device_name' => $refreshToken->device_name,
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
    }

    private function resolveDeviceName(Request $request, ?RefreshToken $refreshToken = null): string
    {
        $deviceName = trim((string) (
            $request->input('device_name')
            ?: $refreshToken?->device_name
            ?: $request->userAgent()
            ?: 'web-client'
        ));

        if ($deviceName === '') {
            return 'web-client';
        }

        return mb_substr($deviceName, 0, 100);
    }

    private function tokenErrorResponse(string $code, string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ], $status);
    }
}
