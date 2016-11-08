<?php 
/*
I need a php script which fetches and acknowledges orders from walmart using the walmart API using the following library: https://github.com/fillup/walmart-partner-api-sdk-php The order information should be sent by email (sendmail). The script will be run via cron job at regular intervals.
*/

/*
1. Fetch order
*/

use Walmart\Order;

$client = new Order([
    'consumerId' => getenv('CONSUMER_ID'),
    'privateKey' => getenv('PRIVATE_KEY'),
    'wmConsumerChannelType' => getenv('WM_CONSUMER_CHANNEL_TYPE'),
]);

/*i just assumed date here because we will be using cron for certain interval*/
$orders = $client->list([   
    'createdStartDate' => '2016-06-01', 
    'createdEndDate' => '2016-06-02',  
]);


/*
2. Acknowledge and Email each order
*/


if (count($orders)) {
	foreach ($orders as $order) {
			

		//acknowledge the order
		$a_order = $client->acknowledge([
   			 'purchaseOrderId' => $order['purchaseOrderId'], // required
		]);


 		//  this depends on the info you like to email
		$message = "


		";

		// In case any of our lines are larger than 70 characters, we should use wordwrap()
		$message = wordwrap($message, 70, "\r\n");

		// Send
		mail('customer@email.com', 'Order acknowledged - purchase order id#'.$order['purchaseOrderId'], $message);


	}


}