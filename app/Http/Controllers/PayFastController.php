<?php

namespace App\Http\Controllers;

use App\Contracts\PayFastCheckoutServiceInterface;
use App\Http\Requests\Payments\InitiateOneTimePaymentRequest;
use App\Http\Requests\Payments\InitiateSubscriptionPaymentRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PayFastController extends Controller
{
    public function __construct(
        private readonly PayFastCheckoutServiceInterface $checkout
    ) {}

    public function initiateOneTime(InitiateOneTimePaymentRequest $request): Response
    {
        $result = $this->checkout->initiateOneTimePayment(
            $request->validated(),
            $request->user()->id
        );

        return response($result['html'], 200)->header('Content-Type', 'text/html');
    }

    public function initiateSubscription(InitiateSubscriptionPaymentRequest $request): Response
    {
        $result = $this->checkout->initiateSubscriptionPayment(
            $request->validated(),
            $request->user()->id
        );

        return response($result['html'], 200)->header('Content-Type', 'text/html');
    }

    public function itn(Request $request): Response
    {
        $result = $this->checkout->processItn($request->all(), $request->getContent());

        logger()->info('payfast.itn', $result);

        if (! ($result['accepted'] ?? false)) {
            $status = $result['reason'] === 'invalid_signature' ? 400 : 422;

            return response($result['reason'], $status);
        }

        return response($result['duplicate'] ? 'duplicate' : 'ok', 200);
    }

    /**
     * PayFast's browser return/cancel redirects are navigational only — the
     * user's browser landing here proves nothing about whether payment
     * actually succeeded. Only a validated ITN (see itn() above) is ever
     * allowed to change payment/subscription state.
     */
    public function handleReturn(): Response
    {
        return response('Payment completed');
    }

    public function handleCancel(): Response
    {
        return response('Payment canceled');
    }
}
