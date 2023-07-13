<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RateLimitSearches
{
    protected $limiter;

    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    public function handle($request, Closure $next)
    {
        $key = $request->ip(); // Use the IP address as the unique key for rate limiting

        // Define the rate limit rules
        $rules = [
            'max_attempts' => 10,     // Maximum number of attempts allowed
            'decay_seconds' => 2,    // Time period in minutes
        ];

        // Check if the request is within the rate limits
        if ($this->limiter->tooManyAttempts($key, $rules['max_attempts'])) {
            // If the user has exceeded the rate limit, return a response
            return new Response('Too Many Requests', 429);
        }

        // Increment the attempts for the IP address
        $this->limiter->hit($key, $rules['decay_seconds']);

        // Continue processing the request
        return $next($request);
    }
}
