{* failure.tpl
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

<h2>{l s='Payment failed' mod='payfast'}</h2>
<p>
  {l s='Oh no! Your PayFast payment has failed the validation! Try again, and if the problem persists, please contact the administrator of the site.' mod='payfast'}
</p>
<a class="btn btn-primary" href="{$link->getPageLink('order', true)}">
  {l s='Return to checkout' mod='payfast'}
</a>
