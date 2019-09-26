<?php

namespace App\Http\Modules;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * App\Http\Modules\Image
 *
 * @property int $id
 * @property string $directory 目录名称
 * @property string $url 图片url
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|Image newModelQuery()
 * @method static Builder|Image newQuery()
 * @method static Builder|Image query()
 * @method static Builder|Image whereCreatedAt($value)
 * @method static Builder|Image whereDirectory($value)
 * @method static Builder|Image whereId($value)
 * @method static Builder|Image whereUpdatedAt($value)
 * @method static Builder|Image whereUrl($value)
 * @mixin Eloquent
 * @property string $qiniu_url 七牛url
 * @property int $type 1-图片 2-视频 3-音频
 * @method static Builder|Image whereQiniuUrl($value)
 * @method static Builder|Image whereType($value)
 */
class Image extends BaseModel
{
    //
}
