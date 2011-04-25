<?php
function smarty_modifier_gs_session($string,$val=null)
{
	$a=gs_session::load($string);
	if (!empty($val)) return isset($a[$val]) ? $a[$val] : null;
	return $a;
}

/* vim: set expandtab: */

?>
