{* processing.tpl
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

<meta http-equiv="refresh" content="5">

<div class="box">
  <h2>{l s='Payment received' mod='payfast'}</h2>
  <p>{if isset($message)}{$message|escape:'html':'UTF-8'}{else}{l s='Payment received. Your order is being processed and will appear in your order history shortly.' mod='payfast'}{/if}</p>
  <p>{l s='This page will refresh automatically. You do not need to pay again or reload manually.' mod='payfast'}</p>
</div>
