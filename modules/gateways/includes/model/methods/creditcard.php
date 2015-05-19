<?php
class model_methods_creditcard extends model_methods_Abstract
{

    public function before_process()
    {

    }

    public function  before_capture($params)
    {

        global  $HTTP_POST_VARS,$order;

        $config = parent::before_capture($params);


        $config['postedParam']['cardToken'] = $_POST['cko-cc-token'];
        return $this->_placeorder($config);

    }

    public function getFooterHtml($param)
    {
        $GATEWAY = getGatewayVariables('checkoutapipayment');
        $configParam = array_merge_recursive($GATEWAY,$param);
     //  $paymentToken = $this->getPaymentToken($configParam);
        $config['debug'] = 'false';
        $config['publicKey']  = $GATEWAY['publickey'] ;
        $config['email'] =   $param['clientsdetails']['email'];
        $config['name'] = $param['clientsdetails']['firstname'].' '. $param['clientsdetails']['lastname'];
        $config['amount'] =   $param['rawtotal']*100;
        $config['currency'] =   $param['currency']['code'];
        $config['widgetSelector'] =  '.widget-container';
        $config['cardTokenReceivedEvent'] = "
                        document.getElementById('cko-cc-paymentToken').value = event.data.paymentToken;

                        ";
        $config['widgetRenderedEvent'] ="";
        $config['readyEvent'] = '';
        $html = "  <script type='text/javascript' >
            window.CKOConfig = {
                publicKey: '".$GATEWAY['publickey']."',
                debugMode: true,
                paymentToken: '".$paymentToken ."',
                ready: function() {
                    console.log(document.getElementById('pgbtncheckoutapipayment').checked);
                    CheckoutKit.monitorForm('#mainfrm', CheckoutKit.CardFormModes.CARD_TOKENISATION);
                    $('[name=ccnumber]').attr('data-checkout','email-address');
                    $('#expiry-month').val($('#ccexpirymonth').val());

                    $('#ccexpirymonth').change(function(){
                        $('#expiry-month').val($(this).val());
                    });

                    $('#expiry-year').val( $('[name=ccexpiryyear]').val());

                    $('[name=ccexpiryyear]').change(function(){
                        $('#expiry-year').val($(this).val());
                    });

                    $('[name=cccvv]').attr('data-checkout','cvv');
                    $('[name=ccnumber]').attr('data-checkout','card-number');
                    $('[name^=submit]').remove();
                    $('#mainfrm').attr('action','/cart.php?a=checkout&submit=true');

                },
                formMonitored: function(event) {

                },
                formSubmitted: function(event) {


                }
            };
        </script>
        <script  src='https://www.checkout.com/cdn/js/checkoutkit.js' async ></script>";
        $html.='<div style="display:block" class="widget-container"><input data-checkout="email-address" type="hidden" placeholder="Enter your e-mail address" class="input-control" value="'. $config['email'] .'"/>
                <input data-checkout="card-name" type="hidden" placeholder="Enter the name on your card" autocomplete="off" class="input-control" value="'. $config['name'].'" />
                <input data-checkout="expiry-month" id="expiry-month" type="hidden" placeholder="MM" autocomplete="off" class="input-control center-align" maxlength="2" value=""/>
                <input data-checkout="expiry-year" id="expiry-year" type="hidden" placeholder="YY" autocomplete="off" class="input-control center-align" maxlength="2" value=""/>

</div><script>   $(".widget-container").insertBefore($("#ccinputform"));</script>';
        return $html;
    }

    public function getHeadHtml($param)
    {
        return '';
    }


    public function getPaymentToken($params)
    {
//        getPaymentToken
//
        $Api = CheckoutApi_Api::getApi(array('mode'=> $params['modetype']));
        $config = array();
        $amountCents = (int)$params['rawtotal']*100 ;
        $config['authorization'] = $params['secretkey'];
        $config['mode'] = $params['modetype'];

        $config['postedParam'] = array (
            'email'     => $params['clientsdetails']['email'] ,
            'value'     =>$amountCents,
            'currency'  => $params['currency']['code'],

            'card'      => array(
                'billingDetails' => array (
                    'addressLine1' => $params['clientsdetails']['address1'],
                    'addressLine2' => $params['clientsdetails']['address2'],
                    'postcode'     => $params['clientsdetails']['postcode'],
                    'country'      => $params['clientsdetails']['country'],
                    'city'         => $params['clientsdetails']['city'],
                    'state'        => $params['clientsdetails']['state'],
                    'phone'        => array('number' => $params['clientsdetails']['phonenumber'])
                )
            )
        );

        if ($params['transmethod']== 'Capture') {
            $config = array_merge_recursive( $this->_captureConfig($params),$config);
        } else {
            $config = array_merge_recursive( $this->_authorizeConfig($params),$config);
        }


        $paymentTokenCharge = $Api->getPaymentToken($config);

        $paymentToken    =   '';

        if($paymentTokenCharge->isValid()){
            $paymentToken = $paymentTokenCharge->getId();
        }

        if(!$paymentToken) {
           throw new Exception($paymentTokenCharge->getExceptionState()->getErrorMessage().
                ' ( '.$paymentTokenCharge->getEventId().')'
            );
        }

        return $paymentToken;

    }
}