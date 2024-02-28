<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->header('accept') != 'application/vnd.api+json') {
            return response()->json([
                "errors"=>[
                    "status"=>406,
                    "title"=>"Not Accetable",
                    "deatails"=>"Content File not specifed"
                ]
            ],406);
        }
        return $next($request);
    }
}
