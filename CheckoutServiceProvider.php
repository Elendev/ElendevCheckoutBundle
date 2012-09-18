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
namespace Elendev\CheckoutBundle;

use Elendev\CheckoutBundle\CheckoutService;
use Elendev\CheckoutBundle\CheckoutProcessHandler;
/**
 * Contain and provide all checkout services available
 *
 * @author Jonas Renaudot <http://www.elendev.com>
 */
class CheckoutServiceProvider {
    //put your code here
    
    
    private $services = array();
    
    
    public function addService($id, CheckoutService $service){
        $this->services[$id] = $service;
    }
    
    
    public function getServices(){
        return $this->services;
    }
    
    public function getService($id){
        
        if(!isset($this->services[$id]))
            throw new \Exception("Can't find payment service with id " . $id);
        
        return $this->services[$id];
    }
}

