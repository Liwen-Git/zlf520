<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/22
 * Time: 14:16
 */

namespace App;


use App\Support\Curl;

class UserCenterService
{

    public static function sendCode($phone='')
    {

        $phone = '15989438364';
        $header['APP-USER-CODE'] = 'U0001';

        $params['phone'] = $phone;
        $rt = self::makeSign($params);
        $header['APP-USER-SIGN'] = $rt['sign'];

        $data = $rt['body'];

        $url = 'http://120.77.249.3:8080/userCenter/user/getText';

        $rt = Curl::postJson($url,$data,$header);
        dd($rt);
    }

    public static function register()
    {
        $url = 'http://120.77.249.3:8080/userCenter/user/addUser';


        $params['code'] = '3705';
        $params['username'] = '';
        $params['phone'] = '15989438364';
        $params['email'] = '';
        $params['password'] = '111111';

        $rt = self::makeSign($params);
        $header['APP-USER-CODE'] = 'U0001';
        $header['APP-USER-SIGN'] = $rt['sign'];


        $data = $rt['body'];
        $rt = Curl::postJson($url,$data,$header);

        dd($rt);
    }

    public static function makeSign($params = [])
    {
        $rt['body'] = '';
        $rt['sign'] = '';
        $key = '93da322eb18a4aaeb707aa61218cc33d';
        $str = '';
        $str .= $key;
        ksort($params);
        foreach ($params as $k=>$v) {

            if (empty($v)) {
                unset($params[$k]);
            }

            $str .= $v;
        }

        $str .= date('Ymd');

        $sign = md5($str);

        $rt['body'] = json_encode($params);
        $rt['sign'] = $sign;
        return $rt;
    }

}