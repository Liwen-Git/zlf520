<?php


namespace App\Http\Controllers;


use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * @throws ValidationException
     */
    public function add()
    {
        $this->validate(request(), [
            'username' => 'required',
            'password' => 'required|between:6,30',
        ]);
    }
}