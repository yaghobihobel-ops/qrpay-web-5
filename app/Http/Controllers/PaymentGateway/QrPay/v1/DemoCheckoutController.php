<?php

namespace App\Http\Controllers\PaymentGateway\QrPay\v1;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class DemoCheckoutController extends Controller
{
   public function index(){
        return view('qrpay-gateway.pages.checkout');
   }
    //get token
    public function getToken(){
        $baseUrl = rtrim(config('services.qrpay.base_url', 'https://qrpay.appdevs.net/pay/sandbox/api/v1'), '/');
        $clientId = config('services.qrpay.client_id');
        $secretId = config('services.qrpay.secret_id');

        if (empty($clientId) || empty($secretId)) {
            return (object) [
                'code' => 500,
                'message' => __('QRPay credentials are not configured.'),
        $base_url = "https://qrpay.appdevs.net/pay/sandbox/api/v1";
        $response = Http::post($base_url .'/authentication/token', [
            'client_id' => "tRCDXCuztQzRYThPwlh1KXAYm4bG3rwWjbxM2R63kTefrGD2B9jNn6JnarDf7ycxdzfnaroxcyr5cnduY6AqpulRSebwHwRmGerA",
            'secret_id' =>   "oZouVmqHCbyg6ad7iMnrwq3d8wy9Kr4bo6VpQnsX6zAOoEs4oxHPjttpun36JhGxDl7AUMz3ShUqVyPmxh4oPk3TQmDF7YvHN5M3",
        ]);
        $statusCode = $response->getStatusCode();
        $body = $response->getBody()->getContents();
        $result = json_decode($body, true);

        if ($statusCode != 200) {
            $errorData = is_array($result) ? $result : [];

            if (empty($errorData)) {
                report(new Exception('QRPay token response returned unexpected payload: ' . $body));
            }

            $code = data_get($errorData, 'message.code', $statusCode);

            $apiMessage = data_get($errorData, 'message.error');
            if (is_array($apiMessage)) {
                $apiMessage = $apiMessage[0] ?? null;
            }

            if (empty($apiMessage)) {
                $apiMessage = data_get($errorData, 'message.message')
                    ?? data_get($errorData, 'message')
                    ?? data_get($errorData, 'error')
                    ?? data_get($errorData, 'errors.0');
            }

            if (empty($apiMessage)) {
                $apiMessage = 'Access token capture failed';
                report(new Exception('QRPay token response missing error details: ' . json_encode($errorData)));
            }

            $data = [
                'code' => $code,
                'message' => sprintf('HTTP %d: %s', $statusCode, $apiMessage),
                'token' => '',
            ];
        }

        try {
            $response = Http::post($baseUrl . '/authentication/token', [
                'client_id' => $clientId,
                'secret_id' => $secretId,
            ]);
        } catch (Exception $exception) {
            report($exception);

            return (object) [
                'code' => 500,
                'message' => __('Failed to contact QRPay authentication service.'),
                'token' => '',
            ];
        }

        $statusCode = $response->getStatusCode();
        $result = $response->json();

        if ($statusCode !== 200) {
            $message = data_get($result, 'message.error.0')
                ?? data_get($result, 'message')
                ?? __('Access token capture failed.');

            Log::warning('QRPay access token request failed.', [
                'status' => $statusCode,
                'response' => $result,
            ]);

            return (object) [
                'code' => $statusCode,
                'message' => $message,
                'token' => '',
            ];
        }

        return (object) [
            'code' => data_get($result, 'message.code', 200),
            'message' => data_get($result, 'type', 'success'),
            'token' => data_get($result, 'data.access_token', ''),
        ];

    }
    //payment initiate
    public function initiatePayment(Request $request){
        $validator = Validator::make($request->all(), [
            'amount'         => "required|string|max:60"
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator->errors())->withInput();
        }
        $validated =$validator->validate();
        $access_token_info = $this->getToken();
        if($access_token_info->code != 200){
            return back()->with(['error' => [$access_token_info->message]]);
        }else{
            $access_token =   $access_token_info->token??'';
        }

        try{
            $client = new \GuzzleHttp\Client();
            $response = $client->request('POST', 'https://qrpay.appdevs.net/pay/sandbox/api/v1/payment/create', [
                'json' => [
                        'amount' =>     $validated['amount'],
                        'currency' =>   "USD",
                        'return_url' =>     route('merchant.checkout.success'),
                        'cancel_url' =>     route('merchant.checkout.cancel'),
                        'custom' =>       $this->custom_random_string(10),
                    ],
                'headers' => [
                    'Authorization' => 'Bearer '. $access_token,
                    'accept' => 'application/json',
                    'content-type' => 'application/json',
                    ],
            ]);
            $result = json_decode($response->getBody(),true);
            return redirect($result['data']['payment_url']);

        }catch(Exception $e){
            report($e);
            $errorMessage = $e->getMessage();
            $errorArray = [];
            if (preg_match('/{.*}/', $errorMessage, $matches)) {
                $errorArray = json_decode($matches[0], true);
            }
            if(isset($errorArray['message']['error'][0])){
                return back()->with(['error' => [ $errorArray['message']['error'][0]]]);
            }else{
                return back()->with(['error' => ["Something Is Wrong, Please Try Again Later"]]);
            }


        }

    }
    //after pay success
    public function paySuccess(Request $request){
        $getResponse = $request->all();
        if( $getResponse['type'] == 'success'){
           //write your needed code here
           return redirect()->route('merchant.checkout.index')->with(['success' => ['Your Payment Done Successfully']]);
        }

    }
    //after cancel payment
    public function payCancel(Request $request){
        //write your needed code here
        return redirect()->route('merchant.checkout.index')->with(['error' => ['Your Payment Cancel Successfully']]);
    }
    //custom transaction id which can use your project transaction
    function custom_random_string($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $char_length = strlen($characters);
        $random_string = '';

        for ($i = 0; $i < $length; $i++) {
            $random_string .= $characters[rand(0, $char_length - 1)];
        }
        return $random_string;
    }
}
