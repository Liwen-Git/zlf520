<?php

namespace App\Http\Controllers\UserApp;

use App\Modules\Invite\InviteChannel;
use App\Modules\Poster\PosterService;
use App\Result;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class QRCodePosterController extends Controller
{
    public function getPoster(){
        $this->validate(request(),[
            'scene'  => 'required|in:4,5,6,7,8,9,11',
            'id'     => 'required',

        ],[
            'scene.required'    =>  '场景不可为空',
            'scene.in'          =>  '场景不在合法范围',
            'id.required'       =>  'ID不可为空',
        ]);
        $user = request()->get('current_user');
        $poster = PosterService::get(request('scene'),request('id'),isset($user->id)?$user->id:0,InviteChannel::ORIGIN_TYPE_USER,request('order_id',0));
        return Result::success($poster);
    }

}
