<?php

namespace Elendev\CheckoutBundle\Controller;

use Elendev\CheckoutBundle\PaypalAPI\PaypalCheckoutService;

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
		
		$ipnMessage = $this->get("elendev.checkout.service_provider.paypal")->getIPNMessage();
		$validate = $ipnMessage->validate();
		
		$logger = $this->get("logger");
		if(!$this->has("elendev.checkout.order_manager")){
			$logger->err("NO PAYPAL ORDER_MANAGER");
			return new Response("No paypal order_manager");
		}
		
		$orderManager = $this->get("elendev.checkout.order_manager");
		
		$paypalManager = $this->get("elendev.checkout.service_provider.paypal");
		
		
		$logger->err("PAYPAL IPN received for command $command");
		
		//DO IPN STUFF - VALIDATE OR UNVALIDATE WITH ORDER MANAGER
		
		
		foreach($ipnMessage->getRawData() as $key => $value) {
			$logger->err("DATA : " . $key . " = " . $value);
		}
		
		if($validate) {
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
		   		$logger->err("PAYPAL IPN command " . $command->getId() . " PaymentActionFailed");
	    	}
			
		} else {
			$logger->err("Error: Got invalid IPN data");
			return new Response("Invalid IPN data");
		}
		
		return new Response("Finished correctly");
	}
	
}