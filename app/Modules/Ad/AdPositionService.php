<?php

namespace App\Modules\Ad;

use Illuminate\Database\Eloquent\Model;

class AdPositionService extends Model
{
    public static function getList()
    {
        $data = AdPosition::paginate();
        $data->each(function($item){
            $item->ads_count = count(json_decode($item->image_sizes_json,true));
        });
        return $data;
    }

    public static function changeStatus($id, $status)
    {
        $adPosition = AdPosition::findOrFail($id);
        $adPosition->status = $status;
        $adPosition->save();
        return $adPosition;
    }

    /**
     * @param $id
     * @return AdPosition
     */
    public static function getById($id)
    {
        $adPosition = AdPosition::findOrFail($id);
        return $adPosition;
    }

    /**
     * 根据广告位code获取广告位
     * @param $positionCode
     * @return AdPosition
     */
    public static function getByCode($positionCode)
    {
        $adPosition = AdPosition::where('code', $positionCode)->first();
        return $adPosition;
    }
}
