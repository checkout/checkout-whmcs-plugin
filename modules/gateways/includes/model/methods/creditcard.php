<?php
class model_methods_creditcard
{
    const CHECKOUT_NON_PCI_JS_URL_LIVE          = 'https://cdn3.checkout.com/js/checkout.js';
    const CHECKOUT_NON_PCI_JS_URL_SANDBOX       = 'https://cdn3.checkout.com/sandbox/js/checkout.js';
    const CHECKOUT_NON_PCI_CHARGE_MODE_NON_3D   = 1;
    const PAYMENT_CODE                          = 'checkoutjs';

    const CHECKOUT_API_RESPONSE_CODE_APPROVED       = 10000;
    const CHECKOUT_API_RESPONSE_CODE_APPROVED_RISK  = 10100;

    /**
     * Decorate post data for Checkout API
     *
     * @param $params
     * @return array
     */
    public function createChargeConfig($params) {
        $Api = CheckoutApi_Api::getApi(array('mode'=>$params['modetype']));

        $secretKey      = $params['secretkey'];
        $amount         = $params['amount'];
        $currencyDesc   = $params['currency'];
        $amountCents    = $Api->valueToDecimal($amount,$currencyDesc);
        $config         = array();
        $config['mode'] = $params['modetype'];

        $transactionMode    = $params['transmethod'];
        $autoCapTime        = $params['autoCaptime'];
        $customerName       = $params['clientdetails']['firstname'] . " " . $params['clientdetails']['lastname'];
        $email              = $params['clientdetails']['email'];
        $autoCapture        = $transactionMode == 'Authorize' ? CheckoutApi_Client_Constant::AUTOCAPUTURE_AUTH : CheckoutApi_Client_Constant::AUTOCAPUTURE_CAPTURE;

        $config['postedParam'] = array (
            'trackId'           => $params['invoiceid'],
            'value'             => $amountCents,
            "chargeMode"        => self::CHECKOUT_NON_PCI_CHARGE_MODE_NON_3D,
            'currency'          => $currencyDesc,
            'autoCapture'       => $autoCapture,
            'autoCapTime'       => $autoCapTime,
            'email'             => $email,
            'custName'          => $customerName,
            'email'             => $email,
            'metadata'          => array(
                'server'            => $_SERVER['HTTP_USER_AGENT'],
                'whmcs_version'     => $params['whmcsVersion'],
                'plugin_version'    => '2.0.0',
                'lib_version'       => CheckoutApi_Client_Constant::LIB_VERSION,
                'integration_type'  => 'JS',
                'time'              => date('Y-m-d H:i:s')
            )
        );

        $config['authorization']    = $secretKey;
        $_SESSION["favcolor"]       = $config;

        return $config;
    }

    /**
     * Get Payment Token from
     *
     * @param $params
     * @return mixed
     */
    public function getPaymentToken($params) {
        $Api    = CheckoutApi_Api::getApi(array('mode'=>$params['modetype']));
        $config = $this->createChargeConfig($params);

        /* Get payment Token */
        $paymentTokenCharge = $Api->getPaymentToken($config);
        $paymentTokenReturn = array(
            'success'   => false,
            'token'     => '',
            'message'   => ''
        );

        if ($Api->getExceptionState()->hasError()) {
            logTransaction('checkoutjs', $Api->getExceptionState()->getErrorMessage(), "Unsuccessful");
        }

        if($paymentTokenCharge->isValid()){
            $paymentToken                   = $paymentTokenCharge->getId();
            $paymentTokenReturn['token']    = $paymentToken ;
            $paymentTokenReturn['success']  = true;
        }

        return $paymentTokenReturn['token'];
    }

    /**
     * Validate Response Object by Response Code
     *
     * @param $response
     * @return bool
     */
    public static function responseValidation($response) {
        $responseCode = (int)$response->getResponseCode();

        if ($responseCode !== self::CHECKOUT_API_RESPONSE_CODE_APPROVED && $responseCode !== self::CHECKOUT_API_RESPONSE_CODE_APPROVED_RISK) {
            return false;
        }

        return true;
    }
}