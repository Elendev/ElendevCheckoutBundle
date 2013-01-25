<?php

namespace Elendev\CheckoutBundle\Controller;


$path = __DIR__ . "/../PaypalAPI/merchant-sdk/lib";
set_include_path(get_include_path() . PATH_SEPARATOR . $path);
require_once 'ipn/PPIPNMessage.php';

use Symfony\Component\HttpFoundation\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;


/**
 * 
 * @author jonas
 * @Route("/_elendevcheckoutbundle/paypalcheckout")
 */

class CheckoutPaypalController extends Controller{
	
	/**
	 * @Route("/ipn", name="elendev.checkout.paypal.ipn")
	 * @param Request $request
	 */
	public function IPNAction(Request $request){
		//$orderManager = $this->get("elendev.checkout.order_manager");
		
		$logger = $this->get("logger");
		
		$logger->debug("PAYPAL : lots of informations");
		
		//DO IPN STUFF - VALIDATE OR UNVALIDATE WITH ORDER MANAGER
		
		$ipnMessage = new \PPIPNMessage();
		foreach($ipnMessage->getRawData() as $key => $value) {
			$logger->debug("DATA : " . $key . " = " . $value);
		}
		
		if($ipnMessage->validate()) {
			$logger->debug("Success: Got valid IPN data");
		} else {
			$logger->debug("Error: Got invalid IPN data");
		}
		
	}
	
}