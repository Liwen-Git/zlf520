<?php


namespace App\Http\Controllers;


use App\Exceptions\BaseResponseException;
use App\Http\Modules\User;
use App\Http\Services\UserService;
use App\Result;
use App\ResultCode;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * @return ResponseFactory|Response
     * @throws ValidationException
     */
    public function login()
    {
        $this->validate(request(), [
            'username' => 'required',
            'password' => 'required|between:6,30',
            'verifyCode' => 'required|captcha',
        ]);
        $user = UserService::getUserByName(request('username'));
        if (empty($user)) {
            throw new BaseResponseException('该用户不存在', ResultCode::ACCOUNT_NOT_FOUND);
        }
        if (User::genPassword(request('password'), $user->salt) != $user->password) {
            throw new BaseResponseException('密码错误', ResultCode::ACCOUNT_PASSWORD_ERROR);
        }
        session([config('admin.user_session') => $user]);

        return Result::success($user);
    }

    /**
     * @return ResponseFactory|Response
     */
    public function logout()
    {
        Session::remove(config('admin.user_session'));
        return Result::success();
    }
}