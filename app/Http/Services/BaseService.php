<?php

namespace App\Http\Services;

class BaseService
{
    const SERVER_KEY = 'SCT25576TfwMvwtu0ob5Ojx5kSNff89Ng';
    public static function sc_send($text, $desp = '', $key = self::SERVER_KEY)
    {
        $postData = http_build_query(
            array(
                'text' => $text,
                'desp' => $desp
            )
        );

        $opts = array('http' =>
            array(
                'method'  => 'POST',
                'header'  => 'Content-type: application/x-www-form-urlencoded',
                'content' => $postData
            )
        );
        $context  = stream_context_create($opts);
        return $result = file_get_contents('https://sctapi.ftqq.com/'.$key.'.send', false, $context);

    }
}
