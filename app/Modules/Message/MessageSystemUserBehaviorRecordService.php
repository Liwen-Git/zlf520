<?php

namespace App\Modules\Message;

use Illuminate\Database\Eloquent\Model;

class MessageSystemUserBehaviorRecordService extends Model
{
    /**
     * @param $userId
     * @param int $userType
     * @return MessageSystemUserBehaviorRecord
     */
    public static function getRecordByUserId($userId, $userType = MessageSystemUserBehaviorRecord::TYPE_USER)
    {
        return MessageSystemUserBehaviorRecord::firstOrCreate(['user_id' => $userId, 'user_type' => $userType]);
    }

    public static function addRecords($userId, $type, $ids, $userType = MessageSystemUserBehaviorRecord::TYPE_USER)
    {
        $record = MessageSystemUserBehaviorRecordService::getRecordByUserId($userId, $userType);
        if(is_string($ids)){
            // 兼容小程序逻辑
            if(strstr($ids,',')){
                $ids = explode(',',$ids);
            }else{
                $arr = [];
                array_push($arr,$ids);
                $ids = $arr;
            }
        }
        $needSaveIds = [];
        if (!empty($record->$type)) {
            $needSaveIds = json_decode($record->$type,true);
        }
        foreach ($ids as $k => $v){
            if(!is_numeric($v) || MessageSystem::where('id',$v)->doesntExist()){
                // 非數字不保存,不存在的ID不保存
                continue;
            }
            if(!in_array($v,$needSaveIds)){
                // 保存没有存过的ID
                array_push($needSaveIds,$v);
            }
        }

        $record->user_type = $userType;
        $record->$type = json_encode($needSaveIds);
        $record->save();
    }
}
