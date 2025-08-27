<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\Cart;
use App\Models\Feature;
use App\Models\FeatureType;
use App\Models\User;
use App\Models\UserType;
use App\Service\PushNotificaion;
use App\Traits\FileUploads;
use App\Traits\Res;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class NotificationController extends Controller
{
    use Res, FileUploads;

    public function __construct() {}

    public function index(Request $request)
    {
        $auth = auth()->user();
        $per_page = request('per_page') ?? 12;
        $notifications = $auth->notifications()
            ->latest()
            ->paginate($per_page);
        // Group the notifications by human-readable date
        $grouped = $notifications->getCollection()->groupBy(function ($notification) {
            $date = \Carbon\Carbon::parse($notification->created_at);
            if ($date->isToday()) {
                return translate('today');
            } elseif ($date->isYesterday()) {
                return translate('yesterday');
            } else {
                return $date->format('Y-m-d'); // Or ->format('F j, Y') for nicer display
            }
        });

        // Replace the collection with grouped collection (optional depending on your usage)
        $notifications->setCollection($grouped);

        return $this->sendRes(translate('all notifications'), true, $notifications);
    }
}
