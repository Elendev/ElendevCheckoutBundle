========
Overview
========

This bundle provide checkout services with paypal implementation.

You will find more informations on http://www.elendev.com or on http://code.google.com/p/elendev-checkout-bundle/.

Installation
------------

Add Elendev path to autoload :
    // app/autoload.php
    $loader->registerNamespaces(array(
        // ...
        'Elendev'              => __DIR__.'/../src',
        // ...
    ));

Load bundle on kernel :
    // in AppKernel::registerBundles()
    $bundles = array(
    	// ...
    	new Elendev\CheckoutBundle\ElendevCheckoutBundle(),
    	// ...
	);

Configuration
-------------
elendev_checkout:
    paypal:
        username: 'paypal_api_username'
        password: 'paypal_api_password'
        signature: 'paypal_api_signature'
        use_sandbox: 'true/false'
        currency: 'EUR/USD/..., optionnal, default : EUR'
        page_style: 'saved pagestyle available on paypal website, optionnal'
        header_image: 'image visible on top left, optionnal'
        header_border_color: '6 char length color code (e.g. 888888), optionnal'
        header_background_color: '6 char length color code (e.g. 777777), optionnal'
        payment_page_background_color: '6 char length color code (e.g. 666666), optionnal'
        brand_name: 'name visible on top of payment page, optionnal'