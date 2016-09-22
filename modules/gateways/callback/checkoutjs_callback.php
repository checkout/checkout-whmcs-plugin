<?php
/**
 * CallBack script for Gateway
 *
 */
include("../../../init.php");
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");
include ("../includes/model/methods/creditcard.php");

$gatewaymodule      = model_methods_creditcard::PAYMENT_CODE;
$GATEWAY            = getGatewayVariables($gatewaymodule);
$secretKey          = $GATEWAY['secretkey'];
$Api                = CheckoutApi_Api::getApi(array('mode' => $GATEWAY['modetype']));
$OtherVariables     = getvariables();

if (empty($_SESSION['favcolor']) || empty($_REQUEST['cko-card-token'])) {
    logTransaction($GATEWAY["name"], 'Your payment was not completed. Customer session or card token is empty. Action - authorize. Try again or contact customer support.', "Unsuccessful");
    header("Location:" . $OtherVariables["systemurl"] . "/cart.php");
    exit();
}

$result = mysql_query("Select * from `tblinvoices` where id =".$_SESSION['favcolor']['postedParam']['trackId']);

while ($row = mysql_fetch_assoc($result)) {
    $ClientID = ($row['userid']);
}

$invoiceId                  = $_SESSION['favcolor']['postedParam']['trackId'];
$currency                   = $_SESSION['favcolor']['postedParam']['currency'];
$config['authorization']    = $GATEWAY['secretkey'];

$config['postedParam'] = array (
    'trackId'           => $invoiceId,
    'value'             => $_SESSION['favcolor']['postedParam']['value'],
    "chargeMode"        => model_methods_creditcard::CHECKOUT_NON_PCI_CHARGE_MODE_NON_3D,
    'currency'          => $currency,
    'autoCapture'       => $_SESSION['favcolor']['postedParam']['autoCapture'],
    'autoCapTime'       => $_SESSION['favcolor']['postedParam']['autoCapTime'],
    'cardToken'         => $_REQUEST['cko-card-token'],
    'email'             => $_SESSION['favcolor']['postedParam']['email'],
    'customerName'      => $_SESSION['favcolor']['postedParam']['custName'],
    'metadata'          => $_SESSION['favcolor']['postedParam']['metadata']
);


$respondCharge  = $Api->createCharge($config);
$returnUrl      = $OtherVariables["systemurl"] . "/viewinvoice.php?id=" . $invoiceId;

if ($Api->getExceptionState()->hasError()) {
    $message = 'Your payment was not completed.'. $Api->getExceptionState()->getErrorMessage().' and try again or contact customer support.';

    logTransaction($GATEWAY["name"], $message ,"Unsuccessful");
    $_SESSION['checkout_error']         = true;
    $_SESSION['checkout_error_message'] = $message;

    header("Location:" . $returnUrl);
    exit();
}

$chargeId       = is_object($respondCharge) ? $respondCharge->getId() : null;
$redirectUrl    = is_object($respondCharge) ? $respondCharge->getRedirectUrl() : null;

if(!$respondCharge->isValid() || !model_methods_creditcard::responseValidation($respondCharge)){
    $transactionMessage = !empty($chargeId) && empty($redirectUrl) ? ", transaction id - {$chargeId}" : '';

    logTransaction($GATEWAY["name"], "Your payment was not completed. Response is invalid for trackId - {$invoiceId}{$transactionMessage}. Action - authorize. Try again or contact customer support.", "Unsuccessful");
    $_SESSION['checkout_error']         = true;
    $_SESSION['checkout_error_message'] = 'Please check you card details and try again. Thank you';

    header("Location:" . $returnUrl);
    exit();
}

if ($redirectUrl) {
    $_SESSION['checkout_payment_token']['invoice_id']   = $invoiceId;
    $_SESSION['checkout_payment_token']['token']        = $respondCharge->getId();

    header("Location:" . $redirectUrl);
    exit();
}

$cardId         = $respondCharge->getCard()->getId();

$amount         = $Api->decimalToValue($respondCharge->getValue(), $currency);
$invoiceId      = checkCbInvoiceID($invoiceId, $GATEWAY["name"]); # Checks invoice ID is a valid invoice number or ends processing

checkCbTransID($chargeId); # Checks transaction number isn't already in the database and ends processing if it does

addInvoicePayment($invoiceId, $chargeId, $amount, '', $gatewaymodule);


$sql = "update `tblclients` set `gatewayid` ='".$cardId."' where `id`=".$ClientID;

if($ClientID != '' && $cardId != '')
{
    mysql_query($sql);
}

$message = 'Your payment was completed. ChargeId: '.$chargeId. ' Invoice Id : '.$invoiceId;

if($respondCharge->getResponseCode() == 10000){
    logTransaction($GATEWAY["name"], $message , "Successful");
} elseif($respondCharge->getResponseCode() == 10100){
    logTransaction($GATEWAY["name"], $message, "Flagged");
}


header("Location:" . $returnUrl);