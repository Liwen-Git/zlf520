<?php

namespace App\Http\Controllers\User;

use App\Modules\Invite\InviteChannel;
use App\Modules\Poster\PosterService;
use App\Result;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class QRCodePosterController extends Controller
{
    public function getPoster(){
        $this->validate(request(),[
            'scene'  => 'required',
            'id'     => 'required',
        ]);
        $user = request()->get('current_user');
        PosterService::get(request('scene'),request('id'),isset($user->id)?$user->id:0,InviteChannel::ORIGIN_TYPE_USER,request('order_id',0));
        $poster = [
            'id'        =>  1,
            'scene_id'  =>  1,
            'url'       =>  'https://timgsa.baidu.com/timg?image&quality=80&size=b9999_10000&sec=1545832943750&di=88bcffb89ecb3b03f39dde922097f6c3&imgtype=0&src=http%3A%2F%2Fimg2.ph.126.net%2FFtLavPpQ3tPi6RUJ5s7Ckw%3D%3D%2F6632426762073984518.jpg',
        ];;
        return Result::success($poster);
    }

}
