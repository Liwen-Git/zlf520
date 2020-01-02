<?php


namespace App\Http\Services;


use App\Http\Modules\Story;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

class StoryService extends BaseService
{
    public static function getList(array $param)
    {
        $wx_user_id = Arr::get($param, 'wx_user_id', '');
        $pageSize = Arr::get($param, 'pageSize', 10);
        $query = new Story();
        $query->when($wx_user_id, function (Builder $query) use ($wx_user_id) {
            $query->where('wx_user_id', $wx_user_id);
        });
        $data = $query->paginate($pageSize);

        return $data;
    }

    public static function add(array $data)
    {
        $story = new Story();
        $story->wx_user_id = $data['wx_user_id'];
        $story->content = $data['content'];
        $story->save();
        return $story;
    }
}