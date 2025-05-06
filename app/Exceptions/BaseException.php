<?php

namespace App\Exceptions;

use Exception;

class BaseException extends Exception
{
    protected mixed $statusCode = 400;
    protected mixed $customMessage;

    public function __construct($message = null, $statusCode = null)
    {
        parent::__construct($statusCode === 422 ? '' : $message);

        if ($statusCode === 422) $this->customMessage = $message;

        if (!is_null($statusCode)) {
            $this->statusCode = $statusCode;
        }
    }

    public function render($request): \Illuminate\Http\JsonResponse
    {

        $response = [
            'error' => class_basename($this),
            'message' => $this->getMessage()
        ];

        if ($this->statusCode === 422):
            $response = $this->customMessage;
        endif;

        return response()->json($response, $this->statusCode);
    }
}
