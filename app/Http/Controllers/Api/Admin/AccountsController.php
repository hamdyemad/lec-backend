<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CityResource;
use App\Models\Account;
use App\Models\City;
use App\Models\Country;
use App\Models\ShippingMethod;
use App\Models\Translation;
use App\Traits\Res;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AccountsController extends Controller
{
    use Res;

    public function index(Request $request)
    {
        $accounts = Account::latest();
        $per_page = $request->get('per_page', 12);
        $keyword = $request->get('keyword');


        if($keyword) {
            $accounts = $accounts->where('name', 'like', "%$keyword%")
                ->orWhere('number', 'like', "%$keyword%");
        }


        $accounts = $accounts->paginate($per_page);

        return $this->sendRes('all accounts', true, $accounts);
    }




    public function form(Request $request, $account = null)
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'number' => ['required', 'string', 'max:255'],
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->errorResponse($validator);
        }

        $data = [
            'name' => $request->name,
            'number' => $request->number,
        ];

        if($account) {
            $account->update($data);
            $message = translate('account updated success');
        } else {
            $data['uuid'] = \Str::uuid();
            $account = Account::create($data);
            $message = translate('account created success');
        }

        return $this->sendRes($message, true, [], [], 200);
    }

    public function store(Request $request)
    {
        return $this->form($request);
    }

    public function edit(Request $request, $uuid)
    {
        $account = Account::where('uuid', $uuid)->first();
        if(!$account) {
            return $this->sendRes(translate('account not found'), false, [], [], 400);
        }
        return $this->form($request, $account);
    }

    public function show(Request $request, $uuid) {
        $account = Account::where('uuid', $uuid)->first();
        if(!$account) {
            return $this->sendRes(translate('account not found'), false, [], [], 400);
        }
        return $this->sendRes(translate('account found'), true, $account);
    }

    public function delete(Request $request, $uuid) {
        $account = Account::where('uuid', $uuid)->first();
        if(!$account) {
            return $this->sendRes(translate('account not found'), false, [], [], 400);
        }
        $account->delete();
        return $this->sendRes(translate('account deleted successfully'), true, [], [], 200);
    }

}
