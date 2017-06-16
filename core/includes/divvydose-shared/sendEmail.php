<?php

function loadTemplate($__name, $__params=array(), $type='html') {
  extract($__params);
  ob_start();
  include(__DIR__."/emails/$__name.php");
  $body = trim(ob_get_contents());
  ob_end_clean();

  if ($type == 'html') {
    $body = nl2br($body);
  }
  return array('body' => $body, 'subject' => $subject);
}



function sendEmail($opts) {
  $mail = new PHPMailer;
  $mail->isSMTP();
  $mail->Host = 'smtp.mandrillapp.com';
  $mail->SMTPAuth = true;
  $mail->Username = 'tthoms@divvydose.com';
  $mail->Password = 'HQbtzlMgJBRK5uPPd6j9rQ';
  $mail->Port = 587;

  $mail->AddReplyTo('hi@divvydose.com', 'divvyDOSE');
  $mail->SetFrom('hi@divvydose.com', 'divvyDOSE');

  if ($opts['to']['name']) {
    $name = $opts['to']['name'];
  }
  else {
    $name = "{$opts['to']['firstName']} {$opts['to']['lastName']}";
  }

  $mail->AddAddress($opts['to']['email'], $name);

  $subject = $opts['subject'];

  if ($opts['template']) {
    $template = loadTemplate($opts['template'], $opts['params'], 'text');
    // var_dump($template);
    $altBody = $template['body'];

    $template = loadTemplate($opts['template'], $opts['params'], 'html');
    $body = $template['body'];
    if ($template['subject']) {
      $subject = $template['subject'];
    }
  }
  else {
    $body = $opts['body'];
  }

  $mail->IsHTML();

  $mail->Subject = $subject;
  $mail->AltBody = $altBody;
  $mail->Body = <<<EOT
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>divvyDOSE</title>



<style type="text/css">
    body{
      margin:0;
      padding:0;
      background-color:#ffffff;
    }
</style></head>

<body>
<table width="80%" border="0" align="center" cellpadding="0" cellspacing="0" style="background-color:#ffffff">
  <tr>
    <td>
    
    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="border-bottom:1px solid #CCCCCC;">
  <tr>
    <td style="text-align:center">&nbsp;</td>
  </tr>
  <tr>
    <td style="text-align:center"><a href="https://www.divvydose.com/"><img src="https://gallery.mailchimp.com/ab45c9f730b13c231b024d730/images/4e7f961b-c0f3-430c-9c34-2561abdd61cd.png" border="0"></a></td>
  </tr>
  <tr>
    <td style="text-align:center">&nbsp;</td>
  </tr>
    </table>

   </td>
  </tr>
  <tr>
    <td>
    
    
    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="border-bottom:1px solid #CCCCCC;">
  <tr>
    <td><table width="90%" border="0" align="center" cellpadding="0" cellspacing="0">
      <tr>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td style="font-family:Arial, Helvetica, sans-serif; font-size:13px; line-height:18px;">
        
  $body

</td>
      </tr>
      <tr>
        <td>&nbsp;</td>
      </tr>
    </table></td>
  </tr>
</table>

    </td>
  </tr>
  <tr>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td align="center"><a href="#"><img src="https://gallery.mailchimp.com/ab45c9f730b13c231b024d730/images/056807a9-4218-4dd4-aaef-8a157c807a28.png" border="0"></a>
    
      <a href="#"><img src="https://gallery.mailchimp.com/ab45c9f730b13c231b024d730/images/d1ee4807-8933-4718-aac3-f3adc8f66a0a.png" border="0"></a> 
      <a href="#"><img src="https://gallery.mailchimp.com/ab45c9f730b13c231b024d730/images/d5eb43b1-f88e-4504-9d83-fe4fd5338ed3.png" border="0"></a> 
      <a href="#"><img src="https://gallery.mailchimp.com/ab45c9f730b13c231b024d730/images/9cc393b1-d5b8-4955-a4b0-e1a1a33b98a4.png" border="0"></a>
    
    
    
    </td>
  </tr>
  <tr>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td style="font-family:Arial, Helvetica, sans-serif; font-size:12px; line-height:20px; text-align:center"><a href="https://www.divvydose.com/" style="color:#4A4F53; text-decoration:none;">www.divvyDOSE.com</a><br>
844.693.4889
</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td style="font-family:Arial, Helvetica, sans-serif; font-size:12px; line-height:20px; text-align:center; color:#4A4F53">divvyDOSE, 3416 46th Ave, Rock Island, IL 61201<br>
2015, divvyDOSE All rights reserved.
</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <a href="http://www.divvydose.com/electronic-privacy-policy.html" style="color:#4775A3; text-decoration:underline;">Privacy Policy</a>  | <a href="https://www.divvydose.com/help.html" style="color:#4775A3; text-decoration:underline;">Help Center</a></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
  </tr>
</table>
</body>
</html>
EOT;

  if (!$mail->send()) {
    echo 'asdf';
  }
}