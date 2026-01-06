<?php

namespace Dunique\AntwanVanBoheemen\Core\Http;

class Response
{
    // Send a generic API response with a status code, message, and optional data
    public function send(int $code, string $status, $message, $data = null)
    {
        ee()->lang->loadfile('dunique');
        // Prepare the response
        $response = [
            'status' => $status,
            'message' => is_string($message) ? lang($message) : $message ,
        ];

        if ($data) {
            $response['data'] = $data;
        }

        // Send the response
        return ee()->output->send_ajax_response($response, $code);
    }

    // Send a success response (200)
    public function success($data = null, $message = 'Request was successful')
    {
        return $this->send(200, 'success', $message, $data);
    }

    // Send an error response (400 or 500)
    public function error($data = null, $message = 'An error occurred', $code = 400)
    {
        return $this->send($code, 'error', $message, $data);
    }

    // Send an unauthorized response (401)
    public function unauthorized($message = 'Unauthorized access')
    {
        return $this->send(401, 'error', $message);
    }

    // Send a not found response (404)
    public function notFound($data = null, $message = 'Resource not found')
    {
        return $this->send(404, 'error', $message, $data);
    }

    // Send a method not allowed response (405)
    public function methodNotAllowed($data = null, $message = 'Method Not Allowed')
    {
        return $this->send(405, 'error', $message, $data);
    }
}
