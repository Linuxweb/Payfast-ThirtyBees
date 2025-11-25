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

// class PayfastSuccessModuleFrontController extends ModuleFrontController
// {
//     public $ssl = true;

//     public function initContent()
//     {
//         parent::initContent();

//         $cart_id = (int)Tools::getValue('id_cart');
//         $cart = new Cart($cart_id);
//         $customer = new Customer($cart->id_customer);

//         if (!$cart->id || !$customer->id) {
//             Tools::redirect($this->context->link->getPageLink('order', true, null, 'step=1'));
//         }

//         $order_id = Order::getOrderByCartId($cart->id);
//         $order = $order_id ? new Order($order_id) : null;

//         if ($order && (int)$order->current_state === (int)Configuration::get('PS_OS_PAYMENT')) {
//             // Payment accepted
//             $this->context->smarty->assign([
//                 'order_reference' => $order->reference,
//                 'order_total' => Tools::displayPrice($cart->getOrderTotal(true, Cart::BOTH), $this->context->currency),
//                 'order_link' => '../../order-history',
//             ]);
//             $this->setTemplate('success.tpl');
//         } else {
//             // Payment not yet validated
//             $this->context->smarty->assign([
//                 'cart_id' => $cart->id,
//                 'retry_link' => '../../order',
//             ]);
//             $this->setTemplate('failure.tpl');
//         }
//     }
// }

class PayfastSuccessModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public function initContent()
    {
        // Do not call parent::initContent() yet to avoid loading header/footer if we redirect

        $cart_id = (int)Tools::getValue('id_cart');
        $cart = new Cart($cart_id);
        $customer = new Customer($cart->id_customer);

        // Security checks
        if (!$cart->id || !$customer->id) {
            Tools::redirect($this->context->link->getPageLink('order', true, null, 'step=1'));
        }

        $order_id = Order::getOrderByCartId($cart->id);
        $order = $order_id ? new Order($order_id) : null;

        // Check if payment was successful
        if ($order && (int)$order->current_state === (int)Configuration::get('PS_OS_PAYMENT')) {
            
            // FIX: Redirect to standard Order Confirmation Controller
            // This triggers the 'displayOrderConfirmation' hook that GTM needs.
            Tools::redirect(
                'index.php?controller=order-confirmation&id_cart=' . (int)$cart->id .
                '&id_module=' . (int)$this->module->id .
                '&id_order=' . (int)$order->id .
                '&key=' . $customer->secure_key
            );

        } else {
            // Payment not valid yet, show failure page
            parent::initContent();
            $this->context->smarty->assign([
                'cart_id' => $cart->id,
                'retry_link' => $this->context->link->getPageLink('order', true),
            ]);
            $this->setTemplate('failure.tpl');
        }
    }
}
