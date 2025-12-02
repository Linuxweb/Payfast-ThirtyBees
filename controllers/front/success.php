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
            // Order exists → redirect
            Tools::redirect(
                'index.php?controller=order-confirmation&id_cart=' . $cartId .
                '&id_module=' . $this->module->id .
                '&id_order=' . $orderId .
                '&key=' . $this->context->customer->secure_key
            );
        }

        // Order not yet created — show "processing" page
        $this->context->smarty->assign([
            'message' => 'Payment received. Order is being processed.',
        ]);

        $this->setTemplate('module:payfast/views/templates/front/success.tpl');
    }
}
