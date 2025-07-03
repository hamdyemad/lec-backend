<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CityResource;
use App\Http\Resources\ServiceResource;
use App\Http\Resources\StatusResource;
use App\Models\Account;
use App\Models\City;
use App\Models\Country;
use App\Models\Service;
use App\Models\ShippingMethod;
use App\Models\Status;
use App\Models\Translation;
use App\Models\User;
use App\Traits\Res;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DashboardController extends Controller
{
    use Res;
    public $lawyer_type_id = 2;

    public function index(Request $request)
    {

        $statuses = Status::withCount('services')->get()->map(function ($status) {
            $status->name = $status->translate('name');
            return $status;
        });
        $all_lawyers_count = User::where('user_type_id', $this->lawyer_type_id)->count();
        $available_lawyers_count = 10;
        $works_lawyers_count = 5;
        $unworks_lawyers_count = 8;
        $latest_orders = Service::with('client', 'status')->latest()->take(10)->get();
        $latest_orders = ServiceResource::collection($latest_orders);
        $data = [
            'statuses' => $statuses,
            'all_lawyers_count' => $all_lawyers_count,
            'available_lawyers_count' => $available_lawyers_count,
            'works_lawyers_count' => $works_lawyers_count,
            'unworks_lawyers_count' => $unworks_lawyers_count,
            'latest_orders' => $latest_orders,
        ];
        return $this->sendRes('dashboard data', true, $data);
    }

}
