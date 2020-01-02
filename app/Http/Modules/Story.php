<?php


namespace App\Http\Modules;


use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * App\Http\Modules\Story
 *
 * @property int $id
 * @property int $wx_user_id 微信用户id
 * @property string $content 日记内容
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|Story newModelQuery()
 * @method static Builder|Story newQuery()
 * @method static Builder|Story query()
 * @method static Builder|Story whereContent($value)
 * @method static Builder|Story whereCreatedAt($value)
 * @method static Builder|Story whereId($value)
 * @method static Builder|Story whereUpdatedAt($value)
 * @method static Builder|Story whereWxUserId($value)
 * @mixin Eloquent
 * @property string $date 日期
 * @method static Builder|Story whereDate($value)
 */
class Story extends BaseModel
{

}