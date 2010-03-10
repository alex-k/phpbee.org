<?
function html_redirect($gspgid=null,$data=null,$type='302') {
	$config=gs_config::get_instance();
	if($gspgid===null) $gspgid=$config->referer_path;
	$url=$config->www_dir.$gspgid;
	$datastr='';
	if ($data) $datastr='?'.http_build_query($data);
	switch ($type) {
		case '302':
			header(sprintf('Location: %s%s',$url,$datastr));
		break;
	}
}
?>
