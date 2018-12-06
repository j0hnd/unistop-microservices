<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\BaseApiController;

class UserController extends BaseApiController
{
    protected $appName = null;

    protected $service = null;


    public function __construct(Request $request)
    {
        $this->appName = $request->headers->get('appname');
        $this->service = $request->headers->get('service');
    }

    public function login(Request $request)
    {
        return $this->respond([
            'status'      => 'success',
            'status_code' => $this->getStatusCode(),
            'message'     => 'Success'
        ]);
    }

    public function logout(Request $request)
    {
    }

    public function profile(Request $request)
    {
        if ($this->isAuthorized($this->appName, $this->service) == false) {
            return $this->respondWithError("Unauthorized module");
        }

        if ($this->isUserAuthorized($request) == false) {
            return $this->respondWithError("Unauthorized access");
        }
    }

    public function forgot(Request $request)
    {
    }
}
