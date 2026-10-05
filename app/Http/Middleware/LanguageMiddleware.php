<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class LanguageMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $language = Session("language",config("app.locale"));
        switch($language){
            case "en":
                $language = "en";
                break;
            default:
                $language = "vi";
                break;
        }
        App::setLocale($language);
        return $next($request);
    }
}
