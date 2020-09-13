<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
$route = env('PACKAGE_ROUTE', '');
Route::get('/', function () {
    return view('email.receiptofficial');//view('welcome');
});
/*
  Accessing uploaded files
*/
Route::get($route.'/storage/profiles/{filename}', function ($filename)
{
    $path = storage_path('/app/profiles/' . $filename);

    if (!File::exists($path)) {
        abort(404);
    }

    $file = File::get($path);
    $type = File::mimeType($path);

    $response = Response::make($file, 200);
    $response->header("Content-Type", $type);

    return $response;
});
Route::get($route.'/storage/test/{filename}', function ($filename)
{
    $path = storage_path('app/images/' . $filename);

    if (!File::exists($path)) {
        abort(404);
    }

    $file = Storage::get($path, 200);
    $type = File::mimeType($path);

    $response = Response::make($file);
    $response->header("Content-Type", $type);

    return $response;
});
Route::get($route.'/storage/logo/{filename}', function ($filename)
{
    $path = storage_path('/app/logos/' . $filename);

    if (!File::exists($path)) {
        abort(404);
    }

    $file = File::get($path);
    $type = File::mimeType($path);

    $response = Response::make($file, 200);
    $response->header("Content-Type", $type);

    return $response;
});

Route::get('/cache', function () {
    $exitCode = Artisan::call('config:cache');
    return 'hey'.$exitCode;

    //
});
Route::get('/clear', function () {
    $exitCode = Artisan::call('config:cache');
    return 'hey'.$exitCode;

    //
});
Route::get('/migrate', function () {
    $exitCode = Artisan::call('migrate');
    return 'hey'.$exitCode;

    //
});

/* Authentication Router */
$route = env('PACKAGE_ROUTE', '').'/authenticate';
Route::resource($route, 'AuthenticateController', ['only' => ['index']]);
Route::post($route, 'AuthenticateController@authenticate');
Route::post($route.'/user', 'AuthenticateController@getAuthenticatedUser');
Route::post($route.'/refresh', 'AuthenticateController@refreshToken');
Route::post($route.'/invalidate', 'AuthenticateController@deauthenticate');
Route::post($route.'/googlelogin', 'AuthenticateController@googleLogin');
Route::post($route.'/broadcast_auth', 'AuthenticateController@broadcastAuth');
Route::post($route.'/auth', function () {
    return true;
});

//Emails Controller
$route = env('PACKAGE_ROUTE', '').'/emails';
Route::post($route.'/create', "EmailController@create");
Route::post($route.'/retrieve', "EmailController@retrieve");
Route::post($route.'/update', "EmailController@update");
Route::post($route.'/delete', "EmailController@delete");
Route::post($route.'/reset_password', 'EmailController@resetPassword');
Route::post($route.'/verification', 'EmailController@verification');
Route::post($route.'/changed_password', 'EmailController@changedPassword');
Route::post($route.'/referral', 'EmailController@referral');
Route::post($route.'/trial', 'EmailController@trial');
Route::post($route.'/test_sms', 'EmailController@testSMS');

//Notification Settings Controller
$route = env('PACKAGE_ROUTE', '').'/notification_settings/';
$controller = 'NotificationSettingController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'update_otp', $controller."generateOTP");
Route::post($route.'block_account', $controller."blockedAccount");
Route::post($route.'update', $controller."update");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller.'test');

//Dashboard Functions Controller
$route = env('PACKAGE_ROUTE', '').'/dashboard/';
$controller = 'ProductController@';
Route::post($route.'category', $controller."retrieveByCategory");
Route::post($route.'featured', $controller."retrieveByFeatured");
Route::post($route.'shops', $controller."retrieveByShop");
Route::post($route.'categories', $controller."getCategories");
// //Status Change for Package
// $route = env('PACKAGE_ROUTE', '').'/checkouts/';
// $controller = 'CheckoutController@';
// Route::post($route.'updateStatus', $controller."updateStatus");


// Google Place
$route = env('PACKAGE_ROUTE', '').'/google_places/';
$controller = 'GooglePlaceController@';
Route::post($route.'search', $controller."search");


// Rider Place
$route = env('PACKAGE_ROUTE', '').'/riders/';
$controller = 'RiderController@';
Route::post($route.'search', $controller."search");

// Custom Messenger Group Controller
$route = env('PACKAGE_ROUTE', '').'/custom_messenger_groups/';
$controller = 'MessengerGroupController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");

