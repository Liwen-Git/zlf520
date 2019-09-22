<?php


namespace App\Http\Modules;


use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * App\Http\Modules\User
 *
 * @property int $id
 * @property string $name 用户名
 * @property string $password 密码
 * @property string $salt 盐值
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|User newModelQuery()
 * @method static Builder|User newQuery()
 * @method static Builder|User query()
 * @method static Builder|User whereCreatedAt($value)
 * @method static Builder|User whereId($value)
 * @method static Builder|User whereName($value)
 * @method static Builder|User wherePassword($value)
 * @method static Builder|User whereSalt($value)
 * @method static Builder|User whereUpdatedAt($value)
 * @mixin Eloquent
 */
class User extends BaseModel
{
    use GenPassword;

    /**
     * @var array
     * 批量赋值
     */
    protected $fillable = ['name'];

    /**
     * @var array
     * 隐藏字段
     */
    protected $hidden = ['password', 'salt'];
}