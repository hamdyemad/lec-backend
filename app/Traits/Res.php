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

  public function errorResponse($validator) {
    $messages = [];
    $messagesWithKeys = [];
    foreach( $validator->errors()->getMessages() as $key => $messageArray) {
        foreach ($messageArray as $message) {
            // Step 1: Generalize the key (e.g., color_ids.0 → color_ids.*)
            $genericKey = preg_replace('/\.\d+/', '.*', $key);
            // Step 2: Replace full key in message with placeholder
            $genericMessage = str_replace($key, ':attribute', $message);
            // Step 3: Fetch translated message from DB
            $translatedMessage = translate($genericMessage); // returns message with :attribute
            // Step 4: Fetch translated attribute name from DB
            $translatedAttribute = translate($genericKey); // returns Arabic label
            // Step 5: Inject attribute name into message
            $finalMessage = str_replace(':attribute', $translatedAttribute, $translatedMessage);
            $messagesWithKeys[$key][] = $finalMessage;
            $messages[] = $finalMessage;
        }
    }
    $message = implode('<br>', $messages);
    return $this->sendRes($message, false, [], $messagesWithKeys, 400);
  }

}
