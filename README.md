# Lipa na MPesa online (STK  Push) + PHP

See documentation is at [Daraja API documentation]('https://developer.safaricom.co.ke/Documentation) to learn more about terminology, APIs available and the payment request process.

## Prerequisite

Before you get started, knowledge of PHP, generally web development (HTML+JS+PHP) is important to understand how this API works.

You'll also need to have a local server installed on your machine, like XAMPP and a web browser for testing. Im sure you know this already..😉

If you are using a framework such as Vue, React, Laravel, Flask or if you're coming from Android, Ill cover that too. 😁

## Description

This is a simplified PHP script that you can use to integrate MPesa payment system in your website or mobile application.

LIPA NA M-PESA ONLINE API also know as M-PESA express (STK Push) is a Merchant/Business initiated C2B (Customer to Business) Payment.

Once you,  the API, you will be able to send a payment prompt on the customers phone (Popularly known as STK Push Prompt) to your customer's M-PESA registered phone number requesting them to enter their M-PESA pin to authorize and complete a payment.

More explanation from the Docs.

## Setting up

Make sure you have a developer account . If you don't hed over to [Daraja API portal]('https://developer.safaricom.co.ke/) and sign up for an account. Its free.👍

Once you have an account, go to **My Apps** tab and create a new application. Give it a name and check the services that you want to use (you can check the first 2);

    [x] Lipa na MPesa sandbox
    [x] MPesa sandbox

Create the app and it will appear in the dashboard. Next, goto **APIs** tab where you'll see a list of available Endpoint you can use.

We need to get a few credentials from here so that we use them in the PHP script.

Click **Authorization API** card, and on the page that opens, select the application you created on the right side under "Simulator", then take note of your *CONSUMER_KEY* and *CONSUMER_SECRET*.

    CONSUMER_KEY=
    CONSUMER_SECRET=

Once you're done, go back to the previous page then click simulate button on **Mpesa Express** card, and on the page that opens, select the application you created on the right side under "Simulator", then take note of the following;

    PARTY_A=
    PARTY_B=
    PHONE_NUMBER=
    ACCOUNT_REFERENCE=
    TRANSACTION_DESCRIPTION=
    CALLBACK_URL=
    TRANSACTION_TYPE=
    PASS_KEY=

For the two procedures, these variables have been defined on the left side of the same page, what they mean, their data type and sample.

The next steps are simple, 👌

## Installation

Create a workspace on your pc, preferrably inside your server directory : eg **C:\xampp\htdocs**. Clone or download this repository. The project is very minimall, so little modifications will be needed.

Here is the folder structure.

    css/
    img/
    js/
    index.html
    Mpesa.php
    process.php
    README.md

For testing, youll need to open index.html. Its a simple form and a button that will take values from the form for submission.

/index.html
```html
<form class="form-style" action="#" method="post">
    <h1>Mpesa Api Test</h1>
    <label for="phone">Phone number</label>
    <input type="number" name="phone" placeholder="Phone (2547********)" id="phone" required/>

    <label for="amount">Transaction amount</label>
    <input type="number" name="amount" placeholder="Amount" id="amount" required/>
    <input type="button" value="Send" id="btn"/>

</form>

<div class="result-style">
    <h1>Response</h1>
    <pre> <code id="result">Your response will come here...</code></pre>
</div>
```

/js/script.js
```js
$('#btn').click(function() {

    //get the form data
    let phone = $("#phone").val();
    let amount = $("#amount").val();

    //append a text in the response div element
   $("#result").text("Performing a network request...");

   //perform a post request to process.php
    $.post('process.php', {phone: phone, amount: amount}, function(result){

        var jsonObj = JSON.parse(result);

        $("#result").text(JSON.stringify(jsonObj, undefined, 2));
    });
});
```

The script is performing a post request to process.php file will handle the input from the form and link with the main class Mpesa.php to initiate the request.

For JS devs, you can also use Fetch API if youre working with React or Vue or just perform a normal Ajax request to process.php.

Same applies to android devs; you can make a Volley POST request (or the library youre using for network calls), to process.php, supplying the necessary data. Incase your're making calls to Laravel API from android, you will have to connect to an endpoint/controller that will accept a Request $request parameter.

Note that these values can also come from the database or user session, you dont have to pass values around if you don't need to, just know that the process.php required user's number and amount.

Also, depending on how your project is wired, you don't have to connect to process.php. Mpesa.php is the main logic that we want to use, hence we only need the right place to initialize the class and use its methods. Just make sure you have your logic set right.

/process.php

```php
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
```

This is what you need for a transaction, simple!
The response will be returned to wherever the script was called from. Open Mpesa.php file.

By now you might be asking about those keys we took from Daraja portal. This is where we use them.

/Mpesa.php
```php

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

});
```

Paste all the details you took earlier and save. Thats it.

For testing purposes, use what you acquired from the portal. The account reference is just a temporary name, pick any. Business short code is the Paybill or Till number registered for your business. But this will be required when going live. For testing, use what was provided. Party_A and Phone is the sender's number (in this case, your number as the tester), where you will receive a prompt to enter a pin for the transaction to complete.

For Laravel users, you could have your variables set in your .env file;

```php

protected $safaricom_pass_key = env("PASS_KEY");
protected $safaricom_bussiness_short_code = "174379"; // for testing, use 174379
protected $safaricom_party_b = "174379";
protected $safaricom_Consumer_Key = env("CONSUMER_KEY");
protected $safaricom_Consumer_Secret = env("CONSUMER_SECRET");


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

protected $account_reference = env("ACCOUNT_REFERENCE");
protected $transaction_description = env("TRANSACTION_DESCRIPTION");

});
```

### The callback url

```php

 protected $callback_url = "YOUR_CALLBACK_URL"; // eg https://mydomain.com/path
```

A CallBack URL is a valid secure URL that is used to receive notifications from M-Pesa API. It is the endpoint to which the results will be sent by M-Pesa API. Basically your website/script that will receive notification if the transaction was successful or not.

Feel free  to dive deeper into the [Docs]('https://developer.safaricom.co.ke/Documentation), youll find more explanations, examples and content to help you understand better.

Hope this gives you a heads up😉
Feel free to contribute to this repo, or suggest changes.

----------------------------------------------------------------

## For Android devs - Volley

Sharing more soon...

## For Laravel devs

Sharing more soon...

## For Javascript devs - deep dive

Sharing more soon...
