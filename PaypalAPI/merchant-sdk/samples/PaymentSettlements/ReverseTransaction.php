<?php
$path = '../../lib';
set_include_path(get_include_path() . PATH_SEPARATOR . $path);
require_once('services/PayPalAPIInterfaceService/PayPalAPIInterfaceServiceService.php');
require_once('PPLoggingManager.php');
/**
 * Get required parameters from the web form for the request
 */
$logger = new PPLoggingManager('ReverseTransaction');

$reverseTransactionReqestDetails = new ReverseTransactionRequestDetailsType();
$reverseTransactionReqestDetails->TransactionID = $_REQUEST['transID'];

$reverseTransactionReqest = new ReverseTransactionRequestType();
$reverseTransactionReqest->ReverseTransactionRequestDetails = $reverseTransactionReqestDetails;


$reverseTransactionReq = new ReverseTransactionReq();
$reverseTransactionReq->ReverseTransactionRequest = $reverseTransactionReqest;

$paypalService = new PayPalAPIInterfaceServiceService();
try {
	/* wrap API method calls on the service object with a try catch */
	$reverseTransactionResponse = $paypalService->ReverseTransaction($reverseTransactionReq);
} catch (Exception $ex) {
	include_once("../Error.php");
	exit;
}
if(isset($reverseTransactionResponse)) {
	echo "<table>";
	echo "<tr><td>Ack :</td><td><div id='Ack'>$reverseTransactionResponse->Ack</div> </td></tr>";
	//echo "<tr><td>Ack :</td><td><div id='Ack'>$reverseTransactionResponse->ReverseTransactionResponseDetails->ReverseTransactionID</div> </td></tr>";
	echo "</table>";
	echo "<pre>";
	print_r($reverseTransactionResponse);
	echo "</pre>";
}
require_once '../Response.php';