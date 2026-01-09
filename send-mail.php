<?php

//Import PHPMailer classes into the global namespace
//These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

//Create an instance; passing `true` enables exceptions
if (isset($_POST["send"])) {

  $mail = new PHPMailer(true);

    //Server settings
    $mail->isSMTP();                              //Send using SMTP
    $mail->Host       = 'smtp.gmail.com';       //Set the SMTP server to send through
    $mail->SMTPAuth   = true;             //Enable SMTP authentication
    $mail->Username   = 'kristineannmaglinao@gmail.com';   //SMTP write your email
    $mail->Password   = 'uufsxxjmqcqmqeph';      //SMTP password
    $mail->SMTPSecure = 'tls';            //Enable implicit SSL encryption
    $mail->Port       = 587;                                    

    //Recipients
    $mail->setFrom('kristineannmaglinao@gmail.com', $_POST["name"]); // Fixed sender email, user's name
    $mail->addAddress($_POST["recipient"]); // Use recipient from form
    $mail->addReplyTo('kristineannmaglinao@gmail.com', $_POST["name"]); // Reply to fixed sender email

    //Content
    $mail->isHTML(true);               //Set email format to HTML
    $mail->Subject = $_POST["subject"];   // email subject headings
    $mail->Body    = $_POST["message"]; //email message

    // Success sent message alert
    $mail->send();
    echo
    " 
    <script> 
     alert('Message was sent successfully!');
     document.location.href = 'index.php';
    </script>
    ";
}
?>