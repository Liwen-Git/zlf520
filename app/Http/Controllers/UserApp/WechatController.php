<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/21
 * Time: 10:57 AM
 */

namespace App\Http\Controllers\UserApp;
use App\Exceptions\BaseResponseException;
use App\Exceptions\DataNotFoundException;
use App\Exceptions\ParamInvalidException;
use App\Http\Controllers\Controller;
use App\Modules\Order\Order;
use App\Support\MarketingApi;
use App\Modules\Cs\CsGood;
use App\Modules\Cs\CsMerchant;
use App\Modules\Dishes\DishesGoods;
use App\Modules\Goods\Goods;
use App\Modules\Merchant\Merchant;
use App\Result;

class WechatController extends Controller
{
    public function getShareInfo(){

        $this->validate(request(), [
            'type' => 'required|in:merchant,goods,cs_merchant,cs_goods,dishes_goods,flash_sale,group_order,group_sale'
        ]);
        $type = request('type');//普通商户：merchant 、 团购商品：goods

        $miniprogramShareInfo = [];
        if($type == 'merchant'){
            $merchantId = request('merchantId',0);
            if(empty($merchantId)){
                throw new ParamInvalidException('参数不合法');
            }
            $merchantInfo = Merchant::where('id',$merchantId)->first();
            if(empty($merchantInfo)){
                throw new DataNotFoundException('商户信息不存在');
            }
            $miniprogramShareInfo['name'] = $merchantInfo->signboard_name;
            $miniprogramShareInfo['desc'] = $merchantInfo->desc;
            $miniprogramShareInfo['logo'] = $merchantInfo->logo;

            $desc_pic = $merchantInfo->logo;

            $miniprogramShareInfo['desc_pic'] = $desc_pic;

            $miniprogramShareInfo['path'] = '/pages/merchant/index?scene=1001&id='.$merchantInfo->id;//小程序页面路径

        }else if($type == "goods"){
            $goodsId = request('goodsId', 0);
            if(empty($goodsId)){
                throw new ParamInvalidException('参数不合法');
            }
            $goodsInfo = Goods::findOrFail($goodsId);
            if(empty($goodsInfo)){
                throw new DataNotFoundException('商品信息不存在');
            }
            $merchantInfo = Merchant::where('id',$goodsInfo->merchant_id)->first();
            if(empty($merchantInfo)){
                throw new DataNotFoundException('商户信息不存在');
            }

            $miniprogramShareInfo['name'] = $goodsInfo->name;
            $miniprogramShareInfo['desc'] = $goodsInfo->desc;
            $miniprogramShareInfo['logo'] = $merchantInfo->logo;
            $miniprogramShareInfo['desc_pic'] = $goodsInfo->thumb_url;

            $miniprogramShareInfo['path'] = '/pages/product/info?scene=1001&id='.$goodsId;//小程序页面路径
        } else if($type == "dishes_goods"){
            $goodsId = request('goodsId', 0);
            if(empty($goodsId)){
                throw new ParamInvalidException('参数不合法');
            }
            $goodsInfo = DishesGoods::findOrFail($goodsId);
            if(empty($goodsInfo)){
                throw new DataNotFoundException('商品信息不存在');
            }
            $merchantInfo = Merchant::where('id',$goodsInfo->merchant_id)->first();
            if(empty($merchantInfo)){
                throw new DataNotFoundException('商户信息不存在');
            }

            $miniprogramShareInfo['name'] = $goodsInfo->name;
            $miniprogramShareInfo['desc'] = $goodsInfo->intro;
            $miniprogramShareInfo['logo'] = $merchantInfo->logo;
            $miniprogramShareInfo['desc_pic'] = $goodsInfo->detail_image;

            $miniprogramShareInfo['path'] = '/pages/dishes/index?scene=1001&merchant_id='.$goodsInfo->merchant_id.'&good_id='.$goodsId;//小程序页面路径
        } else if ($type=='cs_merchant') {
            $merchantId = request('merchantId',0);
            if(empty($merchantId)){
                throw new ParamInvalidException('参数不合法');
            }
            $merchantInfo = CsMerchant::where('id',$merchantId)->first();
            if(empty($merchantInfo)){
                throw new DataNotFoundException('商户信息不存在');
            }
            $user = request()->get('current_user');
            if (empty($user)) throw new BaseResponseException('请先登录');

            $miniprogramShareInfo['name'] = $merchantInfo->signboard_name;
            $miniprogramShareInfo['desc'] = $merchantInfo->desc;
            $miniprogramShareInfo['logo'] = $merchantInfo->logo;

            $desc_pic = $merchantInfo->logo;

            $miniprogramShareInfo['desc_pic'] = $desc_pic;

            $miniprogramShareInfo['path'] = '/pages/supermarket/detail?scene=1001&merchant_id='.$merchantInfo->id.'&share_user_id='.$user->id.'&type=cs_share';//小程序页面路径
        } else if ($type=='cs_goods') {
            $goodsId = request('goodsId', 0);
            if(empty($goodsId)){
                throw new ParamInvalidException('参数不合法');
            }
            $goodsInfo = CsGood::findOrFail($goodsId);
            if(empty($goodsInfo)){
                throw new DataNotFoundException('商品信息不存在');
            }
            $merchantInfo = CsMerchant::where('id',$goodsInfo->cs_merchant_id)->first();
            if(empty($merchantInfo)){
                throw new DataNotFoundException('商户信息不存在');
            }
            $user = request()->get('current_user');
            if (empty($user)) throw new BaseResponseException('请先登录');

            $miniprogramShareInfo['name'] = $goodsInfo->goods_name;
            $miniprogramShareInfo['desc'] = $goodsInfo->summary;
            $miniprogramShareInfo['logo'] = $merchantInfo->logo;
            $miniprogramShareInfo['desc_pic'] = $goodsInfo->logo;

            $miniprogramShareInfo['path'] = '/pages/supermarket/detail?scene=1001&merchant_id='.$goodsInfo->cs_merchant_id.'&good_id='.$goodsId.'&share_user_id='.$user->id.'&type=cs_share';//小程序页面路径
        } else if ($type=='flash_sale') {

            $rs = MarketingApi::getMarketingInfo(1);
            $share_img_url = $rs['marketing']['share_img_url']??'';

            $miniprogramShareInfo['name'] = '爆款好物开抢啦';
            $miniprogramShareInfo['desc'] = '';
            //logo还需要从活动出获取
            $miniprogramShareInfo['logo'] = $share_img_url;
            $miniprogramShareInfo['desc_pic'] = $share_img_url;

            $miniprogramShareInfo['path'] = '/pages/flash/index?scene=1001';//小程序页面路径
        }else if($type == 'group_order') {//拼团订单
            $orderId = request('orderId');
            $orderInfo = Order::where('id',$orderId)->first();
            $pintuanOrderId = $orderInfo['pintuan_order_id'];
            $pinTuanOrderDetail = MarketingApi::getPinTuanOrderDetail($pintuanOrderId);
            $miniprogramShareInfo['logo'] = 'https://o2o.daqian520.com/static/img/poster-logo.png';
            $miniprogramShareInfo['desc_pic'] = $pinTuanOrderDetail['goods_thumb'];//商品图片
            $num = $pinTuanOrderDetail['pintuan_people_number'] - $pinTuanOrderDetail['joined_count'];
            $desc = mb_strimwidth("【仅剩{$num}人】我用{$pinTuanOrderDetail['discount_price']}元买了{$pinTuanOrderDetail['goods_name']}",0,40,'...','utf-8');
            $miniprogramShareInfo['name'] = $desc;//'大千生活平台';
            $miniprogramShareInfo['desc'] = $desc;//mb_strimwidth("【仅剩{$num}人】我用{$pinTuanOrderDetail['discount_price']}元买了{$pinTuanOrderDetail['goods_name']}",0,40,'...','utf-8');
            $miniprogramShareInfo['path'] = 'pages/group/order-detail?scene=1001&order_no='.$orderInfo['order_no'].'&share_user_id='.$orderInfo['user_id'];
        }else if($type == 'group_sale'){//拼团分享
            $rs = MarketingApi::getMarketingInfo(2);
            $share_img_url = $rs['marketing']['share_img_url']??'';

//            $miniprogramShareInfo['name'] = '团购有好货，爱拼才会赢！';
            $miniprogramShareInfo['name'] = $rs['marketing']['share_title'];
            $miniprogramShareInfo['desc'] = '';
            //logo还需要从活动出获取
            $miniprogramShareInfo['logo'] = $share_img_url;
            $miniprogramShareInfo['desc_pic'] = $share_img_url;

            $miniprogramShareInfo['path'] = '/pages/group/index?scene=1001';//小程序页面路径
        }

        $miniProgram = config('platform.miniprogram');
        $miniprogramShareInfo['web_page_url'] = "https://o2o.niucha.ren/app-download-h5";//兼容低版本网页地址
        $miniprogramShareInfo['miniprogram_type'] = 1;// 正式版:0，测试版:1，体验版:2
        $miniprogramShareInfo['gh_id'] = $miniProgram['gh_id'];// 小程序原始id

        return Result::success([
            'miniprogram' => $miniprogramShareInfo
        ]);

    }

}
