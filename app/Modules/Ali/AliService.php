<?php

namespace App\Modules\Ali;


class AliService
{
    public static function getAliPayApp()
    {
        $aliPayApp = config('platform.ali_pay.app');
        $config = [
            'app_id' => $aliPayApp['app_id'],
            'notify_url' => request()->getSchemeAndHttpHost().'/api/pay/aliNotify',
//            'return_url' => request()->getSchemeAndHttpHost().'/api/pay/aliReturn',
            'ali_public_key' => file_get_contents($aliPayApp['public_key_path']),
            'private_key' => file_get_contents($aliPayApp['private_key_path']),
        ];
        return $config;
    }
}
