<?php

namespace App\Http\Controllers\Admin;

use App\Modules\Ad\AdPositionService;
use App\Result;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AdPositionController extends Controller
{
    public function getList()
    {
        $data = AdPositionService::getList();
        return Result::success([
            'list' => $data->items(),
            'total' => $data->total(),
        ]);
    }

    public function changeStatus()
    {
        $this->validate(request(), [
            'id' => 'required|integer|min:1',
            'status' => 'required|integer',
        ]);
        $data = AdPositionService::changeStatus(request('id'), request('status'));
        return Result::success($data);
    }
}
