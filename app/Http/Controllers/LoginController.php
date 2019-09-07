<?php


namespace App\Http\Controllers;


use App\Result;

class LoginController extends Controller
{
    public function login()
    {
        return Result::success();
    }
}