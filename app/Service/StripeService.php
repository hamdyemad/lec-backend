<?php

namespace App\Service;

use Stripe\Stripe;
use Stripe\PaymentIntent;

class StripeService
{
    public $stripe;
    public function __construct()
    {
        $this->stripe = new \Stripe\StripeClient(env('STRIPE_SECRET_KEY'));
    }

    public function listPaymentIntent()
    {
        $paymentIntents = $this->stripe->paymentIntents->all(['limit' => 3]);
        return $paymentIntents;
    }

    public function createPaymentIntent($amount = 3000, $currency = 'usd')
    {
        $paymentIntent = $this->stripe->paymentIntents->create([
            'payment_method_types' => ['card'],
            'amount' => $amount * 100,
            'currency' => $currency,
        ]);
        return $paymentIntent;
    }

    public function confirmPaymentIntent($paymentIntentId, $cardId)
    {
        $paymentIntent = $this->stripe->paymentIntents->confirm(
            $paymentIntentId,
            [
                'payment_method' => 'pm_card_visa',
                'return_url' => 'https://www.example.com',
            ]
        );

        return $paymentIntent;
    }


    public function capturePaymentIntent($paymentIntentId)
    {
        $paymentIntent = $this->stripe->paymentIntents->capture($paymentIntentId, []);

        return $paymentIntent;
    }


    // Customers
    public function createCustomer()
    {
        $customer = $this->stripe->customers->create([
            'name' => 'Jenny Rosen',
            'email' => 'jennyrosen@example.com',
        ]);
        return $customer;
    }

    // Customers Cards
    public function createCard($customerId)
    {
        $paymentSource = $this->stripe->customers->createSource(
            $customerId,
            ['source' => 'tok_visa']
        );
        return $paymentSource;
    }

}
