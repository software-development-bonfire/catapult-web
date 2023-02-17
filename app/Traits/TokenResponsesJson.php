<?php

namespace App\Traits;

use App\Enums\TokenResponseCode;
use Illuminate\Support\Facades\Lang;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\JsonResponse;

trait TokenResponsesJson
{
    public function response($success, $data, $message, $errors, $responseCode = Response::HTTP_OK, $tokenResponseCode)
    {
        return new JsonResponse(
            array(
                'success' => $success,
                'data' => $data,
                'message' => $message,
                'errors' => $errors,
                'tokenResponseCode' => $tokenResponseCode
            ),
            $responseCode
        );
    }

    public function malformedTokenResponse($data = null, $message = 'Request for another token.')
    {
        return $this->response(
            false,
            $data,
            $message,
            array(
                Lang::get('error.malformed_token')
            ),
            Response::HTTP_BAD_REQUEST,
            TokenResponseCode::TOKEN_MALFORMED);
    }

    public function invalidTokenResponse($data = null, $message = 'Request for another token.')
    {
        return $this->response(
            false,
            $data,
            $message,
            array(
                Lang::get('error.invalid_token')
            ),
            Response::HTTP_BAD_REQUEST,
            TokenResponseCode::TOKEN_INVALID);
    }

    public function expiredTokenResponse($data = null, $message = 'Request for another token.')
    {
        return $this->response(
            false,
            $data,
            $message,
            array(
                Lang::get('error.expired_token')
            ),
            Response::HTTP_BAD_REQUEST,
            TokenResponseCode::TOKEN_EXPIRED);
    }

    public function requiredTokenResponse($data = null, $message = 'Request for token.')
    {
        return $this->response(
            false,
            $data,
            $message,
            array(
                Lang::get('error.token_required')
            ),
            Response::HTTP_BAD_REQUEST,
            TokenResponseCode::TOKEN_REQUIRED);
    }

    public function tokenGeneratedResponse($data = null, $message = 'Token Generated')
    {
        return $this->response(
            false,
            $data,
            $message,
            array(),
            Response::HTTP_OK,
            TokenResponseCode::TOKEN_GENERATED);
    }
}
