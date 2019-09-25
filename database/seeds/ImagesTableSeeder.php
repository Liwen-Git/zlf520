<?php

use App\Http\Modules\Image as ImageAlias;
use App\Http\Services\ImageService;
use App\Http\Services\QiNiuService;
use Illuminate\Database\Seeder;

class ImagesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 删除所有行，并重置自增 ID 为零
        ImageAlias::truncate();

        // 获取七牛中的所有数据，遍历
        $list = QiNiuService::getAllFiles('blog');
        if (!empty($list)) {
            foreach ($list as $item) {
                $url = 'http://' . env('QINIU_DOMAINS') . '/' . $item;
                $arr = explode('/', $item);
                $directory = $arr[0];
                ImageService::add($directory, $url);
            }
        }
    }
}
