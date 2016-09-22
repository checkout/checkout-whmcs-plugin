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

$responseToken  = !empty($_REQUEST['cko-payment-token']) ? (string)$_REQUEST['cko-payment-token'] : '';
$otherVariables = getvariables();
$gatewayModule  = model_methods_creditcard::PAYMENT_CODE;
$GATEWAY        = getGatewayVariables($gatewayModule);

if (empty($responseToken) || empty($_SESSION['checkout_payment_token']) || (string)$_SESSION['checkout_payment_token']['token'] !== $responseToken) {
    logTransaction($GATEWAY["name"], 'Your payment was not completed. Your payment tokens do not match. Action - 3d secure authorize. Try again or contact customer support.', "Unsuccessful");
    header("Location:" . $otherVariables["systemurl"] . "/cart.php");
    exit();
}

$secretKey          = $GATEWAY['secretkey'];
$mode               = $GATEWAY['modetype'];
$invoiceId          = $_SESSION['checkout_payment_token']['invoice_id'];

$Api            = CheckoutApi_Api::getApi(array('mode' => $mode));
$verifyParams   = array('paymentToken' => $responseToken, 'authorization' => $secretKey);
$respondCharge  = $Api->verifyChargePaymentToken($verifyParams);
$returnUrl      = $otherVariables["systemurl"] . "/viewinvoice.php?id=" . $invoiceId;

if ($Api->getExceptionState()->hasError()) {
    $message = 'Your payment was not completed.'. $Api->getExceptionState()->getErrorMessage().' and try again or contact customer support.';

    logTransaction($GATEWAY["name"], $message ,"Unsuccessful");
    $_SESSION['checkout_error']         = true;
    $_SESSION['checkout_error_message'] = $message;

    header("Location:" . $returnUrl);
    exit();
}

$chargeId = is_object($respondCharge) ? $respondCharge->getId() : null;

if(!$respondCharge->isValid() || !model_methods_creditcard::responseValidation($respondCharge)){
    $transactionMessage = !empty($chargeId) ? ", transaction id - {$chargeId}" : '';

    logTransaction($GATEWAY["name"], "Your payment was not completed. Response is invalid for trackId - {$invoiceId}{$transactionMessage}. Action - 3d secure authorize. Try again or contact customer support.", "Unsuccessful");
    $_SESSION['checkout_error']         = true;
    $_SESSION['checkout_error_message'] = 'Please check you card details and try again. Thank you';

    header("Location:" . $returnUrl);
    exit();
}

$cardId         = $respondCharge->getCard()->getId();
$amount         = $Api->decimalToValue($respondCharge->getValue(), $respondCharge->getCurrency());
$invoiceId      = checkCbInvoiceID($invoiceId, $GATEWAY["name"]); # Checks invoice ID is a valid invoice number or ends processing

checkCbTransID($chargeId); # Checks transaction number isn't already in the database and ends processing if it does

addInvoicePayment($invoiceId, $chargeId, $amount, '', $gatewayModule);
logTransaction($GATEWAY["name"], 'Your payment completed', "Successful");

unset($_SESSION['checkout_payment_token']);

header("Location:" . $returnUrl);