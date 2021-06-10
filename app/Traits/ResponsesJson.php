<?php

namespace App\Traits;

use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\JsonResponse;

trait ResponsesJson
{
    public function response($success, $data, $message, $errors, $responseCode = Response::HTTP_OK, $alert) {
        return new JsonResponse(
            array(
                'success' => $success,
                'data' => $data,
                'message' => $message,
                'errors' => $errors,
                'alert' => $alert,
            ),
            $responseCode
        );
    }

    public function successfulResponse($data = null, $message = 'Request success.', $alert = false)
    {
        return $this->response(true, $data, $message, array(), Response::HTTP_OK, $alert);
    }

    public function noEntryFoundResponse($errors = array(), $message = 'No entry found.', $alert = false)
    {
        return $this->response(false, array(), $message, $errors, Response::HTTP_NOT_FOUND, $alert);
    }

    public function errorResponse($errors = array(), $message = 'Request failed.', $alert = false)
    {
        return $this->response(false, array(), $message, $errors, Response::HTTP_BAD_REQUEST, $alert);
    }

    public function notAuthorizedResponse($message = 'You are not authorized to access this page.', $alert = false)
    {
        return $this->response(false, array(), $message, array(), Response::HTTP_UNAUTHORIZED, $alert);
    }

    public function createdResponse($data = null, $message = 'Resource created.', $alert = false)
    {
        return $this->response(true, $data, $message, array(), Response::HTTP_CREATED, $alert);
    }

    public function noContentResponse($data = null, $message = 'No content.', $alert = false)
    {
        return $this->response(true, $data, $message, array(), Response::HTTP_NO_CONTENT, $alert);
    }
}
