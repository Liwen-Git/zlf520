<?php

namespace App\Http\Middleware\MerchantApp;


use App\Exceptions\UnloginException;
use Closure;
use Illuminate\Http\Request;

class MerchantAppLoginFilter
{
    // 不需要登录的url列表
    protected $publicUrls = [
        'api/app/merchant/login',
        'api/app/merchant/logout',
    ];

    /**
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if(!in_array($request->path(), $this->publicUrls)){
            $user = $request->get('current_user');
            if(empty($user)){
                throw new UnloginException();
            }
        }
        return $next($request);
    }
}