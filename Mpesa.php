<?php
/**
 * ============================================================
 * Safaricom MPesa STK Push with PHP
 * ============================================================
 * @author Aaron M
 * Github: https://github.com/Aaron-Muuo/MPesa-STK
 * Version 0.2.0
 */

/**
 * ============================================================
 * MPESA Transaction class
 * ============================================================
 * Mpesa class to validate input and initiate MPesa STK transactions
 * Remeber to get your test credentials from Daraja developer's portal
 */

class Mpesa{

    protected $validation_response = null;
    protected $transaction_response = null;

     /**
     * ============================================================
     * Endpoints urls
     * ============================================================
     * STK URL - for the stk push service to prompt the user to enter pin
     * OUTH URL - endpoint for getting the authorization to perform transaction requests
     * CALLBACK URL - endpoint to your callback script (optional)
     */

    protected $stk_request_url = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
    protected $outh_url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
    protected $callback_url = "YOUR_CALLBACK_URL"; // eg https://mydomain.com/path

    /**
     * ============================================================
     * API keys
     * ============================================================
     * These keys are provided when you create a sandbox application from the
     * portal. Consumer key and Consumer secret can be found at APIs/Authorization
     * page from the portal.
     * Passkey, business short code and party_b is at APIs/Express
     */

    protected $safaricom_pass_key = "PASTE_KEY_HERE";
    protected $safaricom_bussiness_short_code = "174379"; // for testing, use 174379
    protected $safaricom_party_b = "174379";
    protected $safaricom_Consumer_Key = "PASTE_CONSUMER_KEY_HERE";
    protected $safaricom_Consumer_Secret = "PASTE_CONSUMER_SECRET_HERE";


     /**
     * ============================================================
     * Transaction metadata
     * ============================================================
     * User's phone number and the amount to transact.
     * Account reference is your business/campany name, this will be shown
     * on the screen when the user is prompted to enter password.
     * Description is optional
     */

    protected $phone_number = 0;
    protected $transaction_amount = 0;

    protected $account_reference = "YOUR_BUSINESS_ACCOUNT_NAME";
    protected $transaction_description = "DESCRIPTION_OF_TRANSACTION";


    function __construct()
    {
        $this->validation_response = json_encode(array(array('response'=>'1', 'reason'=>'Users input has not been validated.')));
        $this->transaction_response = json_encode(array(array('response'=>'1', 'reason'=>'Transaction not successful.')));
    }

    /**
     * Validates inputs to be used for transaction.
     * Returns an object of Mpesa class
     * @param int msisdn users phone number
     * @param int amount the amount to be transacted
     */

    public function validate_transaction_input(Int $msisdn, Int $amount){
        
        $number = $msisdn; //convert to string for validation
        $number = trim($number);//trim any spaces

        //amount is bounded between 1 and 150,000. Max is 150, 000 per transaction
        $is_amount_valid = ($amount > 0 && $amount < 150000) ? true : false;

        /**
         * number should have 12 characters(254...) excluding the (+)
         * if the number is 12 characters long, match the expression to ensure;
         * 1. the number begins with code (254)
         * 2. next number is a 7 or 1 (1 for new numbers)
         * 3. next 8 digits are numbers between 0 and 9
         *  */
    
        $is_number_valid = strlen($number) == 12 ? preg_match("/(254)(7|1)[0-9]{8}/i", $number): false;

        //status code -1 means error, 0 means success
        if(!$is_number_valid){

            $this->validation_response = array(
                array(
                    'response'=>'-1',
                    'reason'=>'MSISDN '.$msisdn.' is incorrect, or contains invalid characters'
                )
            );

        }else if(!$is_amount_valid){

            $this->validation_response = array(
                array(
                    'response'=>'-1',
                    'reason'=>'Amount provided ['.$amount.'] is invalid'
                )
            );

        }else{

            $this->transaction_amount = (int)$amount;
            $this->phone_number = (int)$number;

            $this->validation_response = array(
                array(
                    'response'=>'0',
                    'reason'=>'Validation ok. Ksh. '.$amount.' to be debited from '.$number.'. Proceed with transaction'
                )
            );
        }

        return $this;
    }


     /**
     * Returns validation_response after validation.
     * You can call this to check if the user's details are correct.
     * However, validation will still be done before transaction is initiated.
     *  */
    public function get_validation_response(){

        return json_encode($this->validation_response);

    }

    public function initiate_transaction(){

        if($this->validation_response[0]["response"] == "0"){

            //combine consumer key and consumer secret
            //this will be encoded to base64 and a request sent to $outh_url to obtain a generted access token
            //the access token will be used to access the Express STK service

            $outh = $this->safaricom_Consumer_Key . ':' . $this->safaricom_Consumer_Secret;
            $encoded_outh = base64_encode($outh);

            //cURL session for getting access token
            $curl_outh = curl_init($this->outh_url);
            curl_setopt($curl_outh, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl_outh, CURLOPT_HEADER, false);
            curl_setopt($curl_outh, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl_outh, CURLOPT_HTTPHEADER, array('Authorization: Basic ' . $encoded_outh));

            $curl_outh_response = curl_exec($curl_outh);
            $access_token = json_decode($curl_outh_response, true)["access_token"];

            //generate current time
            //time is needed to generate a password for the transaction
            $time = date("YmdHis", time());

            //generate transaction password by combining
            //short code, pass key and current time
            //then encode the password to base64
            $password = $this->safaricom_bussiness_short_code . $this->safaricom_pass_key . $time;
            $transaction_password = base64_encode($password);
            
            //cURL session for STK
            $curl_stk = curl_init();
            curl_setopt($curl_stk, CURLOPT_URL, $this->stk_request_url);
            curl_setopt($curl_stk, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'Authorization:Bearer ' . $access_token));
            curl_setopt($curl_stk, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl_stk, CURLOPT_POST, 1);
            
            //prepare the data to send
            $curl_post_data = array(
                "BusinessShortCode"=> $this->safaricom_bussiness_short_code,
                "Password"=> $transaction_password,
                "Timestamp"=> $time,
                "TransactionType"=>"CustomerPayBillOnline",
                "Amount"=> $this->transaction_amount,
                "PartyA"=> $this->phone_number,
                "PartyB"=> $this->safaricom_party_b,
                "PhoneNumber"=> $this->phone_number,
                "CallBackURL"=>$this->callback_url,
                "AccountReference"=>$this->account_reference,
                "TransactionDesc"=>$this->transaction_description
            );

            curl_setopt($curl_stk, CURLOPT_POSTFIELDS, json_encode($curl_post_data));
            $response = curl_exec($curl_stk);
            curl_close($curl_stk);
            $payload =  json_decode($response, true);

            $this->transaction_response = json_encode(array($payload));

               
        }else{
            $this->transaction_response =json_encode($this->validation_response);
        }

        return $this;
    }

    public function then($fn){

        call_user_func($fn, $this->transaction_response);

    }
}
?>