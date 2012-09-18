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
namespace Elendev\CheckoutBundle\Command;
/**
 *
 * Represent a custommer (a shipping address)
 * 
 * 
 * @author Jonas Renaudot <http://www.elendev.com>
 */
interface Custommer {
    //put your code here
    
    public function getFirstName();
    
    public function getLastName();
    
    public function getStreet();
    
    public function getStreet2();
    
    public function getZipCode();
    
    public function getCity();
    
    public function getState();
    
    /**
     * Two char long country code (us, ch, fr, de, ...)
     */
    public function getCountryCode();
    
    
}

