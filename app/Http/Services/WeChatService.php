<?php


namespace App\Http\Services;


class WeChatService extends BaseService
{
    public static function getMenu()
    {
        $button = [
            [
                'type' => 'view',
                'name' => '李子园',
                'url' => 'https://liwen-git.github.io/',
            ],
            [
                'name' => '飞飞飞',
                'sub_button' => [
                    [
                        'type' => 'view',
                        'name' => '健康讲堂',
                        'url' => '',
                    ],
                    [
                        'type' => 'view',
                        'name' => '打卡记录',
                        'url' => '',
                    ],
                ]
            ],
            [
                'name' => '天天向上',
                'sub_button' => [
                    [
                        'type' => 'view',
                        'name' => 'Python',
                        'url' => '',
                    ],
                    [
                        'type' => 'view',
                        'name' => 'Go',
                        'url' => '',
                    ]
                ]
            ]
        ];
        return $button;
    }
}