<?php


namespace App\Service;

use Illuminate\Support\Facades\Http;

class WatsappService {

    public $access_token;
    public $phone_number_id;
    public $watsapp_business_account_id;
    public $version;
    public $current_templates = [
        'verify_password_recovery',
        'verify_1',
    ];

    public function __construct() {
        $this->access_token = env('WATSAPPSERVICE_ACCESS_TOKEN');
        $this->phone_number_id = env('WATSAPPSERVICE_PHONE_NUMBER_ID');
        $this->watsapp_business_account_id = env('WATSAPPSERVICE_BUSINESS_ACCOUNT_ID');
        $this->version = env('WATSAPPSERVICE_API_VERSION');
    }

    function send_verify($mobile, $code, $template = 'verify_1') {
        $data = [
            'messaging_product' => 'whatsapp',
            'to' => $mobile,
            'type' => 'template',
            'template' => [
                'name' => $template,
                'language' => [
                    'code' => 'en_US',
                ],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => [
                            [
                                'type' => 'text',
                                'text' => $code,
                            ],
                        ],
                    ],
                    [
                        'type' => 'button',
                        'sub_type' => 'url',
                        'index' => 0,
                        'parameters' => [
                            [
                                'type' => 'text',
                                'text' => $code,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->access_token,
            'Content-Type' => 'application/json',
        ])->post("https://graph.facebook.com/{$this->version}/{$this->phone_number_id}/messages", $data);
        return $response;
    }

}
