<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\Category;
use App\Models\Feature;
use App\Models\FeatureType;
use App\Models\Product;
use App\Models\User;
use App\Models\UserType;
use App\Traits\Res;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CardController extends Controller
{
    use Res;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */



    public function index(Request $request)
    {
        $authUser = auth()->user();

        $validator = Validator::make($request->all(), [
            'per_page' => ['nullable', 'integer', 'min:1'],
            'keyword' => ['nullable', 'string', 'max:255'],
        ]);
        if ($validator->fails()) {
            $message = implode('<br>', $validator->errors()->all());
            return $this->sendRes($message, false, [], $validator->errors(), 400);
        }
        $keyword = $request->keyword ?? '';
        $per_page = $request->per_page ?? 12;

        $cards = $authUser->cards()->latest();


        if ($keyword) {
            $cards = $cards
                ->where('card_type', 'like', "%$keyword%")
                ->orWhere('card_num', 'like', "%$keyword%")
                ->orWhere('card_name', 'like', "%$keyword%")
                ->orWhere('card_month', 'like', "%$keyword%")
                ->orWhere('card_year', 'like', "%$keyword%")
                ->orWhere('card_cvv', 'like', "%$keyword%");
        }

        $cards = $cards->paginate($per_page);

        return $this->sendRes(translate('cards data'), true, $cards);
    }


    function getCardType(string $cardNumber): string
    {
        $number = preg_replace('/\D/', '', $cardNumber); // Remove non-digits
        if (preg_match('/^4\d{12}(\d{3})?(\d{3})?$/', $number)) {
            return 'Visa';
        } elseif (
            preg_match('/^5[1-5]\d{14}$/', $number) ||
            preg_match('/^2(2[2-9]\d|[3-6]\d{2}|7([01]\d|20))\d{12}$/', $number)
        ) {
            return 'Mastercard';
        } elseif (preg_match('/^3[47]\d{13}$/', $number)) {
            return 'American Express';
        } elseif (preg_match('/^6(?:011|5\d{2}|4[4-9]\d|22[1-9]|2[3-9]\d|[3-6]\d{2}|7[01]\d|720)\d{12}$/', $number)) {
            return 'Discover';
        } else {
            return 'Unknown';
        }
    }



    public function form(Request $request, $card = null)
    {
        $auth = auth()->user();
        $rules = [
            'card_num' => ['required', 'string', 'regex:/^\d{13,19}$/', Rule::unique('users_cards', 'card_num')->where('user_id', $auth->id)->ignore($card ? $card->id : null)],
            'card_name' => ['required', 'string'],
            'card_month' => ['required', 'string', 'regex:/^(0[1-9]|1[0-2])$/'],
            'card_year' => ['required', 'string', 'regex:/^\d{2}$/'],
            'card_cvv' => ['required', 'string', 'regex:/^\d{3,4}$/'],
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $this->errorResponse($validator);
        }

        $data = $request->only([
            'card_num',
            'card_name',
            'card_month',
            'card_year',
            'card_cvv',
        ]);
        $data['card_type'] = $this->getCardType($data['card_num']);

        if($card) {
            $card->update($data);
            $message = translate('card updated successfully');
        } else {
            $data['uuid'] = \Str::uuid();
            $card = $auth->cards()->create($data);
            $message = translate('card added successfully');
        }
        return $this->sendRes($message, true, $card);
    }

    public function store(Request $request)
    {
        return $this->form($request);
    }

    public function edit(Request $request, $uuid)
    {
        $authUser = auth()->user();
        $card = $authUser->cards()->where('uuid', $uuid)->first();
        if (!$card) {
            return $this->sendRes(translate('card not found'), false, [], [], 400);
        }
        return $this->form($request, $card);
    }


    public function show(Request $request, $uuid)
    {
        $authUser = auth()->user();
        $card = $authUser->cards()->where('uuid', $uuid)->first();
        if (!$card) {
            return $this->sendRes(translate('card not found'), false, [], [], 400);
        }
        return $this->sendRes(translate('card found'), true, $card, [], 200);

    }


    public function delete(Request $request, $uuid)
    {
        $authUser = auth()->user();
        $card = $authUser->cards()->where('uuid', $uuid)->first();
        if (!$card) {
            return $this->sendRes(translate('card not found'), false, [], [], 400);
        }

        $card->delete();
        return $this->sendRes(translate('card deleted successfully'), true);
    }
}
