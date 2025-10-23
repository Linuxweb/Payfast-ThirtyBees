<?php
/**
 * payfast.php
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

if (!defined('_TB_VERSION_') && !defined('_PS_VERSION_')) {
    exit;
}

class Payfast extends PaymentModule
{
    private const LEFT_COLUMN  = 0;
    private const RIGHT_COLUMN = 1;
    private const FOOTER       = 2;
    private const DISABLE      = -1;
    const CHECKED = 'checked';
    const PFLINK = 'pf_link';
    const PAYFASTURL = 'https://payfast.io';

    public function __construct()
    {
        $this->name = 'payfast';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->author = 'Linuxweb';
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('PayFast (South Africa)');
        $this->description = $this->l('Accept payments via PayFast.');
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('payment')
            && $this->registerHook('paymentReturn')
            && $this->registerHook('header')
            && $this->initializeConfig();
    }

    public function uninstall()
    {
        return parent::uninstall() 
            && Configuration::deleteByName('payment')
            && Configuration::deleteByName('paymentReturn')
            && Configuration::deleteByName('header')
            && $this->deleteConfig();

    }

    private function initializeConfig()
    {
        Configuration::updateValue('PAYFAST_MODE', 'test');
        Configuration::updateValue('PAYFAST_MERCHANT_ID', '');
        Configuration::updateValue('PAYFAST_MERCHANT_KEY', '');
        Configuration::updateValue('PAYFAST_PASSPHRASE', '');
        Configuration::updateValue('PAYFAST_SPLIT_PAYMENT_ENABLED', 0);
        Configuration::updateValue('PAYFAST_SPLIT_PAYMENT_MERCHANT_ID', '');
        Configuration::updateValue('PAYFAST_SPLIT_PAYMENT_AMOUNT', '');
        Configuration::updateValue('PAYFAST_SPLIT_PAYMENT_PERCENTAGE', '');
        Configuration::updateValue('PAYFAST_SPLIT_PAYMENT_MIN', '');
        Configuration::updateValue('PAYFAST_SPLIT_PAYMENT_MAX', '');
        Configuration::updateValue('PAYFAST_LOGS', 0);
        Configuration::updateValue('PAYFAST_PAYNOW_TEXT', 'Pay via PayFast');
        Configuration::updateValue('PAYFAST_LOGO_POSITION', 'left');
        return true;
    }

    private function deleteConfig()
    {
        $keys = [
            'PAYFAST_MODE', 'PAYFAST_MERCHANT_ID', 'PAYFAST_MERCHANT_KEY',
            'PAYFAST_PASSPHRASE', 'PAYFAST_SPLIT_PAYMENT_ENABLED',
            'PAYFAST_SPLIT_PAYMENT_MERCHANT_ID', 'PAYFAST_SPLIT_PAYMENT_AMOUNT',
            'PAYFAST_SPLIT_PAYMENT_PERCENTAGE', 'PAYFAST_SPLIT_PAYMENT_MIN',
            'PAYFAST_SPLIT_PAYMENT_MAX', 'PAYFAST_LOGS', 'PAYFAST_PAYNOW_TEXT',
            'PAYFAST_LOGO_POSITION'
        ];

        foreach ($keys as $key) {
            Configuration::deleteByName($key);
        }
        return true;
    }
    
    public function getContent()
        {
            $output = '';

            if (Tools::isSubmit('submitPayfast')) {
                Configuration::updateValue('PAYFAST_MODE', Tools::getValue('payfast_mode'));
                Configuration::updateValue('PAYFAST_MERCHANT_ID', Tools::getValue('payfast_merchant_id'));
                Configuration::updateValue('PAYFAST_MERCHANT_KEY', Tools::getValue('payfast_merchant_key'));
                Configuration::updateValue('PAYFAST_PASSPHRASE', Tools::getValue('payfast_passphrase'));
                Configuration::updateValue('PAYFAST_SPLIT_PAYMENT_ENABLED', Tools::getValue('payfast_split_payments_enabled'));
                Configuration::updateValue('PAYFAST_SPLIT_PAYMENT_MERCHANT_ID', Tools::getValue('payfast_split_payment_merchant_id'));
                Configuration::updateValue('PAYFAST_SPLIT_PAYMENT_AMOUNT', Tools::getValue('payfast_split_payment_amount'));
                Configuration::updateValue('PAYFAST_SPLIT_PAYMENT_PERCENTAGE', Tools::getValue('payfast_split_payment_percentage'));
                Configuration::updateValue('PAYFAST_SPLIT_PAYMENT_MIN', Tools::getValue('payfast_split_payment_min'));
                Configuration::updateValue('PAYFAST_SPLIT_PAYMENT_MAX', Tools::getValue('payfast_split_payment_max'));
                Configuration::updateValue('PAYFAST_LOGS', Tools::getValue('payfast_logs'));
                Configuration::updateValue('PAYFAST_PAYNOW_TEXT', Tools::getValue('payfast_paynow_text'));
                Configuration::updateValue('PAYFAST_LOGO_POSITION', Tools::getValue('logo_position'));

                foreach (array('displayLeftColumn', 'displayRightColumn', 'displayFooter') as $hookName) {
                    if ($this->isRegisteredInHook($hookName)) {
                        $this->unregisterHook($hookName);
                    }
                }
                if (Tools::getValue('logo_position') == self::LEFT_COLUMN) {
                    $this->registerHook('displayLeftColumn');
                } elseif (Tools::getValue('logo_position') == self::RIGHT_COLUMN) {
                    $this->registerHook('displayRightColumn');
                } elseif (Tools::getValue('logo_position') == self::FOOTER) {
                    $this->registerHook('displayFooter');
                }
                if (method_exists('Tools', 'clearSmartyCache')) {
                    Tools::clearSmartyCache();
                }

                $output .= $this->displayConfirmation($this->l('Settings updated successfully.'));
            }

            $blockPositionList = array(
                self::DISABLE      => $this->l('Disable'),
                self::LEFT_COLUMN  => $this->l('Left Column'),
                self::RIGHT_COLUMN => $this->l('Right Column'),
                self::FOOTER       => $this->l('Footer')
            );

            if ($this->isRegisteredInHook('displayLeftColumn')) {
                $currentLogoBlockPosition = self::LEFT_COLUMN;
            } elseif ($this->isRegisteredInHook('displayRightColumn')) {
                $currentLogoBlockPosition = self::RIGHT_COLUMN;
            } elseif ($this->isRegisteredInHook('displayFooter')) {
                $currentLogoBlockPosition = self::FOOTER;
            } else {
                $currentLogoBlockPosition = -1;
            }

            $html = '
            <head>
                <link href="' . __PS_BASE_URI__ . 'modules/payfast/views/css/payfast_config.css" rel="stylesheet" type="text/css" />
            </head>
            <form action="' . $_SERVER['REQUEST_URI'] . '" method="post">
            <div class="pf__main--section" id="main__section">
                <a href="https://payfast.io" target="_blank" rel="nofollow">
                    <img class="pf__logo" src="' . __PS_BASE_URI__ . 'modules/payfast/logo.svg"
                    alt="Payfast" border="0" style="width: auto; height: 60px;"/>
                </a>
                <span class="main__section--header">Settings:</span>
                <!-- Insert your entire HTML layout here as shown in your example -->
            ';

            // for brevity, reuse your full provided HTML from earlier
            $html .= $this->buildPayfastForm($blockPositionList, $currentLogoBlockPosition);
            return $output . $html;
        }

    /* ------------------ BACK OFFICE CONFIG FORM ------------------ */
    private function buildPayfastForm(array $blockPositionList, $currentLogoBlockPosition)
    {
        // Helper to get saved or submitted values (and escape)
        $get = function ($name, $default = '') {
            $val = Tools::getValue($name, Configuration::get($default));
            return htmlspecialchars(trim((string)$val), ENT_QUOTES, 'UTF-8');
        };

        // Individual fields (readable variables for template)
        $mode = Tools::getValue('payfast_mode', Configuration::get('PAYFAST_MODE'));

        $merchant_id = htmlspecialchars(
            (string)Tools::getValue('payfast_merchant_id', (string)Configuration::get('PAYFAST_MERCHANT_ID')),
            ENT_QUOTES,
            'UTF-8'
        );

        $merchant_key = htmlspecialchars(
            trim((string)Tools::getValue('payfast_merchant_key', (string)Configuration::get('PAYFAST_MERCHANT_KEY'))),
            ENT_QUOTES,
            'UTF-8'
        );

        $passphrase = htmlspecialchars(
            trim((string)Tools::getValue('payfast_passphrase', (string)Configuration::get('PAYFAST_PASSPHRASE'))),
            ENT_QUOTES,
            'UTF-8'
        );

        $split_enabled = Tools::getValue(
            'payfast_split_payments_enabled',
            (string)Configuration::get('PAYFAST_SPLIT_PAYMENT_ENABLED')
        );

        $split_mid = htmlspecialchars(
            (string)Tools::getValue('payfast_split_payment_merchant_id', (string)Configuration::get('PAYFAST_SPLIT_PAYMENT_MERCHANT_ID')),
            ENT_QUOTES,
            'UTF-8'
        );

        $split_amount = htmlspecialchars(
            (string)Tools::getValue('payfast_split_payment_amount', (string)(Configuration::get('PAYFAST_SPLIT_PAYMENT_AMOUNT') ?? '')),
            ENT_QUOTES,
            'UTF-8'
        );

        $split_percentage = htmlspecialchars(
            trim((string)Tools::getValue('payfast_split_payment_percentage', (string)(Configuration::get('PAYFAST_SPLIT_PAYMENT_PERCENTAGE') ?? ''))),
            ENT_QUOTES,
            'UTF-8'
        );

        $split_min = htmlspecialchars(
            (string)Tools::getValue('payfast_split_payment_min', (string)(Configuration::get('PAYFAST_SPLIT_PAYMENT_MIN') ?? '')),
            ENT_QUOTES,
            'UTF-8'
        );

        $split_max = htmlspecialchars(
            trim((string)Tools::getValue('payfast_split_payment_max', (string)(Configuration::get('PAYFAST_SPLIT_PAYMENT_MAX') ?? ''))),
            ENT_QUOTES,
            'UTF-8'
        );

        $logs = Tools::getValue('payfast_logs', Configuration::get('PAYFAST_LOGS'));

        $paynow_text = htmlspecialchars(
            (string)Tools::getValue('payfast_paynow_text', (string)Configuration::get('PAYFAST_PAYNOW_TEXT')),
            ENT_QUOTES,
            'UTF-8'
        );

        $currentLogoBlockPosition = htmlspecialchars((string)$currentLogoBlockPosition, ENT_QUOTES, 'UTF-8');


        $pf_base = __PS_BASE_URI__ . 'modules/' . $this->name;

        // Start building HTML
        $html = '
            <head>
                <link href="' . $pf_base . '/views/css/payfast_config.css" rel="stylesheet" type="text/css" />
            </head>

            <form action="' . htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8') . '" method="post">
              <div class="pf__main--section" id="main__section">

                <div class="merchant__config">
                  <div class="payfast__mode">
                   <span class="pf__subheading">' . $this->l('Mode:') . '</span>
                   <div class="pf__selector">
                     <label style="margin-right:10px;">
                       <input type="radio" name="payfast_mode" value="live" ' . ($mode === 'live' ? self::CHECKED : '') . ' /> ' . $this->l('Live') . '
                     </label>
                     <label>
                       <input type="radio" name="payfast_mode" value="test" ' . ($mode === 'test' ? self::CHECKED : '') . ' /> ' . $this->l('Test') . '
                     </label>
                   </div>
                  </div>
                  <p class="additional__info">' . $this->l('Select "Test" mode to test sandbox payments, and "Live" mode when you are ready to go live.') . '</p>
                </div>

                <div class="divider"></div>

                <div class="merchant__details merchant__config">
                   <div class="account__details">
                      <span class="merchant__headers">' . $this->l('Merchant ID') . '</span>
                      <input class="merchant__input" type="number" step="0" min="0" name="payfast_merchant_id" placeholder="e.g. 1000010.." value="' . $merchant_id . '" />
                      <span class="merchant__headers" style="margin-top:10px; display:block;">' . $this->l('Merchant Key') . '</span>
                      <input class="merchant__input" type="text" name="payfast_merchant_key" placeholder="e.g. 46f0cd69458.." value="' . $merchant_key . '" />
                   </div>
                   <p class="additional__info additional__info--smaller">' . $this->l('You can find your Merchant ID and Merchant Key on your') . ' <a id="' . self::PFLINK . '" href="' . self::PAYFASTURL . '" target="_blank" rel="noopener">' . $this->l('payfast.io') . '</a> ' . $this->l('account under DASHBOARD.') . '</p>
                </div>

                <div class="divider"></div>

                <div class="merchant__details merchant__config">
                   <div class="account__details">
                      <span class="merchant__headers">' . $this->l('Secure Passphrase') . '</span>
                      <input class="merchant__input" type="text" name="payfast_passphrase" placeholder="Same as your Payfast account" value="' . $passphrase . '" />
                   </div>
                   <p class="additional__info additional__info--taller">' . $this->l('The passphrase is an optional/ extra security feature that must be set on your') . ' <a id="' . self::PFLINK . '" href="' . self::PAYFASTURL . '" target="_blank" rel="noopener">' . $this->l('payfast.io') . '</a> ' . $this->l('account in order to be used. You can find your passphrase under SETTINGS > Integration SECURITY PASSPHRASE.') . '</p>
                </div>

                <div class="divider"></div> ';

                //UNCOMMENT THE FOLLOWING TO INCLUDE SPLIT PAYMENTS


               //  <div class="merchant__details merchant__config">
               // <div class="account__details">
               //     <span class="merchant__headers">
               //      ' . $this->l('Enable Split Payments:') . '
               //     </span>
               // <div class="pf__selector split__selector">
               //     <span class="merchant__headers">
               //     ' . $this->l('Enable') . '
               //     </span>
               //     <input type="radio" name="payfast_split_payments_enabled"  value="1" ' .
        //          (
        //          empty(
        //          Tools::getValue(
        //              'payfast_split_payments_enabled',
        //              Configuration::get('PAYFAST_SPLIT_PAYMENT_ENABLED')
        //          )
        //          ) ? '' : self::CHECKED
        //          ) . ' />
        //            <span class="merchant__headers">
        //            ' . $this->l('Disable') . '
        //            </span>
        //            <input type="radio" name="payfast_split_payments_enabled"  value="0" ' .
        //          (
        //          empty(
        //          Tools::getValue(
        //              'payfast_split_payments_enabled',
        //              Configuration::get('PAYFAST_SPLIT_PAYMENT_ENABLED')
        //          )
        //          ) ? self::CHECKED : '') . ' />
        //         </div>
        //    </div>
        //         <p class="additional__info additional__info--taller">' . $this->l(
        //         'Enable Split Payments to allow a portion of every payment to be split to a specified
        //          receiving merchant. Split Payments must be enabled on your '
        //     ) . '<a id="' . self::PFLINK . '" href="' . self::PAYFASTURL . '">' .
        //          $this->l('payfast.io') . '</a>' . $this->l(' account under SETTINGS > Integration.') . '</p>
        //    </div>

        //    <div class="merchant__details merchant__config">
        //       <div class="account__details">
        //          <span class="merchant__headers">
        //              ' . $this->l('Receiving Merchant ID') . '
        //          </span>
        //          <input class="merchant__input"   type="number" step="0" min="0"
        //           name="payfast_split_payment_merchant_id" placeholder="e.g. 1000010.." value="' .
        //          Tools::getValue(
        //              'payfast_split_payment_merchant_id',
        //              Configuration::get('PAYFAST_SPLIT_PAYMENT_MERCHANT_ID')
        //          ) . '" />
        //          </div>
        //   <p class="additional__info additional__info--smaller">' . $this->l(
        //         'This will be on the receiving merchants Payfast Dashboard.'
        //     ) . '</p>
        //   </div>

        //   <div class="merchant__details merchant__config">
        //      <div class="account__details">
        //         <span class="merchant__headers">
        //             ' . $this->l('Amount in cents (ZAR)') . '
        //         </span>
        //         <input class="merchant__input"   type="number" step="0" min="0"
        //           name="payfast_split_payment_amount" placeholder="e.g. 1000" value="' .
        //          Tools::getValue(
        //              'payfast_split_payment_amount',
        //              Configuration::get('PAYFAST_SPLIT_PAYMENT_AMOUNT')
        //          ) . '" />
        //         <span class="merchant__headers">
        //         ' . $this->l('Percentage') . '
        //         </span>
        //         <input class="merchant__input"   type="number" step="0" min="0" max="100"
        //         name="payfast_split_payment_percentage" placeholder="e.g. 10" value="' .
        //         trim(
        //             Tools::getValue(
        //                 'payfast_split_payment_percentage',
        //                 Configuration::get('PAYFAST_SPLIT_PAYMENT_PERCENTAGE')
        //             ) ?? ''
        //         )
        //          . '" />
        //      </div>
        //  <p class="additional__info additional__info--smaller">' . $this->l(
        //         'Required amount in cents (ZAR) or/and percentage allocated to the receiving merchant of
        //          a split payment.'
        //     ) . '</p>
        //  </div>

        //  <div class="merchant__details merchant__config">
        //     <div class="account__details">
        //        <span class="merchant__headers">
        //            ' . $this->l('Min in cents (ZAR)') . '
        //        </span>
        //        <input class="merchant__input"   type="number" step="0" min="0"
        //         name="payfast_split_payment_min" placeholder="e.g. 500" value="' . Tools::getValue(
        //         'payfast_split_payment_min',
        //         Configuration::get('PAYFAST_SPLIT_PAYMENT_MIN')
        //     ) . '" />
        //        <span class="merchant__headers">
        //        ' . $this->l('Max in cents (ZAR)') . '
        //        </span>
        //        <input class="merchant__input"   type="number" step="0" min="0"
        //        name="payfast_split_payment_max" placeholder="e.g. 10000" value="' . trim(
        //              Tools::getValue('payfast_split_payment_max', Configuration::get('PAYFAST_SPLIT_PAYMENT_MAX')) ?? ''
        //          ) . '" />
        //     </div>
        // <p class="additional__info additional__info--smaller">' . $this->l(
        //         'Optional maximum or/and minimum amount that will be split, in cents (ZAR).'
        //     ) . '</p>
        // </div>

        //         <div class="divider"></div>

                $html .= '
                <div class="merchant__details merchant__config">
                   <div class="account__details">
                     <span class="merchant__headers">' . $this->l('Debug to log server-to-server communication:') . '</span>
                     <div class="pf__selector debug__selector">
                       <label style="margin-right:10px;">
                         <input type="radio" name="payfast_logs" value="1" ' . ($logs ? self::CHECKED : '') . ' /> ' . $this->l('Enable') . '
                       </label>
                       <label>
                         <input type="radio" name="payfast_logs" value="0" ' . (!$logs ? self::CHECKED : '') . ' /> ' . $this->l('Disable') . '
                       </label>
                     </div>
                   </div>
                   <p class="additional__info additional__info--taller">' . $this->l('Enable Debug to log the server-to-server communication. The log file for debugging can be found at ') . ' ' . __PS_BASE_URI__ . 'modules/' . $this->name . '/log.txt. ' . $this->l('If activated, be sure to protect it by putting a .htaccess file in the same directory. If not, the file will be readable by everyone.') . '</p>
                </div>

                <div class="divider"></div>

                <div class="merchant__details merchant__config preview__section">
                   <p class="additional__info additional__info--taller">' . $this->l('The following payment option text is displayed during checkout.') . '</p>
                   <div class="account__details">
                     <span class="merchant__headers">' . $this->l('Payment option text') . '</span>
                     <input class="merchant__input" type="text" name="payfast_paynow_text" value="' . $paynow_text . '">
                     <span class="merchant__headers preview__header">' . $this->l('Preview') . '</span>
                     <div style="display:inline-block; vertical-align:middle;">'
                    . '<img alt="Pay with Payfast" title="Pay with Payfast" src="' . $pf_base . '/logo.png" style="width:auto;height:80px;display:inline-block;vertical-align:middle;margin-right:12px;">'
                    . $paynow_text . '&nbsp;&nbsp;'
                    . '</div>

                   </div>
                </div>

                <div class="divider"></div>

                <div class="merchant__details merchant__config preview__section">
                   <p class="additional__info additional__info--taller">' . $this->l('Select the position where the "Pay with Payfast" image will appear on your website. This will be dependant on your theme.') . '</p>
                   <div class="account__details">
                     <span class="merchant__headers">' . $this->l('Image position') . '</span>
                     <select class="pf__dropdown" id="box" name="logo_position">';

            // Populate the dropdown
            foreach ($blockPositionList as $position => $translation) {
                $selected = ($currentLogoBlockPosition == $position) ? 'selected="selected"' : '';
                $html .= '<option value="' . htmlspecialchars($position, ENT_QUOTES, 'UTF-8') . '" ' . $selected . '>' . htmlspecialchars($translation, ENT_QUOTES, 'UTF-8') . '</option>';
            } 
            $html .= '
                     </select>
                   </div>
                </div>

                <div class="divider"></div>

                <div>
                    <button type="submit" name="submitPayfast" class="button" id="pf__button" value="Save">' . $this->l('Save Changes') . '</button>
                    <div id="payfastDetailsError" style="display:none;color:red"></div>
                </div>

              </div>
            </form>

            <div class="divider divider__longer"></div>
            <div class="pf__form--footer">
              <span class="footer__header">' . $this->l('Additional Information:') . '</span>
              <div class="footer__info">
                <span class="footer__info--para">- ' . $this->l('In order to use your Payfast module, you must insert your Payfast Merchant ID and Merchant Key above.') . '</span>
                <span class="footer__info--para">- ' . $this->l('Any orders in currencies other than ZAR will be converted by PrestaShop prior to be sent to the Payfast payment gateway.') . '</span>
                <span class="footer__info--para">- ' . $this->l('It is possible to setup an automatic currency rate update using crontab. You will simply have to create a cron job with currency update link available at the bottom of "Currencies" section.') . '</span>
              </div>
            </div>
            ';

    return $html;
    }

    public function hookDisplayRightColumn($params)
    {
        return $this->displayLogoBlock(self::RIGHT_COLUMN);
    }

    public function hookDisplayLeftColumn($params)
    {
        return $this->displayLogoBlock(self::LEFT_COLUMN);
    }

    public function hookDisplayFooter($params)
    {
        return '
        <section id="payfast_footer_link" class="footer-block col-xs-12 col-sm-2">
            <div style="text-align:center;">
                <a href="https://payfast.io" target="_blank" rel="nofollow" title="Pay with Payfast">
                    <img src="' . __PS_BASE_URI__ . 'modules/payfast/logo.svg" style="width: 150px; height: auto;" />
                </a>
            </div>
        </section>';
    }

    private function displayLogoBlock($position)
    {
        return '
            <div style="text-align:center;">
                <a href="https://payfast.io" target="_blank" rel="nofollow" title="Pay with Payfast">
                    <img src="' . __PS_BASE_URI__ . 'modules/payfast/logo.svg" style="width: 150px; height: auto;" />
                </a>
            </div>';
    }

        /* ------------------ FRONT OFFICE ------------------ */

    public function hookPayment($params)
    {
        if (!$this->active) {
            return '';
        }

        // Read stored value; fall back to a sensible default if missing
        $paynow_text = Configuration::get('PAYFAST_PAYNOW_TEXT');
        if ($paynow_text === false || $paynow_text === null || $paynow_text === '') {
            $paynow_text = $this->l('Pay via PayFast');
        }



        $this->context->smarty->assign([
            'module_link' => $this->context->link->getModuleLink($this->name, 'payment', [], true),
            'this_path'   => $this->_path,
            'paynow_text' => $paynow_text,
        ]);

        // Correct for thirty bees / PrestaShop 1.6
        return $this->display(__FILE__, 'views/templates/hook/payment.tpl');
    }


    public function hookPaymentReturn($params)
    {
        Tools::redirect($this->context->link->getPageLink('order-history', true));
    }

    public function hookHeader()
    {
        $controller = $this->context->controller->php_self;
        if ($controller === 'order' || $controller === 'order-opc') {
            if (method_exists($this->context->controller, 'registerStylesheet')) {
                $this->context->controller->registerStylesheet(
                    'payfast-fontawesome',
                    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css',
                    ['server' => 'remote', 'priority' => 100]
                );
                $this->context->controller->registerStylesheet(
                    'payfast-style',
                    'modules/'.$this->name.'/views/css/payfast_checkout.css',
                    ['media' => 'all', 'priority' => 999]
                );
            } else {
                // fallback for older thirtybees / PrestaShop controllers
                $this->context->controller->addCSS('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css', 'all', null, false);
                $this->context->controller->addCSS('modules/'.$this->name.'/views/css/payfast_checkout.css', 'all', null, false);
            }
        }

    }
}
