<?php

namespace App\Traits;

trait Res
{
  public function sendRes($message, $status = true,  $data = [], $errors = [],$code=200)
  {
    return response()->json([
      'status' => $status,
      'message' => $message,
      'data' => $data,
      'errors' => $errors,
    ],$code);
  }

  protected function respondWithToken($token, $status = true, $message = '', $data = [], $errors = [])
  {
    $other_res = [
        'access_token' => $token,
        'token_type' => 'bearer',
        'expires_in' => auth('api')->factory()->getTTL() . ' ' . __('main.minutes'),
    ];
    $data = array_merge($other_res, $data);
      return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => $data,
            'errors' => $errors,
      ]);
  }

  public function realtime($channel, $event)
  {
    return [
        'channel' => $channel,
        'event' => $event
    ];
  }

}
