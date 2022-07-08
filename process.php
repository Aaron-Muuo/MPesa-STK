<?php

    $phone = $_POST["phone"];
    $amount = $_POST["amount"];

    //endpoints url
    $stk_request_url = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
    $outh_url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';


    //provided keys and codes
    $safaricom_pass_key = "PASTE_KEY_HERE";
    $safaricom_bussiness_short_code = "174379";
    $safaricom_party_b = "174379";
    $safaricom_Consumer_Key = "PASTE_CONSUMER_KEY_HERE"; 
    $safaricom_Consumer_Secret = "PASTE_CONSUMER_SECRET_HERE";

    //combine to get an access token
    $outh = $safaricom_Consumer_Key . ':' . $safaricom_Consumer_Secret;

    //network calls using curl to get authorization
    $curl_outh = curl_init($outh_url);
    curl_setopt($curl_outh, CURLOPT_RETURNTRANSFER, 1);
    $credentials = base64_encode($outh);
    curl_setopt($curl_outh, CURLOPT_HTTPHEADER, array('Authorization: Basic ' . $credentials));
    curl_setopt($curl_outh, CURLOPT_HEADER, false);
    curl_setopt($curl_outh, CURLOPT_SSL_VERIFYPEER, false);

    $curl_outh_response = curl_exec($curl_outh);
    $json = json_decode($curl_outh_response, true);

    //generate time
    $time = date("YmdHis", time());

    //generate password
    $password = $safaricom_bussiness_short_code . $safaricom_pass_key . $time;
    $transaction_password = base64_encode($password);
    
    //stk
    $curl_stk = curl_init();

    curl_setopt($curl_stk, CURLOPT_URL, $stk_request_url);
    curl_setopt($curl_stk, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'Authorization:Bearer ' . $json['access_token']));
    
    //prepare the data to send
    $curl_post_data = array(
        "BusinessShortCode"=> $safaricom_bussiness_short_code,
        "Password"=> $transaction_password,
        "Timestamp"=> $time,
        "TransactionType"=>"CustomerPayBillOnline",
        "Amount"=> $amount,
        "PartyA"=> $phone,
        "PartyB"=> $safaricom_party_b,
        "PhoneNumber"=> $phone,
        "CallBackURL"=>"https://mydomain.com/path",
        "AccountReference"=>"Ronnabble Digital LTD",
        "TransactionDesc"=>"Test payment"
    );

    $data_string = json_encode($curl_post_data);
    curl_setopt($curl_stk, CURLOPT_POST, 1);
    curl_setopt($curl_stk, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($curl_stk, CURLOPT_RETURNTRANSFER, 1);

    //execute and close connection
    $response = curl_exec($curl_stk);
    curl_close($curl_stk);

   // returrn response
    echo $response;
?>