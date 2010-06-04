<?php


function smarty_function_validate($params, &$smarty) {
	/*
    global $_POST;
    $vars=$_POST;
    	*/
//	md($params);
    if (!isset($params['id']) ) {
        $smarty->trigger_error("validate: missing 'id' parameter");
        return;
    }
    if (!isset($params['criteria']) ) {
        $smarty->trigger_error("validate: missing 'criteria' parameter");
        return;
    }
    /*
    $validate=($smarty->get_template_vars('validate'));
    if (isset($validate['ERRORS'][$params['id']]) && $validate['ERRORS'][$params['id']]==$params['criteria']) {
	    $ret.="<div class=validate_error_message>".$params['message']."</div>";
    }
    */
    
    $ret.=sprintf('<input type=hidden name="_validate_%s_%s" value="%s"\n>',$params['id'],$params['criteria'],base64_encode(serialize($params)));

    return $ret;

}

?>
