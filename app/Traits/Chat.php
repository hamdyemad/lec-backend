<?php

namespace App\Traits;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

trait Chat
{

    public function chat(Request $request, $order_id)
    {
        $sender_id = auth()->user()->id;
        $created_array = [
            'sender_id' => $sender_id,
            'order_id' => $order_id,
            'content' => $request->content ?? '',
        ];

        if(isset($request->file)) {
            $file = $this->uploadFile($request, $this->messages_path,'file');
            $created_array['file'] = $file;
        }
        $message = Message::create($created_array);
        return $this->sendRes(__('chats.add'), true, $message);

    }

}
