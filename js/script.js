
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
