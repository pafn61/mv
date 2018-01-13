<?
class Email
{
	static private $template = array();
	
	static public function setTemplate($name)
	{
		$registry = Registry :: instance();
		$file = $registry -> getSetting("IncludePath")."customs/emails/".$name.".php";
		
		unset($email_template);
		self :: $template = array();
		
		if(!is_file($file))
		{
			if($name != "default")
				Log :: add("Unable to load email template with file name '".$name.".php' from folder customs/emails/");
			
			return;
		}
		
		include_once $file;
		
		if(isset($email_template) && is_array($email_template))
		{
			if(isset($email_template["body"]) && strpos($email_template["body"], "{message}") !== false)
				self :: $template["body"] = $email_template["body"];
				
			if(isset($email_template["css"]) && is_array($email_template["css"]) && count($email_template["css"]))
				self :: $template["css"] = $email_template["css"];
		}
	}
	
	static public function send($recipient, $subject, $message)
	{
		$registry = Registry :: instance();		
		require_once $registry -> getSetting("IncludeAdminPath")."swiftmailer/lib/swift_required.php";				
		$from = $registry -> getSetting("EmailFrom");
		$extra_headers = array();
		$arguments = func_get_args();
		
		//Adds extra headers
	   	if(isset($arguments[3]) && is_array($arguments[3]) && count($arguments[3]))
   		{
   			if(isset($arguments[3]['From']))
   			{
   				$from = $arguments[3]['From'];
   				unset($arguments[3]['From']);
   			}
   			
   			$extra_headers = $arguments[3];
   		}
		
		if($registry -> getSetting("EmailMode") == "smtp")
			$transport = Swift_SmtpTransport :: newInstance($registry -> getSetting("SMTPHost"), 
															$registry -> getSetting("SMTPPort"))
											 -> setUsername($registry -> getSetting("SMTPUsername"))
											 -> setPassword($registry -> getSetting("SMTPPassword"));
		else
			$transport = Swift_MailTransport :: newInstance();
		
		$mailer = Swift_Mailer :: newInstance($transport);
		
		if(!count(self :: $template))
			self :: setTemplate("default");
				
		//If template is set up we parse it
		if(isset(self :: $template["body"]) && self :: $template["body"])
		{
			$message = str_replace("{message}", $message, self :: $template["body"]);
			$message = str_replace("{subject}", $subject, $message);
			
			if($registry -> getSetting("EmailSignature"))
				$message = str_replace("{signature}", $registry -> getSetting("EmailSignature"), $message);
		}
		else if($registry -> getSetting("EmailSignature")) //Add signature from config/settings.php if exists
			$message .= "\r\n".$registry -> getSetting("EmailSignature");
				
		//Add domain name into message
		$message = str_replace("{domain}", $registry -> getSetting("HttpPath"), $message);
		$message = preg_replace("/\s*([-a-z0-9_\.]+@[-a-z0-9_\.]+\.[a-z]{2,5})/i", ' <a href="mailto:$1">$1</a>', $message);
		
   		//Add css styles from config/settings.php
   		$message = self :: addCssStyles($message);		
   		
		$message = Swift_Message :: newInstance($subject)
  			                     -> setTo(self :: explodeEmailAddress($recipient))
  			                     -> setBody($message, 'text/html');
  		if($from)             
			$message -> setFrom(self :: explodeEmailAddress($from));
  			                     
		$type = $message -> getHeaders() -> get('Content-Type');
		$type -> setParameter('charset', 'utf-8');
		
		$headers = $message -> getHeaders();
		
		foreach($extra_headers as $key => $value)
			$headers->addTextHeader($key, $value);		
		
		$result = $mailer -> send($message, $errors);
		  			                     
		return (bool) $result;	
	}
	
	static public function explodeEmailAddress($email)
	{
		$emails = (strpos($email, ",") === false) ? array($email) : explode(",", $email);
		$result = array();		
		
		foreach($emails as $email)
		{   			
   			if(strpos($email, "<") !== false)
   			{
   				$address = trim(preg_replace("/.*<([^>]+)>.*/", "$1", $email));
   				$name = trim(preg_replace("/(.*)<[^>]+>.*/", "$1", $email));   				
   				$result[$address] = "=?utf-8?b?".base64_encode($name)."?=";
   			}
   			else
   				$result[] = trim($email);
		}

   		return $result;
	}
		
	static public function addCssStyles($message)
	{
		$registry = Registry :: instance();
		$css_templates = $registry -> getSetting('EmailCssStyles');
		
		if(!is_array($css_templates))
			$css_templates = array();
		
		if(isset(self :: $template["css"]) && count(self :: $template["css"]))
			foreach(self :: $template["css"] as $key => $value)
				$css_templates[$key] = $value;
		
		$common_styles = false;
		
		if(!$css_templates || !count($css_templates))
			return $message;
			
		$tags = array();
		
		foreach($css_templates as $key => $template)
		{
			$template = preg_replace("/;\s*$/", "", $template);

			if($key == "*" && $template)
			{
				$common_styles = $template;
				continue;
			}
			else if(strpos($key, ",") !== false)
			{
				$keys = preg_split("/\s*,\s*/", $key);
				
				foreach($keys as $i)
					if(isset($tags[$i]) && $tags[$i])
						$tags[$i] .= ";".$template;
					else
						$tags[$i] = $template;
			}
			else
			{
				if(isset($tags[$key]) && $tags[$key])
					$tags[$key]	.= "; ".$template;
				else
					$tags[$key]	= $template;
			}
		}
		
		if($common_styles)
			foreach($tags as $key => $style)
				$tags[$key] = $common_styles."; ".$style;
				
		foreach($tags as $key => $style)
		{
			//If style attribute exists we move it to the first position
			$message = preg_replace("/<(".$key.")([^>]*)\sstyle=.([^\"'>]+).(.*)>/", "<$1 style=\"$3\"$2$4>", $message);
			$message = preg_replace("/<(".$key.")([^>]*)>/", "<$1 style=\"".$style."\"$2>", $message); //Adds config styles
			
			$re = "/<(".$key.")\sstyle=.([^\"'>]+).\sstyle=.([^\"'>]+).(.*)>/";			
			$message = preg_replace($re, "<$1 style=\"$2; $3\"$4>", $message); //Deletes double attribute style
			$message = str_replace(";;", ";", $message); //Clean up
		}
			
		return $message;
	}
}
?>