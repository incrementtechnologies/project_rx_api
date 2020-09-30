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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Arr;

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

    /**
     * @param
     * {
     *    latitude,
     *    longitude,
     *    limit,
     *    offset,
     *    condition: e.g {
     *        "condition": [
     *            {"column": "category", "clause": "=", "value": "Burger"},
     *            {"column": "category", "clause": "=", "value": "Fast Food"}
     *         ],
     *    }
     * }
     */
    public function retrieveByCategory(Request $request){
        $dashboardarr = [];
        $conditions = $request['condition'];
        foreach ($conditions as $condition){
            $datatemp = [];

            $result = Product::select('products.id','products.account_id','products.merchant_id','products.title', 'products.description','products.status','products.category', 'locations.latitude', 'locations.longitude', 'locations.route')
                ->leftJoin('locations', 'products.account_id',"=","locations.account_id")
                ->distinct("products.id")
                ->where($condition['column'],$condition['value'])
                ->limit($request['limit'])
                ->offset($request['offset'])->get();

            for($i=0; $i<count($result); $i++){
                $result[$i]["distance"] = $this->LongLatDistance($request["latitude"],$request["longitude"],$result[$i]["latitude"], $result[$i]["longitude"]);
                if ($result[$i]["distance"] <= 30){
                    $result[$i]["rating"] = app('Increment\Common\Rating\Http\RatingController')->getRatingByPayload("merchant", $result[$i]["account_id"]);
                    $result[$i]["image"] = app('Increment\Imarket\Product\Http\ProductImageController')->getProductImage($result[$i]["id"], "featured");
                    array_push($datatemp, $result[$i]);
                }
            }

            array_push($dashboardarr, $datatemp);
        }

        
        $dashboard["request_timestamp"]= date("Y-m-d h:i:s");
        $dashboard["data"] = $dashboardarr;
        return $dashboard;
    }

    /**
     * @param
     * {
     *    latitude,
     *    longitude,
     *    limit,
     *    offset,
     * }
     */
    public function retrieveByFeatured(Request $request){
        $dashboardarr = [];
        $datatemp = [];
        $conditions = $request['condition'];
        $modifiedrequest = new Request([]);
        $modifiedrequest['limit'] = $request['limit'];

        $result = Product::select('products.id', 'products.account_id','products.merchant_id','products.category','products.title', 'products.description','locations.latitude', 'locations.longitude', 'locations.route')
            ->leftJoin('locations', 'products.account_id',"=","locations.account_id")
            ->where("status","featured")
            ->distinct("products.id")
            ->limit($modifiedrequest['limit'])
            ->offset($request['offset'])
            ->get();

        for($i=0; $i<count($result); $i++){
            $result[$i]["distance"] = $this->LongLatDistance($request["latitude"],$request["longitude"],$result[$i]["latitude"], $result[$i]["longitude"]);
            if ($result[$i]["distance"] <= 30){
                $result[$i]["rating"] = app('Increment\Common\Rating\Http\RatingController')->getRatingByPayload("merchant", $result[$i]["account_id"]);
                $result[$i]["image"] = app('Increment\Imarket\Product\Http\ProductImageController')->getProductImage($result[$i]["id"], "featured");
                array_push($datatemp, $result[$i]);            
            }
        }

        array_push($dashboardarr, $datatemp);        
        $dashboard["request_timestamp"]= date("Y-m-d h:i:s");
        $dashboard["data"] = $dashboardarr;
        return $dashboard;
    }

    /**
     * @param
     * {
     *    limit,
     *    offset,
     * }
     */
    public function getCategories(Request $request){
        //limit and offset only
        $this->model = new Product;
        (isset($request['offset'])) ? $this->model = $this->model->offset($request['offset']) : null;
        (isset($request['limit'])) ? $this->model = $this->model->limit($request['limit']) : null;
        $result = $this->model->select('category')->where('category', '!=', null)->groupBy('category')->distinct()->get();
        return $result;
    }

    /**
     * @param
     * {
     *    limit,
     *    offset,
     *    sort,
     *    latitude,
     *    longitude
     * } => grab all shops
     * 
     * if { id } only => grab by shop
     */
    public function retrieveByShop(Request $request){
        $dashboardarr = [];
        $datatemp = [];
        $conditions = $request['condition'];
        $modifiedrequest = new Request([]);
        if (isset($request["id"])){
            $result = DB::table('merchants as T1')
                ->leftJoin('locations as T2','T2.merchant_id',"=", "T1.id")
                ->where("T1.id", '=', $request['id'])
                ->where('T2.deleted_at', '=', null)
                ->where('T1.deleted_at', '=', null)
                ->get();
            for($i=0; $i<count($result); $i++){
                $result[$i]["distance"] = $this->LongLatDistance($request["latitude"],$request["longitude"],$result[$i]["latitude"], $result[$i]["longitude"]);
                if ($result[$i]["distance"] <= 30){
                    $result[$i]["rating"] = app('Increment\Common\Rating\Http\RatingController')->getRatingByPayload("merchant", $result[$i]["account_id"]);
                    $result[$i]["image"] = app('Increment\Imarket\Product\Http\ProductImageController')->getProductImage($result[$i]["id"], "featured");
                    $datatemp[] = $result[$i];                }
            }
        }else{
            $result = DB::table('merchants as T1')
                ->select(["T1.id", "T1.code","T1.account_id", "T1.name", "T1.prefix", "T1.logo", "T2.latitude","T2.longitude","T2.route","T2.locality"])
                ->leftJoin('locations as T2', 'T2.merchant_id',"=","T1.id")
                ->where('T2.deleted_at', '=', null)
                ->where('T1.deleted_at', '=', null)
                ->distinct("T1.id")
                ->get();
                // sort disabled

                // ->limit($request['limit'])
                // ->offset($request['offset'])
            $result = json_decode($result, true);
            for($i=0; $i<count($result); $i++){
                $result[$i]["distance"] = $this->LongLatDistance($request["latitude"],$request["longitude"],$result[$i]["latitude"], $result[$i]["longitude"]);
                if ($result[$i]["distance"] <= 30 && $result[$i]["distance"] != null){
                    $result[$i]["rating"] = app('Increment\Common\Rating\Http\RatingController')->getRatingByPayload("merchant", $result[$i]["account_id"]);
                    $result[$i]["image"] = app('Increment\Imarket\Product\Http\ProductImageController')->getProductImage($result[$i]["id"], "featured");
                    $datatemp[] = $result[$i];
                    // array_push($datatemp, $result[$i]);
                }
            }
            
        }

        $distance = array_column($datatemp, 'distance');
        array_multisort($distance, SORT_ASC, $datatemp);
        array_push($dashboardarr, $datatemp);
        $dashboard["request_timestamp"]= date("Y-m-d h:i:s");
        $dashboard["data"] = $dashboardarr; 
        return $dashboard;
    }

    public function retrieveByShopInArray(Request $request){
      $dashboardarr = [];
      $datatemp = [];
      $conditions = $request['condition'];
      $modifiedrequest = new Request([]);
      if (isset($request["id"])){
        $result = DB::table('merchants as T1')
          ->leftJoin('locations as T2','T2.merchant_id',"=", "T1.id")
          ->where("T1.id", '=', $request['id'])
          ->where('T2.deleted_at', '=', null)
          ->where('T1.deleted_at', '=', null)
          ->get();
        if (count($result) > 0) {
          $result[0]->distance = $this->LongLatDistance($request["latitude"],$request["longitude"],$result[0]->latitude, $result[0]->longitude);
          $result[0]->rating = app('Increment\Common\Rating\Http\RatingController')->getRatingByPayload("merchant", $result[0]->account_id);
          $result[0]->image = app('Increment\Imarket\Product\Http\ProductImageController')->getProductImage($result[0]->id, "featured");
          $datatemp[] = $result[0];
        }
      }else{
        $result = DB::table('merchants as T1')
          ->select(["T1.id", "T1.code","T1.account_id", "T1.name", "T1.prefix", "T1.logo", "T2.latitude","T2.longitude","T2.route","T2.locality"])
          ->leftJoin('locations as T2', 'T2.merchant_id',"=","T1.id")
          ->where('T2.deleted_at', '=', null)
          ->where('T1.deleted_at', '=', null)
          ->distinct("T1.id")
          ->get();
          // sort disabled

          // ->limit($request['limit'])
          // ->offset($request['offset'])
        $result = json_decode($result, true);
        for($i=0; $i<count($result); $i++){
          $result[$i]["distance"] = $this->LongLatDistance($request["latitude"],$request["longitude"],$result[$i]["latitude"], $result[$i]["longitude"]);
          if ($result[$i]["distance"] <= 30 && $result[$i]["distance"] != null){
            $result[$i]["rating"] = app('Increment\Common\Rating\Http\RatingController')->getRatingByPayload("merchant", $result[$i]["account_id"]);
            $result[$i]["image"] = app('Increment\Imarket\Product\Http\ProductImageController')->getProductImage($result[$i]["id"], "featured");
            $datatemp[] = $result[$i];
          }
        }
      }
      $distance = array_column($datatemp, 'distance');
      array_multisort($distance, SORT_ASC, $datatemp);
      $dashboard["request_timestamp"]= date("Y-m-d h:i:s");
      $dashboard["data"] = $datatemp; 
      return $dashboard;
    }
  }
    