<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FormDataParser
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('PUT') && 
            strpos($request->header('Content-Type'), 'multipart/form-data') !== false) {
            
            $rawContent = $request->getContent();
            if (preg_match('/boundary=(.*)$/', $request->header('Content-Type'), $matches)) {
                $boundary = $matches[1];
                $parts = explode('--' . $boundary, $rawContent);
                
                $data = [];
                foreach ($parts as $part) {
                    if (strpos($part, 'Content-Disposition: form-data;') !== false) {
                        preg_match('/name=\"([^\"]*)\"/', $part, $matches);
                        if (isset($matches[1])) {
                            $name = $matches[1];
                            $value = trim(substr($part, strpos($part, "\r\n\r\n") + 4));
                            $data[$name] = $value;
                        }
                    }
                }
                
                // Merge the parsed data with the request
                $request->merge($data);
            }
        }

        return $next($request);
    }
}