<?php


namespace App\Http\Services;

use App\Http\Modules\Image;
use function foo\func;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

class ImageService extends BaseService
{
    /**
     * 图片添加
     * @param $directory
     * @param $url
     * @return Image
     */
    public static function add($directory, $url)
    {
        $image = new Image();
        $image->directory = $directory;
        $image->url = $url;
        $image->save();

        return $image;
    }

    /**
     * 图片列表
     * @param array $param
     * @return LengthAwarePaginator
     */
    public static function list(array $param)
    {
        $directory = Arr::get($param, 'directory', '');
        $startTime = Arr::get($param, 'startTime', '');
        $endTime = Arr::get($param, 'endTime', '');
        $pageSize = Arr::get($param, 'pageSize', 15);

        $data = Image::when($directory, function (Builder $query) use ($directory) {
            $query->where('directory', $directory);
        })->when($startTime, function (Builder $query) use ($startTime) {
            $query->where('created_at', '>=', $startTime);
        })->when($endTime, function (Builder $query) use ($endTime) {
            $query->where('created_at', '<=', $endTime);
        })->orderBy('id', 'desc')
            ->paginate($pageSize);

        return $data;
    }
}