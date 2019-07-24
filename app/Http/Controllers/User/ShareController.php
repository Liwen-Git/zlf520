<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Result;
use Illuminate\Support\Facades\Cache;

class ShareController extends Controller
{
    /**
     * 分享超市 获取分享的token
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserShareToken()
    {
        $this->validate(request(), [
            'share_user_id' => 'required'
        ]);
        $shareUserId = request('share_user_id');
        $key = request('share_key');

        if (!$key) {
            $key = md5(uniqid(mt_rand(), 1));
        }
        $value = md5($key.microtime());
        $expiresAt = now()->addYear();
        Cache::put($key, $value, $expiresAt);

        return Result::success([
            'share_key' => $key,
            'share_token' => $value,
            'share_user_id' => $shareUserId,
        ]);
    }
}