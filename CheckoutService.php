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


use Elendev\CheckoutBundle\Command\Command;

use Symfony\Component\HttpFoundation\Request;
use Elendev\CheckoutBundle\CheckoutProcessHandler;
/**
 * Represent a checkout service (like paypal checkout service or 
 * google checkout service)
 * 
 * @author Jonas Renaudot <http://www.elendev.com>
 */
abstract class CheckoutService {
    
    /**
     * Do command checkout
     * @param Command $command current comment to use 
     * @return CheckoutResult return command results
     */
    public abstract function doCheckout(Command $command);
    
}