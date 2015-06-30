<?php

error_reporting(1);

include_once("openerp_models.php");
require_once('send_sms.php');

/////////////////////////////////////////////////////////////////////
//confirm that a username and password is set 

if (isset($_SERVER['PHP_AUTH_USER']) && $_SERVER['PHP_AUTH_PW']) {

    //password and username -- Basic Authentication
    $username = $_SERVER['PHP_AUTH_USER'];
    $password = $_SERVER['PHP_AUTH_PW'];

    ///important parameters
    $phone_no = $_REQUEST['from']; //get from gateway, result->from
    $sale_order_no = $_REQUEST['sale_order_no']; // get from gateway, result->sale order
    $payment_no = $_REQUEST['payment_no']; // get from gateway, result->payment number
    $amount   = $_REQUEST["amount"];   // get from gateway, result->amount

	try{
        pushToOpenERP($username,$password,$phone_no,$sale_order_no,$payment_no,$amount);
	}catch (Exception $e){
		pushSMSToCSV($phone_no,$sale_order_no,$payment_no,$amount);
	}

} else {
    //If this file has been accessed without login credentials, we read the csv file and post values
	$row = 1;
	if (($handle = fopen("sms_messages.csv", "r")) !== FALSE) {
	    $new_contents = array();
		while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            //$handle_delete = fopen("sms_messages.csv", "w+");
			try{
                pushToOpenERP('admin','@dm1n2014_cop1a_erp',$data[0],$data[1],$data[2],$data[3],$data[4],$data[5]);
				//exit;
                //file_put_contents("sms_messages.csv", implode('',$data));
       	    	//fputcsv($handle_delete, "");
			}catch (Exception $e){
                print 'Error is -'.$e->getMessage();
    	    }
			$row++;
		}
		fclose($handle);
		//fclose($handle_delete);
	}
	//fclose($fp);

    header('WWW-Authenticate: Basic realm="Copia ERP"');
    header('HTTP/1.0 401 Unauthorized');
    print 'Access Denied';
    exit();
}

function pushSMSToCSV($phone_no,$sale_order_no,$payment_no,$amount){
	$list = array (
		array($phone_no,$sale_order_no,$payment_no,$amount),
	);

	$fp = fopen('sms_messages.csv', 'a');

	foreach ($list as $fields) {
		fputcsv($fp, $fields);
	}

	fclose($fp);
}

function pushToOpenERP($username,$password,$phone_no,$sale_order_no,$payment_no,$amount){
    //openerp instance, pass login,password,dbname and serverURL
//    $erp_model = new OpenERPXmlrpc($username, $password, 'copiaERP', 'http://54.201.157.222:8069/xmlrpc/');
    $erp_model = new OpenERPXmlrpc($username, $password, 'copiaERP', 'http://127.0.0.1:8069/xmlrpc/');
    //
    $date = strtotime($date);
    $date = date('Y-m-d H:i:s',strtotime("+3 hour", $date));
    /* TODO
     * after a successful login, Create payment number on respective sale order.
     */

    $sale_order_search = $erp_model->search('sale.order', 'name', '=', $sale_order_no);

    if($sale_order_search){

            $payment_number_line = array(
                'name' => $payment_no,
                'amount' => $amount,
		'date'  => $date,
                'sale_id' => new xmlrpcval($sale_order_search[0],'int'),

            );
            //echo "phone is ".$phone."<br/>";
            $payment_no_id = $erp_model->createRecord($payment_number_line, 'sale.order');
    		
    	
    }
}

function get_agents_outstanding_balance($username,$password){

    	$erp_model = new OpenERPXmlrpc($username, $password, 'copiaERP', 'http://127.0.0.1:8069/xmlrpc/');

	$vendor_payment_array = $erp_model->call_function_func_string_param('sale.order', 'vendor_payment_array' );

	return $vendor_payment_array
}


?>
