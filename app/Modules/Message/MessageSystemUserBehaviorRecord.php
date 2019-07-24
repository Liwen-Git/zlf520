<?php

namespace App\Modules\Message;

use Illuminate\Database\Eloquent\Model;

/**
 * 系统信息用户行为记录表
 * Class MessageSystemUserBehaviorRecord
 * @package App\Modules\Message
 * @property int user_id
 * @property integer user_type
 * @property string is_view
 * @property string is_read
 */
class MessageSystemUserBehaviorRecord extends Model
{
    protected $fillable = ['user_id', 'user_type'];
    protected $table = 'message_system_user_behavior_record';
    const IS_READ_NORMAL = 1;
    const IS_READ_READED = 2;
    const IS_VIEW_NORMAL = 1;
    const IS_VIEW_VIEWED = 2;

    const TYPE_USER = 1;
    const TYPE_MERCHANT = 2;
    const TYPE_BIZER = 3;
    const TYPE_OPER = 4;
}
