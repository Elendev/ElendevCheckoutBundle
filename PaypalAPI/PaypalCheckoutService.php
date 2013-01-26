<?php
/**
 * Copyright 2012 Jonas Renaudot <http://www.elendev.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Elendev\CheckoutBundle\PaypalAPI;

use Monolog\Logger;

//TODO : ADD IT IN PATH SYMFONY !
$path = __DIR__ . "/merchant-sdk/lib";
set_include_path(get_include_path() . PATH_SEPARATOR . $path);
include("services/PayPalAPIInterfaceService/PayPalAPIInterfaceServiceService.php");

use Symfony\Bundle\FrameworkBundle\Routing\Router;

use Elendev\CheckoutBundle\Command\Custommer;

use Elendev\CheckoutBundle\Command\Item;

use Elendev\CheckoutBundle\CheckoutService;
use Elendev\CheckoutBundle\Command\Command;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Elendev\CheckoutBundle\CheckoutResult;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Allow to do checkout through paypal
 *
 * @author Jonas Renaudot <http://www.elendev.com>
 */
class PaypalCheckoutService extends CheckoutService {
    //put your code here
    
    
    private $username;
    private $password;
    private $signature;

    private $payerId;

    //API URL
    private $endpoint;


    private $paypalURL;
    
    private $currency = "EUR";
    
    private $currentUrl;
    
    private $container;
    
    private $cancelUrl;
    private $returnUrl;
    
    private $session;
    
    private $router;
    
    /** @var \Symfony\Bridge\Monolog\Logger */
    private $logger;
    
    /** 
     * Paypal codes :
     * 
     * PAGESTYLE //page style defined in paypal account
     * HDRIMG //image left
     * HDRBORDERCOLOR //border color for header
     * HDRBACKCOLOR //background color for header
     * PAYFLOWCOLOR //background color for payment page
     * BRANDNAME //name of enterprise
     */
    private $pageStyle = null;
    private $headerImage = null;
    private $borderColor = null;
    private $headerBackgroundColor = null;
    private $paymentPageBackgroundColor = null;
    private $brandName = null;
    
    
    /**
     * Receive the request object at initialization
     * @param ContainerInterface container
     */
    public function __construct(ContainerInterface $container, Router $router, $username, $password, $signature, $useSandbox){
        $this->container = $container;
        
        $this->username = $username;
        $this->password = $password;
        $this->signature = $signature;
        
        $this->router = $router;
        $this->logger = $container->get("logger");
        
        $this->session = $container->get("session");
        
        $this->currentUrl = $container->get("request")->getUri();
        
        $this->cancelUrl = $this->currentUrl . "?status=cancel";
        $this->returnUrl = $this->currentUrl . "?status=continue";
        
        if($useSandbox){
            //$this->endpoint = "https://api-3t.sandbox.paypal.com/2.0";
            define('PP_CONFIG_PATH', __DIR__ . '/config/sandbox');
            $this->paypalURL = "https://www.sandbox.paypal.com/webscr&cmd=_express-checkout&token=";
        }else{
            //$this->endpoint = "https://api-3t.paypal.com/2.0";
        	define('PP_CONFIG_PATH', __DIR__ . '/config/production');
            $this->paypalURL = "https://www.paypal.com/webscr&cmd=_express-checkout&token=";
        }
    }
    
    /**
     * Endpoint url address
     * @param type $endPoint 
     */
    public function setEndPoint($endPoint){
        $this->endpoint = $endPoint;
    }
    
    /**
     * Paypal redirect url
     * @param type $paypalUrl 
     */
    public function setPaypalUrl($paypalUrl){
        $this->paypalURL = $paypalUrl;
    }
    
    /**
     * Validate current IPN
     * @return \PPIPNMessage
     */
    public function getIPNMessage(){
    	require_once 'ipn/PPIPNMessage.php';
    	return new \PPIPNMessage();
    }
    
    /**
     * @param Command $command
     * @return CheckoutResult : result of checkout operations
     */
    public function doCheckout(Command $command){
    	
        $token = $this->session->get("checkout.payment.token");
        
        $result = new CheckoutResult();
        $result->setToken($token);
        
        $paypalService = new \PayPalAPIInterfaceServiceService();
        
        
        if(!$token){
            //$response = $this->setExpressCheckoutRequest($command);
            try{
            	$response = $this->setExpressCheckout($command);
            	
            	$result->setCommandData($response);
            	$result->setToken($response->Token);
            	
            	if($response->Ack =='Success'){
            		$this->session->set("checkout.payment.token", $response->Token);
            	
            		$result->setStatus(CheckoutResult::STATUS_IN_PROGRESS);
            	
            		$result->setHttpResponse(new RedirectResponse($this->paypalURL . $response->Token));
            	}else{
            		$result->setStatus(CheckoutResult::STATUS_ERROR); //TODO Check this !
            	}
            	
            }catch(\Exception $e){
            	$this->logger->err('Command PAYPAL setExpressCheckout error : ' . $e->getCode() . " - " . $e->getMessage());
            	$this->session->remove("checkout.payment.token");
            	$result->setStatus(CheckoutResult::STATUS_ERROR);
            	throw $e;
            }
            
            
        }else{
            
            if($this->container->get("request")->query->get("status") == "continue"){
                
                $token = $this->session->get("checkout.payment.token");
                
                $ecDetails = null;
                $ecPayment = null;
                
                try{
                	$ecDetails = $this->getExpressCheckoutDetails($token);
                	
                	if($ecDetails->Ack == 'Success'){
                		
                		$ecPayment = $this->doExpressCheckout($command, $ecDetails->GetExpressCheckoutDetailsResponseDetails);
                		
                		if($ecPayment->Ack == 'Success'){
                			$paymentInfo = $ecPayment->DoExpressCheckoutPaymentResponseDetails->PaymentInfo;
                			if($paymentInfo && count($paymentInfo) > 0){
                				$paymentStatus = $paymentInfo[0]->PaymentStatus;
                				 
                				if($paymentStatus == 'Completed' || $paymentStatus == 'Completed-Funds-Held'){
                					$result->setStatus(CheckoutResult::STATUS_SUCCESS);
                				}else if($paymentStatus == 'In-Progress' || $paymentStatus == 'Partially-Refunded' || $paymentStatus == 'Pending' || $paymentStatus == 'Processed'){
                					$result->setStatus(CheckoutResult::STATUS_PENDING);
                					$result->setCommandData($paymentInfo[0]->PendingReason);
                				}else{
                					$result->setStatus(CheckoutResult::STATUS_ERROR);
                				}
                			}else{ //simple success : need to check after if ok
                				$result->setStatus(CheckoutResult::STATUS_PENDING);
                			}
                			
                		}else{
                			$this->logger->err('Command PAYPAL doExpressCheckoutPayment [' . $token . '] ack not success : ' . $ecPayment->Ack , array(json_encode($ecPayment)));
                			$result->setStatus(CheckoutResult::STATUS_ERROR);
                		}
                	}else{
                		$this->logger->err('Command PAYPAL getExpressCheckoutDetails [' . $token . '] ack not success : ' . $ecDetails->Ack , array(json_encode($ecDetails)));
                		$result->setStatus(CheckoutResult::STATUS_ERROR); 
                	}
                }catch(\Exception $e){
                	$this->session->remove("checkout.payment.token");
                	$this->logger->err('Command PAYPAL [' . $token . '] get/do express checkout payment error : ' . $e->getCode() . " - " . $e->getMessage(), array("ecDetails" => json_encode($ecDetails), "ecPayment" => json_encode($ecPayment)));
                	$result->setCommandData($e->getCode() . " - " . $e->getMessage());
                	$result->setStatus(CheckoutResult::STATUS_ERROR);
                }
                
                $this->session->remove("checkout.payment.token");
            }else{
                //Session delete checkout.payment.token
                $this->session->remove("checkout.payment.token");
                $result->setStatus(CheckoutResult::STATUS_CANCELED);
                return $result;
            }
        }
        
        return $result;
    }
    
    /**
     * 
     * @param Command $command
     * @return \SetExpressCheckoutResponseType
     */
    public function setExpressCheckout(Command $command){
    	$ecReqDetail = new \SetExpressCheckoutRequestDetailsType();
    	
    	$ecReqDetail->PaymentDetails[0] = $this->getPaymentDetailType($command);
    	$ecReqDetail->ReturnURL = $this->returnUrl;
    	$ecReqDetail->CancelURL = $this->cancelUrl;
    	
    	$ecReqDetail->NoShipping = true;
    	$ecReqDetail->AddressOverride = false;
    	$ecReqDetail->ReqConfirmShipping = false;
    	$ecReqDetail->AllowNote = false;
    	
    	$ecReqDetail->cppheaderimage = $this->headerImage;
    	$ecReqDetail->BrandName = $this->brandName;
    	$ecReqDetail->PageStyle = $this->pageStyle;
    	$ecReqDetail->cppheaderbordercolor = $this->borderColor;
    	$ecReqDetail->cppheaderbackcolor = $this->headerBackgroundColor;
    	$ecReqDetail->cpppayflowcolor = $this->paymentPageBackgroundColor;
    	
    	$setECReqType = new \SetExpressCheckoutRequestType();
    	$setECReqType->SetExpressCheckoutRequestDetails = $ecReqDetail;
    	$setECReq = new \SetExpressCheckoutReq();
    	$setECReq->SetExpressCheckoutRequest = $setECReqType;

    	$paypalService = new \PayPalAPIInterfaceServiceService();
    	
    	return $paypalService->SetExpressCheckout($setECReq, $this->getAPICredentials());
    }
    
    /**
     * 
     * @param unknown $token
     * @return \GetExpressCheckoutDetailsResponseType
     */
    public function getExpressCheckoutDetails($token){
    	
    	$ecReqType = new \GetExpressCheckoutDetailsRequestType();
    	$ecReqType->Token = $token;
    	$ecReqType->DetailLevel = "ReturnAll";
    	
    	$ecReq = new \GetExpressCheckoutDetailsReq();
    	$ecReq->GetExpressCheckoutDetailsRequest = $ecReqType;
    	
    	$paypalService = new \PayPalAPIInterfaceServiceService();
    	
    	return $paypalService->GetExpressCheckoutDetails($ecReq, $this->getAPICredentials());
    }
    
    /**
     * 
     * @param Command $command
     * return \DoExpressCheckoutPaymentResponseType
     */
    public function doExpressCheckout(Command $command, \GetExpressCheckoutDetailsResponseDetailsType $ecDetails){
    	$ecReqDetail = new \DoExpressCheckoutPaymentRequestDetailsType();
    	
    	$ecReqDetail->PayerID = $ecDetails->PayerInfo->PayerID;
    	//$ecReqDetail->PaymentAction = $ecDetails->PaymentDetails->PaymentAction; //TODO Here problem !!!!
    	$ecReqDetail->PaymentDetails = $ecDetails->PaymentDetails;
    	$ecReqDetail->Token = $ecDetails->Token;
    	
    	$ecReqType = new \DoExpressCheckoutPaymentRequestType();
    	$ecReqType->DoExpressCheckoutPaymentRequestDetails = $ecReqDetail;
    	$ecReq = new \DoExpressCheckoutPaymentReq();
    	$ecReq->DoExpressCheckoutPaymentRequest = $ecReqType;
    	
    	$paypalService = new \PayPalAPIInterfaceServiceService();
    	
    	return $paypalService->DoExpressCheckoutPayment($ecReq, $this->getAPICredentials());
    }
    
    /**
     * 
     * @param Command $command
     * @return \PaymentDetailsType
     */
    private function getPaymentDetailType(Command $command){
    	$paymentDetail = new \PaymentDetailsType();
    	
    	$paymentDetail->ShipToAddress = $this->getAddressType($command->getCustommer());
    	
    	$paymentDetail->ItemTotal = new \BasicAmountType($this->currency, $command->getItemsAmount());
    	$paymentDetail->ShippingTotal = new \BasicAmountType($this->currency, $command->getShippingAmount());
    	$paymentDetail->ShippingDiscount = new \BasicAmountType($this->currency, $command->getShippingDiscount());
    	$paymentDetail->OrderTotal = new \BasicAmountType($this->currency, $command->getTotalAmount());
    	
    	$paymentDetail->PaymentAction = "Sale";
    	$paymentDetail->SellerDetails = $this->getSellerDetail();
    	
    	foreach($command->getItems() as $item){
    		$paymentDetail->PaymentDetailsItem[] = $this->getItemDetail($item);
    	}
    	
    	$paymentDetail->NotifyURL = $this->router->generate("elendev.checkout.paypal.ipn", array('command' => $command->getId()), true);
    	
    	return $paymentDetail;
    }
    
    /**
     * @param Custommer $custommer
     * @return \AddressType
     */
    private function getAddressType(Custommer $custommer){
    	$address = new \AddressType();
    	
    	$address->CityName = $custommer->getCity();
    	$address->Name = $custommer->getFirstName() . " " . $custommer->getLastName();
    	$address->Street1 = $custommer->getStreet();
    	$address->Street2 = $custommer->getStreet2();
    	$address->PostalCode = $custommer->getZipCode();
    	$address->Country = $custommer->getCountryCode();
    	$address->StateOrProvince = $custommer->getState();
    	
    	return $address;
    }
    
    /**
     * @return \PaymentDetailsItemType
     */
    private function getItemDetail(Item $item){
    	$paypalItem = new \PaymentDetailsItemType();
    	
    	$amount = new \BasicAmountType();
    	$amount->value = $item->getAmount();
    	$amount->currencyID = $this->currency;
    	
    	$paypalItem->Name = $item->getName();
    	$paypalItem->Description = $item->getDescription();
    	$paypalItem->Amount = $amount;
    	$paypalItem->Quantity = $item->getQuantity();
    	
    	return $paypalItem;
    }
    
    /**
     * @return \SellerDetailsType
     */
    private function getSellerDetail(){
    	$sellerDetailType = new \SellerDetailsType();
    	
    	$sellerDetailType->PayPalAccountID = $this->payerId;
    }
    
    /**
     * @return \APICredentialsType
     */
    private function getAPICredentials(){
    	$apiCredentials = new \PPSignatureCredential($this->username, $this->password, $this->signature);
    	
    	return $apiCredentials;
    }
    
    public function setCurrency($currency){
        $this->currency = $currency;
    }
    
    public function setPageStyle($pageStyle) {
        $this->pageStyle = $pageStyle;
    }

    public function setHeaderImage($headerImage) {
        $this->headerImage = $headerImage;
    }

    public function setBorderColor($borderColor) {
        $this->borderColor = $borderColor;
    }

    public function setHeaderBackgroundColor($headerBackgroundColor) {
        $this->headerBackgroundColor = $headerBackgroundColor;
    }

    public function setPaymentPageBackgroundColor($paymentPageBackgroundColor) {
        $this->paymentPageBackgroundColor = $paymentPageBackgroundColor;
    }

    public function setBrandName($brandName) {
        $this->brandName = $brandName;
    }


}

