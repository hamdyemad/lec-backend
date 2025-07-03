<?php

namespace App\Http\Controllers\Api;

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
}
