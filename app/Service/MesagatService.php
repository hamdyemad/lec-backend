<?php


namespace App\Service;

use Illuminate\Support\Facades\Http;

class MesagatService {

    function sms($mobile, $msg) {

        $data = [
            'userName' => 'Essa1980',
            'numbers' => $mobile,
            'userSender' => 'K7ilan',
            'apiKey' => '96B9402B4115ACCEE6E359706FBFEF91',
            'msg' => $msg,
            'msgEncoding' => 'UTF8'

        ];
        return Http::post('https://www.msegat.com/gw/sendsms.php', $data);
        // $mobile = intval($mobile);
        // $ch = curl_init();

        // curl_setopt($ch, CURLOPT_URL, "https://www.msegat.com/gw/sendsms.php");
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        // curl_setopt($ch, CURLOPT_HEADER, TRUE);
        // curl_setopt($ch, CURLOPT_POST, TRUE);
        // $fields = <<<EOT
        //     {
        //       "userName": "Essa1980",
        //       "numbers": "$mobile",
        //       "userSender": "K7ilan",
        //       "apiKey": "96B9402B4115ACCEE6E359706FBFEF91",
        //       "msg": "$msg",
        //       "msgEncoding":"UTF8"
        //     }
        //     EOT;
        // curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);

        // curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        //     "Content-Type: application/json"
        // ));

        // $response = curl_exec($ch);
        // $info = curl_getinfo($ch);
        // curl_close($ch);
    }

    function send_otp($mobile, $msg) {

        $data = [
            'userName' => 'Essa1980',
            'numbers' => $mobile,
            'userSender' => 'K7ilan',
            'apiKey' => '96B9402B4115ACCEE6E359706FBFEF91',
            'msg' => $msg,
            'msgEncoding' => 'UTF8'

        ];
        return Http::post('https://www.msegat.com/gw/sendsms.php', $data);
    }

    function verify_otp($mobile, $msg) {

        $data = [
            'userName' => 'Essa1980',
            'numbers' => $mobile,
            'userSender' => 'K7ilan',
            'apiKey' => '96B9402B4115ACCEE6E359706FBFEF91',
            'msg' => $msg,
            'msgEncoding' => 'UTF8'

        ];
        return Http::post('https://www.msegat.com/gw/sendsms.php', $data);
    }

}
