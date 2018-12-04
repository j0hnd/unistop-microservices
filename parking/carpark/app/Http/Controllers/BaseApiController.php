<?php

namespace App\Http\Controllers;

use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use \Illuminate\Http\Response as Res;
use Illuminate\Http\Request;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Validator;
use Response;

/**
 * Class ApiController
 * @package App\Modules\Api\Lesson\Controllers
 */
class BaseApiController extends Controller
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

    protected $token = null;

    protected $rules = [
        'airport'    => 'required|exists:airports,id',
        'start_date' => 'required|date|after_or_equal:now',
        'end_date'   => 'required|date|after_or_equal:start_date',
        'start_time' => 'present',
        'end_time'   => 'present',
	];

    protected $headerRules = [
        'appname' => 'required',
        'service' => 'required'
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

    public function validateFields($required_fields, $rules)
	{
		$validator = Validator::make($required_fields, $rules);

		if ($validator->fails()) {
			$this->errors = $validator->messages();

			return false;
		}

		return true;
	}

    public function isAuthorized($applicationName, $serviceName)
    {
        if (is_null($this->token)) {
            $this->token = $this->getToken($applicationName, $serviceName);

            if (is_null($this->token)) {
                return null;
            }

            $client = new Client([
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token
                ]
            ]);

            $response = $client->post(config('app.hasher_api_url') . "/validate.json", [
                'form_params' => [
                    'appname' => $applicationName,
                    'service' => $serviceName
                ]
            ]);

            $hasher = json_decode($response->getBody()->getContents(), true);
            if (json_last_error() == JSON_ERROR_NONE) {
                if ($hasher['status'] == 'success') {
                    return true;
                }

                $this->errors = $hasher['message'];
            }
        }

        return null;
    }

    private function getToken($applicationName, $serviceName)
    {
        $client = new Client();

        $token = null;

        $response = $client->post(config('app.hasher_api_url') . "/get.json", [
            'form_params' => [
                'appname' => $applicationName,
                'service' => $serviceName
            ]
        ]);

        if ($response->getStatusCode() == 200) {
            $hasher = json_decode($response->getBody()->getContents(), true);
            if (json_last_error() == JSON_ERROR_NONE) {
                if ($hasher['status'] == 'success') {
                    $token = $hasher['data']['token'];
                } elseif ($hasher['status'] == 'error') {
                    $this->errors = $hasher['message'];
                } else {
                    $this->errors = $hasher['data'];
                }
            }
        }

        return $token;
    }
}
