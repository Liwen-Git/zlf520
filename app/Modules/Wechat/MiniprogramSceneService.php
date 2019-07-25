<?php

namespace App\Modules\Wechat;


use App\BaseService;
use App\Exceptions\BaseResponseException;
use App\Modules\Invite\InviteChannel;
use App\Modules\Invite\InviteChannelService;
use App\Modules\Merchant\MerchantService;
use App\Modules\Oper\OperService;
use App\Modules\Oper\Oper;
use App\Modules\Order\Order;
use App\Modules\Poster\PosterService;
use App\Support\Cosv5;
use App\Support\ImageTool;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class MiniprogramSceneService extends BaseService
{

    /**
     * 根据ID获取小程序场景信息
     * @param int $sceneId
     * @return MiniprogramScene
     */
    public static function getById(int $sceneId) : MiniprogramScene
    {
        return MiniprogramScene::find($sceneId);
    }

    /**
     * 获取海报场景
     * @param   int $inviteChannelId
     * @param   int $type
     * @param   int $merchantId
     * @param   array $payload
     * @return MiniprogramScene
     */
    public static function getPosterScene($inviteChannelId,$type,$merchantId,$payload)
    {
        if($type == MiniprogramScene::TYPE_GROUP_BOOK_TEAM){
            $orderId = $payload['order_id'];
            $scene = MiniprogramScene::where('type',$type)
                ->where('order_id',$orderId)
                ->first();
        }else{
            $scene = MiniprogramScene::where('invite_channel_id',$inviteChannelId)
                ->where('type',$type)
                ->where('merchant_id',$merchantId)
                ->where('good_id',isset($payload['good_id'])?$payload['good_id']:0)
                ->first();
        }
        if(!$scene){
            $scene = self::createPosterScene($inviteChannelId,$type,$merchantId,$payload);
        }
        return $scene;
    }

    /**
     * 新建海报场景
     * @param   int $inviteChannelId
     * @param   int $type
     * @param   int $merchantId
     * @param   array $payload
     * @return MiniprogramScene
     */
    public static function createPosterScene($inviteChannelId,$type,$merchantId,$payload)
    {
        $scene = new MiniprogramScene();
        $scene->oper_id = isset($payload['oper_id']) ? $payload['oper_id'] : 0;
        $scene->type = $type;
        $scene->merchant_id = $merchantId;
        $scene->invite_channel_id = $inviteChannelId;
        $scene->payload = json_encode($payload);
        $scene->good_id = isset($payload['good_id']) ? $payload['good_id'] : 0;
        $scene->order_id = isset($payload['order_id']) ? $payload['order_id'] : 0;
        $scene->page = MiniprogramScene::POSTER_PAGES[$type];
        $scene->save();
        return $scene;
    }

    /**
     * 根据邀请渠道获取【推广小程序】小程序场景信息
     * @param InviteChannel $inviteChannel
     * @return MiniprogramScene
     */
    public static function getByInviteChannel(InviteChannel $inviteChannel): MiniprogramScene
    {
        return self::getByInviteChannelId($inviteChannel->id, $inviteChannel->oper_id);
    }

    /**
     * 根据渠道ID与运营中心ID获取小程序场景信息
     * @param $inviteChannelId
     * @param $operId
     * @return MiniprogramScene
     */
    public static function getByInviteChannelId($inviteChannelId, $operId): MiniprogramScene
    {
        // 判断是否切换到平台
        if($operId!=0){
            $oper = OperService::getById($operId);
            if(!is_null($oper) && $oper->pay_to_platform!=Oper::PAY_TO_OPER){
                $operId=0;
            }
        }

        $miniprogramScene = MiniprogramScene::where('invite_channel_id', $inviteChannelId)
            ->where('oper_id', $operId)
            ->where('type', MiniprogramScene::TYPE_INVITE_CHANNEL)
            ->orderBy('id', 'desc')
            ->first();
        if (empty($miniprogramScene)) {
            $inviteChannel = InviteChannelService::getById($inviteChannelId);
            $miniprogramScene = self::createInviteScene($inviteChannel);
        }
        return $miniprogramScene;
    }

    /**
     * 获取商户邀请渠道的小程序场景
     * @param $merchantId
     * @param $operId
     * @return MiniprogramScene
     */
    public static function getMerchantInviteChannelScene($merchantId, $operId) : MiniprogramScene
    {
        $inviteChannel = InviteChannelService::getByOriginInfo($merchantId, InviteChannel::ORIGIN_TYPE_MERCHANT, $operId);
        $scene = self::getByInviteChannel($inviteChannel);
        return $scene;
    }

    /**
     * @param $cs_merchant_id
     * @param $oper_id
     * @return MiniprogramScene
     */
    public static function getCsMerchantInviteChannelScene(int $cs_merchant_id, int $oper_id) : MiniprogramScene
    {
        $inviteChannel = InviteChannelService::getByOriginInfo($cs_merchant_id, InviteChannel::ORIGIN_TYPE_CS_MERCHANT, $oper_id);
        $scene = self::getByInviteChannel($inviteChannel);
        return $scene;

    }

    /**
     * 获取小程序码的url或图片对象
     * @param MiniprogramScene $scene
     * @param int $width
     * @param bool $getImage
     * @param string $name
     * @return string|\Intervention\Image\Image
     */
    public static function getMiniprogramAppCode(MiniprogramScene $scene, $width=375, $getImage=false, $name = '')
    {
        // 判断是否切换到平台
        $operId = $scene->oper_id;
        if($operId != 0){
            $oper = OperService::getById($scene->oper_id);
            if(!is_null($oper) && $oper->pay_to_platform!=Oper::PAY_TO_OPER){
                $operId = 0;
            }
        }
        if($getImage){
            $image = WechatService::genMiniprogramAppCode($operId, $scene->id, $scene->page, $width, true, $name);
            return $image;
        }

        if(!empty($scene->qrcode_url)){
            return $scene->qrcode_url;
        }else {
            $url = WechatService::genMiniprogramAppCode($scene->oper_id, $scene->id, $scene->page, $width, false, $name);
            $scene->qrcode_url = $url;
            $scene->save();
            return $url;
        }
    }

    public static function createPayBridgeScene(Order $order)
    {
        // todo
    }

    /**
     * 从邀请渠道创建小程序场景
     * @param InviteChannel $inviteChannel
     * @return MiniprogramScene
     */
    public static function createInviteScene(InviteChannel $inviteChannel)
    {
        $miniprogramScene = new MiniprogramScene();
        // 判断是否切换到平台
        if($inviteChannel->oper_id!=0){
            $oper = OperService::getById($inviteChannel->oper_id);
            if(!is_null($oper) && $oper->pay_to_platform!=Oper::PAY_TO_OPER){
                $inviteChannel->oper_id=0;
            }
        }
        $miniprogramScene->oper_id = $inviteChannel->oper_id;
        $miniprogramScene->invite_channel_id = $inviteChannel->id;
        $miniprogramScene->page = MiniprogramScene::PAGE_INVITE_REGISTER;
        $miniprogramScene->type = MiniprogramScene::TYPE_INVITE_CHANNEL;
        $miniprogramScene->payload = json_encode([
            'origin_id' => $inviteChannel->oper_id,
            'origin_type' => InviteChannel::ORIGIN_TYPE_OPER,
        ]);
        $miniprogramScene->save();

        return $miniprogramScene;
    }

    /**
     * 创建扫码二维码
     * @param int $merchantId
     * @return MiniprogramScene
     */
    public static function createScanPayScene(int $merchantId)
    {
        $merchant = MerchantService::getById($merchantId, 'oper_id');
        if(empty($merchant) || empty($operId = $merchant->oper_id)){
            throw new BaseResponseException('商户信息不存在或商户尚未审核');
        }
        if($operId!=0){
            $oper = OperService::getById($operId);
            if(!is_null($oper) && $oper->pay_to_platform!=Oper::PAY_TO_OPER){
                // 判断是否切换到平台
                $operId = 0;
            }
        }
        $scene = new MiniprogramScene();
        $scene->oper_id = $operId;
        $scene->merchant_id = $merchantId;
        $scene->type = MiniprogramScene::TYPE_PAY_SCAN;
        $scene->page = MiniprogramScene::PAGE_PAY_SCAN;
        $scene->payload = json_encode([
            'merchant_id' => $merchantId,
        ]);
        $scene->save();

        return $scene;
    }

    /**
     * 创建扫码支付（带价格）的场景
     * @param $merchantId
     * @param $price
     * @param $time
     * @return MiniprogramScene
     */
    public static function createScanPayWithPriceScene($merchantId, $price, $time)
    {
        $merchant = MerchantService::getById($merchantId, 'oper_id');
        if(empty($merchant) || empty($operId = $merchant->oper_id)){
            throw new BaseResponseException('商户信息不存在或商户尚未审核');
        }
        if($operId!=0){
            $oper = OperService::getById($operId);
            if(!is_null($oper) && $oper->pay_to_platform!=Oper::PAY_TO_OPER){
                // 判断是否切换到平台
                $operId = 0;
            }
        }
        $scene = new MiniprogramScene();
        $scene->oper_id = $operId;
        $scene->merchant_id = $merchantId;
        $scene->type = MiniprogramScene::TYPE_PAY_SCAN_WITH_PRICE;
        $scene->page = MiniprogramScene::PAGE_PAY_SCAN_WITH_PRICE;
        $scene->payload = json_encode([
            'merchant_id' => $merchantId,
            'price' => $price,
            'time' => $time,
        ]);
        $scene->save();

        return $scene;
    }


    /**
     * 获取商户支付小程序码
     * @param $merchantId
     * @return MiniprogramScene
     */
    public static function getPayAppCodeByMerchantId($merchantId)
    {
        // 判断是否切换到平台
        $merchant = MerchantService::getById($merchantId);
        $query = MiniprogramScene::where('type', MiniprogramScene::TYPE_PAY_SCAN)
            ->where('merchant_id', $merchantId);
        if($merchant->oper_id != 0){
            $oper = OperService::getById($merchant->oper_id);
            if($oper->pay_to_platform!=Oper::PAY_TO_OPER){
                $query->where('oper_id',0);
            }
        }
        $scene = $query->first();
        if(empty($scene)){
            $scene = self::createScanPayScene($merchantId);
        }
        return $scene;
    }

    /**
     * 编辑场景
     * @param $sceneId
     * @param $operId
     * @param $merchantId
     * @param $inviteChannelId
     * @param $page
     * @param int $type
     * @param $payload
     * @param string $qrcodeUrl
     * @return MiniprogramScene
     */
    /*public static function edit($sceneId, $operId, $merchantId, $inviteChannelId, $page, $type = 1, $payload, $qrcodeUrl = ''): MiniprogramScene
    {
        $miniprogramScene = MiniprogramScene::find($sceneId);
        if (empty($miniprogramScene)) {
            throw new DataNotFoundException('小程序场景不存在');
        }
        $miniprogramScene->oper_id = $operId;
        $miniprogramScene->merchant_id = $merchantId;
        $miniprogramScene->invite_channel_id = $inviteChannelId;
        $miniprogramScene->page = $page;
        $miniprogramScene->type = $type;
        $miniprogramScene->payload = $payload;
        $miniprogramScene->qrcode_url = $qrcodeUrl;
        $miniprogramScene->save();

        return $miniprogramScene;
    }*/

    /**
     * @param MiniprogramScene $scene
     * @param int $size
     * @param bool $returnImage
     * @param string $name
     * @return string|\Intervention\Image\Image
     */
    public static function genSceneQrCode(MiniprogramScene $scene, int $size=375, $returnImage = false, string $name = '')
    {

        $qrcodeText = config('app.url') . "/scene?id={$scene->id}";

        $filename = "/qrcode/scene_qrcode/{$scene->id}_{$size}.png";

        $qrcodeContent = QrCode::format('png')->errorCorrection('H')->encoding('UTF-8')->margin(3)->size($size)->generate($qrcodeText);
        // 合成新图片
        $image = Image::make($qrcodeContent);
        $image = self::addSceneIdToQrCode($image, $scene->id);
        if ($name) {
            $image = self::addNameToQrCode($image,$name);
        }

        $url = Cosv5::storeImageToCos($image, $filename);
        $scene->qrcode_url = $url;
        $scene->save();
        return $returnImage ? $image : $scene->qrcode_url;
    }

    public static function genScenePosterQrCode(MiniprogramScene $scene, int $size=375, $returnImage=false)
    {
        $qrcodeText = config('app.url') . "/scene?id={$scene->id}";

        $qrcodeContent = QrCode::format('png')->errorCorrection('H')->encoding('UTF-8')->margin(3)->size($size)->generate($qrcodeText);

        $filename = "/qrcode/scene_qrcode/{$scene->id}_{$size}.png";
        $image = Image::make($qrcodeContent);

        $url = Cosv5::storeImageToCos($image, $filename);

        $scene->qrcode_url = $url;
        $scene->save();
        return $returnImage ? $image : $scene->qrcode_url;
    }

    /**
     * 给小程序码增加场景ID
     * @param \Intervention\Image\Image $qrCode
     * @param int|string $sceneId
     * @return \Intervention\Image\Image
     */
    public static function addSceneIdToQrCode($qrCode, $sceneId)
    {
        $name = 'ID：' . str_pad($sceneId, 8, "0", STR_PAD_LEFT);
        $fontSizeRatio = 0.045;
        $width = $qrCode->width();
        $height = $qrCode->height();

        // 计算文字大小
        $nameSize = intval($fontSizeRatio * $width);
        $nameX = intval($width * 0.9);
        $nameY = intval($height - 5);

        // 将文字添加到画布上
        $qrCode = ImageTool::text($qrCode,  $name, $nameSize, $nameX, $nameY, 'right', '#666666');
        return $qrCode;
    }

    /**
     * 给二维码添加名称
     * @param \Intervention\Image\Image $qrCode
     * @param string $name
     * @return \Intervention\Image\Image
     */
    public static function addNameToQrCode($qrCode, $name)
    {
        if (empty($name)) {
            return $qrCode;
        }
        $fontSizeRatio = 0.045;
        $width = $qrCode->width();
        $height = $qrCode->height();

        // 计算文字大小
        $nameSize = intval($fontSizeRatio * $width);
        $nameX = intval($width / 2);
        $nameY = intval($fontSizeRatio * $height)+5;

        // 将文字添加到画布上
        $qrCode = ImageTool::text($qrCode,  $name, $nameSize, $nameX, $nameY,'center','#666666');
        return $qrCode;
    }
}
