<?php

namespace App\Http\Controllers\Api;

use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use \Illuminate\Http\Response as Res;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use JWTAuth;
use Response;

/**
 * Class ApiController
 * @package App\Modules\Api\Lesson\Controllers
 */
class ApiController extends Controller
{
    /**
     * Create a new authentication controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->beforeFilter('auth', ['on' => 'post']);
    }

    /**
     * @var int
     */
    protected $statusCode = Res::HTTP_OK;

    protected $errors = null;

    protected $rules = [
		'appname' => 'required|exists:applications,slug',
		'service' => 'required|exists:services,slug',
	];

    /**
     * @return mixed
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @param $message
     * @return json response
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    public function respondCreated($message, $data = null)
    {
        return $this->respond([
            'status' => 'success',
            'status_code' => Res::HTTP_CREATED,
            'message' => $message,
            'data' => $data
        ]);
    }

    /**
     * @param Paginator $paginate
     * @param $data
     * @return mixed
     */
    protected function respondWithPagination(Paginator $paginate, $data, $message)
    {
        $data = array_merge($data, [
            'paginator' => [
                'total_count'  => $paginate->total(),
                'total_pages' => ceil($paginate->total() / $paginate->perPage()),
                'current_page' => $paginate->currentPage(),
                'limit' => $paginate->perPage(),
            ]
        ]);
        return $this->respond([
            'status' => 'success',
            'status_code' => Res::HTTP_OK,
            'message' => $message,
            'data' => $data
        ]);
    }

    public function respondNotFound($message = 'Not Found!')
    {
        return $this->respond([
            'status' => 'error',
            'status_code' => Res::HTTP_NOT_FOUND,
            'message' => $message,
        ]);
    }

    public function respondInternalError($message)
    {
        return $this->respond([
            'status' => 'error',
            'status_code' => Res::HTTP_INTERNAL_SERVER_ERROR,
            'message' => $message,
        ]);
    }

    public function respondValidationError($message, $errors)
    {
        return $this->respond([
            'status' => 'error',
            'status_code' => Res::HTTP_UNPROCESSABLE_ENTITY,
            'message' => $message,
            'data' => $errors
        ]);
    }

    public function respond($data, $headers = [])
    {
        return Response::json($data, $this->getStatusCode(), $headers);
    }

    public function respondWithError($message)
    {
        return $this->respond([
            'status' => 'error',
            'status_code' => Res::HTTP_UNAUTHORIZED,
            'message' => $message,
        ]);
    }

    public function validateFields($required_fields)
	{
		$validator = Validator::make($required_fields, $this->rules);

		if ($validator->fails()) {
			$this->errors = $validator->messages();

			return false;
		}

		return true;
	}

//    public function isUserAuthorized(Request $request)
//    {
//        static $isUserAuthorized = null;
//        static $requestHash = null;
//
//        $requestHash = $requestHash ?? spl_object_hash($request);
//        $authProcess = function(Request $request) {
//            try {
//                JWTAuth::setRequest($request)->parseToken();
//            } catch (\Throwable $e) {
//                return false;
//            }
//
//            try {
//                $token = JWTAuth::getToken();
//
//                if (false === $token) {
//                    return false;
//                }
//
//                $user = JWTAuth::authenticate($token);
//
//                if (false === $user) {
//                    return false;
//                }
//
//            } catch (TokenExpiredException $e) {
//                try {
//                    $token = JWTAuth::refresh();
//                    static::$newToken = $token;
//
//                } catch (TokenExpiredException $e) {
//                    return false;
//                }
//            } catch (\Throwable $e) {
//                return false;
//            }
//
//            return true;
//        };
//
//        if ($requestHash !== spl_object_hash($request)) {
//            $isUserAuthorized = $authProcess($request);
//
//        } elseif (is_null($isUserAuthorized)) {
//            $isUserAuthorized = $authProcess($request);
//
//        }
//
//        return $isUserAuthorized;
//    }
}
