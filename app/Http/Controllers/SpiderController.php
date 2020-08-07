<?php


namespace App\Http\Controllers;


use QL\QueryList;

class SpiderController extends Controller
{
    public function index()
    {
        $rules = [
            'title' => ['a', 'text'],
            'href' => ['a', 'href']
        ];
        $range = '.post-item .body';

        $ql = QueryList::getInstance()->rules($rules)->range($range);

        $res = [];
        for ($i = 1; $i <= 2; $i++) {
            $url = 'https://eleduck.com/?page=' . $i;
            $data = $ql->get($url)->query()->getData()->all();
            $res = array_merge($res, $data);
        }
        dd($res);
    }
}
