<?
// Template for email messages, contains body and css style of message
//
// Variables for body: {message} - message body,
//                     {subject} - email subject,
//                     {domain} - current domain with root folder
//                     {signature} - common signature from config/settings.php}
//
// Email :: setTemplate("name");
// Email :: send($recipient, $subject, $message);

$email_template = array(
	"body" => "<body style=\"background:#eee; width:100%; height:100%; margin:0; padding:30px 0;\">
			   <div style=\"background:#fff; padding:30px 3% 20px 3%; max-width:600px; margin:0 auto; box-shadow:0 0 4px rgba(0, 0, 0, 0.2);\">
			   <h1>{subject}</h1>
			   {message}
			   <p style=\"margin:15px 0 0 0;padding:15px 0 0 0; border-top:1px solid #ddd;\">Сообщение отправлено с сайта 
			   <a href=\"{domain}\">{domain}</a></p>
			   </div>
			   </body>",

	"css" => array(
		'*'      => 'font-family:Arial; font-size:14px; color:#333',
		'a'      => 'color:#0057c2;', 
		'h1'     => 'font-weight:normal; margin:0 0 20px 0; font-size:24px; line-height:25px; padding:0',
		'p'      => 'margin:0 0 15px 0; line-height:16px',
		'ul'     => 'margin:0 0 9px 12px; padding:0; list-style:square outside;',
		'li'     => 'padding:0 0 6px 0; margin:0 0 0 12px;',
		'table'  => 'margin:15px 0; border:none; border-collapse:collapse; border-spacing:0;',
		'th'     => 'text-align:left; background:#eee; font-size:13px; font-weight:bold; padding:10px 20px; vertical-align:top',
		'td'     => 'text-align:left; padding:10px 20px; text-align:left; border-bottom:1px solid #d6d6ce; vertical-align:top')
);
?>