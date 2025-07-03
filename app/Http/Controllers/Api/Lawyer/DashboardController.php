<?php

namespace App\Http\Controllers\Api\Lawyer;

use App\Http\Controllers\Controller;
use App\Http\Resources\CaseSessionResource;
use App\Http\Resources\CaseSessionStatusResource;
use App\Http\Resources\CityResource;
use App\Http\Resources\ServiceResource;
use App\Http\Resources\StatusResource;
use App\Models\Account;
use App\Models\CaseSession;
use App\Models\CaseSessionStatus;
use App\Models\CaseStatus;
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

    public function index(Request $request)
    {

        $statuses = CaseSessionStatus::all();



        foreach($statuses as $status) {
            $sessions_count = CaseSession::where('status_id', $status->id)
            ->where('lawyer_id', auth()->id())->count();
            $status['sessions_count'] = $sessions_count;

        }

        $latest_sessions = CaseSession::with('case.client', 'status')
            ->where('lawyer_id', auth()->id())
            ->latest()
            ->take(10)
            ->get();
        $latest_sessions = CaseSessionResource::collection($latest_sessions);

        return $latest_sessions;

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
