<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Application;
use Carbon\Carbon;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;

class TokenController extends ApiController
{
    public function __construct()
    {

    }

    /*
     * Get application token
     *
     * @param Illuminate\Http\Request $request
     * @return JSON object
     *
     */
    public function getToken(Request $request)
    {
        $data = $request->only(['appname', 'service']);
        $service_name = $data['service'];

		if (! $this->validateFields($data)) {
			return $this->respondValidationError("Validation errors", $this->errors);
		}

        $application = (Application::getDetails($data['appname'], $service_name))->first();

        if ($application->exists()) {
            $service = $application->services->where('slug', $data['service'])->first();
            $token = $service->token->first();

            if (!is_null($token->expired_at)) {
                $expired_at = Carbon::parse($token->expired_at);
                if ($expired_at->timestamp < now()->timestamp) {
                    return $this->respondWithError("Application token is expired!");
                }
            }

            return $this->respond([
                'status'      => 'success',
                'status_code' => $this->getStatusCode(),
                'message'     => 'Success',
                'data'        => [
                    'token' => $token->token
                ]
            ]);

        } else {
            return $this->respondWithError("Unknown application or service");
        }
    }

    /*
     * Request new token
     *
     * @param Illuminate\Http\Request $request
     * @param JSON object
     *
     * */
    public function refreshToken(Request $request)
	{
		$data = $request->only(['appname', 'service']);

		if (! $this->validateFields($data)) {
			return $this->respondValidationError("Validation errors", $this->errors);
		}

		try {
			$token = JWTAuth::getToken();

			if (false === $token) {
				return $this->respondWithError("Invalid token");
			}

			$token = (string) $token;

			if ($token == Application::getToken($data['appname'], $data['service'])) {
				return $this->respond([
					'status'      => 'success',
					'status_code' => $this->getStatusCode(),
					'message'     => 'Success',
					'data'        => [
						'token' => $token
					]
				]);
			} else {
				return $this->respondWithError("Token not match.");
			}

		} catch (TokenExpiredException $tokenExpiredException) {
			try {
				$token = JWTAuth::refresh();

				return $this->respond([
					'status'      => 'success',
					'status_code' => $this->getStatusCode(),
					'message'     => 'Success',
					'data'        => [
						'token' => $token
					]
				]);

			} catch (TokenExpiredException $tokenExpiredException) {
				return $this->respondWithError($tokenExpiredException->getMessage());

			} catch (TokenBlacklistedException $blacklistedException) {
				return $this->respondWithError($blacklistedException->getMessage());
			}

		} catch (TokenBlacklistedException $blacklistedException) {
			return $this->respondWithError($blacklistedException->getMessage());
		}
	}

    public function validateToken(Request $request)
    {
        $data = $request->only(['appname', 'service']);

		if (! $this->validateFields($data)) {
			return $this->respondValidationError("Validation errors", $this->errors);
		}

        try {
            JWTAuth::setRequest($request)->parseToken();
        } catch (\Throwable $e) {
            return $this->respondWithError($e->getMessage());
        }

        try {
            if (false == JWTAuth::authenticate(JWTAuth::getToken())) {
                return $this->respondWithError("Token not authenticated");
            }

            return $this->respond([
                'status'      => 'success',
                'status_code' => $this->getStatusCode(),
                'message'     => 'Success'
            ]);
        } catch (TokenExpiredException $e) {
            try {
                $token = JWTAuth::refresh(JWTAuth::getToken());

                $application = (Application::getDetails($data['appname'], $data['service']))->first();

                if ($application->exists()) {
                    $service = $application->services->where('slug', $data['service'])->first();
                    if ($service->exists()) {
                        if ($service->token()->update(['token' => $token])) {
                            return $this->respond([
                                'status'      => 'success',
                                'status_code' => $this->getStatusCode(),
                                'message'     => 'Token has been refreshed'
                            ]);
                        }
                    }
                }

                return $this->respondWithError("Unable to update token");
            } catch (TokenExpiredException $e) {
                return $this->respondWithError("Unable to refresh token");
            }
    	} catch (TokenInvalidException $e) {
            return $this->respondWithError("Token Invalid");
    	} catch (JWTException $e) {
            return $this->respondWithError("Token not available");
    	}

        return $this->respondWithError("Application not authorized!");
    }
}
