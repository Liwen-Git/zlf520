<?php

namespace App\Http\Services;

use App\Http\Modules\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UserService extends BaseService
{
    /**
     * @param $name
     * @return Builder|Model|object|null
     */
    public static function getUserByName($name)
    {
        $user = User::where('name', $name)->first();
        return $user;
    }

    /**
     * @param $name
     * @param $password
     * @return User
     */
    public function add($name, $password)
    {
        $user = new User();
        $user->name = $name;
        $salt = Str::random();
        $user->salt = $salt;
        $user->password = User::genPassword($password, $salt);
        $user->save();

        return $user;
    }
}