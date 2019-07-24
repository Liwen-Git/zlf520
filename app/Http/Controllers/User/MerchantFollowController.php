<?php

namespace App\Http\Controllers\User;

use App\Exceptions\BaseResponseException;
use App\Http\Controllers\Controller;
use App\Modules\Merchant\MerchantFollow;
use App\Modules\Merchant\MerchantFollowService;
use App\Result;

class MerchantFollowController extends Controller
{
    /**
     * 普通商户和超市商户关注
     * @return \Illuminate\Http\JsonResponse
     */
    public function modifyFollowStatus()
    {
        $user = request()->get('current_user');
        $merchantId = request('merchant_id');
        $merchantType = request('merchant_type', MerchantFollow::MERCHANT_TYPE_NORMAL);
        $status = request('status');

        if (!$merchantId) {
            throw new BaseResponseException('商户ID不能为空');
        }
        if (!$status) {
            throw new BaseResponseException('状态不能为空');
        }
        if (!$merchantType) {
            throw new BaseResponseException('商户类型不能为空');
        }

        $data = MerchantFollowService::modifyFollows([
            'status' => $status, //1-未关注，2-已关注
            'user_id' => $user->id,
            'merchant_id' => $merchantId,
            'merchant_type' => $merchantType,
        ]);

        return Result::success([
            'data' => $data
        ]);
    }

    /**
     * 普通商户和超市商户的关注列表
     * @return \Illuminate\Http\JsonResponse
     */
    public function userFollowList()
    {
        $userId = request()->get('current_user')->id;
        $lng = request('lng',0);
        $lat = request('lat',0);
        $merchantType = request('merchant_type', MerchantFollow::MERCHANT_TYPE_NORMAL);
        //获取用户收藏的列表
        if ($merchantType == MerchantFollow::MERCHANT_TYPE_NORMAL) {
            $data = MerchantFollowService::getFollowMerchantList($userId, $lng, $lat, false);
        } else {
            $user_key =  request()->get('current-device-no');
            if (empty($user_key)) {
                $user_key = request()->headers->get('token');
                if (empty($user_key)) {
                    $user_key = request()->ip();
                }
            }
            $data = MerchantFollowService::getFollowCsMerchantList($userId, [
                'lng' => request('lng'),
                'lat' => request('lat'),
                'user_key' => $user_key,
                'pageSize' => request('pageSize',15),
                'page'  =>  request('page'),
            ]);
        }

        return Result::success($data);
    }
}