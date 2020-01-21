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
        $date = Arr::get($param, 'date', '');
        $pageSize = Arr::get($param, 'pageSize', 10);

        $query = Story::when($wx_user_id, function (Builder $query) use ($wx_user_id) {
            $query->where('wx_user_id', $wx_user_id);
        })->when($date, function (Builder $query) use ($date) {
            $query->where('date', $date);
        });
        $data = $query->orderBy('date', 'desc')->paginate($pageSize);

        return $data;
    }

    public static function add(array $data)
    {
        $story = new Story();
        $story->wx_user_id = $data['wx_user_id'];
        $story->content = $data['content'];
        $story->date = $data['date'];

        $images = $data['images'];
        if(is_array($images)){
            $images = implode(',', $images);
        }
        $story->images = $images;

        $story->save();
        return $story;
    }

    public static function edit(array $data)
    {
        $story = Story::find($data['id']);
        $story->content = $data['content'];
        $story->date = $data['date'];

        $images = $data['images'];
        if(is_array($images)){
            $images = implode(',', $images);
        }
        $story->images = $images;

        $story->save();

        return $story;
    }

    public static function getStoryById($id)
    {
        $story = Story::find($id);
        return $story;
    }

    public static function delStoryById($id)
    {
        Story::destroy($id);
    }
}