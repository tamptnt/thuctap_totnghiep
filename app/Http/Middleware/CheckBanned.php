<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
class CheckBanned
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && (auth()->user()->status == 0)) {
            Auth::logout();

            $request->session()->invalidate();

            $request->session()->regenerateToken();

            return redirect()->back()->with('warning', 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ ban quản trị ! ');
        }
//        if (auth()->check() && (auth()->user()->email_verified == 0)) {
//            Auth::logout();
//
//            $request->session()->invalidate();
//
//            $request->session()->regenerateToken();
//
//            return redirect()->back()->with('warning', 'Tài khoản của bạn chưa kích hoạt.Vui lòng kích hoạt tài khoản ! ');
//        }
        return $next($request);
    }
}
