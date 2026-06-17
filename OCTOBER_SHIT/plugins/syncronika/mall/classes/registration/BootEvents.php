<?php

namespace Syncronika\Mall\Classes\Registration;

use Syncronika\Mall\Classes\Events\MailingEventHandler;
use RainLab\User\Facades\Auth;
use Event;

trait BootEvents
{
    protected function registerEvents()
    {
        Event::listen('mall.order.afterCreate', function ($order, $cart) {
            $data = post();

            if (array_key_exists('flag_company', $data) && array_key_exists('fiscal_code', $data)) {
                $order->flag_company = $data['flag_company'];
                $order->fiscal_code = $data['fiscal_code'];
                $order->save();
            } else {
                $customer = $cart->customer;
                if (!$customer) {
                    return;
                }

                $order->flag_company = strlen($customer->company_name) > 0 ? 1 : 0;
                $order->fiscal_code = $customer->fiscal_code;
                $order->save();
            }
        });

        Event::listen('mall.checkout.succeeded', function ($paymentResult) {
            Auth::logout();
        });
    }

    /** Se dovessi modificare il comportamento dell'invio email intervieni in MailingEventHandler registrando una tua versione */
    protected function registerGenericEvents()
    {
        $this->app->bind('MailingEventHandler', MailingEventHandler::class);
        //\Illuminate\Support\Facades\Event::subscribe('MailingEventHandler');
    }
}
