<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\Translate;
use App\Models\Language;
use App\Models\Message;
use App\Models\Translation;
use App\Traits\Res;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MessageController extends Controller
{
    use Res;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $per_page = request('per_page') ?? 12;
        $keyword = request('keyword') ?? '';
        $messages = Message::orderBy('created_at', 'desc');

        if($keyword) {
            $messages = $messages
            ->where('name', 'like', "%{$keyword}%")
            ->orWhere('email', 'like', "%{$keyword}%")
            ->orWhere('message', 'like', "%{$keyword}%");
        }

        $messages = $messages->paginate($per_page);
        return $this->sendRes('', true, $messages);
    }

}
