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

namespace Elendev\CheckoutBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Definition;

/**
 * 
 *
 * @author Jonas Renaudot <http://www.elendev.com>
 */
class CheckoutServicesLoaderPass implements CompilerPassInterface{
    //put your code here
    public function process(ContainerBuilder $container){
        if (false === $container->hasDefinition('elendev.checkout.service_provider')) {
            return;
        }
        
        $definition = $container->getDefinition('elendev.checkout.service_provider');

        
        if(!$container->getParameter("elendev.checkout.paypal.enabled")){
            $container->removeDefinition("elendev.checkout.service_provider.paypal");
        }else{
            $this->initPaypalService($container, $container->getDefinition("elendev.checkout.service_provider.paypal"));
        }
        
        
        
        // Extensions must always be registered before everything else.
        // For instance, global variable definitions must be registered
        // afterward. If not, the globals from the extensions will never
        // be registered.
        
        $calls = $definition->getMethodCalls();
        $definition->setMethodCalls(array());
        foreach ($container->findTaggedServiceIds('elendev.checkout.service') as $id => $attributes) {
            
            $attrib = array();
            
            foreach ($attributes as $subAttr) {
                $attrib = array_merge($attrib, $subAttr);
            }
            
            if(isset($attrib["id"]))
                $currentId = $attrib["id"];
            else
                $currentId = $id;
            
            $definition->addMethodCall('addService', array($currentId, new Reference($id)));
        }
        
        $definition->setMethodCalls(array_merge($definition->getMethodCalls(), $calls));
    }
    
    /**
     * Init the paypal service with values
     */
    private function initPaypalService(ContainerBuilder $container, Definition $paypal){
        
        /*
         * PAGESTYLE //page style defined in paypal account
         * HDRIMG //image left
         * HDRBORDERCOLOR //border color for header
         * HDRBACKCOLOR //background color for header
         * PAYFLOWCOLOR //background color for payment page
         * BRANDNAME //name of enterprise
         */
        
        if($container->hasParameter("elendev.checkout.paypal.page_style")){
            $paypal->addMethodCall("setPageStyle", array($container->getParameter("elendev.checkout.paypal.page_style")));
        }
        
        if($container->hasParameter("elendev.checkout.paypal.header_image")){
            $paypal->addMethodCall("setHeaderImage", array($container->getParameter("elendev.checkout.paypal.header_image")));
        }
        
        if($container->hasParameter("elendev.checkout.paypal.header_border_color")){
            $paypal->addMethodCall("setBorderColor", array($container->getParameter("elendev.checkout.paypal.header_border_color")));
        }
        
        if($container->hasParameter("elendev.checkout.paypal.header_background_color")){
            $paypal->addMethodCall("setHeaderBackgroundColor", array($container->getParameter("elendev.checkout.paypal.header_background_color")));
        }
        
        if($container->hasParameter("elendev.checkout.paypal.payment_page_background_color")){
            $paypal->addMethodCall("setPaymentPageBackgroundColor", array($container->getParameter("elendev.checkout.paypal.payment_page_background_color")));
        }
        
        if($container->hasParameter("elendev.checkout.paypal.brand_name")){
            $paypal->addMethodCall("setBrandName", array($container->getParameter("elendev.checkout.paypal.brand_name")));
        }
        
        if($container->hasParameter("elendev.checkout.paypal.currency")){
            $paypal->addMethodCall("setCurrency", array($container->getParameter("elendev.checkout.paypal.currency")));
        }
        
    }
}

