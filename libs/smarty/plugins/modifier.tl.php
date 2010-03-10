<?php
function smarty_modifier_tl($string,$val=null)
{
	$tpl=gs_tpl::get_instance();
	$contentLanguage=$tpl->get_template_vars('contentLanguage');
	$t=new text;
	$l=$t->find_records(array('textName'=>$string))->current()->Lang[$contentLanguage]->Text;
	return $l ? $l : $string;
}

/* vim: set expandtab: */

?>
