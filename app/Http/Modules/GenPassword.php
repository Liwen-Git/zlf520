<?php


namespace App\Http\Modules;


trait GenPassword
{
    /**
     * 用户密码加密
     * @param $password
     * @param $salt
     * @return string
     */
    public static function genPassword($password, $salt)
    {
        return md5(md5($password) . $salt);
    }
}