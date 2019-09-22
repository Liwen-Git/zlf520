<?php

use App\Http\Modules\User;
use App\Http\Services\UserService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = UserService::getUserByName('zhenglifei');
        if (!empty($user)) {
            $salt = Str::random();
            $user->salt = $salt;
            $user->password = User::genPassword('zlf902.com', $salt);
            $user->save();
        } else {
            $userService = new UserService();
            $userService->add('zhenglifei', 'zlf902.com');
        }
    }
}
