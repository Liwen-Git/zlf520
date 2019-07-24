<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/2/6
 * Time: 12:47
 */

namespace App\Http\Controllers\Admin;


use App\DataCacheService;
use App\Exceptions\UnloginException;
use App\Http\Controllers\Controller;
use App\Modules\Admin\AdminRuleService;
use App\Modules\Admin\AdminUserService;
use App\Result;
use Illuminate\Support\Facades\Session;

class SelfController extends Controller
{

    protected $user = null;

    public function __construct()
    {
        $this->user = request()->get('current_user');
    }

    public function login()
    {
        $rules = [
            'username' => 'required',
            'password' => 'required|between:6,30',
            'verifyCode' => 'required|captcha'
        ];
        // 测试环境添加万能验证码
        if(env('APP_ENV') != 'production' && request('verifyCode') == '6666'){
            unset($rules['verifyCode']);
        }
        $this->validate(request(), $rules);

        $user = AdminUserService::checkPasswordByUsername(request('username'), request('password'));

        $rules = AdminUserService::getUserRules($user);
        $menuTree = AdminRuleService::convertRulesToTree($rules);

        $user->token = md5($user->username . time());
        $user->marketing_url = config('common.marketing_domain') . '/front/admin?token=' . $user->token;
        DataCacheService::setLoginCache('admin',$user);
        session([
            config('admin.user_session') => $user,
            config('admin.user_rule_session') => $rules
        ]);
        return Result::success([
            'user' => $user,
            'menus' => $menuTree,
            'rules' => $rules,
        ]);
    }

    public function logout()
    {
        $user = session(config('admin.user_session'));
        DataCacheService::delLoginCache('admin',$user);
        Session::remove(config('admin.user_session'));
        Session::remove(config('admin.user_rule_session'));
        return Result::success();
    }

    public function getRules()
    {
        $user = request()->get('current_user');
        if(empty($user)){
            throw new UnloginException();
        }

        if(!isset($user['token'])){
            $user->token = md5($user->merchantName . time());
            $user->marketing_url = config('common.marketing_domain') . '/front/admin?token=' . $user->token;
            DataCacheService::setLoginCache('admin',$user);
        }

        $rules = AdminUserService::getUserRules($user);
        $menuTree = AdminRuleService::convertRulesToTree($rules);

        session([
            config('admin.user_rule_session') => $rules
        ]);

        return Result::success([
            'user' => $user,
            'menus' => $menuTree,
            'rules' => $rules,
        ]);
    }

    public function modifyPassword()
    {
        $this->validate(request(), [
            'password' => 'required',
            'newPassword' => 'required|between:6,30',
            'reNewPassword' => 'required|same:newPassword'
        ]);
        $user = request()->get('current_user');

        $user = AdminUserService::modifyPassword($user, request('password'), request('newPassword'));

        // 修改密码成功后更新session中的user
        session([
            config('admin.user_session') => $user,
        ]);

        return Result::success($user);
    }

}