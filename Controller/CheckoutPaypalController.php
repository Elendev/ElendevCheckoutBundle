<?php

namespace Elendev\CheckoutBundle\Controller;

$path = __DIR__ . "/../PaypalAPI/merchant-sdk/lib";
set_include_path(get_include_path() . PATH_SEPARATOR . $path);
require_once 'ipn/PPIPNMessage.php';

use Symfony\Component\HttpFoundation\Response;

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
	 * @Route("/ipn/{command}", name="elendev.checkout.paypal.ipn")
	 * @param Request $request
	 */
	public function IPNAction(Request $request, $command){
		$logger = $this->get("logger");
		if(!$this->has("elendev.checkout.order_manager")){
			$logger->err("NO PAYPAL ORDER_MANAGER");
			return new Response();
		}
		
		$orderManager = $this->get("elendev.checkout.order_manager");
		
		$paypalManager = $this->get("elendev.checkout.service_provider.paypal");
		
		
		$logger->err("PAYPAL IPN received for command $command");
		
		//DO IPN STUFF - VALIDATE OR UNVALIDATE WITH ORDER MANAGER
		
		$ipnMessage = new \PPIPNMessage();
		foreach($ipnMessage->getRawData() as $key => $value) {
			$logger->err("DATA : " . $key . " = " . $value);
		}
		
		if($ipnMessage->validate()) {
			$logger->debug("Success: Got valid IPN data");
			
			$command = $orderManager->getCommand($command);
			
			$data = $paypalManager->getExpressCheckoutDetails($command->getToken());
			$checkoutStatus = $data->GetExpressCheckoutDetailsResponseDetails->CheckoutStatus;
			
	    	if($checkoutStatus == 'PaymentActionCompleted'){
   				$orderManager->validateCommand($command);
		   	}else if($checkoutStatus == 'PaymentActionInProgress'){
		   		//do nothing
		   	}else if($checkoutStatus == 'PaymentActionFailed'){
		   		$orderManager->errorCommand($command);
	    	}
			
		} else {
			$logger->error("Error: Got invalid IPN data");
		}
		
		return new Response();
	}
	
}