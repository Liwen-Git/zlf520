<?php

namespace App\Modules\Poster;

use App\BaseService;
use App\Exceptions\BaseResponseException;
use App\Exceptions\DataNotFoundException;
use App\Http\Controllers\Marketing\GoodsController;
use App\Modules\Cs\CsActivityService;
use App\Modules\Cs\CsGood;
use App\Modules\Cs\CsMerchantService;
use App\Modules\CsOrder\CsOrderGood;
use App\Modules\Dishes\DishesGoods;
use App\Modules\Goods\Goods;
use App\Modules\Goods\GoodsService;
use App\Modules\Invite\InviteChannel;
use App\Modules\Invite\InviteChannelService;
use App\Modules\Merchant\Merchant;
use App\Modules\Merchant\MerchantAccount;
use App\Modules\Merchant\MerchantService;
use App\Modules\Order\Order;
use App\Modules\Order\OrderService;
use App\Modules\Wechat\MiniprogramScene;
use App\Modules\Wechat\MiniprogramSceneService;
use App\Support\Cosv5;
use App\Support\Curl;
use App\Support\ImageTool;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use App\Support\MarketingApi;

class PosterService extends BaseService
{
    const GOOD_SCENE_TYPE = [
        MiniprogramScene::TYPE_GROUP_BUY,
        MiniprogramScene::TYPE_MERCHANT_GOOD,
        MiniprogramScene::TYPE_SUPERMARKET_GOOD,
        MiniprogramScene::TYPE_DISHES_GOOD,
    ];
    const SUPERMARKET_SCENE_TYPE = [
        MiniprogramScene::TYPE_SUPERMARKET_GOOD,
        MiniprogramScene::TYPE_SUPERMARKET_DETAIL
    ];


    public static function get($sceneType,$id,$userId,$originType=InviteChannel::ORIGIN_TYPE_USER,$orderId=0){
        // 获取渠道ID
        $channelId = self::getChannelIdByPoster($sceneType,$userId,$originType);
        $payload = self::getPayload($id,$sceneType,$userId);
        $scene = MiniprogramSceneService::getPosterScene($channelId,$sceneType,$payload['merchant_id'],$payload);
        /*$poster = self::getPosterBySceneId($scene->id);
        if(!$poster) $poster = self::createPoster($scene);*/
        // 先取消图片存储
        $poster = self::createPoster($scene,$orderId);
        return $poster;
        //通过场景值判断是否有对应的海报信息
    }

    public static function getChannelIdByPoster($sceneType,$userId,$originType)
    {
        // 如果为超市类场景，添加对应的用户场景值ID，用于分润系统
        return (in_array($sceneType,self::SUPERMARKET_SCENE_TYPE)) ? InviteChannelService::getByOriginInfo($userId,$originType)->id : 0;
    }

    public static function getPayload($id,$sceneType,$userId){
        // 为商品的场景类型
        $payload = [];
        $payload['merchant_id'] = $id;
        if(in_array($sceneType,self::GOOD_SCENE_TYPE)){
            //如果为商品分类
            switch ($sceneType){
                case MiniprogramScene::TYPE_SUPERMARKET_GOOD:
                    $goods = CsGood::where('id',$id)->firstOrFail();
                    $merchantId = $goods->cs_merchant_id;
                    break;
                case MiniprogramScene::TYPE_DISHES_GOOD:
                    $goods = DishesGoods::where('id',$id)->firstOrFail();
                    $merchantId = $goods->merchant_id;
                    break;
                default:
                    $goods = Goods::where('id',$id)->firstOrFail();
                    $merchantId = $goods->merchant_id;
            }
            $payload['merchant_id'] = $merchantId;
            $payload['good_id'] =  $id;
        }
        $payload['merchant_type'] = (in_array($sceneType,self::SUPERMARKET_SCENE_TYPE)) ?
            MerchantAccount::TYPE_CS :
            MerchantAccount::TYPE_NORMAL;
        if(in_array($sceneType, self::SUPERMARKET_SCENE_TYPE)){
            // 添加超市场景分享用户ID
            $payload['share_user_id'] = $userId;
        }
        if($sceneType == MiniprogramScene::TYPE_GROUP_BOOK_TEAM){
            $orderId = $id;
            $order = Order::where('id',$orderId)->first();
            if (!in_array($order->type, [Order::TYPE_SUPERMARKET, Order::TYPE_GROUP_BUY] )) {
                throw new BaseResponseException('非合法拼团订单');
            }
            if(!$order) throw new DataNotFoundException('订单不存在');
            $payload['order_id'] = $orderId;
            $payload['order_no'] = $order['order_no'];
            $payload['pintuan_order_id'] = $order['pintuan_order_id'];
            $payload['type'] = MiniprogramScene::TYPE_GROUP_BOOK_TEAM;
            $payload['share_user_id'] = $userId;
            $payload['merchant_id'] = $order->merchant_id;
            $type = [
                Order::TYPE_SUPERMARKET => MerchantAccount::TYPE_CS,
                Order::TYPE_GROUP_BUY => MerchantAccount::TYPE_NORMAL,
            ];
            $payload['merchant_type'] = $type[$order->type];
        }
        $merchant = ($payload['merchant_type']==MerchantAccount::TYPE_CS) ?
            CsMerchantService::getById($payload['merchant_id']) :
            MerchantService::getById($payload['merchant_id']);
        if(!$merchant) throw new DataNotFoundException('该商铺信息不存在');
        return $payload;
    }

    /**
     * 创建海报
     * @param MiniprogramScene $scene
     * @param $orderId
     * @return Poster
     */
    public static function createPoster(MiniprogramScene $scene,$orderId)
    {
        try {
            if (in_array($scene->type, self::GOOD_SCENE_TYPE)) {
                $good = self::getGoodByScene($scene);
                $isHotGood = isset($good['hot_status']) && $good['hot_status'] == CsGood::HOT_STATUS_ON && CsActivityService::isOpenHotSell();
                $path = ($isHotGood) ? self::createHotGoodPoster($scene, $good) : self::createGoodPoster($scene, $good);
            } else {
                // 判断是否为拼团场景
                $path = ($scene->type==MiniprogramScene::TYPE_GROUP_BOOK_TEAM) ?
                    self::createGroupBookTeamPoster($scene)
                    :
                    self::createMerchantPoster($scene);
            }
            $poster = new Poster();
            $poster->url = $path.'?'.time();
            $poster->scene_id = $scene->id;
//            $poster->save();
            return $poster;
        }catch (\Exception $e){
            throw $e;
            throw new BaseResponseException('商品或已下架,海报生成失败');
        }
    }

    /**
     * 通过场景值获取对应的商品
     * @param $scene
     * @return CsGood|Goods|\Illuminate\Database\Query\Builder
     */
    public static function getGoodByScene($scene)
    {
        switch ($scene->type){
            case MiniprogramScene::TYPE_SUPERMARKET_GOOD:
                $good = CsGood::where('id',$scene->good_id)
                    ->where('status',CsGood::STATUS_ON)
                    ->firstOrFail();
                $marketingGoodParam = [
                    'merchant_id' => $good->cs_merchant_id,
                    'merchant_type'=> GoodsController::MERCHANT_TYPE_SUPERMARKET,
                    'goods_type'    =>  GoodsController::GOODS_TYPE_SUPERMARKET,
                    'goods_id'  =>  $good->id
                ];
                break;
            case MiniprogramScene::TYPE_DISHES_GOOD:
                $good = DishesGoods::where('id',$scene->good_id)
                    ->where('status',DishesGoods::STATUS_ON)
                    ->select('*')
                    ->selectRaw('sale_price as price')
                    ->selectRaw('name as goods_name')
                    ->selectRaw('detail_image as logo')
                    ->firstOrFail();
                $marketingGoodParam = [
                    'merchant_id' => $good->merchant_id,
                    'merchant_type'=> GoodsController::MERCHANT_TYPE_NORMAL,
                    'goods_type' => GoodsController::GOODS_TYPE_DISHES,
                    'goods_id' => $good->id
                ];
                break;
            default:
                $good = Goods::where('id',$scene->good_id)
                    ->where('status',Goods::STATUS_ON)
                    ->select('*')
                    ->selectRaw('name as goods_name')
                    ->selectRaw('thumb_url as logo')
                    ->firstOrFail();
                $marketingGoodParam = [
                    'merchant_id' => $good->merchant_id,
                    'merchant_type'=> GoodsController::MERCHANT_TYPE_NORMAL,
                    'goods_type' => GoodsController::GOODS_TYPE_NORMAL,
                    'goods_id' => $good->id
                ];
                break;
        }
        $good->marketingGood = MarketingApi::getDuringPhaseGoodFromMarketing($marketingGoodParam);

        if(in_array($scene->type, [
            MiniprogramScene::TYPE_SUPERMARKET_GOOD,
            MiniprogramScene::TYPE_GROUP_BUY,
        ])) {
            $goodsType = [
                MiniprogramScene::TYPE_GROUP_BUY => 1,
                MiniprogramScene::TYPE_SUPERMARKET_GOOD => 3,
            ];
            $groupBooking = MarketingApi::getGroupBookingGoodsFromMarketingById($goodsType[$scene->type], $good->id);
            if($groupBooking) {
                GoodsService::insertGroupBookingData($good, $groupBooking);
            }
        }
        return $good;
    }


    /**
     * 创建商品详情海报
     * @param MiniprogramScene $scene
     * @param $good
     * @return string
     */
    public static function createGoodPoster(MiniprogramScene $scene,$good)
    {
        $path = env('POSTER_URL', '/test_poster/').$scene->merchant_id.'/'.$scene->id.'_'.$scene->good_id.'.png';
        $goodImg = $good['logo'];
        // 限时抢
        $marketPrice = ($good->marketingGood) ? floatval($good->marketingGood['price']) : floatval($good['market_price']);
        $price = ($good->marketingGood) ? floatval($good->marketingGood['discount_price']) : floatval($good['price']);
        // 拼团
        $marketPrice = ($good->group_booking) ? floatval($good->group_booking->price) : floatval($good['market_price']);
        $price = ($good->group_booking) ? floatval($good->group_booking->discount_price) : floatval($good['price']);

        $priceLength = strlen($price);
        $marketPriceLength = strlen($marketPrice);
        $width = 380;
        $height = 525;
        $img = Image::canvas($width,$height,'#FFFFFF');

        // 二维码
        $qrCode = MiniprogramSceneService::genScenePosterQrCode($scene,115,true);
        $qrCode = $qrCode->resize(160,160);
        $img->insert($qrCode, 'bottom-right', -10,0);
        // 插入产品图片
        $goodFile = Image::make($goodImg)->resize(380,380);
        $img->insert($goodFile, 'top-let', 0,0);
        $ellipse = Image::make(public_path('static/img/ellipse.png'))->resize(120+($priceLength*7)+($marketPriceLength*7),50);
        $img->insert($ellipse, 'top-let', 10,315);
        // 商铺邀请内容

        $img = ImageTool::text($img,self::handleText($good['goods_name'],10,18),'18',10,420,'bottom-left','#000000');
        $img = ImageTool::text($img,'￥'.$price,23,20,350,'top-left','#FFFFFF');
        $marketPriceX = 75+($priceLength*9);
        $deleteLineX1 = $marketPriceX;
        $deleteLineX2 = $deleteLineX1+25/**/+($marketPriceLength*8.5);
        $img = ImageTool::text($img,'￥'.$marketPrice,17,$marketPriceX,350,'top-left','#FFFFFF');
        $img->line($deleteLineX1,$height-182,$deleteLineX2,$height-182,function($draw){
            $draw->color('#ECEFF1');
        });

        $insertMarketingBanner = function($img, $endTime, $bannerPath) {
            $groupBookPng = Image::make($bannerPath)->resize(220,26);
            $img->insert($groupBookPng, 'top-let', 10,468);
            $finishedTime = strtotime($endTime) - time();
            $day = self::getDayToTime($finishedTime);
            $hour = self::getHourToTime($finishedTime);
            $minute = self::getMinToTime($finishedTime);
            $img = ImageTool::text($img,"距结束{$day}天{$hour}时{$minute}分",16,80,488,'bottom-left','#FFFFFF');
        };

        if($good->marketingGood){
            $insertMarketingBanner($img, $good->marketingGood['end_time'], public_path('static/img/timeLimit.png'));
        }

        if($good->group_booking){
            $insertMarketingBanner($img, $good->group_booking->end_time, public_path('static/img/groupBook.png'));
        }

        $resPath = Cosv5::storeImageToCos($img,$path);
        $img->destroy();
        return $resPath;
    }

    public static function getDayToTime($time)
    {
        return floor($time/(60*60*24));
    }

    public static function getHourToTime($time)
    {
        return floor($time/(60*60))%24;
    }

    public static function getMinToTime($time)
    {
        return floor($time/60)%60;
    }

    /**
     * 爆款海报
     * @param MiniprogramScene $scene
     * @param $good
     * @return string
     */
    public static function createHotGoodPoster(MiniprogramScene $scene,$good)
    {
        $path = env('POSTER_URL', '/test_poster/').$scene->merchant_id.'/'.$scene->id.'_'.$scene->good_id.'.png';
        $goodImg = $good['logo'];
        $marketPrice = floatval($good['market_price']);
        $price = floatval($good['price']);
        $priceLength = strlen($price);
        $marketPriceLength = strlen($marketPrice);
        $width = 380;
        $height = 525;
        $img = Image::canvas($width,$height,'#FFFFFF');
        // 二维码
        $qrCode = MiniprogramSceneService::genScenePosterQrCode($scene,115,true);
        $qrCode = $qrCode->resize(160,160);
        $img->insert($qrCode, 'bottom-right', -10,0);
        // 插入产品图片
        $orange = Image::make($goodImg)->resize(380,380);
        $img->insert($orange, 'top-let', 0,0);
        $ellipse = Image::make(public_path('static/img/ellipse.png'))->resize(120+($priceLength*7)+($marketPriceLength*7),50);
        $img->insert($ellipse, 'top-let', 10,315);

        $img = ImageTool::text($img,self::handleText($good['goods_name'],10,18),'18',10,420,'bottom-left','#000000');
        $img = ImageTool::text($img,'￥'.$price,23,20,350,'top-left','#FFFFFF');
        $marketPriceX = 75+($priceLength*9);
        $deleteLineX1 = $marketPriceX;
        $deleteLineX2 = $deleteLineX1+25/**/+($marketPriceLength*8.5);
        $img = ImageTool::text($img,'￥'.$marketPrice,17,$marketPriceX,350,'top-left','#FFFFFF');
        $img->line($deleteLineX1,$height-182,$deleteLineX2,$height-182,function($draw){
            $draw->color('#ECEFF1');
        });

        $hot = Image::make(public_path('static/img/hot.png'))->resize(220,26);
        $img->insert($hot, 'top-let', 10,468/*-$cutYHeight*/);
        $img = ImageTool::text($img,'今日 23:59:59 结束',16,85,488/*-$cutYHeight*/,'bottom-left','#FFFFFF');
        $resPath = Cosv5::storeImageToCos($img, $path);
        $img->destroy();
        return $resPath;
    }


    /**
     * 处理文本换行与截取
     * @param $str
     * @param int $changeLineNum
     * @param int $cutLength
     * @return string
     */
    public static function handleText($str ,$changeLineNum=8,$cutLength=22) {
        $returnStr = '';
        $i = 0;
        $n = 0;
        $realNum = 0;
        $str_length = strlen ( $str ); //字符串的字节数
        while ( ($n < $cutLength) and ($i <= $str_length) ) {
            $temp_str = substr ( $str, $i, 1 );
            $ascNum = Ord ( $temp_str ); //得到字符串中第$i位字符的ascii码
            if ($ascNum >= 224) {//如果ASCII位高与224，
                $returnStr .=  substr ( $str, $i, 3 ); //根据UTF-8编码规范，将3个连续的字符计为单个字符
                $i = $i + 3; //实际Byte计为3
                $n ++; //字串长度计1
                $realNum++;
            } elseif ($ascNum >= 192){ //如果ASCII位高与192，
                $returnStr .= substr ( $str, $i, 2 ); //根据UTF-8编码规范，将2个连续的字符计为单个字符
                $i = $i + 2; //实际Byte计为2
                $n ++; //字串长度计1
                $realNum++;
            } elseif ($ascNum >= 65 && $ascNum <= 90) {//如果是大写字母，
                $returnStr .= substr ( $str, $i, 1 );
                $i = $i + 1; //实际的Byte数仍计1个
                $n ++; //但考虑整体美观，大写字母计成一个高位字符
                $realNum++;
            }elseif ($ascNum >= 97 && $ascNum <= 122) {
                $returnStr .= substr ( $str, $i, 1 );
                $i = $i + 1; //实际的Byte数仍计1个
                $n ++; //但考虑整体美观，大写字母计成一个高位字符
                $realNum++;
            } else {//其他情况下，半角标点符号，
                $returnStr .= substr ( $str, $i, 1 );
                $i = $i + 1;
                $n = $n + 0.5;
                $realNum++;
            }
            // 处理截取
            if($realNum==$changeLineNum){
                $returnStr .= "\r\n";
            }
        }
        if($realNum>=$cutLength){
            $returnStr .= '...';
        }
        return $returnStr;
    }

    public static function createGroupBookTeamPoster(MiniprogramScene $scene)
    {
        $title = '大千生活';
        $qrCode = MiniprogramSceneService::genScenePosterQrCode($scene,115,true);
        $path = env('POSTER_URL', '/test_poster/').$scene->merchant_id.'/'.$scene->id.'.png';
        $width = 455;
        $height = 650;
        $img = Image::canvas($width,$height,'#FFFFFF');
        // logo
        $logo = Image::make(public_path('static/img/poster-logo.png'))->resize(45,45);
        $img->insert($logo, 'top-let', 125,25);
        // 标题
        $img = ImageTool::text($img, $title, 32, 185,55,'top-left', '#000000');
        $payload = json_decode($scene['payload'],true);

        if ($payload['merchant_type'] == MerchantAccount::TYPE_NORMAL) {
            $orderInfo = Order::where('id', $payload['order_id'])->first();
            $goodsPicUrl = $orderInfo->goods_thumb_url;
        } else {
            $orderGoodInfo = CsOrderGood::where('order_id', $payload['order_id'])->first();
            $csGoodInfo = CsGood::where('id', $orderGoodInfo->cs_goods_id)->first();
            $goodsPicUrl = $csGoodInfo->logo;
        }
        // 插入产品图片
        $goodImg = Image::make($goodsPicUrl)->resize(420,420);
        $img->insert($goodImg, 'top-let', 18,90);
        //获取营销端拼团订单：
        $pintuanOrderId = $payload['pintuan_order_id'];
        $pinTuanOrderDetail = MarketingApi::getPinTuanOrderDetail($pintuanOrderId);
        // 二维码
        $qrCode = $qrCode->resize(150,150);
        $img->insert($qrCode, 'bottom-right', -10,-10);
        //拼团商品金额：
        $price = $pinTuanOrderDetail['discount_price'];
        $priceLength = strlen($price);
        $ellipse = Image::make(public_path('static/img/ellipse2.png'))->resize(140+$priceLength,50);
        $img->insert($ellipse, 'top-let', 35,440);
        $img = ImageTool::text($img,'￥'.$price,30,50,475,'top-left','#FFFFFF');
        //商品名：
        $goodsNmae = mb_strimwidth($pinTuanOrderDetail['goods_name'],0,25,'...','utf-8');
        $img = ImageTool::text($img,$goodsNmae,23,20,560,'bottom-left','#141414');
        //拼团详情：
        $num = $pinTuanOrderDetail['pintuan_people_number'] - $pinTuanOrderDetail['joined_count'];
        $pad_length = strlen($num);
        if($pad_length <= 2){
            $pad_rate = 3;
        }else{
            $pad_rate = 2.7;
        }
        $blank = str_pad('',intval(strlen($num)*$pad_rate)," ");
        $msg = "我已参与，还剩 {$blank} 人拼团成功";
        $img = ImageTool::text($img,$msg,16,20,595,'bottom-left','#141414');
        $img = ImageTool::text($img,$num,18,140,597,'bottom-left','#FF3030');
        //拼团结束时间：
        $endTime = strtotime($pinTuanOrderDetail['created_at'])+24*60*60;//24小时有效
        $finishedTime = $endTime - time();
        if($finishedTime > 0){
            $hour = self::getHourToTime($finishedTime);
            $minute = self::getMinToTime($finishedTime);
            $timeStr = "{$hour}时{$minute}分后结束";
        }else{
            $timeStr = "已结束";
        }
        $img = ImageTool::text($img,$timeStr,16,20,625,'bottom-left','#FF3030');
        $resPath = Cosv5::storeImageToCos($img,$path);
        $img->destroy();
        return $resPath;
    }

    /**
     * 创建商铺详情海报
     * @param MiniprogramScene $scene
     * @return string
     */
    public static function createMerchantPoster(MiniprogramScene $scene)
    {
        $title = '大千生活';
        $merchant = self::getMerchantByScene($scene);
        $goods = self::getTopSellGoodsByScene($scene)->toArray();
        $qrCode = MiniprogramSceneService::genScenePosterQrCode($scene,115,true);
        $path = env('POSTER_URL', '/test_poster/').$scene->merchant_id.'/'.$scene->id.'.png';
        $width = 455;
        $height = 595;
        $img = Image::canvas($width,$height,'#FFFFFF');
        // logo
        $logo = Image::make(public_path('static/img/poster-logo.png'))->resize(45,45);
        $img->insert($logo, 'top-let', 125,25);
        // 标题
        $img = ImageTool::text($img, $title, 32, 185,55,'top-left', '#000000');

        if(isset($goods[0])){
            // 插入产品图片
            $good1 = Image::make($goods[0]['logo'])->resize(212,212);
            $img->insert($good1, 'top-let', 10,85);
            $goodTitle1 = self::handleText($goods[0]['goods_name'],10,16);
            $img = ImageTool::text($img, $goodTitle1, 18, 15,330,'top', '#000000');
            //插入价格
            $marketPrice = floatval($goods[0]['market_price']);
            $price = floatval($goods[0]['price']);
            $good1PriceLength = strlen($price);
            $marketPriceLength = strlen($marketPrice);
            $good1PriceX = 10;
            $img = ImageTool::text($img, '￥'.floatval($goods[0]['price']), 26, $good1PriceX,395,'top', '#FB8C00');
            $good1MarketPriceX = $good1PriceX+45+($good1PriceLength*10)+($marketPriceLength*5);
            $good1MarketLineX1 = $good1MarketPriceX;
            $good1MarketLineX2 = $good1MarketLineX1+25+($marketPriceLength*10);
            $img = ImageTool::text($img, "￥".floatval($goods[0]['market_price']), 18, $good1MarketPriceX,395,'top', '#666666');
            $img->line($good1MarketLineX1,388,$good1MarketLineX2,388,function($draw){
                $draw->color('#666666');
            });
        }
        if(isset($goods[1])){
            $good2 = Image::make($goods[1]['logo'])->resize(212,212);
            $img->insert($good2, 'top-right', 10,85);
            // 插入产品文案
            $goodTitle2 = self::handleText($goods[1]['goods_name'],10,16);
            $img = ImageTool::text($img, $goodTitle2, 18, 235,330,'top', '#000000');

            //插入价格
            $marketPrice = floatval($goods[1]['market_price']);
            $price = floatval($goods[1]['price']);
            $good2PriceLength = strlen($price);
            $marketPriceLength = strlen($marketPrice);
            $good2PriceX = 235;
            $img = ImageTool::text($img, '￥'.floatval($goods[1]['price']), 26, $good2PriceX,395,'top', '#FB8C00');
            $good2MarketPriceX = $good2PriceX+45+($good2PriceLength*10)+($marketPriceLength*5);
            $good2MarketLineX1 = $good2MarketPriceX;
            $good2MarketLineX2 = $good2MarketLineX1+25+($marketPriceLength*10);
            $img = ImageTool::text($img, "￥".floatval($goods[1]['market_price']), 18, $good2MarketPriceX,395,'top', '#666666');
            $img->line($good2MarketLineX1,388,$good2MarketLineX2,388,function($draw){
                $draw->color('#666666');
            });
        }



        //底部
        $merchantLogo = Image::make($merchant->logo)->resize(45,45);
        $logo = Image::make($merchantLogo)->resize(65,65);
        $foot = Image::canvas(455,175);
        // 二维码
        $qrCode = $qrCode->resize(200,200);
        $foot->insert($qrCode, 'top-right', -10,0);
        $foot->insert($logo,'top-left',10,35);
        // 商铺名称
        $merchantName = self::handleText($merchant->signboard_name,7,11);
        $foot = ImageTool::text($foot,$merchantName,18,100,60,'top','#000000');
        // 商铺邀请内容
        $foot = ImageTool::text($foot,"千千邀请您抢福利啦！\r\n实在好物，超值低价！",'18',10,135,'top','#666666');
        $img->insert($foot,'bottom-left',0,20);
        $img->line(10,$height-180,$width-10,$height-180,function($draw){
            $draw->color('#ECEFF1');
        });
        $resPath = Cosv5::storeImageToCos($img,$path);
        $img->destroy();
        return $resPath;
    }

    /**
     * 通过场景值获取销量最高商品
     * @param MiniprogramScene $scene
     * @param int $limit
     * @return CsGood[]|Goods[]|\Illuminate\Database\Eloquent\Collection
     */
    public static function getTopSellGoodsByScene(MiniprogramScene $scene,$limit=2)
    {
        $payload = json_decode($scene->payload,true);
        if($payload['merchant_type']==MerchantAccount::TYPE_NORMAL){
            $goodQuery = Goods::where('status',Goods::STATUS_ON)
                ->where('merchant_id',$scene->merchant_id)
                ->select('status','market_price','sell_number','merchant_id','price')
                ->selectRaw('name as goods_name')
                ->selectRaw('thumb_url as logo')
                ->selectRaw("'goods' as type");
            return  DishesGoods::where('status',DishesGoods::STATUS_ON)
                ->where('merchant_id',$scene->merchant_id)
                ->select('status','market_price','sell_number','merchant_id')
                ->selectRaw('sale_price as price')
                ->selectRaw('name as goods_name')
                ->selectRaw('detail_image as logo')
                ->selectRaw("'dishes' as type")
                ->union($goodQuery)
                ->orderBy('sell_number','desc')
                ->limit($limit)
                ->get();
        }
        return CsGood::where('cs_merchant_id',$scene->merchant_id)
            ->where('status',CsGood::STATUS_ON)
            ->where('saas_audit_status','!=',CsGood::SAAS_AUDIT_STATUS_FAIL)
            ->orderBy('sale_num','desc')
            ->limit($limit)
            ->get();
    }

    /**
     * 通过场景值获取商铺详情
     * @param MiniprogramScene $scene
     * @return \App\Modules\Cs\CsMerchant|Merchant
     */
    public static function getMerchantByScene(MiniprogramScene $scene)
    {
        $payload = json_decode($scene->payload,true);
        return ($payload['merchant_type']==MerchantAccount::TYPE_NORMAL) ? MerchantService::getById($scene->merchant_id):CsMerchantService::getById($scene->merchant_id);
    }

    /**
     * 获取海报通过场景ID
     * @param int $sceneId
     * @return Poster
     */
    public static function getPosterBySceneId($sceneId){
        return Poster::where('scene_id',$sceneId)->first();
    }

}
