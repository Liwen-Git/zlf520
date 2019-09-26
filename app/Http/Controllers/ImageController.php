<?php


namespace App\Http\Controllers;

use App\Http\Services\ImageService;
use App\Result;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;

class ImageController extends Controller
{
    /**
     * @return ResponseFactory|Response
     */
    public function getList()
    {
        $param = [
            'directory' => request('directory'),
            'type' => request('type', 1),
            'startTime' => request('start_time') ? date('Y-m-d 00:00:00', strtotime(request('start_time'))) : '',
            'endTime' => request('end_time') ? date('Y-m-d 23:59:59', strtotime(request('end_time'))) : '',
            'pageSize' => request('page_size'),
        ];
        $data = ImageService::list($param);

        return Result::success([
            'list' => $data->items(),
            'total' => $data->total(),
        ]);
    }
}