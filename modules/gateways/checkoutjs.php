<?php
/**
 * Config class for Gateway
 */

/**
 * Lib include
 *
 */
include 'includes/autoload.php';
session_start();

/**
 * Define gateway configuration options.
 *
 * @return array
 */
function checkoutjs_config(){
    $configarray = array(
        "FriendlyName" => array(
            "Type"  => "System",
            "Value" => "Checkout.com Credit Card Payment"
        ),
        "publickey" => array(
            "FriendlyName" => "Publishable API Key",
            "Type"         => "text",
            "Description"  => "The Checkout.com account publishable API key to use.",
        ),
        "secretkey" => array(
            "FriendlyName" => "Secret API Key",
            "Type"         => "text",
            "Description"  => "The Checkout.com account secret API key to use .",
        ),
        "modetype" => array(
            "FriendlyName" => "Transaction Server",
            "Type"         => "dropdown",
            "Options"      => "Sandbox,Live",
        ),
        "transmethod" => array(
            "FriendlyName" => "Transaction Method",
            "Type"         => "dropdown",
            "Options"      => "Authorize,Capture",
        ),
        "autoCaptime" => array(
            "FriendlyName" => "Set auto capture time.",
            "Type"         => "text",
            "size"         => "15",
            "Value"        => 0,
            "Description"  => "Time to caputure the transaction.",
        ),
        "timeout" => array(
            "FriendlyName" => "Set Gateway timeout.",
            "Type"         => "text",
            "size"         => "15",
            "Value"        => 60,
            "Description"  => "Set how long request timeout on server.",
        ),
        "logoUrl" => array(
            "FriendlyName" => "Lightbox logo url",
            "Type"         => "text",
            "Description"  => "The URL of your company logo. Must be 180 x 36 pixels. Default: Checkout logo.",
        ),
        "themeColor" => array(
            "FriendlyName"  => "Theme Color",
            "Type"          => "text",
            "Description"   => "#HEX value of your chosen theme color.",
            "Value"         => "#00b660"
        ),
        "buttonColor" => array(
            "FriendlyName"  => "Button color",
            "Type"          => "text",
            "Description"   => "#HEX value of your chosen button color.",
            "Value"         => "#00b660"
        ),
        "iconColor" => array(
            "FriendlyName" => "Icon color",
            "Type"         => "text",
        ),
        "useCurrencyCode" => array(
            "FriendlyName" => "Use currency Code",
            "Type"         => "yesno",
            "Description"  => "Use ISO3 currency code (e.g. GBP) instead of the currency symbol (e.g. £)s",
        ),
        "title" => array(
            "FriendlyName"  => "Title",
            "Type"          => "text",
            "Description"   => "The title of your payment form.",
        ),
        "widgetColor" => array(
            "FriendlyName"  => "Widget Color",
            "Type"          => "text",
            "Description"   => "#HEX value of your chosen widget color.",
        ),
        "buttonLabelColor" => array(
            "FriendlyName"  => "Button Label Color",
            "Type"          => "text",
            "Description"   => "#HEX value of your chosen button text color.",
            "Value"         => "#ffffff"
        ),
        "formButtonColor" => array(
            "FriendlyName"  => "Form Button Color",
            "Type"          => "text",
            "Description"   => "#HEX value of your chosen lightbox submit button color.",
            "Value"         => "#00b660"
        ),
        "formButtonColorLabel" => array(
            "FriendlyName"  => "Form Button Color Label",
            "Type"          => "text",
            "Description"   => "#HEX value of your chosen lightbox submit button label color.",
            "Value"         => "#ffffff"
        ),
        "overlayShade" => array(
            "FriendlyName"  => "Overlay Shade",
            "Type"          => "dropdown",
            "Options"       => "dark, light",
            "Value"         => "dark"
        ),
        "overlayOpacity" => array(
            "FriendlyName"  => "Overlay Opacity",
            "Type"          => "text",
            "Description"   => "A number between 0.7 and 1",
            "Value"         => "0.8"
        ),
        "showMobileIcons" => array(
            "FriendlyName"  => "Show Mobile Icons",
            "Type"          => "yesno",
            "Description"   => "Show widget icons on mobile."
        ),
        "widgetIconSize" => array(
            "FriendlyName"  => "Widget Icon Size",
            "Type"          => "dropdown",
            "Options"       => "small, medium, large",
            "Description"   => "Available sizes: small, medium, large.",
            "Value"         => "small"
        ),
    );
    return $configarray;
}

/**
 * Payment link.
 *
 * @param $params
 * @return string
 */
function checkoutjs_link($params){
    $Api            = CheckoutApi_Api::getApi(array('mode'=>$params['modetype']));
    $model          = new model_methods_creditcard();
    $paymentToken   = $model->getPaymentToken($params);

    $publicKey      = $params['publickey'];
    $email          = $params['clientdetails']['email'];
    $amount         = $params['amount'];
    $currencyCode   = $params['currency'];

    $amountCents = $Api->valueToDecimal($amount, $currencyCode);

    $customerName   = $params['clientdetails']['firstname'] . " " . $params['clientdetails']['lastname'];
    $returnUrl      = $params['systemurl'].'/modules/gateways/callback/checkoutjs_callback.php?cko-card-token=';
    $address1       = $params['clientdetails']['address1'];
    $address2       = $params['clientdetails']['address2'];
    $city           = $params['clientdetails']['city'];
    $postcode       = $params['clientdetails']['postcode'];

    $country        = $params['clientdetails']['countrycode'];
    $state          = $params['clientdetails']['state'];

    $logoUrl        = $params['logoUrl'];
    $themeColor     = $params['themeColor'];
    $iconColor      = $params['iconColor'];
    $buttonColor    = $params['buttonColor'];

    $useCurrencyCode    = $params['useCurrencyCode'] ? 'true' : 'false';
    $title              = $params['title'];
    $widgetColor        = $params['widgetColor'];
    $buttonLabelColor   = $params['buttonLabelColor'];

    $formButtonColor        = $params['formButtonColor'];
    $formButtonColorLabel   = $params['formButtonColorLabel'];
    $overlayShade           = $params['overlayShade'];
    $overlayOpacity         = $params['overlayOpacity'];
    $showMobileIcons        = $params['showMobileIcons'];
    $widgetIconSize         = $params['widgetIconSize'];

    $url            = $params['modetype'] == 'Live' ? model_methods_creditcard::CHECKOUT_NON_PCI_JS_URL_LIVE : model_methods_creditcard::CHECKOUT_NON_PCI_JS_URL_SANDBOX;
    $errorMessage   = getErrorMessage();

    $code = @"
        <div style='width:150px; margin:0 auto;'>
            <script src='https://code.jquery.com/jquery-1.11.0.min.js'></script>
            <script src=$url></script>
            <script>
                Checkout.render({
                    debugMode           : true,
                    publicKey           : '$publicKey',
                    paymentToken        : '$paymentToken',
                    customerEmail       : '$email',
                    customerName        : '$customerName',
                    value               : '$amountCents',
                    currency            : '$currencyCode',
                    paymentMode         : 'card',
                    forceRedirect       : false,
                    //payButtonSelector   : '#payNow',
                    cardFormMode        : 'cardTokenisation',
                    billingDetails: {
                        'addressLine1'  : '$address1',
                        'addressLine2'  : '$address2',
                        'postcode'      : '$postcode',
                        'country'       : '$country',
                        'city'          : '$city',
                        'state'         : '$state'
                    },
                    logoUrl             :'$logoUrl',
                    renderMode          : 1,
                    themeColor          :'$themeColor',
                    buttonColor         :'$buttonColor',
                    iconColor           :'$iconColor',
                    useCurrencyCode     :'$useCurrencyCode',
                    title               :'$title',
                    widgetColor         :'$widgetColor',
                    buttonLabelColor    :'$buttonLabelColor',
                    styling : {
                        formButtonColor         : '$formButtonColor',
                        formButtonColorLabel    : '$formButtonColorLabel',
                        overlayShade            : '$overlayShade',
                        overlayOpacity          : '$overlayOpacity',
                        showMobileIcons         : '$showMobileIcons',
                        widgetIconSize          : '$widgetIconSize'
                    },
                    ready: function (event) {
                        if(document.getElementById('whmcsorderfrm')){
                            document.getElementsByClassName('alert')[0].style.visibility='hidden';
                            document.getElementsByClassName('textcenter')[0].style.visibility='hidden';
                        }
                    },
                    widgetRendered: function (){
                        if(document.getElementById('whmcsorderfrm')){
                            //document.getElementById('cko-widget').style.visibility='hidden';
                        }
                    },
                    cardTokenised: function(event) {
                        if(document.getElementById('whmcsorderfrm')){
                            document.getElementById('cko-widget').style.visibility='hidden';
                            document.getElementsByClassName('textcenter')[0].style.visibility='visible';
                        }
                        window.location.href = '$returnUrl'+event.data.cardToken;
                    }
                });
            </script>
        </div>
    ";

    if ($errorMessage) {
        $code .= "<div><p style='color:red;'>{$errorMessage}</p></div>";
    }

    return $code;
}

/**
 * Do not Delete, this function need for check in cor
 */
function checkoutjs_nolocalcc() {}

/**
 * Refund transaction.
 *
 * @param $params
 * @return array
 */
function checkoutjs_refund($params) {
    $_config                    = array();
    $amount                     = (int)$params['amount'] ;
    $currencyDesc               = $params['amount'] ;
    $_config['authorization']   = $params['secretkey'];
    $_config['mode']            = $params['modetype'];
    $_config['chargeId']        = $params['transid'] ;

    $_Api           = CheckoutApi_Api::getApi(array('mode' => $_config['modetype']));
    $amountCents    = $_Api->valueToDecimal($amount, $currencyDesc);

    $_config['postedParam'] = array (
        'value' => $amountCents//$amountCents
    );

    $_refundCharge = $_Api->refundCharge($_config);

    if ($_Api->getExceptionState()->hasError()) {
        logTransaction(model_methods_creditcard::PAYMENT_CODE, $_Api->getExceptionState()->getErrorMessage(), "Unsuccessful");
    }

    if($_refundCharge->isValid()) {
        logTransaction(model_methods_creditcard::PAYMENT_CODE, $_refundCharge->toArray(), "Success");
        return array("status" => "success", "transid" => $params["invoiceid"], "rawdata" => $_refundCharge->getRawRespond());
    }

    logTransaction(model_methods_creditcard::PAYMENT_CODE, $_refundCharge->toArray(), "fail");

    return array("status" => "error", "rawdata" => $_refundCharge->getRawRespond());
}

/**
 * Return error message from $_SESSION
 *
 * @return string
 */
function getErrorMessage() {
    $result = '';

    if (!empty($_SESSION['checkout_error'])) {
        $result = $_SESSION['checkout_error_message'];

        unset($_SESSION['checkout_error_message']);
        unset($_SESSION['checkout_error']);
    }

    return $result;
}
