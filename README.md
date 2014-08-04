woo-commerce
============

Woo Commerce Payment Module

1. Installation

IMPORTANT - ALWAYS BACKUP YOU SYSTEM BEFORE MAKING ANY UPGRADES OR CHANGES!

Installation of this plugin is fairly simple. To install a WordPress Plugin automatically with the WordPress built-in installer, you need to manual upload this plugin to your WordPress plugins directory.

    - Login to your WordPress admin site and select click on the "Plugins" menu
    - From the submenus under the "Plugins" menu select "Add New"
    - Select the "Upload" option from the links on the top of the "Install Plugins" page
    - Press the "Browser" button to select the plugin file and press "Install Now" button
    - Once installed go to your "Plugins" page again and activate the newly uploaded plugin

2. Merchant Plus API Credentials

In order to accept Credit Card payments on your website you must first register for a MerchantPlus NaviGate account. Please do so at http://www.merchantplus.com/get-started .  Once you've been approved for your account, MerchantPlus will provide you with a NaviGate login and password. 

  - Login to your gateway at https://gateway.merchantplus.com/
  - Click to the "Profile & Settings" and then the "Security" sub page
  - Scroll down and click to "Edit" your transaction key and then "Generate a new Key"
  - You will use this transaction key plus your NaviGate login ID when configuring the plugin inside of WordPress

Without the above credentials you will not be able to accept payment via the MerchantPlus NaviGate plugin.

3. Enabling the MerchantPlus payment gateway in WordPress

    - Browse to the WooCommerece settings page inside the WordPress admin area
    - Select the Payment Gateways tab from the top
    - From the list of different payment gateways select "MerchantPlus NaviGate" to view its settings page
    - Tick the Enable/Disable checkbox to enable the payment gateway
    - Paste your API Login Id and Transaction Key inside their corresponding input fields
    - Save your changes by pressing the "Save changes" button

4. Testing using Merchant Plus Sandbox

It's possible to test the MerchantPlus payment system by checking the "Enable NaviGate Sandbox" option. When ticked, this option will send all your requests to NaviGate in TEST mode. This results in no actual transfer of money.

IMPORTANT - EVEN THOUGH SANDBOX TESTING DOESN'T USE REAL MONEY, PERFORMING A SUCCESSFUL PAYMENT TEST WILL CREATE NEW ORDERS AND ALTER YOUR STOCK!

5. Going Live

Once you are ready to go live, you need to uncheck "Enable NaviGate Sandbox" in the Payment Gateway settings. 

YOU *MUST* ACQUIRE A VALID SSL CERTIFICATE FOR YOUR WEBSITE AND ALSO ENABLE SSL FROM THE SETTINGS PANEL TO USE THIS PLUGIN IN LIVE MODE - THIS IS FOR SECURITY AND PCI COMPLIANCE REQUIREMENTS.
