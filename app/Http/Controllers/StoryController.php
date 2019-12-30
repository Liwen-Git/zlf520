<?php


namespace App\Http\Controllers;


class StoryController extends Controller
{
    public function checkPassword()
    {
        var_dump(str_plural('story', 2));
    }
}