<?php


namespace App\Http\Services;

use App\Http\Modules\Image;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class ImageService extends BaseService
{
    /**
     * 图片添加
     * @param array $data
     * @return Image
     */
    public static function add(array $data)
    {
        $image = new Image();
        $image->directory = $data['directory'];
        $image->url = $data['url'];
        $image->qiniu_url = $data['qiniu_url'];
        $image->type = $data['type'];
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
        $type = Arr::get($param, 'type', 1);
        $startTime = Arr::get($param, 'startTime', '');
        $endTime = Arr::get($param, 'endTime', '');
        $pageSize = Arr::get($param, 'pageSize', 15);

        $data = Image::where('type', $type)
        ->when($directory, function (Builder $query) use ($directory) {
            $query->where('directory', $directory);
        })->when($startTime, function (Builder $query) use ($startTime) {
            $query->where('created_at', '>=', $startTime);
        })->when($endTime, function (Builder $query) use ($endTime) {
            $query->where('created_at', '<=', $endTime);
        })->orderBy('id', 'desc')
            ->paginate($pageSize);

        return $data;
    }

    /**
     * @param $id
     * @return Image|Image[]|Collection|Model|null
     */
    public static function getById($id)
    {
        $detail = Image::find($id);
        return $detail;
    }

    /**
     * @param $id
     * @return int
     */
    public static function deleteById($id)
    {
        $res = Image::destroy($id);
        return $res;
    }
}