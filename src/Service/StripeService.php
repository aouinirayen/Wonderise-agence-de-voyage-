<?php

namespace App\Service;

use Stripe\Stripe;
use Stripe\PaymentIntent;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class StripeService
{
    private $params;
    private $secretKey;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
        $this->secretKey = $this->params->get('stripe_secret_key');
        Stripe::setApiKey($this->secretKey);
    }

    public function createPaymentIntent(float $amount, string $currency = 'eur'): array
    {
        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => $amount * 100, // Stripe utilise les centimes
                'currency' => $currency,
                'payment_method_types' => ['card'],
            ]);

            return [
                'clientSecret' => $paymentIntent->client_secret,
                'id' => $paymentIntent->id,
            ];
        } catch (\Exception $e) {
            throw new \Exception('Erreur lors de la création du paiement : ' . $e->getMessage());
        }
    }
}
