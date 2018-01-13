<?
if(isset($_POST['email']) && trim($_POST['email']))
	$email = htmlspecialchars(trim($_POST['email']), ENT_QUOTES);
else
	$email = "";
?>
	           <form method="post" action="">
	               <table cellpadding="0" cellspacing="0">
                      <tr>
                        <td class="errors" colspan="2">
                           <? echo $login -> displayLoginErrors($errors); ?>
                        </td>
                      </tr>
	                  <tr>
	                     <td class="caption"><? echo I18n :: locale('email'); ?></td>
	                     <td>
                              <input class="password" type="text" name="email" value="<? echo $email; ?>"  autocomplete="off" />                              
                         </td>
	                  </tr>
                      <? include $registry -> getSetting('IncludeAdminPath')."login/captcha.php"; ?>
                      <tr>
                        <td colspan="2">
                           <div class="submit recover">
                              <input class="submit" type="submit" value="<? echo I18n :: locale('restore'); ?>" />
                              <input type="hidden" name="admin-login-csrf-token" value="<? echo $login -> getToken(); ?>" />
                           </div>
                           <div class="cancel">
                               <a href="<? echo $registry -> getSetting('AdminPanelPath'); ?>login/"><? echo I18n :: locale('cancel'); ?></a>
                           </div>
                        </td>
                      </tr>
	               </table>
	           </form>