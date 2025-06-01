<?php


namespace App\Service;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;

class MoyasarService {

    public $moyasar_pub_key;
    public $moyasar_sec_key;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct() {
        $this->moyasar_pub_key = env('MOYASAR_PUBLIC_KEY');
        $this->moyasar_sec_key = env('MOYASAR_SECRET_KEY');
    }

    public function make_payment($data, $type = 'creditcard') {
        $encoded_auth =base64_encode($this->moyasar_sec_key);
        $body = [
            "amount" => $data['amount'] * 100, // to make it number without halala
            "currency" => $data['currency'],
            "description" => "Payment for order #" . $data['reference'],
            "callback_url" => $data['callback_url'],
            'source' => [
                'type' => $type
            ]
        ];

        if($type == 'creditcard') {
            $body['source']['name'] = $data['card_name'];
            $body['source']['number'] = $data['card_number'];
            $body['source']['cvc'] = $data['card_cvc'];
            $body['source']['month'] = $data['card_month'];
            $body['source']['year'] = $data['card_year'];
        } else if($type == 'stcpay') {
            $body['source']['mobile'] = $data['mobile'];
        }

        $response = Http::withHeaders([
            'Authorization' => "Basic $encoded_auth"
        ])->post('https://api.moyasar.com/v1/payments', $body);
        return $response;
    }

    public function payment($payment_id) {
        $encoded_auth =base64_encode($this->moyasar_sec_key);
        $response = Http::withHeaders([
            'Authorization' => "Basic $encoded_auth"
        ])->get("https://api.moyasar.com/v1/payments/$payment_id");
        return $response;
    }


    public function payment_stc_otp($url, $otp_value) {
        $encoded_auth =base64_encode($this->moyasar_sec_key);
        $data = [
            'otp_value' => $otp_value
        ];
        $response = Http::withHeaders([
            'Authorization' => "Basic $encoded_auth"
        ])->post($url, $data);
        return $response;
    }


    public function payouts_accounts_create($data) {
        $encoded_auth =base64_encode($this->moyasar_sec_key);
        $data = [
            'account_type' => $data['account_type'],
            'properties' => [
                'iban' => $data['iban'],
            ],
            'credentials' => [
                'client_id' => $data['client_id'],
                'client_secret' => $data['client_secret'],
            ],
        ];

        $response = Http::withHeaders([
            'Authorization' => "Basic $encoded_auth"
        ])->post("https://api.moyasar.com/v1/payout_accounts", $data);
        return $response;
    }

    public function getPayouts($request)
    {
        $encoded_auth =base64_encode($this->moyasar_sec_key);

        $page = 1;
        if($request->page) {
            $page = $request->page;
        }
        // Fetch data from the API
        $response = Http::withHeaders([
            'Authorization' => "Basic $encoded_auth"
        ])->get('https://api.moyasar.com/v1/payouts?page=' . $page); // Replace with the actual API URL
        $data = $response->json(); // Decode JSON response

        // Extract payouts and meta information
        $payouts = $data['payouts'];
        $meta = $data['meta'];

        // Current page from request, default is 1
        $currentPage = $request->page ?? 1;

        // Per page (use the meta data or a default value)
        $perPage = count($payouts);

        // Manually create a paginator
        $paginator = new LengthAwarePaginator(
            $payouts,
            $meta['total_count'], // Total count
            $perPage, // Per page
            $currentPage,
            [
                'path' => $request->url(), // Keep the current URL
                'query' => $request->query(), // Query parameters
            ]
        );

        return $paginator;
    }

    public function getPayout($payout_id)
    {
        $encoded_auth =base64_encode($this->moyasar_sec_key);
        // Fetch data from the API
        $response = Http::withHeaders([
            'Authorization' => "Basic $encoded_auth"
        ])->get('https://api.moyasar.com/v1/payouts/' . $payout_id); // Replace with the actual API URL

        return $response;

    }

    public function createPayout($data)
    {
        $encoded_auth =base64_encode($this->moyasar_sec_key);
        // Fetch data from the API
        $response = Http::withHeaders([
            'Authorization' => "Basic $encoded_auth"
        ])->post('https://api.moyasar.com/v1/payouts', $data); // Replace with the actual API URL

        return $response;

    }




}
