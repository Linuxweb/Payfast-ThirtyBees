<?php
/**
 * payment.php
 *
 * Copyright (c) 2026 LinuxISP (Pty) Ltd
 * You (being anyone who is not LinuxISP (Pty) Ltd) may download and use this plugin / code in your own website in conjunction with a registered and active PayFast account. If your PayFast account is terminated for any reason, you may not use this plugin / code or part thereof.
 * Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or part thereof in any way.
 * 
 * @author     Ruben Venter (ruben@linuxweb.co.za)
 * @version    1.0.0
 * @date       23/10/2025
 *
 * @link       https://github.com/Linuxweb/Payfast-ThirtyBees/
 */

class PayfastPaymentModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public function initContent()
    {
        parent::initContent();
        $mode = Configuration::get('PAYFAST_MODE');
        $cart = $this->context->cart;
        $customer = new Customer($cart->id_customer);
        $currency = $this->context->currency;

        // Endpoint selection
        if ($mode === 'live') {
            $payfast_base = 'https://www.payfast.co.za';            // live base
            $payfast_process = $payfast_base . '/eng/process';
        } else {
            // default to sandbox/test
            $payfast_base = 'https://sandbox.payfast.co.za';
            $payfast_process = $payfast_base . '/eng/process';
        }

        $merchant_id  = Configuration::get('PAYFAST_MERCHANT_ID');
        $merchant_key = Configuration::get('PAYFAST_MERCHANT_KEY');
        $passphrase   = Configuration::get('PAYFAST_PASSPHRASE');

        if (!$merchant_id || !$merchant_key) {
            Tools::redirect($this->context->link->getModuleLink('payfast', 'failure', ['payfast_error' => 1], true));
        }

        // -- Convert cart currency to ZAR --
        $cartTotal = (float)$cart->getOrderTotal(true, Cart::BOTH);

        $cartCurrency = new Currency((int)$cart->id_currency);
        $zarId = Currency::getIdByIsoCode('ZAR');
        $zarCurrency = new Currency((int)$zarId);

        // PayFast only ever accepts ZAR. If the shop doesn't have a ZAR
        // currency configured at all, new Currency(0) is an empty/invalid
        // object and convertPriceFull() below has nothing sane to convert
        // into - bail out to the failure page instead of sending PayFast a
        // bogus amount.
        if (!$zarId || !Validate::isLoadedObject($zarCurrency)) {
            Tools::redirect($this->context->link->getModuleLink('payfast', 'failure', ['payfast_error' => 2], true));
        }

        $amountZAR = Tools::convertPriceFull($cartTotal, $cartCurrency, $zarCurrency);
        $amountZAR = number_format((float)$amountZAR, 2, '.', '');

        // Return URL with cart ID
        $return_url = $this->context->link->getModuleLink('payfast', 'success', ['id_cart' => $cart->id], true);
        $notify_url = $this->context->link->getModuleLink('payfast', 'ipn', [], true);
        $cancel_url = $this->context->link->getModuleLink('payfast', 'cancel', [], true);

        // Build data array
        $data = [
            'merchant_id'    => $merchant_id,
            'merchant_key'   => $merchant_key,
            'return_url'     => $return_url,
            'cancel_url'     => $cancel_url,
            'notify_url'     => $notify_url,
            'name_first'     => $customer->firstname,
            'name_last'      => $customer->lastname,
            'email_address'  => $customer->email,
            'm_payment_id'   => $cart->id,
            'amount'         => $amountZAR,
            'item_name'      => 'Order #' . $cart->id,
        ];

        // Generate signature
        $pfOutput = '';
        foreach ($data as $key => $val) {
            if ($val !== '') {
                $pfOutput .= $key . '=' . urlencode(trim($val)) . '&';
            }
        }
        $pfOutput = rtrim($pfOutput, '&');
        if (!empty($passphrase)) {
            $pfOutput .= '&passphrase=' . urlencode($passphrase);
        }
        $data['signature'] = md5($pfOutput);

        // Render auto-submit form instead of redirect
        echo '<form id="payfast_form" action="'.$payfast_process.'" method="post">';
        foreach ($data as $key => $val) {
            echo '<input type="hidden" name="'.htmlspecialchars($key).'" value="'.htmlspecialchars($val).'">';
        }
        echo '</form>';
        echo '<script>document.getElementById("payfast_form").submit();</script>';
        exit;
    }
}
