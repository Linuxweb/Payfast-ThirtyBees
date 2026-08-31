<?php
/**
 * ipn.php
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

    /**
     * Append a line to modules/payfast/log.txt when the "Debug to log
     * server-to-server communication" setting is enabled. The directory
     * already ships a .htaccess denying access to that file. This is the
     * only place in the module that touches the log, so PAYFAST_LOGS
     * actually does what the admin screen says it does.
     */
    private function logDebug($message)
    {
        if (!Configuration::get('PAYFAST_LOGS')) {
            return;
        }

        $path = dirname(__FILE__) . '/../../log.txt';
        $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . "\n";
        @file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
    }

    public function postProcess()
    {
        // Ensure we always return 200 to PayFast quickly
        header('HTTP/1.1 200 OK');

        // Read POST safely
        $pfData = $_POST;

        if (empty($pfData)) {
            exit;
        }

        $remoteIp = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        $this->logDebug('IPN received from ' . $remoteIp . ': ' . json_encode($pfData));

        // Security check flags
        $bSigPassed    = false;
        $bDomainPassed = false;
        $bComparePassed = false;
        $bServerPassed = false;

        // ----------------------------------------------------------------
        // 1. Signature check
        // ----------------------------------------------------------------
        $pfPassphrase = Configuration::get('PAYFAST_PASSPHRASE');
        if ($pfPassphrase === false) {
            $pfPassphrase = null;
        }

        $pfParamString = '';
        foreach ($pfData as $key => $val) {
            if ($key === 'signature') {
                continue;
            }
            $pfParamString .= $key . '=' . urlencode(trim($val)) . '&';
        }
        $pfParamString = rtrim($pfParamString, '&');

        $tempParamString = $pfParamString;
        if (!empty($pfPassphrase)) {
            $tempParamString .= '&passphrase=' . urlencode($pfPassphrase);
        }

        $calculatedSignature = md5($tempParamString);
        $receivedSignature   = isset($pfData['signature']) ? $pfData['signature'] : '';
        $bSigPassed = ($receivedSignature === $calculatedSignature);

        $this->logDebug('Signature check: ' . ($bSigPassed ? 'PASSED' : 'FAILED') .
            ' (received=' . $receivedSignature . ', calculated=' . $calculatedSignature . ')');

        // ----------------------------------------------------------------
        // 2. Domain / IP check
        //
        // This is PayFast's own documented approach (resolve their known
        // hostnames and compare against REMOTE_ADDR), but it's also a
        // documented source of false negatives in production: any site
        // behind a reverse proxy or CDN sees the proxy's IP, not PayFast's,
        // and PayFast's IPs have changed under merchants before without the
        // DNS-resolved list catching it in time (see e.g. the WooCommerce
        // PayFast plugin's own "Bad source IP address" reports, whose fix
        // was to stop treating this check as blocking). Signature
        // verification (above) plus the server-side /eng/query/validate
        // round trip (below) are the two checks that actually prove the
        // notification came from PayFast and wasn't tampered with; this IP
        // check adds defense in depth but must not be the thing that makes
        // a real payment silently vanish. So: compute and log it, but don't
        // let it block order creation on its own.
        // ----------------------------------------------------------------
        $pfMode = Configuration::get('PAYFAST_MODE');
        $pfHost = ($pfMode === 'live') ? 'www.payfast.co.za' : 'sandbox.payfast.co.za';

        $validHosts = ['www.payfast.co.za', 'sandbox.payfast.co.za', 'w1w.payfast.co.za', 'w2w.payfast.co.za'];
        $validIps   = [];
        foreach ($validHosts as $host) {
            $ips = @gethostbynamel($host);
            if ($ips !== false) {
                $validIps = array_merge($validIps, $ips);
            }
        }
        $validIps = array_unique($validIps);

        $bDomainPassed = in_array($remoteIp, $validIps, true);

        if (!$bDomainPassed) {
            $this->logDebug('Domain/IP check: did not match known PayFast IPs (remote=' . $remoteIp .
                ', resolved=' . implode(',', $validIps) . '). Not blocking on this alone - ' .
                'see comment above. If this logs on every IPN, the site is very likely behind a ' .
                'proxy/CDN and REMOTE_ADDR is not the real client IP.');
        } else {
            $this->logDebug('Domain/IP check: PASSED (remote=' . $remoteIp . ')');
        }

        // ----------------------------------------------------------------
        // 3. Amount comparison
        // getOrderTotal() returns the total in the cart's own currency (e.g.
        // USD), not always ZAR. PayFast's amount_gross is always ZAR (it's
        // the only currency PayFast accepts), so convert the cart total to
        // ZAR the same way payment.php did before comparing.
        // ----------------------------------------------------------------
        $m_payment_id = isset($pfData['m_payment_id']) ? $pfData['m_payment_id'] : null;
        $postedAmount = isset($pfData['amount_gross']) ? (float)$pfData['amount_gross'] : null;

        $cartTotalOwnCurrency = null;

        if ($m_payment_id !== null && $postedAmount !== null) {
            try {
                $cart = new Cart((int)$m_payment_id);
                if (Validate::isLoadedObject($cart)) {
                    $cartCurrency = new Currency((int)$cart->id_currency);
                    $zarCurrency  = new Currency((int)Currency::getIdByIsoCode('ZAR'));

                    if (!Validate::isLoadedObject($zarCurrency)) {
                        $this->logDebug('Amount check: FAILED - store has no ZAR currency configured, cannot compare.');
                    } else {
                        // getOrderTotal() prices the cart using the active Context
                        // currency, not $cart->id_currency. This IPN request comes
                        // straight from PayFast's server with no customer session,
                        // so Context defaults to the shop's default currency (ZAR)
                        // unless we point it at the cart's own currency here. Without
                        // this, the cart re-prices at conversion rate 1 (i.e. the
                        // raw, unconverted default-currency price).
                        $this->context->currency = $cartCurrency;

                        $cartTotalOwnCurrency = (float)$cart->getOrderTotal(true, Cart::BOTH);

                        $cartTotalZAR = (float)number_format(
                            Tools::convertPriceFull($cartTotalOwnCurrency, $cartCurrency, $zarCurrency),
                            2, '.', ''
                        );

                        $bComparePassed = (abs($cartTotalZAR - $postedAmount) <= 0.50);

                        $this->logDebug('Amount check: ' . ($bComparePassed ? 'PASSED' : 'FAILED') .
                            ' (cart total in ZAR=' . $cartTotalZAR . ', posted amount_gross=' . $postedAmount . ')');
                    }
                } else {
                    $this->logDebug('Amount check: FAILED - cart ' . (int)$m_payment_id . ' could not be loaded.');
                }
            } catch (Exception $e) {
                // Comparison failed; $bComparePassed stays false and the IPN is rejected below.
                $this->logDebug('Amount check: FAILED with exception: ' . $e->getMessage());
            }
        } else {
            $this->logDebug('Amount check: FAILED - missing m_payment_id or amount_gross in IPN payload.');
        }

        // ----------------------------------------------------------------
        // 4. Server-side validation with PayFast
        // ----------------------------------------------------------------
        if (in_array('curl', get_loaded_extensions(), true)) {
            $url = 'https://' . $pfHost . '/eng/query/validate';
            $ch  = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $pfParamString);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

            $response = curl_exec($ch);
            curl_close($ch);

            if ($response !== false && trim($response) === 'VALID') {
                $bServerPassed = true;
            }

            $this->logDebug('Server validate (' . $url . '): response=' . var_export($response, true) .
                ' -> ' . ($bServerPassed ? 'PASSED' : 'FAILED'));
        } else {
            $this->logDebug('Server validate: FAILED - curl extension not available.');
        }

        // ----------------------------------------------------------------
        // 5. Create order if the checks that actually prove authenticity pass
        //
        // Signature + amount match + PayFast's own server confirming the
        // data are the three checks that establish this notification is
        // genuine and untampered. The IP/domain check is logged above for
        // visibility but intentionally not required here - see the comment
        // in step 2.
        // ----------------------------------------------------------------
        if ($bSigPassed && $bComparePassed && $bServerPassed) {

            $cart     = new Cart((int)$m_payment_id);
            $customer = new Customer($cart->id_customer);
            $order_id = Order::getOrderByCartId($cart->id);

            if (!$order_id) {
                // Record the order in the cart's own currency (e.g. USD), not
                // in ZAR. ZAR is only what PayFast, as a gateway, requires -
                // it must never become the order's currency of record, or
                // ThirtyBees will re-price the order's products in ZAR at a
                // 1:1 rate (the unconverted base price) instead of showing
                // what the customer actually agreed to pay.
                $orderCurrencyId = (int)$cart->id_currency;
                $orderAmount = (float)number_format($cartTotalOwnCurrency, 2, '.', '');

                try {
                    $this->module->validateOrder(
                        $cart->id,
                        Configuration::get('PS_OS_PAYMENT'),
                        $orderAmount,
                        $this->module->displayName,
                        'PayFast payment successful',
                        [],
                        $orderCurrencyId,
                        false,
                        $customer->secure_key
                    );
                } catch (Exception $e) {
                    // Order creation failed; $order_id stays unset/false below.
                    $this->logDebug('validateOrder() threw: ' . $e->getMessage());
                }

                $order_id = Order::getOrderByCartId($cart->id);
                $this->logDebug('Order creation ' . ($order_id ? 'SUCCEEDED (id_order=' . $order_id . ')' : 'FAILED') .
                    ' for cart ' . $cart->id);
            } else {
                $this->logDebug('Order already exists for cart ' . $cart->id . ' (id_order=' . $order_id . '), skipping.');
            }
        } else {
            $this->logDebug('IPN rejected - sig=' . ($bSigPassed ? 'pass' : 'FAIL') .
                ' amount=' . ($bComparePassed ? 'pass' : 'FAIL') .
                ' server=' . ($bServerPassed ? 'pass' : 'FAIL') .
                ' (domain=' . ($bDomainPassed ? 'pass' : 'fail') . ', non-blocking)');
        }
        exit;
    }
}
