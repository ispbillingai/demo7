<?php
require_once 'config.php';
require_once 'system/orm.php';
require_once 'system/autoload/PEAR2/Autoload.php';
include "system/autoload/Hookers.php";

ORM::configure("mysql:host=$db_host;dbname=$db_name");
ORM::configure('username', $db_user);
ORM::configure('password', $db_password);
ORM::configure('return_result_sets', true);
ORM::configure('logging', true);

$captureLogs = file_get_contents("php://input");
$analizzare = json_decode($captureLogs);

$now = new DateTime('now', new DateTimeZone('GMT+3'));
$receivedTimestamp = $now->format('Y-m-d H:i:s');
file_put_contents('secondupdate.log', "Received callback data in second_update.php at " . $receivedTimestamp . ":\n" . $captureLogs . "\n", FILE_APPEND);

sleep(12);

$response_code = $analizzare->Body->stkCallback->ResultCode;
$resultDesc = ($analizzare->Body->stkCallback->ResultDesc);
$merchant_req_id = ($analizzare->Body->stkCallback->MerchantRequestID);
$checkout_req_id = ($analizzare->Body->stkCallback->CheckoutRequestID);

$amount_paid = ($analizzare->Body->stkCallback->CallbackMetadata->Item['0']->Value);
$mpesa_code = ($analizzare->Body->stkCallback->CallbackMetadata->Item['1']->Value);
$sender_phone = ($analizzare->Body->stkCallback->CallbackMetadata->Item['3']->Value);  // Adjusted index to 3 for phone number

// Log the extracted callback data
file_put_contents('secondupdate.log', "Extracted callback data:\nResponse Code: $response_code\nResult Description: $resultDesc\nMerchant Request ID: $merchant_req_id\nCheckout Request ID: $checkout_req_id\nAmount Paid: $amount_paid\nM-PESA Code: $mpesa_code\nSender Phone: $sender_phone\n", FILE_APPEND);

if ($response_code == "0") {
    $PaymentGatewayRecord = ORM::for_table('tbl_payment_gateway')
        ->where('checkout', $checkout_req_id)
        ->order_by_desc('id')
        ->find_one();

    if (!$PaymentGatewayRecord) {
        file_put_contents('secondupdate.log', "PaymentGatewayRecord not found for Checkout Request ID: $checkout_req_id\n", FILE_APPEND);
        exit();
    }

    $uname = $PaymentGatewayRecord->username;
    $plan_id = $PaymentGatewayRecord->plan_id;
    $routerId = $PaymentGatewayRecord->routers_id;

    $userid = ORM::for_table('tbl_customers')
        ->where('username', $uname)
        ->order_by_desc('id')
        ->find_one();

    if (!$userid) {
        file_put_contents('secondupdate.log', "User not found for username: $uname\n", FILE_APPEND);
        exit();
    }

    $plans = ORM::for_table('tbl_plans')
        ->where('id', $plan_id)
        ->order_by_desc('id')
        ->find_one();

    if (!$plans) {
        file_put_contents('secondupdate.log', "Plan not found for plan_id: $plan_id\n", FILE_APPEND);
        exit();
    }

    $plan_type = $plans->type;
    $plan_name = $plans->name_plan;
    $validity = $plans->validity;
    $units = $plans->validity_unit;

    // Convert units to seconds
    $unit_in_seconds = [
        'Mins' => 60,
        'Hrs' => 3600,
        'Days' => 86400,
        'Months' => 2592000 // Assuming 30 days per month for simplicity
    ];

    $unit_seconds = $unit_in_seconds[$units];
    $expiry_timestamp = $now->getTimestamp() + ($validity * $unit_seconds); // Use $now to get the current timestamp

    // Set the timezone explicitly for expiry date and time
    $expiry_datetime = new DateTime("@$expiry_timestamp");
    $expiry_datetime->setTimezone(new DateTimeZone('GMT+3'));
    $expiry_date = $expiry_datetime->format('Y-m-d');
    $expiry_time = $expiry_datetime->format('H:i:s');

    $recharged_on = $now->format('Y-m-d');
    $recharged_time = $now->format('H:i:s');

    // Log the recharge details
    file_put_contents('secondupdate.log', "Recharge details:\nPlan Type: $plan_type\nPlan Name: $plan_name\nValidity: $validity $units\nExpiry Date: $expiry_date\nExpiry Time: $expiry_time\n", FILE_APPEND);

    // Update any existing records for the username to status 'off'
    $existing_recharges = ORM::for_table('tbl_user_recharges')
        ->where('username', $uname)
        ->where('status', 'on')
        ->find_many();

    foreach ($existing_recharges as $record) {
        $record->status = 'off';
        $record->save();
    }

    // Include the external script
    $file_path = 'system/adduser.php';
    if (file_exists($file_path)) {
        include_once $file_path;
        file_put_contents('secondupdate.log', "Included file: $file_path\n", FILE_APPEND);
    } else {
        file_put_contents('secondupdate.log', "File not found: $file_path\n", FILE_APPEND);
        exit();
    }

    // Fetch the router name
    $router = ORM::for_table('tbl_routers')
        ->where('id', $routerId)
        ->find_one();

    if (!$router) {
        file_put_contents('secondupdate.log', "Router not found for router ID: $routerId\n", FILE_APPEND);
        exit();
    }

    $router_name = $router->name;

    // Delete existing records for the username
    $deleted_count = ORM::for_table('tbl_user_recharges')
        ->where('username', $uname)
        ->delete_many();

    file_put_contents('secondupdate.log', "Deleted $deleted_count existing recharge records for username: $uname\n", FILE_APPEND);

    // Insert new record into tbl_user_recharges
    try {
        ORM::for_table('tbl_user_recharges')->create(array(
            'customer_id' => $userid->id,
            'username' => $uname,
            'plan_id' => $plan_id,
            'namebp' => $plan_name,
            'recharged_on' => $recharged_on,
            'recharged_time' => $recharged_time,
            'expiration' => $expiry_date,
            'time' => $expiry_time,
            'status' => "on",
            'method' => $PaymentGatewayRecord->gateway . "-" . $mpesa_code,
            'routers' => $router_name, // Use the router name instead of the ID
            'type' => $plan_type
        ))->save();

        file_put_contents('secondupdate.log', "New recharge record inserted successfully for username: $uname\n", FILE_APPEND);

        // Check if a transaction with the same invoice already exists
        $existingTransactions = ORM::for_table('tbl_transactions')
            ->where('invoice', $mpesa_code)
            ->order_by_desc('id')
            ->find_many();

        if (count($existingTransactions) > 1) {
            // Delete older transactions and keep the latest one
            $keepTransaction = array_shift($existingTransactions); // Keep the latest transaction
            foreach ($existingTransactions as $transaction) {
                $transaction->delete();
                file_put_contents('secondupdate.log', "Deleted duplicate transaction with invoice: $mpesa_code\n", FILE_APPEND);
            }
        }

        if (count($existingTransactions) == 0) {
            // Insert new record into tbl_transactions
            ORM::for_table('tbl_transactions')->create(array(
                'invoice' => $mpesa_code,
                'username' => $uname,
                'plan_name' => $plan_name,
                'price' => $amount_paid,
                'recharged_on' => $recharged_on,
                'recharged_time' => $recharged_time,
                'expiration' => $expiry_date,
                'time' => $expiry_time,
                'method' => $PaymentGatewayRecord->gateway . "-" . $mpesa_code,
                'routers' => $router_name,
                'type' => $plan_type
            ))->save();

            file_put_contents('secondupdate.log', "New transaction record inserted successfully for username: $uname\n", FILE_APPEND);
        } else {
            file_put_contents('secondupdate.log', "Transaction record already exists for invoice: $mpesa_code\n", FILE_APPEND);
        }

        // Fetch the customer details
        $customer = ORM::for_table('tbl_customers')
            ->where('username', $uname)
            ->find_one();

        if ($customer) {
            // Prepare the customer and transaction data
            $cust = array(
                'phonenumber' => $customer->phonenumber,
                'fullname' => $customer->fullname,
                'password' => $customer->password
            );
            $trx = array(
                'invoice' => $mpesa_code,
                'recharged_on' => $recharged_on,
                'recharged_time' => $recharged_time,
                'method' => $PaymentGatewayRecord->gateway . "-" . $mpesa_code,
                'type' => $plan_type,
                'plan_name' => $plan_name,
                'price' => $amount_paid,
                'username' => $uname,
                'expiration' => $expiry_date,
                'time' => $expiry_time
            );

            Message::sendInvoice($cust, $trx);
        }
    } catch (Exception $e) {
        file_put_contents('secondupdate.log', "Error inserting new recharge record: " . $e->getMessage() . "\n", FILE_APPEND);
    }

    $PaymentGatewayRecord->status = 2;
    $PaymentGatewayRecord->paid_date = $now->format('Y-m-d H:i:s');
    $PaymentGatewayRecord->gateway_trx_id = $mpesa_code;
    $PaymentGatewayRecord->save();

    // Log completion
    $completionTimestamp = (new DateTime('now', new DateTimeZone('GMT+3')))->format('Y-m-d H:i:s');
    file_put_contents('secondupdate.log', "Process completed at $completionTimestamp\n", FILE_APPEND);
} else {
    file_put_contents('secondupdate.log', "Response code is not 0. No action taken.\n", FILE_APPEND);
}
?>
