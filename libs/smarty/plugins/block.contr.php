<?php
function smarty_block_contr($params, $content, $smarty, &$repeat) {
// only output on the closing tag
	if ($repeat) {
	}
	if(!$repeat) {
		$ret='';
		if (isset($content)) {
			include_once('function.controller.php');
			$rs=smarty_function_controller($params, $smarty);
			$smarty->assign($params['_assign'],$rs->first());
			$rs->state=RS_STATE_LATE_LOAD;
			$smarty->fetch('string:'.$content);
			$rs->late_load_records();

			foreach ($rs as $rec) {
				$smarty->assign($params['_assign'],$rec);
				$ret.=$smarty->fetch('string:'.$content);
			}
		}
		return $ret;
	}
}

?>

