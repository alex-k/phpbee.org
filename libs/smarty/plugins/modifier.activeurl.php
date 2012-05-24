<?php
function smarty_modifier_activeurl($string) {
	$smarty=gs_tpl::get_instance();
	$data=$smarty->getTemplateVars('_gsdata');
	if ($data['handler_key_root']==trim($string,'/')) return 'class="active"';
};
