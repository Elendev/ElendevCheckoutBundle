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
 * Represent a command and have to be passed to the checkout service
 * to do a checkout.
 * To do discounts on items (like gift voucher) you need to add
 * an item with negative amount.
 *
 * @author Jonas Renaudot <http://www.elendev.com>
 */
interface Command {
    //put your code here
    public function getId();
	
    public function getToken();
    
    public function getCustommer();
    
    public function getItems();
    
    public function getTotalAmount();
    
    public function getShippingAmount();
    
    public function getItemsAmount();
    
    public function getShippingDiscount();
}


