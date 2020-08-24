<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Imarket\Checkout\Models\Checkout;
use Increment\Imarket\Product\Models\Product;
use Increment\Imarket\Merchant\Models\Merchant;
use Increment\Imarket\Merchant\Models\Location;
use Increment\Imarket\Product\Http\ProductImageController;
use Increment\Common\Rating\Http\RatingController;
use Increment\Common\Rating\Models\Rating;
use Increment\Imarket\Cart\Models\Cart;
use Carbon\Carbon;

class ProductController extends APIController
{
    protected $dashboard = array(      
        "data" => null,
        "error" => array(),// {status, message}
        "debug" => null,
        "request_timestamp" => 0,
        "timezone" => 'Asia/Manila'
    );
    
    public static function LongLatDistance(
        $latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371)
      {
        if (is_null($latitudeFrom) || is_null($longitudeFrom) || is_null($latitudeTo) || is_null($longitudeTo)) {
          return null;
        }
        $latitudeFrom = floatval($latitudeFrom);
        $longitudeFrom = floatval($longitudeFrom);
        $latitudeTo = floatval($latitudeTo);
        $longitudeTo = floatval($longitudeTo);
        // convert from degrees to radians
        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);
        $lonDelta = $lonTo - $lonFrom;
        $a = pow(cos($latTo) * sin($lonDelta), 2) +
          pow(cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta), 2);
        $b = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($lonDelta);
      
        $angle = atan2(sqrt($a), $b);
        return $angle * $earthRadius;
      }

    public function retrieveByCategory(Request $request){
        //Data to be passed: category, limit
        $dashboardarr = [];
        $conditions = $request['condition'];
        foreach ($conditions as $condition){
            //manual query without raw query;
            $result = Product::select('products.id','products.account_id','products.merchant_id','products.title', 'products.description','products.status','products.category', 'locations.latitude', 'locations.longitude', 'locations.route')
                ->leftJoin('locations', 'products.account_id',"=","locations.account_id")
                ->distinct("products.merchant_id")
                ->where($condition['column'],$condition['value'])
                ->limit($request['limit'])
                ->offset($request['offset'])->get();
            for($i=0; $i<count($result); $i++){
                $result[$i]["distance"] = $this->LongLatDistance($request["latitude"],$request["longitude"],$result[$i]["latitude"], $result[$i]["longitude"]);
                $result[$i]["rating"] = app('Increment\Common\Rating\Http\RatingController')->getRatingByPayload("merchant", $result[$i]["account_id"]);
                $result[$i]["image"] = app('Increment\Imarket\Product\Http\ProductImageController')->getProductImage($result[$i]["id"], "featured");
            }
            array_push($dashboardarr, $result);
        }
        $dashboard["request_timestamp"]= date("Y-m-d h:i:s");
        $dashboard["data"] = $dashboardarr;
        return $dashboard;
    }

    public function retrieveByFeatured(Request $request){
        $dashboardarr = [];
        $conditions = $request['condition'];
        $modifiedrequest = new Request([]);
        $modifiedrequest['limit'] = $request['limit'];
        //manual query without raw query;
        $result = Product::select('products.id', 'products.account_id','products.merchant_id','products.category','products.title', 'products.description','locations.latitude', 'locations.longitude', 'locations.route')
            ->leftJoin('locations', 'products.account_id',"=","locations.account_id")
            ->where("status","featured")
            ->limit($modifiedrequest['limit'])
            ->offset($request['offset'])->get();
        for($i=0; $i<count($result); $i++){
            $result[$i]["distance"] = $this->LongLatDistance($request["latitude"],$request["longitude"],$result[$i]["latitude"], $result[$i]["longitude"]);
            $result[$i]["rating"] = app('Increment\Common\Rating\Http\RatingController')->getRatingByPayload("merchant", $result[$i]["account_id"]);
            $result[$i]["image"] = app('Increment\Imarket\Product\Http\ProductImageController')->getProductImage($result[$i]["id"], "featured");
        }
        array_push($dashboardarr, $result);
        $dashboard["request_timestamp"]= date("Y-m-d h:i:s");
        $dashboard["data"] = $dashboardarr;
        return $dashboard;
    }

    public function getCategories(Request $request){
        //limit and offset only
        $this->model = new Product;
        (isset($request['offset'])) ? $this->model->offset($request['offset']) : null;
        (isset($request['limit'])) ? $this->model = $this->model->limit($request['limit']) : null;
        $result = $this->model->select('category')->where('category', '!=', null)->groupBy('category')->get();
        return $result;
    }

    public function retrieveByShop(Request $request){
        //pass by code? pass by id?
        $dashboardarr = [];
        $conditions = $request['condition'];
        $modifiedrequest = new Request([]);
        if (isset($request["id"])){
            //grab by shop
            //$result = Merchant::with(['products'])->select('account_id','merchant_id','category')->distinct()->where("code",$request["id"])->limit($modifiedrequest['limit'])->get();
            $result = Merchant::select()
                ->where("merchants.id",$request['id'])
                ->leftJoin('locations','merchants.account_id',"=", "locations.account_id")->get();
            for($i=0; $i<count($result); $i++){
                $result[$i]["distance"] = $this->LongLatDistance($request["latitude"],$request["longitude"],$result[$i]["latitude"], $result[$i]["longitude"]);
                $result[$i]["rating"] = app('Increment\Common\Rating\Http\RatingController')->getRatingByPayload("merchant", $result[$i]["account_id"]);
            }
            array_push($dashboardarr, $result);
        }else{
            //"merchants.code","merchants.account_id","locations.latitude","locations.longitude","locations.route","locations.locality"
            //manual query without raw query;
            $result = Merchant::select("merchants.id", "merchants.code","merchants.account_id", "merchants.name", "merchants.prefix", "merchants.logo", "locations.latitude","locations.longitude","locations.route","locations.locality")
                ->leftJoin('locations', 'merchants.account_id',"=","locations.account_id")
                ->limit($request['limit'])
                ->offset($request['offset'])
                ->orderBy($request['sort'], 'desc')->get();
            for($i=0; $i<count($result); $i++){
                $result[$i]["distance"] = $this->LongLatDistance($request["latitude"],$request["longitude"],$result[$i]["latitude"], $result[$i]["longitude"]);
                $result[$i]["rating"] = app('Increment\Common\Rating\Http\RatingController')->getRatingByPayload("merchant", $result[$i]["account_id"]);
            }
            //$result = Product::select('account_id','merchant_id','category')->where($condition['column'],$condition['value'])->limit($request['limit'])->offset($request['offset'])->orderBy($request['sort'], 'desc')->get();
            //$result[0]["location"] = Location::select('latitude', 'longitude', 'route')->where("account_id", $result[0]["account_id"])->get();
            array_push($dashboardarr, $result);
        }
        $dashboard["request_timestamp"]= date("Y-m-d h:i:s");
        $dashboard["data"] = $dashboardarr;
        return $dashboard;
    }

}
    