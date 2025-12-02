# Payfast Payment Gateway Module

## About
The Payfast Payment Gateway Module allows you to conduct payments using the PayFast Payment Gateway. This module allows payments with many different payment methods (See at: https://payfast.io/features/payment-methods/)

It is reccomended to download this module from the ThirtyBees Marketplace (https://store.thirtybees.com/shop-modules/payment/payfast-payment-gateway). If you have downloaded it from GitHub, you should rename the module folder to "payfast" for the module to work. 

## Setup
### Setting up the PayFast module
To use this module on your site, you need to create a merchant account on payfast (https://registration.payfast.io/). 

- After creating your account, you will need to access your merchant details (MerchantID, MerchantKey and Passphrase (Optional)).
- Enter these details in the Configuration of the module.
- You're all set up!

NOTE: Split payments, multiple currencies support and recurring payments are still under development (looking to release in v2.0). To enable them, uncomment the code in payfast.php.

### Multi-Currency
This module has the ability to execute payments with any currency. Our module automatically retrieves the conversion rate of any currency and converts it to ZAR, since that is the only currency PayFast allows.

NOTE: It is reccomended to set up a crontab for ThirtyBees to retrieve the latest conversion rates at least once a day (Back Office -> Localisation -> Currencies).

## License
This software is published under the [Academic Free License 3.0](https://opensource.org/licenses/afl-3.0.php)

## Contributing
thirty bees modules are open-source extensions to the thirty bees e-commerce solution. Everyone is welcome and even encouraged to contribute with their own improvements.
