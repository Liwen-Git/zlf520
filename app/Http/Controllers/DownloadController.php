<?php

namespace App\Http\Controllers;


use App\Exceptions\BaseResponseException;
use App\Exceptions\DataNotFoundException;
use App\Exceptions\ParamInvalidException;
use App\Modules\Cs\CsMerchant;
use App\Modules\Cs\CsMerchantService;
use App\Modules\Invite\InviteChannel;
use App\Modules\Invite\InviteChannelService;
use App\Modules\Merchant\Merchant;
use App\Modules\Merchant\MerchantService;
use App\Modules\Oper\Oper;
use App\Modules\Oper\OperService;
use App\Modules\Wechat\MiniprogramSceneService;
use App\Modules\Wechat\WechatService;
use App\Support\Cosv5;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DownloadController extends Controller
{

    public function download()
    {
        $code = request('code', 'normal');
        switch ($code){
            case 'normal': // 普通下载, 通过文件url或path下载
                return $this->normalDownload();
            case 'merchant_pay_app_code': // 下载商户支付二维码或小程序码
                return $this->downloadMerchantPayAppCode();
            case 'merchant_invite_channel_qrcode': // 下载商户邀请渠道二维码
                return $this->downloadMerchantInviteChannelQrcode();
            case 'cs_merchant_invite_channel_qrcode': // 下载超市商户邀请渠道二维码
                return $this->downloadCsMerchantInviteChannelQrcode();
            case 'oper_invite_channel_qrcode': // 下载运营中心邀请渠道二维码
                return $this->downloadOperInviteChannelQrcode();
            case 'doc':
                return $this->downloadDoc();
            default:
                abort(404);
        }
    }

    private function responseDownload($filePath,$fileName='')
    {
        $fileType = pathinfo($filePath, PATHINFO_EXTENSION);
        if(!empty($fileName)){
            header('Content-Disposition:attachment;filename=' .$fileName . ".{$fileType}" );
        }
        readfile($filePath);
    }

    /**
     * 普通下载, 通过文件url或path下载
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Symfony\Component\HttpFoundation\StreamedResponse
     */
    private function normalDownload()
    {
        $path = request('path') ?? request('url');
        $as = request('as');
        if(empty($path)){
            throw new ParamInvalidException();
        }
        if(empty($as)){
            $as = basename($path);
        }
        if(Str::startsWith($path, 'storage://')){
            $path = Str::replaceFirst('storage://', '', $path);
            if(!Storage::exists($path)){
                throw new BaseResponseException('要下载的文件不存在');
            }
            return Storage::download($path, $as);
        }else if(Str::startsWith($path, 'http://') || Str::startsWith($path, 'https://')){
            $c = new Client();
            $tempFilename = Str::random();
            $data = $c->get($path)->getBody()->getContents();
            $dir = storage_path('app/download/temp/' . date('Y-m-d') );
            if(!is_dir($dir)){
                mkdir($dir, 0755, true);
            }
            file_put_contents($dir . '/' . $tempFilename, $data);

            return response()->download($dir . '/' . $tempFilename, $as);
        }
        if(!Storage::exists($path)){
            // 不是storage存储返回的路径时, 尝试使用绝对路径获取
            if(file_exists($path)){
                return response()->download($path, $as);
            }
            throw new BaseResponseException('要下载的文件不存在');
        }
        return Storage::download($path, $as);
    }

    /**
     * 商户支付二维码或小程序码下载
     */
    private function downloadMerchantPayAppCode()
    {
        $this->validate(request(), [
            'merchantId' => 'required|integer|min:1'
        ]);
        $type = request('type', 1);
        $merchantId = request('merchantId');

        $width = $type == 3 ? 1280 : ($type == 2 ? 430 : 258);

        $scene = MiniprogramSceneService::getPayAppCodeByMerchantId($merchantId);
        $merchant = Merchant::findOrFail($merchantId,['oper_id']);
        $oper = OperService::getById($merchant['oper_id']);
        if ( $oper->pay_to_platform != Oper::PAY_TO_OPER ) {

            $signboardName = MerchantService::getSignboardNameById($merchantId);
            $url = MiniprogramSceneService::genSceneQrCode($scene, $width,false,$signboardName);

        } else {
            $signboardName = MerchantService::getSignboardNameById($merchantId);
            $url = MiniprogramSceneService::getMiniprogramAppCode($scene, $width, false, $signboardName);

        }

        $fileName = '支付二维码_' . ['', '小', '中', '大'][$type];
        $this->responseDownload($url,$fileName);

    }

    /**
     * 下载商户邀请渠道二维码或小程序码
     */
    private function downloadMerchantInviteChannelQrcode()
    {
        $this->validate(request(), [
            'merchantId' => 'required|integer|min:1'
        ]);
        $type = request('type', 1);
        $merchantId = request('merchantId');

        $width = $type == 3 ? 1280 : ($type == 2 ? 430 : 258);

        $merchant = Merchant::findOrFail($merchantId,['oper_id']);
        $scene = MiniprogramSceneService::getMerchantInviteChannelScene($merchantId, $merchant->oper_id);
        $oper = OperService::getById($merchant['oper_id']);

        if ( $oper->pay_to_platform != Oper::PAY_TO_OPER ) {

            $signboardName = MerchantService::getSignboardNameById($merchantId);
            $url = MiniprogramSceneService::genSceneQrCode($scene, $width,false,$signboardName);

        } else {
            $signboardName = MerchantService::getSignboardNameById($merchantId);
            $url = MiniprogramSceneService::getMiniprogramAppCode($scene, $width, false, $signboardName);
        }

        $fileName = '分享用户二维码_' . ['', '小', '中', '大'][$type];
        $this->responseDownload($url,$fileName);
    }

    /**
     * 下载超市商户邀请二维码
     */
    private function downloadCsMerchantInviteChannelQrcode()
    {
        $this->validate(request(), [
            'merchantId' => 'required|integer|min:1'
        ]);
        $type = request('type', 1);
        $csMerchantId = request('merchantId');

        $width = $type == 3 ? 1280 : ($type == 2 ? 430 : 258);

        $csMerchant = CsMerchant::findOrFail($csMerchantId,['oper_id']);
        $scene = MiniprogramSceneService::getCsMerchantInviteChannelScene($csMerchantId, $csMerchant->oper_id);

        $signboardName = CsMerchantService::getSignboardNameById($csMerchantId);
        $url = MiniprogramSceneService::genSceneQrCode($scene, $width,false,$signboardName);

        $fileName = '分享用户二维码_' . ['', '小', '中', '大'][$type];
        $this->responseDownload($url,$fileName);
    }

    /**
     * 下载运营汇总新邀请渠道二维码
     */
    private function downloadOperInviteChannelQrcode()
    {
        $this->validate(request(), [
            'inviteChannelId' => 'required|integer|min:1',
            'operId' => 'required|integer|min:1',
        ]);
        $type = request('type', 1);
        $inviteChannelId = request('inviteChannelId');
        $operId = request('operId');

        $width = $type == 3 ? 1280 : ($type == 2 ? 430 : 258);

        $inviteChannel = InviteChannelService::getById($inviteChannelId);
        if(!$inviteChannel
            || $inviteChannel->origin_id != $operId
            ||  $inviteChannel->origin_type != InviteChannel::ORIGIN_TYPE_OPER) {
            throw new DataNotFoundException('邀请渠道信息不存在');
        }
        $scene = MiniprogramSceneService::getByInviteChannel($inviteChannel);
        $oper = OperService::getById($operId);

        if ( $oper->pay_to_platform != Oper::PAY_TO_OPER ) {
            $url = MiniprogramSceneService::genSceneQrCode($scene, $width,false, $inviteChannel->name);
        } else {
            $url = MiniprogramSceneService::getMiniprogramAppCode($scene, $width, false, $inviteChannel->name);
        }

        $fileName = '分享用户二维码_' . ['', '小', '中', '大'][$type];
        $this->responseDownload($url,$fileName);
    }

    /**
     * doc 文件下载
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    private function downloadDoc()
    {
        $path = request('path') ?? request('url');
        $as = request('as');
        if(empty($path)){
            throw new ParamInvalidException();
        }
        if(empty($as)){
            $as = basename($path);
        }

        if (!file_exists($path)) {
            throw new BaseResponseException('要下载的文件不存在');
        }
        $response = response(file_get_contents($path));
        $response->headers->set('Content-Disposition', 'attachment; filename="'. $as .'"');
        return $response;
    }

}
