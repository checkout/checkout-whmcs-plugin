<?php

include("../../../init.php");
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");
include ("../includes/model/methods/creditcard.php");

$gatewaymodule      = model_methods_creditcard::PAYMENT_CODE;
$GATEWAY            = getGatewayVariables($gatewaymodule);
$secretKey          = $GATEWAY['secretkey'];

$stringCharge = file_get_contents("php://input");

$Api = CheckoutApi_Api::getApi(array('mode'=> $GATEWAY['modetype']));
$objectCharge = $Api->chargeToObj($stringCharge);
$invoiceId = $objectCharge->getTrackId();
$status = $objectCharge->getStatus();

if($objectCharge->isValid()) {
    $message = "Charge for Invoice Id - ".$invoiceId." has been ".$status;
    logTransaction($GATEWAY["name"],$message,$status);
} else {
    logTransaction($GATEWAY["name"],$stringCharge,"Error");
}

