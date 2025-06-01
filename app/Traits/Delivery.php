<?php

namespace App\Traits;

use App\Models\UserLocation;
use Illuminate\Support\Facades\DB;

trait Delivery
{
    public function delivery_cost($distance) {
        $cost_min = settings("cost_min");
        $kilo_price = settings("kilo_price");
        $tax = settings("tax");
        if($distance < 3) {
            $total_kilo_price = $cost_min;
        } else {
            $diff_3_killos = $distance - 3;
            $total_kilo_price = $cost_min + ($kilo_price * $diff_3_killos);
        }
        $total = $total_kilo_price + ($total_kilo_price * ($tax / 100));

        return [
            'total_kilo_price' => $total_kilo_price,
            'tax' => $tax,
            'total_kilo_price_after_tax' => $total,
        ];
    }

    public function getFullDeliveryPriceFromLocations($client_location, $sellers_ids) {

        // Get Nearest Location Of Seller To The Point one of Client

        // return $sellers_ids;
        $nearestSeller = $this->getNearestSellerLocation($client_location, $sellers_ids);
        if($nearestSeller) {
            // Get The Delivery Cost By First Distance
            $total_kilo_price = $this->delivery_cost($nearestSeller['distance']);

            // Get Nearest Location Of Seller To The Point Two of Seller
            $next_distance = $this->getNearestSellerLocation($client_location, $sellers_ids, $nearestSeller['location']);
            if($next_distance) {

                $total_kilo_price['total_kilo_price'] += (settings('kilo_price') * $next_distance['distance']);

                $total_kilo_price['total_kilo_price_after_tax'] = $total_kilo_price['total_kilo_price'] + ($total_kilo_price['total_kilo_price'] * ($total_kilo_price['tax'] / 100 ));
                $total_kilo_price['total_kilo_price_after_tax'] = round($total_kilo_price['total_kilo_price_after_tax'], 2);
                $total_kilo_price['total_kilo_price'] = round($total_kilo_price['total_kilo_price'], 2);


            }
            return $total_kilo_price;
        }


    }


}
