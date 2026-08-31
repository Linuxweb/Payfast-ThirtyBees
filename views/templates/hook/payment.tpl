{* payment.tpl
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
*}

<div class="row">
  <div class="col-xs-12">
    <p class="payment_module">
      <a class="payfast" href="{$link->getModuleLink('payfast', 'payment')|escape:'html':'UTF-8'}" title="{$paynow_text|escape:'html':'UTF-8'}">{$paynow_text|escape:'html':'UTF-8'}</a>
    </p>
  </div>
</div>
  
