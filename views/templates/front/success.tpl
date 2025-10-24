{* success.tpl
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
*}

<div class="box">
  <h2>{l s='Payment Successful' mod='payfast'}</h2>
  <p>{l s='Thank you for your purchase! Your payment via PayFast has been successfully processed.' mod='payfast'}</p>

  <p>
    <strong>{l s='Order Reference:' mod='payfast'}</strong> {$order_reference}<br>
    <strong>{l s='Order Total:' mod='payfast'}</strong> {$order_total}
  </p>

  <a class="btn btn-primary" href="{$order_link}">
    {l s='View your order history' mod='payfast'}
  </a>
</div>
