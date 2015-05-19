<?php
class model_methods_creditcardpci extends model_methods_Abstract
{

    public function before_capture($params)
    {

        $config = parent::before_capture($params);
        $config['postedParam']['card']['phoneNumber'] = $params['clientdetails']['phonenumber'];
        $config['postedParam']['card']['name'] = $params['clientdetails']['firstname'] .' '.$params['clientdetails']['lastname'];
        $config['postedParam']['card']['number'] = $params['cardnum'];
        $config['postedParam']['card']['expiryMonth'] = substr ($params['cardexp'],0,2);
        $config['postedParam']['card']['expiryYear'] = substr($params['cardexp'],2,4);
        $config['postedParam']['card']['cvv'] = $_POST['cccvv'];

        return $this->_placeorder($config);
    }

}