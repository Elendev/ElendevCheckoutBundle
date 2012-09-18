ElendevCheckoutBundle
=====================

Bundle configuration
--------------------
Symfony 2 configuration config.yml

If you don't want to use paypal checkout service, you can remove the paypal configuration. Other available configurations are visible below :

	elendev_checkout:
    	paypal:
        	username: 'your_paypal_username'
        	password: 'your_paypal_password'
        	signature: 'your_paypal_signature'
        	use_sandbox: 'true/false'
        	currency: 'EUR/USD/..., optionnal, default : EUR'
        	page_style: 'saved pagestyle available on paypal website, optionnal'
        	header_image: 'image visible on top left, optionnal'
        	header_border_color: '6 char length color code (e.g. 888888), optionnal'
        	header_background_color: '6 char length color code (e.g. 777777), optionnal'
        	payment_page_background_color: '6 char length color code (e.g. 666666), optionnal'
        	brand_name: 'name visible on top of payment page, optionnal'

Simple example
--------------
This page explain how to use the ElendevCheckoutBundle into a simple controller.

Simple implementation example
Here you can see a really simple implementation example :

	/**
	 * @author Jonas Renaudot
	 */
	class OrderingController extends Controller {
	 
    	...
 	
	    public function doCheckoutAction($checkoutMethod){
	 
	        $command = ...; //instance of Elendev\CheckoutBundle\Command\Command
	 
	        $checkoutProvider = $this->get("elendev.checkout.service_provider")->getService($checkoutMethod);
 
	        $result = $checkoutProvider->doCheckout($command);
	 
 	
        	if($result->getStatus() == CheckoutResult::STATUS_IN_PROGRESS){
            	return $result->getHttpResponse();
        	}else if($result->getStatus() == CheckoutResult::STATUS_CANCELED){
            	//Command canceled : return to the info page
            	$this->getRequest()->getSession()->setFlash("info", "Command canceled");
            	return new RedirectResponse($this->generateUrl("ordering.info"));
        	}else if($result->getStatus() == CheckoutResult::STATUS_ERROR){
            	//Error occured : return to the info page allowing user to chose an other payment method
            	$this->get('session')->setFlash("error", "An error occured, retry again please");
            	return new RedirectResponse($this->generateUrl("ordering.info"));
        	}else if($result->getStatus() == CheckoutResult::STATUS_SUCCESS){
            	//save current command and mark it as done
            	...
            	//go to confirmation page
            	return new RedirectResponse($this->generateUrl("ordering.confirmation"));
        	}
    	}
 	
	    ...
	}


Create a new service class
--------------------------
This part explain how to add a new service to the CheckoutServiceProvider. This service will be available through the CheckoutServiceProvider->getService('id') method.

- Creating a new service class
To create a valid service class you need to extends the Elendev\CheckoutBundle\CheckoutService class. The simplest checkout service you can implement is visible below :

	namespace Your\Service\Namespace;
	
	use Elendev\CheckoutBundle\CheckoutService;
	use Elendev\CheckoutBundle\Command\Command;
	use Elendev\CheckoutBundle\CheckoutResult;

	class SimpleCheckoutService extends CheckoutService {
	    
	    /**
	     * @param Command $command
 	    * @return CheckoutResult : result of checkout operations
  	   */
 	   public function doCheckout(Command $command){
   	     
    	    $result = new CheckoutResult();

	        $result->setStatus(CheckoutResult::STATUS_SUCCESS);
        
    	    return $result;
   	 	}
	}
When you return a `CheckoutResult::STATUS_IN_PROGRESS status in the returned response object, the doCheckout method will be called again on page reloading (or on redirect on the current page).

- Configure a new service
Add your new checkout service as a simple service with the elendev.checkout.service tag. The specified id parameter is the service id that you need to use to get the service with the CheckoutServiceProvider->getService('id') method.

	your.service.id:
    	class : Your\Checkout\Service\Class
    	tags :
        	- {name : elendev.checkout.service, id: your_service_id}