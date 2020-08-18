<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Jobs\Notifications;
class RiderController extends APIController
{

    public $timezone = 'Asia/Manila';

    function __construct(){
        $this->localization();
        $this->timezone = $this->response['timezone'];
    }

    public function search(Request $request){
        $data = $request->all();
        Notifications::dispatch('rider', $data->toArray());
        return $this->response();
    }
}