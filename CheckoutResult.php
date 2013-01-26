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

use Elendev\CheckoutBundle\Command\Custommer;

/**
 * Response returned by a checkout service on docheckout method call
 *
 * @author Jonas Renaudot <http://www.elendev.com>
 */
class CheckoutResult {
    
    const STATUS_SUCCESS = 0;
    const STATUS_PENDING = 1;
    const STATUS_IN_PROGRESS = 2;
    const STATUS_CANCELED = 3;
    const STATUS_ERROR = 4;
    
    //custommer informations, available when command is marked 'success'
    private $custommer;
    
    //current command status
    private $status;
    
    //current command data, available when command has 'success' status
    private $commandData;
    
    private $token;
    
    /**
     *
     * @var HTTPResponse response to return when status is not success ?
     */
    private $httpResponse;
    
    
    public function __construct($custommer = null, $status = CheckoutResult::STATUS_IN_PROGRESS, $commandData = null, $token = null){
        $this->custommer = $custommer;
        $this->status = $status;
        $this->commandData = $commandData;
    }
    
    /**
     * Return custommer data (eventually new address, ...)
     */
    public function getCustommer() {
        return $this->custommer;
    }

    public function setCustommer($custommer) {
        $this->custommer = $custommer;
    }

    /**
     * Current status
     */
    public function getStatus() {
        return $this->status;
    }

    public function setStatus($status) {
        $this->status = $status;
    }

    /**
     * Return a simple dictionnary containing all data details or null
     */
    public function getCommandData() {
        return $this->commandData;
    }

    public function setCommandData($commandData) {
        $this->commandData = $commandData;
    }
    
    public function getHttpResponse() {
        return $this->httpResponse;
    }

    public function setHttpResponse($httpResponse) {
        $this->httpResponse = $httpResponse;
    }

	public function getToken(){
		return $this->token;
	}
	
	public function setToken($token){
		$this->token = $token;
	}
}


