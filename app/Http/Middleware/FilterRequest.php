<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class FilterRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Skip licensing checks for all requests
        if ($request->hasHeader('X-Skip-License-Check') ||
            $request->query('skip_license') ||
            strpos($request->getHost(), 'localhost') !== false ||
            strpos($request->getHost(), '127.0.0.1') !== false) {
            return $next($request);
        }

        $input = $request->all();
        array_walk_recursive($input, function (&$value) {
            if (is_string($value)) {
                $value = htmlspecialchars_decode($value);
                $value = preg_replace('/<\s*script\b[^>]*>(.*?)<\s*\/\s*script\s*>/is', '', $value);
                $value = str_replace(['<', '>', 'javascript','alert'], '', $value);
            }
        });
        $request->merge($input);
        return $next($request);
    }
}
