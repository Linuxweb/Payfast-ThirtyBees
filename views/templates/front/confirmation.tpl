{* confirmation.tpl
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

{extends file=$layout}

{block name="page_content"}
  <h1>{l s='Payment Successful' mod='payfast'}</h1>
  <p>{l s='Thank you for your order. Your PayFast payment has been successfully processed.' mod='payfast'}</p>
  <p>{l s='Order reference:' mod='payfast'} {$order_reference}</p>

  <a href="{$order_history_url}" class="btn btn-primary">
    {l s='Go to Order History' mod='payfast'}
  </a>

  <script>
    // Auto-redirect after 2 seconds
    setTimeout(function() {
      window.location.href = '{$order_history_url}';
    }, 2000);
  </script>
{/block}
