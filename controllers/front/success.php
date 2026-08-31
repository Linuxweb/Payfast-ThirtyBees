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

class PayfastSuccessModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public function initContent()
    {
        parent::initContent();

        $cartId = (int)Tools::getValue('id_cart');
        if (!$cartId) {
            die('Missing cart ID.');
        }

        $orderId = Order::getOrderByCartId($cartId);

        if ($orderId) {
            // Order exists → redirect. Use the order's own secure_key (set from
            // its customer's secure_key at creation time in validateOrder()),
            // not $this->context->customer->secure_key - the current session's
            // customer object isn't guaranteed to still be the order's owner by
            // the time the browser lands back here from PayFast (e.g. the
            // session cookie failing to round-trip through the redirect), and
            // in that case the confirmation controller's key check would
            // reject a perfectly legitimate order.
            $order = new Order((int)$orderId);
            Tools::redirect(
                'index.php?controller=order-confirmation&id_cart=' . $cartId .
                '&id_module=' . $this->module->id .
                '&id_order=' . $orderId .
                '&key=' . $order->secure_key
            );
        }

        // Order not yet created — show "processing" page. This is a distinct
        // state from a confirmed order, so it gets its own template rather
        // than reusing success.tpl (which expects order_reference/order_total/
        // order_link - none of which exist yet here).
        $this->context->smarty->assign([
            'message' => 'Payment received. Your order is being processed and will appear in your order history shortly.',
        ]);

        $this->setTemplate('processing.tpl');
    }
}
