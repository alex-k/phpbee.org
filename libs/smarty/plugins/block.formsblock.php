<?
function smarty_block_formsblock($params, $content, &$smarty, &$repeat) {
	$n=$params['item'];
	$field=$params['field'];
	$structure=$n->get_recordset()->structure['htmlforms'][$field];
	switch ($structure['type']) {
	case 'tab':
		$content=smarty_block_formsblock_tab($smarty,$content,$n,$field,$structure);
		break;
	default:
		return $content;
	}
	return $content;
}

function smarty_block_formsblock_tab($smarty,$content,$n,$field,$structure) {
	if (is_array($structure['options']['array'])) {
		$smarty->assign('smarty_block_formsblock_tab_array',$structure['options']['array']);
		$smarty->assign('smarty_block_formsblock_tab_content',$content);
		$smarty->assign('smarty_block_formsblock_tab_field',$field);
		$ret=$smarty->fetch('inc/smarty_block_formsblock_tab.html');
		//var_dump($ret);
	}
	return $ret;
}

?>
