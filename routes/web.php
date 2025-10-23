<?php

use App\Http\Controllers\Api\Agent\AddMoneyController as AgentAddMoneyController;
use App\Http\Controllers\Api\User\AddMoneyController as UserAddMoneyController;
use App\Http\Controllers\DeveloperController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\User\AddMoneyController;
use App\Http\Controllers\User\PaymentLinkController;
use Illuminate\Support\Facades\Route;


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


//landing pages
Route::controller(SiteController::class)->group(function(){
    Route::get('/','home')->name('index');
    Route::get('about','about')->name('about');
    Route::get('service','service')->name('service');
    Route::get('faq','faq')->name('faq');
    Route::get('web/journal','blog')->name('blog');
    Route::get('web/journal/details/{id}/{slug}','blogDetails')->name('blog.details');
    Route::get('web/journal/by/category/{id}/{slug}','blogByCategory')->name('blog.by.category');
    Route::get('agent-info','agentInfo')->name('agent');
    Route::get('merchant-info','merchant')->name('merchant');
    Route::get('contact','contact')->name('contact');
    Route::post('contact/store','contactStore')->name('contact.store');
    Route::get('change/{lang?}','changeLanguage')->name('lang');
    Route::get('page/{slug}','usefulPage')->name('useful.link');
    Route::post('newsletter','newsletterSubmit')->name('newsletter.submit');
    Route::get('pagadito/success','pagaditoSuccess')->name('success');
    Route::get('pricing','pricing')->name('pricing')->middleware(['page_setup:pricing']);
    Route::get('section/{parent_id}','headerPage')->name('header.page');

});

Route::controller(DeveloperController::class)->prefix('developer')->name('developer.')->group(function(){
    Route::get('/','index')->name('index');
    Route::get('quick-start','quickStart')->name('quickstart');
    Route::get('prerequisites','prerequisites')->name('prerequisites');
    Route::get('authentication','authentication')->name('authentication');
    Route::get('base-url','baseUrl')->name('base.url');
    Route::get('sandbox','sandbox')->name('sandbox');
    Route::get('openapi','openapi')->name('openapi');
    Route::get('postman','postman')->name('postman');
    Route::get('feedback','feedback')->name('feedback');
    Route::get('access.token','accessToken')->name('access.token');
    Route::get('initiate-payment','initiatePayment')->name('initiate.payment');
    Route::get('check-status-payment','checkStatusPayment')->name('check.status.payment');
    Route::get('response-code','responseCode')->name('response.code');
    Route::get('error-handling','errorHandling')->name('error.handling');
    Route::get('best-practices','bestPractices')->name('best.practices');
    Route::get('examples','examples')->name('examples');
    Route::get('faq','faq')->name('faq');
    Route::get('support','support')->name('support');

});

//for sslcommerz callback urls(web)
Route::controller(AddMoneyController::class)->prefix("add-money")->name("add.money.")->group(function(){
    //sslcommerz
    Route::post('sslcommerz/success','sllCommerzSuccess')->name('ssl.success');
    Route::post('sslcommerz/fail','sllCommerzFails')->name('ssl.fail');
    Route::post('sslcommerz/cancel','sllCommerzCancel')->name('ssl.cancel');
    Route::post("/callback/response/{gateway}",'callback')->name('payment.callback')->withoutMiddleware(['web','auth','verification.guard','user.google.two.factor']);
});
//for sslcommerz callback urls(api)
Route::controller(UserAddMoneyController::class)->prefix("api/add-money")->name("api.add.money.")->group(function(){
    //sslcommerz
    Route::post('sslcommerz/success','sllCommerzSuccess')->name('ssl.success');
    Route::post('sslcommerz/fail','sllCommerzFails')->name('ssl.fail');
    Route::post('sslcommerz/cancel','sllCommerzCancel')->name('ssl.cancel');
});
//for Perfect Money Agent From Submit url
Route::controller(AgentAddMoneyController::class)->prefix("agent/add-money")->name("agent.add.money.")->group(function(){
    Route::get('redirect/form/{gateway}', 'redirectUsingHTMLForm')->name('payment.redirect.form');
});

//both merchants/users(PayLink)
Route::controller(PaymentLinkController::class)->prefix('payment-link')->name('payment-link.')->group(function(){
    Route::get('/share/{token}','paymentLinkShare')->name('share');
    Route::post('/submit','paymentLinkSubmit')->name('submit')->middleware('app.mode');
    Route::get('/transaction/success/{token}','transactionSuccess')->name('transaction.success');
    //route for payment gateway
    Route::prefix('gateway/payment')->name('gateway.payment.')->group(function(){
        Route::get('success/stripe/{trx}', 'stripeSuccess')->name('stripe.success');
        Route::get('success/response/paypal/{gateway}','paypalSuccess')->name('paypal.success');
        Route::get("cancel/response/paypal/{gateway}",'paypalCancel')->name('paypal.cancel');
        Route::get('flutterwave/callback/response', 'flutterwaveCallback')->name('flutterwave.callback');

        //redirect with Btn Pay
        Route::get('redirect/btn/checkout/{gateway}', 'redirectBtnPay')->name('btn.pay')->withoutMiddleware(['web']);
        //callback
        Route::post("callback/response/{gateway}",'callback')->name('callback')->withoutMiddleware(['web']);

        Route::get('success/response/{gateway}','successGlobal')->name('global.success');
        Route::get("cancel/response/{gateway}",'cancelGlobal')->name('global.cancel');

        // POST Route For Unauthenticated Request
        Route::post('success/response/{gateway}', 'postSuccess')->name('global.success')->withoutMiddleware(['web']);
        Route::post('cancel/response/{gateway}', 'postCancel')->name('global.cancel')->withoutMiddleware(['web']);

        //sslcommerz
        Route::post('sslcommerz/success','sllCommerzSuccess')->name('ssl.success');
        Route::post('sslcommerz/fail','sllCommerzFails')->name('ssl.fail');
        Route::post('sslcommerz/cancel','sllCommerzCancel')->name('ssl.cancel');
        //coingate
        Route::match(['get','post'],'coingate/success/response/{gateway}','coinGateSuccess')->name('coingate.success');
        Route::match(['get','post'],"coingate/cancel/response/{gateway}",'coinGateCancel')->name('coingate.cancel');
        //perfect Money
         Route::get('redirect/form/{gateway}', 'redirectUsingHTMLForm')->name('redirect.form')->withoutMiddleware(['auth']);
    });
    //wallet system login by user
    Route::prefix('user/wallet')->name('user.wallet.')->group(function(){
        Route::get('login/{token}','userLogin')->name('login')->middleware(['web','auth']);

    });
});

Route::get('token',function(){

    // Define the API endpoint
    $url = 'https://api-demo.airwallex.com/api/v1/authentication/login';

    // Define the headers
    $headers = [
        'Content-Type: application/json',
        'x-client-id: W_ORsgAFTiuA9k2KuqZt8A',
        'x-api-key: 8ac97c856c6d6cae7eb8fd05511f7a165be798d032381cb8026de7b4aa9aaee2e6312a8888a3474d783a40913ab6b55d'
    ];

    // Initialize cURL
    $curl = curl_init();

    // Set cURL options
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => $headers,
    ]);

    // Execute the request
    $response = curl_exec($curl);

    // Check for errors
    if (curl_errno($curl)) {
        echo 'cURL Error: ' . curl_error($curl);
    } else {
        // Print the response
        echo $response;
    }

    // Close the cURL session
    curl_close($curl);



});
Route::get('get-holder',function(){

    // Define the URL and headers
    $url = 'https://api-demo.airwallex.com/api/v1/issuing/cardholders';
    $params = http_build_query([
        'cardholder_status' => 'READY',
        'page_num' => 0,
        'page_size' => 100
    ]);
    $authorizationToken = 'eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiJ0b20iLCJyb2xlcyI6WyJ1c2VyIl0sImlhdCI6MTQ4ODQxNTI1NywiZXhwIjoxNDg4NDE1MjY3fQ.UHqau03y5kEk5lFbTp7J4a-U6LXsfxIVNEsux85hj-Q';

    // Initialize cURL
    $curl = curl_init();

    // Set cURL options
    curl_setopt_array($curl, [
        CURLOPT_URL => $url . '?' . $params,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $authorizationToken,
        ],
    ]);

    // Execute the request
    $response = curl_exec($curl);

    // Check for errors
    if (curl_errno($curl)) {
        echo 'cURL Error: ' . curl_error($curl);
    } else {
        // Print the response
        echo $response;
    }

    // Close the cURL session
    curl_close($curl);


});
Route::get('create-holder',function(){

    // Define the URL and headers
    $url = 'https://api-demo.airwallex.com/api/v1/issuing/cardholders/create';
    $authorizationToken = '<your_bearer_token>'; // Replace with your actual bearer token

    // Define the data payload
    $data = [
        "email" => "test@example.com",
        "mobile_number" => "(257) 563-7401",
        "type" => "INDIVIDUAL",
        "individual" => [
            "date_of_birth" => "1982-11-02",
            "name" => [
                "first_name" => "Test",
                "last_name" => "Name"
            ],
            "address" => [
                "city" => "Hong Kong",
                "country" => "HK",
                "line1" => "38 Chengtu Rd",
                "postcode" => "999077",
                "state" => "Hong Kong"
            ],
            "express_consent_obtained" => "yes"
        ]
    ];

    // Initialize cURL
    $curl = curl_init();

    // Set cURL options
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $authorizationToken,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode($data), // Convert array to JSON
    ]);

    // Execute the request
    $response = curl_exec($curl);

    // Check for errors
    if (curl_errno($curl)) {
        echo 'cURL Error: ' . curl_error($curl);
    } else {
        // Print the response
        echo $response;
    }

    // Close the cURL session
    curl_close($curl);



});
