<?php

declare(strict_types=1);

namespace App\Middleware;

use Framework\Contracts\MiddlewareInterface;
use Framework\Exceptions\ValidationException;

class ValidationExceptionMiddleware implements MiddlewareInterface
{
    public function process(callable $next)
    {
        try {
            $next();
        } catch (ValidationException $e) {
            // Save the current form data from the POST request in a variable
            $oldFormData = $_POST;

            // Define an array of field names to be excluded from the form data (e.g., sensitive information like passwords)
            $excludedField = ['password', 'confirmPassword'];

            // Create a new array that includes only the form data without the excluded fields
            $formattedFormData = array_diff_key($oldFormData, array_flip($excludedField));

            // Store any errors from an exception object (assuming $e is an exception object with an 'errors' property) in the session
            $_SESSION['errors'] = $e->errors;

            // Save the formatted form data in the session to repopulate the form with valid data in case of a redirect
            $_SESSION['oldFormData'] = $formattedFormData;

            // Retrieve the referer (the previous page's URL) from the server's HTTP_REFERER header
            $referer = $_SERVER['HTTP_REFERER'];

            // Redirect the user back to the referer page
            redirectTo($referer);
        }
    }
}
