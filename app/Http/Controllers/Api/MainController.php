<?php
/**
 * Created by PhpStorm.
 * User: tytar
 * Date: 24.12.15
 * Time: 3:21
 */

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Http\Requests\Request;

class MainController extends Controller
{

    public function index()
    {
        return view('main');
    }


    public function options($user_id = 0, $message_id = 0)
    {
        return response('')->header(
            'Allow', 'HEAD,GET,POST,PUT,DELETE,OPTIONS'
        );
    }

}