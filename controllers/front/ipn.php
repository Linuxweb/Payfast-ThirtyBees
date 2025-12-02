<?php
/**
 * ipn.php
 *
 * Copyright (c) 2025 Linuxweb (Pty) Ltd
 * You (being anyone who is not Linuxweb (Pty) Ltd) may download and use this plugin / code in your own website in conjunction with a registered and active PayFast account. If your PayFast account is terminated for any reason, you may not use this plugin / code or part thereof.
 * Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or part thereof in any way.
 * 
 * @author     Ruben Venter (ruben@linuxweb.co.za)
 * @version    1.0.0
 * @date       23/10/2025
 *
 * @link       https://github.com/Linuxweb/Payfast-ThirtyBees/
 */

//Make sure the module is installed on thirtybees
if (!defined('_TB_VERSION_') && !defined('_PS_VERSION_')) {
    http_response_code(400);
    exit;
}

//Main class
class PayfastIpnModuleFrontController extends ModuleFrontController
{
    public $display_header = false;
    public $display_footer = false;
    public $ssl = true;

    public function postProcess()
    {
        // Ensure we always return 200 to PayFast quickly
        header('HTTP/1.1 200 OK');

        // Read POST safely
        $pfData = $_POST;

        if (empty($pfData)) {
            exit;
        }

        // booleans for all the security checks
        $bSigPassed = false;
        $bDomainPassed = false;
        $bComparePassed = false;
        $bServerPassed = false;

        // Get passphrase from configuration 
        $pfPassphrase = Configuration::get('PAYFAST_PASSPHRASE', null);
        if ($pfPassphrase === false) {
            $pfPassphrase = null;
        }

        // Build parameter string (exclude signature) using POST order
        $pfParamString = '';
        foreach ($pfData as $key => $val) {
            if ($key === 'signature') {
                continue;
            }
            $pfParamString .= $key . '=' . urlencode(trim($val)) . '&';
        }
        $pfParamString = rtrim($pfParamString, '&');

        // Append passphrase if set
        $tempParamString = $pfParamString;
        if (!empty($pfPassphrase)) {
            $tempParamString .= '&passphrase=' . urlencode($pfPassphrase);
        }

        // Calculate signature
        $calculatedSignature = md5($tempParamString);
        $receivedSignature = isset($pfData['signature']) ? $pfData['signature'] : '';
        $bSigPassed = ($receivedSignature === $calculatedSignature);

        // Validate remote IP against PayFast hostnames
        $pfMode = Configuration::get('PAYFAST_MODE', 'sandbox'); // default sandbox
        $pfHost = ($pfMode === 'live') ? 'www.payfast.co.za' : 'sandbox.payfast.co.za';

        $validHosts = ['www.payfast.co.za', 'sandbox.payfast.co.za', 'w1w.payfast.co.za', 'w2w.payfast.co.za'];
        $validIps = [];
        foreach ($validHosts as $host) {
            $ips = @gethostbynamel($host);
            if ($ips !== false) {
                $validIps = array_merge($validIps, $ips);
            }
        }
        $validIps = array_unique($validIps);

        $remoteIp = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        $bDomainPassed = in_array($remoteIp, $validIps, true);

        // Compare amount: ensure posted amount matches order amount (you must fetch the order/cart)
        // Example: m_payment_id is our cart/order id; fetch and compare
        $m_payment_id = isset($pfData['m_payment_id']) ? $pfData['m_payment_id'] : null;
        $postedAmount = isset($pfData['amount_gross']) ? (float)$pfData['amount_gross'] : null;

        if ($m_payment_id !== null && $postedAmount !== null) {
            try {
                $cart = new Cart((int)$m_payment_id);
                if (Validate::isLoadedObject($cart)) {
                    $expectedAmount = (float)number_format($cart->getOrderTotal(true, Cart::BOTH), 2, '.', '');
                    $bComparePassed = (abs($expectedAmount - $postedAmount) <= 0.10);
                }
            } catch (Exception $e) {

            }
        }

        // Server-side validate with PayFast validate endpoint
        if (in_array('curl', get_loaded_extensions(), true)) {
            $url = 'https://' . $pfHost . '/eng/query/validate';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $pfParamString);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

            $response = curl_exec($ch);
            $curlErr = curl_error($ch);
            curl_close($ch);

            if ($response !== false && trim($response) === 'VALID') {
                $bServerPassed = true;
            }

        // Final check
        if ($bSigPassed && $bDomainPassed && $bComparePassed && $bServerPassed) {
            
            $cart = new Cart((int)$m_payment_id);
            $customer = new Customer($cart->id_customer);
            $order_id = Order::getOrderByCartId($cart->id);

            if (!$order_id) {
                //Convert currency to zar for order
                $fromCurrency = new Currency(Currency::getIdByIsoCode('ZAR'));
                $toCurrency   = new Currency((int)$cart->id_currency);

                $orderAmount = Tools::convertPriceFull(
                    (float)$postedAmount,
                    $fromCurrency,
                    $toCurrency
                );
                $orderAmount = (float)number_format($orderAmount, 2, '.', '');



                // Create order if it doesn’t exist
                $this->module->validateOrder(
                    $cart->id,
                    //'Payment error',
                    Configuration::get('PS_OS_PAYMENT'), // “Payment accepted” status
                    $orderAmount,
                    $this->module->displayName,
                    'PayFast payment successful',
                    [],
                    null,
                    false,
                    $customer->secure_key
                );
                $order_id = Order::getOrderByCartId($cart->id);
            }
        } 
        exit; // ensure nothing else outputs
    }
}
}
