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

namespace Elendev\CheckoutBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;
/**
 * Description of ElendevPaypalVendorExtension
 *
 * @author Jonas Renaudot <http://www.elendev.com>
 */
class ElendevCheckoutExtension extends Extension {
    //put your code here
    public function load(array $configs, ContainerBuilder $container){
        
        //create a "flat" config by merging dev and test ones : USE COMPILER
        $config = array();
        
        foreach ($configs as $subConfig) {
            $config = array_merge($config, $subConfig);
        }
        
        
        //if(!isset($config["process_handler_id"])){
        //    throw new \Exception("Config process_handler_id have to exist");
        //}
        
        //$container->setAlias("elendev.checkout.provided_process_handler", $config["process_handler_id"]);
        
        //OrderManagerService
        if(isset($config["order_manager"])){
        	$container->setAlias("elendev.checkout.order_manager", $config["order_manager"]);
        }
        
        if(isset($config["paypal"])){
            $container->setParameter("elendev.checkout.paypal.enabled", true);
            
            $this->initPaypal($config, $container);
        }else{
            $container->setParameter("elendev.checkout.paypal.enabled", false);
            
            $container->setParameter("elendev.checkout.paypal.username", null);
            $container->setParameter("elendev.checkout.paypal.password", null);
            $container->setParameter("elendev.checkout.paypal.signature", null);
            $container->setParameter("elendev.checkout.paypal.use_sandbox", null);
        }
        
        
        
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . "/../Resources/config"));
        
        $loader->load("services.yml");
        
    }
    
    /**
     * Init paypal service
     * @param type $config 
     */
    private function initPaypal($config, ContainerBuilder $container){
        $container->setParameter("elendev.checkout.paypal.username", $config["paypal"]["username"]);
        $container->setParameter("elendev.checkout.paypal.password", $config["paypal"]["password"]);
        $container->setParameter("elendev.checkout.paypal.signature", $config["paypal"]["signature"]);
        $container->setParameter("elendev.checkout.paypal.use_sandbox", $config["paypal"]["use_sandbox"]);
            
        /*
         * PAGESTYLE //page style defined in paypal account
         * HDRIMG //image left
         * HDRBORDERCOLOR //border color for header
         * HDRBACKCOLOR //background color for header
         * PAYFLOWCOLOR //background color for payment page
         * BRANDNAME //name of enterprise
         */
        
        $keys = array("page_style", "header_image", "header_border_color", "header_background_color", "payment_page_background_color", "brand_name", "currency");
        
        foreach($keys as $key){
            if(isset($config["paypal"][$key])){
                $container->setParameter("elendev.checkout.paypal." . $key, $config["paypal"][$key]);
            }
        }
    }
}

