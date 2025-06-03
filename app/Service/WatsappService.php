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
        $this->access_token = 'EAAPZBfXIoMKIBOyWTtTqpkcbZCCHCUtOg42nxPEOncM8qKvuEhB7ZAteug7BWRyO671KaEhPN5XZAES8rDNQYfqzZBEWZAPg0vLig59UjrpfM1dTxCL31PI2vOGILJuzzNlv3cw8a7aLCca46W6txYP8KjvJZCDEE3IBnRc76x44qTodzZAcVpfYLerUs1VC6EUhGDcXVeiI2D5OzrZC511aUOZAtqr8rsRtbETtR8gzZATUZB4wxjHw4dYZD';
        $this->phone_number_id = '603840889489486';
        $this->watsapp_business_account_id = '1425655498870407';
        $this->version = 'v22.0';
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
