<tr>
	<td class="caption captcha"><? echo I18n :: locale('captcha'); ?></td>
	<td>
	  <img src="<? echo $registry -> getSetting('AdminPanelPath'); ?>login/captcha/" alt="<? echo I18n :: locale('captcha'); ?>" />
	  <input type="text" name="captcha" value="" autocomplete="off" />
	</td>
</tr>