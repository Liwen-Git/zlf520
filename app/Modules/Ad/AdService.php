<?php

namespace App\Modules\Ad;


class AdService
{
    public static function getList()
    {

        $data  = Ad::paginate();
        return $data;
    }

    public static function getEnableListByPositionCode($positionCode)
    {
        $list = Ad::where('position_code', $positionCode)
            ->where('status', 1)
            ->get();
        return $list;
    }
}
