<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\BaseApiController;
use App\Models\Product;

class CarparkController extends BaseApiController
{
    public function __construct()
    {

    }

    /*
     * Search for a carpark on a given airport, date and time
     *
     * @param Illuminate\Http\Request $request
     * @return JSON
     */
    public function search(Request $request)
    {
        /*****************************
         * Required parameters:
         *  - Airport ID
         *  - Start date
         *  - End date
         *
         * Optional parameters
         *  - Start time
         *  - End time
         *****************************/
        $formRequest = $request->only(['airport', 'start_date', 'end_date', 'start_time', 'end_time']);
        $application = $request->header('appname');
        $service = $request->header('service');

        if (! $this->validateFields(['appname' => $application, 'service' => $service], $this->headerRules)) {
            return $this->respondValidationError("Validation errors", $this->errors);
        }

        if (! $this->validateFields($formRequest, $this->rules)) {
            return $this->respondValidationError("Validation errors", $this->errors);
        }

        if (! $this->isAuthorized($application, $service)) {
            return $this->respondWithError($this->errors);
        }

        $products = Product::search($formRequest['airport'], $formRequest['start_date'], $formRequest['end_date'], $formRequest['start_time'], $formRequest['end_time']);

        return $this->respond([
            'status'      => 'success',
            'status_code' => $this->getStatusCode(),
            'message'     => 'Success',
            'data'        => [
                'products' => $products
            ]
        ]);
    }
}
