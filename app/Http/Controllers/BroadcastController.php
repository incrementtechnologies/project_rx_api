<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Jobs\Notifications;
class BroadcastController extends APIController
{

    public $timezone = 'Asia/Manila';

    function __construct(){
        $this->localization();
        $this->timezone = $this->response['timezone'];
    }

    public function search(Request $request){
        $data = $request->all();
        Notifications::dispatch('rider', $data);
        return $this->response();
    }

    public function locationSharing(Request $request){
        $data = $request->all();
        Notifications::dispatch('location_sharing', $data);
        return $this->response();
    }

    public function accountStatus(Request $request){
        $data = $request->all();
        Notifications::dispatch('account_status', $data);
        return $this->response();
    }
}