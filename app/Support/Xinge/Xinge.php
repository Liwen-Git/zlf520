<?php

namespace App\Support\Xinge;

require 'XingeApp.php';

use App\Exceptions\BaseResponseException;
use MessageIOS;
use XingeApp;
use Message;
use Style;
use ClickAction;

class Xinge
{
    const USER_APP = 1;
    const MERCHANT_APP = 2;

    private $iosAppId;
    private $iosSecretKey;
    private $iosAccessId;

    private $androidAppId;
    private $androidSecretKey;
    private $androidAccessId;

    public function __construct($app = self::USER_APP)
    {
        if ($app == self::USER_APP) {
            $this->iosAppId = config('xinge.ios_app_id');
            $this->iosSecretKey = config('xinge.ios_secret_key');
            $this->iosAccessId = config('xinge.ios_access_id');

            $this->androidAppId = config('xinge.android_app_id');
            $this->androidSecretKey = config('xinge.android_secret_key');
            $this->androidAccessId = config('xinge.android_access_id');
        } elseif ($app == self::MERCHANT_APP) {
            $this->iosAppId = config('xinge.merchant_app.ios_app_id');
            $this->iosSecretKey = config('xinge.merchant_app.ios_secret_key');
            $this->iosAccessId = config('xinge.merchant_app.ios_access_id');

            $this->androidAppId = config('xinge.merchant_app.android_app_id');
            $this->androidSecretKey = config('xinge.merchant_app.android_secret_key');
            $this->androidAccessId = config('xinge.merchant_app.android_access_id');
        } else {
            throw new BaseResponseException('不存在的app端');
        }
    }

    /**
     * ios单个设备或多个设备 下发通知消息
     * @param $token
     * @param $title
     * @param $content
     * @param $subtitle
     * @param string $environment
     * @param array $custom
     * @param string $messageType
     * @return array|mixed
     */
    public function iosPushByToken($token, $title, $content, $subtitle = '', $custom = [], $environment = XingeApp::IOSENV_DEV, $messageType = MessageIOS::TYPE_APNS_NOTIFICATION) {
        $push = new XingeApp($this->iosAppId, $this->iosSecretKey, $this->iosAccessId);
        $mess = new MessageIOS();
        $mess->setType($messageType);
        $mess->setTitle($title);
        $mess->setContent($content);
        $mess->setAlert(['subtitle' => $subtitle]);
        $mess->setCustom($custom);
        $mess->setMutable(1);
        if (is_array($token) && count($token) > 0) {
            $ret = $push->PushTokenList($token, $mess, $environment);
        } elseif (is_string($token)) {
            $ret = $push->PushSingleDevice($token, $mess, $environment);
        } else {
            throw new BaseResponseException('ios设备token无效');
        }
        return $ret;
    }

    /**
     * android单个设备或多个设备 下发通知消息
     * @param $token
     * @param $title
     * @param $content
     * @param array $custom
     * @param string $messageType
     * @return array|mixed
     */
    public function androidPushByToken($token, $title, $content, $custom = [], $messageType = Message::TYPE_NOTIFICATION)
    {
        $push = new XingeApp($this->androidAppId, $this->androidSecretKey);
        $mess = new Message();
        $mess->setTitle($title);
        $mess->setContent($content);
        $mess->setType($messageType);
        $mess->setStyle(new Style(0, 1, 1, 1, 0));
        $action = new ClickAction();
        $action->setActionType(ClickAction::TYPE_ACTIVITY);
        $mess->setAction($action);
        $mess->setCustom($custom);
        if (is_array($token) && count($token) > 0) {
            $ret = $push->PushTokenList($token, $mess);
        } elseif (is_string($token)) {
            $ret = $push->PushSingleDevice($token, $mess);
        } else {
            throw new BaseResponseException('android设备token无效');
        }
        return $ret;
    }

    /**
     * ios 推送单个或多个account账户 下发通知
     * @param $account
     * @param $title
     * @param $content
     * @param $subtitle
     * @param string $environment
     * @param array $custom
     * @param string $messageType
     * @return array|mixed
     */
    public function iosPushByAccount($account, $title, $content, $subtitle = '', $custom = [], $environment = XingeApp::IOSENV_DEV, $messageType = MessageIOS::TYPE_APNS_NOTIFICATION)
    {
        $push = new XingeApp($this->iosAppId, $this->iosSecretKey, $this->iosAccessId);
        $mess = new MessageIOS();
        $mess->setType($messageType);
        $mess->setTitle($title);
        $mess->setContent($content);
        $mess->setAlert(['subtitle' => $subtitle]);
        $mess->setCustom($custom);
        $mess->setMutable(1);
        if (is_array($account) && count($account) > 0) {
            $ret = $push->PushAccountList($account, $mess, $environment);
        } elseif (is_string($account)) {
            $ret = $push->PushSingleAccount($account, $mess, $environment);
        } else {
            throw new BaseResponseException('ios账户无效');
        }
        return $ret;
    }

    /**
     * android 推送单个或多个account账户 下发通知
     * @param $account
     * @param $title
     * @param $content
     * @param array $custom
     * @param string $messageType
     * @return array|mixed
     */
    public function androidPushByAccount($account, $title, $content, $custom = [], $messageType = Message::TYPE_NOTIFICATION)
    {
        $push = new XingeApp($this->androidAppId, $this->androidSecretKey);
        $mess = new Message();
        $mess->setTitle($title);
        $mess->setContent($content);
        $mess->setType($messageType);
        $mess->setStyle(new Style(0, 1, 1, 1, 0));
        $action = new ClickAction();
        $action->setActionType(ClickAction::TYPE_ACTIVITY);
        $mess->setAction($action);
        $mess->setCustom($custom);
        if (is_array($account) && count($account) > 0) {
            $ret = $push->PushAccountList($account, $mess);
        } elseif (is_string($account)) {
            $ret = $push->PushSingleAccount($account, $mess);
        } else {
            throw new BaseResponseException('android账户无效');
        }
        return $ret;
    }

    /**
     * 推送所以ios设备
     * @param $title
     * @param $content
     * @param $subtitle
     * @param string $environment
     * @param array $custom
     * @param string $messageType
     * @return array|mixed
     */
    public function iosPushAll($title, $content, $subtitle = '', $custom = [], $environment = XingeApp::IOSENV_DEV, $messageType = MessageIOS::TYPE_APNS_NOTIFICATION)
    {
        $push = new XingeApp($this->iosAppId, $this->iosSecretKey, $this->iosAccessId);
        $mess = new MessageIOS();
        $mess->setType($messageType);
        $mess->setTitle($title);
        $mess->setContent($content);
        $mess->setAlert(['subtitle' => $subtitle]);
        $mess->setCustom($custom);
        $mess->setMutable(1);
        $ret = $push->PushAllDevices($mess, $environment);
        return $ret;
    }

    /**
     * 推送所有android设备
     * @param $title
     * @param $content
     * @param array $custom
     * @param string $messageType
     * @return array|mixed
     */
    public function androidPushAll($title, $content, $custom = [], $messageType = Message::TYPE_NOTIFICATION)
    {
        $push = new XingeApp($this->androidAppId, $this->androidSecretKey);
        $mess = new Message();
        $mess->setTitle($title);
        $mess->setContent($content);
        $mess->setType($messageType);
        $mess->setStyle(new Style(0, 1, 1, 1, 0));
        $action = new ClickAction();
        $action->setActionType(ClickAction::TYPE_ACTIVITY);
        $mess->setAction($action);
        $mess->setCustom($custom);
        $ret = $push->PushAllDevices($mess);
        return $ret;
    }
}