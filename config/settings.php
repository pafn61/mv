<?
/**
 * MV - content management framework for developing internet sites and applications.
 * Released under the terms of BSD License.
 * http://mv-framework.ru
 */

//Main config variable, which keeps all important options of system
//Goes to Registry object to read the settings from any part of backend or frontend

$mvMainSettings = array(

//Supported regional packages for internationalization located at ~adminpanel/i18n/
//am - american, the same as english (en) exept for date format
'SupportedRegions' => array('en', 'am', 'ru', 'de'),

//Current version of system (do not change it)
'Version' => '2.0',

//Allowed data types for models elements
'ModelsDataTypes' => array('bool','int','float','char','url','redirect','email','phone','password','text','enum','parent',
						   'order','date','date_time','image','multi_images','file','many_to_one','many_to_many','group'),

 //All allowed types of files to upload all other files will be skiped
'AllowedFiles' => array('gif', 'jpg', 'jpeg', 'png', 'zip', 'rar', 'gzip', 'txt', 'doc', 'docx', 'flv', 'rtf', 
'swf', 'sxc', 'sxw', 'vsd', 'wav', 'wma', 'wmv', 'xls','xlsx', 'xml', 'aiff','asf', 'avi', 'csv', 'mid', 
'mov', 'mp3', 'mp4', 'mpc', 'mpeg', 'mpg', 'pdf', 'ppt', 'pxd'),

//All allowed types of image files to upload
'AllowedImages' => array('gif', 'jpg', 'jpeg', 'png'),

//Quality of .jpg images created by imagejpeg() of GD
'JpgQuality' => 90,

//Mime types to check for uploaded images
'DefaultImagesMimeTypes' => array('image/jpeg', 'image/gif', 'image/png'),

//Max allowed file size of any type of file, but images
'MaxFileSize' => 1048576 * 3, 

//Max allowed image file size in uploader in bytes
'MaxImageSize' => 1048576 * 2, 

//Max allowed width of image in pixels
'MaxImageWidth' => 1920, 

//Max allowed height of image in pixels
'MaxImageHeight' => 1600, 

//Session maximal duration in seconds
'SessionLifeTime' => 3600 * 3, 

 //During this time newly generated password is available to be confirmed by user
'NewPasswordLifeTime' => 10800 / 3,

 //After 3 incorrect passwords the ip of user is added into special list and this user must fill captcha during this time
'LoginCaptchaLifeTime' => 3600,

//Time interval in seconds from last hit of user when we show that user is online
'UserOnlineTime' => 900,

//Time interval in seconds for autologin cookies, after this time cookies die
'AutoLoginLifeTime' => 3600 * 24 * 31 * 3,

//Not allowed names of models fields
'ForbiddenFieldsNames' => array('page','done','pager-limit','sort-field','sort-order',
			                    'multi-action','multi-value','version','continue','restore','edit'),
								
'ForbiddenModelsNames' => array('settings','users_logins','users_passwords',
								'users_rights','users_sessions','versions'),
								
//Max execution time of data processing during csv files uploading
'CsvUploadTimeLimit' => 180,

//Max number of versions for each model record (false - disables versions writing)
'ModelVersionsLimit' => 25,

'EmailSignature' => '<p>Сообщение отправлено с сайта <a href="{domain}">{domain}</a></p>'
);
?>