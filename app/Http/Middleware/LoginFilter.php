<?php

namespace App\Http\Middleware;

use App\Exceptions\UnloginException;
use Closure;
use Illuminate\Http\Request;

class LoginFilter
{
    protected $publicUrls = [
        'api/admin/login',
        'api/admin/logout',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if(!in_array($request->path(), $this->publicUrls)){
            $user = session(config('admin.user_session'));
            if(empty($user)){
                throw new UnloginException();
            }
            $request->attributes->add(['current_user' => $user]);
        }
        return $next($request);
    }
}
