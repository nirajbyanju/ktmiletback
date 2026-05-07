<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;

class BaseController extends Controller
{
    /**
     * Success response method.
     *
     * @param mixed $result
     * @param string $message
     * @return \Illuminate\Http\Response
     */
    public function sendResponse($result, $message)
    {
        $response = [
            'success' => true,
            'data'    => $result,
            'message' => $message,
        ];

        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Return error response.
     *
     * @param string $error
     * @param array $errorMessages
     * @param int $code
     * @return \Illuminate\Http\Response
     */
    public function sendError($error, $errorDetails = null, $code = 400)
    {
        $response = [
            'success' => false,
            'message' => $error,
        ];

        if (!empty($errorDetails)) {
            $response['errors'] = $errorDetails;
        }

        if (config('app.debug')) {
            $response['debug'] = [
                'exception' => $errorDetails
            ];
        }

        return response()->json($response, (int) $code);
    }

    /**
     * Paginated response method.
     *
     * @param mixed $data
     * @param string $message
     * @param array $pagination
     * @return \Illuminate\Http\Response
     */
    public function sendPaginatedResponse($data, $message, $pagination)
    {
        $response = [
            'success' => true,
            'data'    => $data,
            'message' => $message,
            'pagination' => $pagination,
        ];

        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Default constructor for any shared logic (e.g., middleware).
     */
    public function __construct()
    {
        // Example: Apply middleware globally for all child controllers
        // $this->middleware('auth:api');
    }
}
