<?php

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
