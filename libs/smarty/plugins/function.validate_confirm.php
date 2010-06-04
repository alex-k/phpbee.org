<?php

function smarty_function_validate_confirm($params, &$smarty) {
	//$val=gs_validator::get_html_confirm_code($val);
	
	
	$txt=$smarty->get_template_vars($params['id']);
	
	
	$valid=$smarty->get_template_vars('validate');
	if (!isset($valid['STATUS']) || !$valid['STATUS']) {
		if (is_array($valid['MESSAGES'])) foreach ($valid['MESSAGES'] as $k=>$e) {
			/*
			$e='<div class=validate_error_message>'.$e.'</div>';
			$txt=preg_replace('/(<[^<]*?name=[\'\"]?'.$k.')/',$e.'\1',$txt);
			*/

			if(preg_match('/<[^<]*?name=[\'\"]?'.$k.'[\'\"][^>]*?>/i',$txt,$maches)){
				$txt2=$maches[0];
				if (stripos($txt2,'class=')!==FALSE) {
					$txt3=preg_replace('/class=[\'\"](\w*?)[\'\"]/i','class="\1 validate_error_message"',$txt2);
				} else {
					$txt3=preg_replace('/(name=)/i','class="validate_error_message" \1',$txt2);
				}
				$txt=str_replace($txt2,$txt3,$txt);
			}


		}
	}
		
	preg_match_all('/ name=[\'\"]?([\w:]+).*?[\"\']?[ >]/is',$txt,$matches);
	preg_match('/ name=[\'\"]?gspgid[\"\']?.*? value=[\'\"]?([\w\/]+).*?[\"\']?[ >]/i',$txt,$m_gspgid);
	$gid=base64_encode(preg_replace('|^/|','',$m_gspgid[1]));	
	gs_session::save(array_unique($matches[1]),'gs_validator_html_confirm_array_'.$gid);
	$ret.=$txt;
	return $ret;
}

?>
