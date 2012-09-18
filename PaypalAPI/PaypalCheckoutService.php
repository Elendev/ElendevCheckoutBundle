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

    private $version = "65.3";

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
    public function __construct(ContainerInterface $container, $username, $password, $signature, $useSandbox){
        $this->container = $container;
        
        $this->username = $username;
        $this->password = $password;
        $this->signature = $signature;
        
        $this->session = $container->get("session");
        
        $this->currentUrl = $container->get("request")->getUri();
        
        $this->cancelUrl = $this->currentUrl . "?status=cancel";
        $this->returnUrl = $this->currentUrl . "?status=continue";
        
        
        if($useSandbox){
            $this->endpoint = "https://api-3t.sandbox.paypal.com/nvp";
            $this->paypalURL = "https://www.sandbox.paypal.com/webscr&cmd=_express-checkout&token=";
        }else{
            $this->endpoint = "https://api-3t.paypal.com/nvp";
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
     * @param Command $command
     * @return CheckoutResult : result of checkout operations
     */
    public function doCheckout(Command $command){
        
        //use container request
        
        $token = $this->session->get("checkout.payment.token");
        
        $result = new CheckoutResult();
        
        if(!$token){
            $response = $this->setExpressCheckoutRequest($command);
            
            if($response["ACK"] == "Success"){
                $this->session->set("checkout.payment.token", $response["TOKEN"]);
                
                $result->setStatus(CheckoutResult::STATUS_IN_PROGRESS);
                
                $result->setHttpResponse(new RedirectResponse($this->paypalURL . $response["TOKEN"]));
                
            }else{
                $result->setStatus(CheckoutResult::STATUS_ERROR);
                $result->setCommandData($response);
            }
            
        }else{
            //print_
            
            //print_r($this->container->get("request")->request);
            //die("this is the end : " . $this->container->);
            
            if($this->container->get("request")->query->get("status") == "continue"){
                
                $token = $this->session->get("checkout.payment.token");
                
                $detailResponse = $this->getExpressCheckoutDetail($command, $token);
                
                if($detailResponse["ACK"] != "Success"){
                    $result->setCommandData($detailResponse);
                    $result->setStatus(CheckoutResult::STATUS_ERROR);
                    return $result;
                }
                
                $response = $this->doExpressCheckoutPayment($command, $token, $detailResponse["PAYERID"]);
                
                //merge to keep a trace of every steps
                $finalResponse = array_merge($detailResponse, $response);
                
                $result->setCommandData($finalResponse);
                
                if($response["ACK"] != "Success"){
                    $result->setStatus(CheckoutResult::STATUS_ERROR);
                }else{
                    $result->setStatus(CheckoutResult::STATUS_SUCCESS);
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
     * First step : go to paypal and get a valid token
     * @param \Shop\CommandBundle\Entity\Command $command
     * @return Token
     */
    private function setExpressCheckoutRequest(Command $command) {

        $params = array(
            "RETURNURL" => $this->returnUrl,
            "CANCELURL" => $this->cancelUrl,
            "REQCONFIRMSHIPPING" => 0,
            "NOSHIPPING" => 1, // 0 : display shipping, 2 : obtain shipping from paypal account
            "ADDROVERRIDE" => 0,
        );
        
        
        //add personalization parameters
        if($this->pageStyle){
            $params["PAGESTYLE"] = $this->pageStyle;
        }
        if($this->headerImage){
            $params["HDRIMG"] = $this->headerImage;
        }
        if($this->borderColor){
            $params["HDRBORDERCOLOR"] = $this->borderColor;
        }
        if($this->headerBackgroundColor){
            $params["HDRBACKCOLOR"] = $this->headerBackgroundColor;
        }
        if($this->paymentPageBackgroundColor){
            $params["PAYFLOWCOLOR"] = $this->paymentPageBackgroundColor;
        }
        if($this->brandName){
            $params["BRANDNAME"] = $this->brandName;
        }
        
        
        
        $this->setCommandAddressParameters($command, $params);
        $this->setCommandDetailsParameters($command, $params);
        
        $response = $this->getMethodResponse("SetExpressCheckout", $params);

        return $response;
    }
    
    /**
     * Step 2
     * Modify command according to the new details (address, amount, ...)
     * @param $command
     * @param <type> $token
     * @return the associed paypalPayment
     */
    public function getExpressCheckoutDetail(Command $command, $token) {
        $params = array("TOKEN" => $token);//$this->sessionService->getValue("token");

        return $this->getMethodResponse("GetExpressCheckoutDetails", $params);
    }
    
    /**
     * last step : validate payment
     * @param \Shop\CommandBundle\Entity\Command $command
     * @return Token
     */
    private function doExpressCheckoutPayment(Command $command, $token, $payerId) {

        $params = array(
            "TOKEN" => $token,
            "PAYMENTACTION" => "Sale",
            "PAYERID" => $payerId,
        );
        
        $this->setCommandAddressParameters($command, $params);
        $this->setCommandDetailsParameters($command, $params);
        
        $response = $this->getMethodResponse("DoExpressCheckoutPayment", $params);
        
        if(strtolower($response["ACK"]) != "success") {
            echo "<hr>";
            print_r($params);
            echo "<hr>";
            print_r($response);
            die();
        }

        return $response;
    }
    
    
    /**
     * Return URL content as a string
     * @param <type> $url
     * @param <type> $params
     */
    private function getMethodResponse($method, $params = null) {
        $ch = curl_init($this->endpoint);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);

        //turning off the server and peer verification(TrustManager Concept).
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_POST, 1);


        $params["PWD"] = $this->password;
        $params["USER"] = $this->username;
        $params["SIGNATURE"] = $this->signature;
        $params["VERSION"] = $this->version;
        $params["method"] = $method;

        $resultStringArray = array();

        foreach($params as $key => $value) {
            $resultStringArray[] = urlencode($key) . "=" . urlencode($value);
        }

        $paramString = implode("&", $resultStringArray);

        curl_setopt($ch, \CURLOPT_POSTFIELDS, $paramString);

        $response = curl_exec($ch);

        return $this->responseToArray($response);

    }
    
    /**
     * set address details
     * @param Command $command
     * @param type $params
     * @param type $commandNumber 
     */
    private function setCommandAddressParameters(Command $command, &$params, $commandNumber = 0){
        
        $customer = $command->getCustommer();
        
        $params["PAYMENTREQUEST_".$commandNumber."_SHIPTONAME"] = substr($customer->getFirstName() . " " . $customer->getLastName(), 0, 32);
        $params["PAYMENTREQUEST_".$commandNumber."_SHIPTOSTREET"] = substr($customer->getStreet(), 0, 100);
        
        if($customer->getStreet() != null && strlen($customer->getStreet()) > 0){
            $params["PAYMENTREQUEST_".$commandNumber."_SHIPTOSTREET2"] = substr($customer->getStreet2(), 0, 100);
        }
        
        
        $params["PAYMENTREQUEST_".$commandNumber."_SHIPTOCITY"] = substr($customer->getCity(), 0, 40);
        $params["PAYMENTREQUEST_".$commandNumber."_SHIPTOSTATE"] = substr($customer->getState(), 0, 40);
        $params["PAYMENTREQUEST_".$commandNumber."_SHIPTOZIP"] = substr($customer->getZipCode(), 0, 20);
        $params["PAYMENTREQUEST_".$commandNumber."_SHIPTOCOUNTRYCODE"] = substr($customer->getCountryCode(), 0, 2);
        
    }
    
    /**
     * Add every objects
     * @param Command $command
     * @param type $params
     */
    private function setCommandDetailsParameters(Command $command, &$params, $commandNumber = 0){
        
        $params["PAYMENTREQUEST_".$commandNumber."_SELLERPAYPALACCOUNTID"] = $this->payerId;
        
        $params["PAYMENTREQUEST_" . $commandNumber . "_AMT"] = $command->getTotalAmount();
        $params["PAYMENTREQUEST_" . $commandNumber . "_CURRENCYCODE"] = $this->currency;
        $params["PAYMENTREQUEST_" . $commandNumber . "_ITEMAMT"] = $command->getItemsAmount();
        $params["PAYMENTREQUEST_" . $commandNumber . "_SHIPPINGAMT"] = $command->getShippingAmount();
        $params["PAYMENTREQUEST_" . $commandNumber . "_SHIPDISCAMT"] = $command->getShippingDiscount();
        
        $params["PAYMENTREQUEST_" . $commandNumber . "_PAYMENTACTION"] = "Sale";
        
        
        $count = 0;
        foreach($command->getItems() as $item){
            
            $params["L_PAYMENTREQUEST_" . $commandNumber . "_NAME" . $count] = substr($item->getName(), 0, 127);
            $params["L_PAYMENTREQUEST_" . $commandNumber . "_DESC" . $count] = substr($item->getDescription(), 0, 127);
            $params["L_PAYMENTREQUEST_" . $commandNumber . "_AMT" . $count] = $item->getAmount();
            $params["L_PAYMENTREQUEST_" . $commandNumber . "_QTY" . $count] = $item->getQuantity();
            
            $count ++;
        }
        
    }
    
    /**
     * Change the response to an associative array
     * @param type $response
     * @return type 
     */
    private function responseToArray($response) {
        $responseArray = explode("&", $response);

        $associativeArrayResponse = array();

        foreach($responseArray as $token) {
            $tokens = explode("=", $token);
            $associativeArrayResponse[$tokens[0]] = urldecode($tokens[1]);
        }
        return $associativeArrayResponse;
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

