<?php

/*
 * Add values that you would want centralised through out the plugin
*/

class TinggWordPressConstants
{
    const BRAND_NAME = 'tingg';

    const PAYMENT_GATEWAY = self::BRAND_NAME . '_checkout';
    const PAYMENT_GATEWAY_DESCRIPTION = (self::BRAND_NAME) . ' allows you to make and collect payment in 33+ countries in Africa from a single integration';

    //supported countries
    const COUNTRIES = array(
        "kenya" => array(
            "currencyCode" => "KES",
            "countryCode" => "KE"
        ),
        "tanzania" => array(
            "currencyCode" => "TZS",
            "countryCode" => "TZ"
        ),
        "uganda" => array(
            "currencyCode" => "UGX",
            "countryCode" => "UG"
        ),
        "ghana" => array(
            "currencyCode" => "GHS",
            "countryCode" => "GH"
        ),
        "zambia" => array(
            "currencyCode" => "ZMW",
            "countryCode" => "ZM"
        ),
        "zimbabwe" => array(
            "currencyCode" => "USD",
            "countryCode" => "ZW"
        ),
        "mozambique" => array(
            "currencyCode" => "MZN",
            "countryCode" => "MZ"
        ),
        "nigeria" => array(
            "currencyCode" => "NGN",
            "countryCode" => "NG"
        )
    );
}