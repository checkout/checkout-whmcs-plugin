<?php
abstract class model_methods_Abstract {



    public function before_capture($params)
    {
        global $customer_id, $order, $currency, $HTTP_POST_VARS;

        $config = array();
        $amountCents = (int)$params['amount']*100 ;
        $config['authorization'] = $params['secretkey'];
        $config['mode'] = $params['modetype'];
        $products = array();
//            foreach ($orderedItems as $item ) {
//                $product = Mage::getModel('catalog/product')->load($item->getProductId());
//                $products[] = array (
//                    'name'       =>     $item->getName(),
//                    'sku'        =>     $item->getSku(),
//                    'price'      =>     $item->getPrice(),
//                    'quantity'   =>     $item->getQtyOrdered(),
//                    'image'      =>     Mage::helper('catalog/image')->init($product, 'image')->__toString()
//                );
//            }

        $config['postedParam'] = array (
            'email'     => $params['clientdetails']['email'] ,
            'value'     => $amountCents,
            'currency'  => $params['currency'],
            'trackid'   => $params['invoiceid'],
            'products'  => $products,
            'card'      => array(
                        'billingDetails' => array (
                                            'addressLine1' => $params['clientdetails']['address1'],
                                            'addressLine2' => $params['clientdetails']['address2'],
                                            'postcode'     => $params['clientdetails']['postcode'],
                                            'country'      => $params['clientdetails']['country'],
                                            'city'         => $params['clientdetails']['city'],
                                            'state'        => $params['clientdetails']['state'],
                                            'phone'        => array('number' => $params['clientdetails']['phonenumber'])
                                         )
                         )
        );

        if ($params['transmethod']== 'Capture') {
            $config = array_merge( $this->_captureConfig($params),$config);
        } else {
            $config = array_merge( $this->_authorizeConfig($params),$config);
        }
        return $config;
    }

    protected function _placeorder($config)
    {
        global $messageStack,$order;

        //building charge
        $respondCharge = $this->_createCharge($config);
        $GATEWAY = getGatewayVariables('checkoutapipayment');

        if( $respondCharge->isValid()) {
            if (preg_match('/^1[0-9]+$/', $respondCharge->getResponseCode())) {
                logTransaction($GATEWAY["name"],$respondCharge->toArray(),"Successful");
                $command = "addtransaction";
                $adminuser = "admin";
                $values["transid"] = $respondCharge->getId();
                $values["date"] = date('d/m/Y',$respondCharge->getCreated());

                $results = localAPI($command,$values,$adminuser);
                return  array("status"=>"success","gatewayid"=>$respondCharge->getId(),"transid"=>$config['trackid'] ,"rawdata"=>$respondCharge->getRawOutput());
                //return array("status"=>"success","transid"=>$config['metadata']['trackid'],"rawdata"=>$respondCharge);
            }
            return array("status"=>"declined","rawdata"=>$respondCharge->getRawOutput());
        } else  {

            return array("status"=>"error","rawdata"=>$respondCharge->getRawOutput());
        }
        logTransaction($GATEWAY["name"],$respondCharge->toArray(),"fail");
    }
    private function _createCharge($config)
    {
        $Api = CheckoutApi_Api::getApi(array('mode'=> $config['mode']));
        return $Api->createCharge($config);
    }
    protected function _captureConfig($params)
    {
        $to_return['postedParam'] = array (
            'autoCapture' => CheckoutApi_Client_Constant::AUTOCAPUTURE_CAPTURE,
            'autoCapTime' => $params['capturetime']
        );

        return $to_return;
    }

    protected function _authorizeConfig($params)
    {
        $to_return['postedParam'] = array(
            'autoCapture' => ( CheckoutApi_Client_Constant::AUTOCAPUTURE_AUTH),
            'autoCapTime' => 0
        );
        return $to_return;
    }


    public function getFooterHtml($param)
    {
        return '';
    }

    public function getHeadHtml($param)
    {
        return '';
    }

    public function getShoppingCartValidateCheckout($param)
    {
        return array();
    }
}