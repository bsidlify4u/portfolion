<?php

namespace Portfolion\Http;

use Portfolion\Routing\Controller;

/**
 * Base API controller with standardized response methods
 */
class ApiController extends Controller
{
    /**
     * Return a successful response with data
     * 
     * @param mixed $data The data to include in the response
     * @param int $status The HTTP status code
     * @param array $headers Additional headers
     * @return Response
     */
    protected function success($data = null, int $status = 200, array $headers = []): Response
    {
        $responseData = [
            'success' => true,
        ];
        
        if ($data !== null) {
            if ($data instanceof ApiResource || $data instanceof ResourceCollection) {
                $responseData = array_merge($responseData, $data->jsonSerialize());
            } else {
                $responseData['data'] = $data;
            }
        }
        
        return $this->response($responseData, $status, $headers);
    }
    
    /**
     * Return an error response
     * 
     * @param string $message The error message
     * @param int $status The HTTP status code
     * @param array $errors Additional error details
     * @param array $headers Additional headers
     * @return Response
     */
    protected function error(string $message, int $status = 400, array $errors = [], array $headers = []): Response
    {
        $responseData = [
            'success' => false,
            'message' => $message,
        ];
        
        if (!empty($errors)) {
            $responseData['errors'] = $errors;
        }
        
        return $this->response($responseData, $status, $headers);
    }
    
    /**
     * Return a created response
     * 
     * @param mixed $data The data to include in the response
     * @param array $headers Additional headers
     * @return Response
     */
    protected function created($data = null, array $headers = []): Response
    {
        return $this->success($data, 201, $headers);
    }
    
    /**
     * Return a validation error response
     * 
     * @param array $errors Validation errors
     * @param string $message Custom message
     * @param array $headers Additional headers
     * @return Response
     */
    protected function validationError(array $errors, string $message = 'The given data was invalid.', array $headers = []): Response
    {
        return $this->error($message, 422, $errors, $headers);
    }
    
    /**
     * Return a not found error response
     * 
     * @param string $message The error message
     * @param array $headers Additional headers
     * @return Response
     */
    protected function notFound(string $message = 'Resource not found.', array $headers = []): Response
    {
        return $this->error($message, 404, [], $headers);
    }
    
    /**
     * Return an unauthorized error response
     * 
     * @param string $message The error message
     * @param array $headers Additional headers
     * @return Response
     */
    protected function unauthorized(string $message = 'Unauthorized.', array $headers = []): Response
    {
        return $this->error($message, 401, [], $headers);
    }
    
    /**
     * Return a forbidden error response
     * 
     * @param string $message The error message
     * @param array $headers Additional headers
     * @return Response
     */
    protected function forbidden(string $message = 'Forbidden.', array $headers = []): Response
    {
        return $this->error($message, 403, [], $headers);
    }
    
    /**
     * Return a no content response
     * 
     * @param array $headers Additional headers
     * @return Response
     */
    protected function noContent(array $headers = []): Response
    {
        return (new Response('', 204, $headers));
    }
    
    /**
     * Build and send a JSON response
     * 
     * @param array $data Response data
     * @param int $status HTTP status code
     * @param array $headers Additional headers
     * @return Response
     */
    protected function response(array $data, int $status = 200, array $headers = []): Response
    {
        return (new Response)->json($data, $status, array_merge([
            'Content-Type' => 'application/json',
        ], $headers));
    }
} 