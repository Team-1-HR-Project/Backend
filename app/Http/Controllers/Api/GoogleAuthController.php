<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Services\Auth\GoogleAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class GoogleAuthController extends Controller
{
    public function __construct(
        protected GoogleAuthService $googleAuthService
    ) {}

    public function redirect(): JsonResponse
    {
        try {
            $url = $this->googleAuthService->getGoogleRedirectUrl();

            return ResponseHelper::success(
                data: ['url' => $url],
                message: __('auth.google_url_generated')
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                message: __('auth.google_login_failed'),
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function callback(): RedirectResponse
    {
        $frontendUrl = config('app.frontend_url');
        try {
            $result = $this->googleAuthService->handleGoogleCallback();

            return redirect()->to("{$frontendUrl}#token={$result['access_token']}");
        } catch (ValidationException $e) {
            return redirect()->to("{$frontendUrl}?message=not_authorized");
        } catch (Throwable $e) {
            report($e);

            return redirect()->to("{$frontendUrl}?message=auth_failed");
        }
    }
}
