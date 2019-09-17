<?php

namespace app\http\middleware;

class Before
{
    public function handle($request, \Closure $next)
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, If-Match, If-Modified-Since, If-None-Match, If-Unmodified-Since, X-Requested-With,Token');
        header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        header('Access-Control-Max-Age: 1728000');
        if (strtoupper($request->method()) == 'OPTIONS') exit();
        return $next($request);
    }
}
