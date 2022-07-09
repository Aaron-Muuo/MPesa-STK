<?php

    include 'Mpesa.php';

    /**
     * User's data needed - fetch from the database or get from form/request params
     * Phone number/MSISDN - user's phone number, must be a registered Safaricom number and should begin with 254
     * Amount - to be paid by the user
     */
    $phone = $_POST["phone"];
    $amount = $_POST["amount"];

    $mpesa = new Mpesa();
    $transaction_session = $mpesa->validate_transaction_input($phone,$amount);

    //echo $transaction_session->get_validation_response();

    $transaction_session->initiate_transaction()->then(function($response){

        //do something extra here with the response returned from Safaricom
        echo $response;

    });

?>