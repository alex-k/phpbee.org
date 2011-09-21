<?php
function html_redirect($gspgid=null,$data=array(),$type='302') {
	$config=gs_config::get_instance();
	$query=array();
	if($gspgid===null) {
		$url=$config->referer_path;
		parse_str(parse_url($config->referer,PHP_URL_QUERY),$query);
	} else {
		$scheme=parse_url($gspgid,PHP_URL_SCHEME);
		$url=$scheme ? $gspgid : $config->www_dir.$gspgid;
	}
	$url=cfg('www_dir').$url;
	$url='/'.ltrim($url,'/');
	$data=array_merge($query,$data);
	$datastr='';
	if ($data) $datastr='?'.http_build_query($data);
	switch ($type) {
	case '302':
		header(sprintf('Location: %s%s',$url,$datastr));
		break;
	}
}
function object_to_array($obj) {
	$arr=array();
	$_arr = is_object($obj) ? get_object_vars($obj) : $obj;
	if (is_array($_arr)) foreach ($_arr as $key => $val) {
		$val = (is_array($val) || is_object($val)) ? object_to_array($val) : $val;
		$arr[$key] = $val;
	}
	return $arr;
}
function array_search_recursive($n,$a,$s=false) {
	$r=array_search($n,$a,$s);
	if ($r!==FALSE) return $r;
	foreach ($a as $aa) {
		if(is_array($aa)) {
			$r=array_search_recursive($n,$aa,$s);
			if ($r!==FALSE) return $r;
		}
	}
	return FALSE;
}

function get_output() {
	$txt=ob_get_contents();
	ob_end_clean();
	return $txt;
}


function array_merge_recursive_distinct ( array &$array1, array &$array2 )
{
	$merged = $array1;

	foreach ( $array2 as $key => &$value )
	{
		if ( is_array ( $value ) && isset ( $merged [$key] ) && is_array ( $merged [$key] ) )
		{
			$merged [$key] = array_merge_recursive_distinct ( $merged [$key], $value );
		}
		else
		{
			$merged [$key] = $value;
		}
	}

	return $merged;
}
function html_fetch($url,$data=array(),$scheme='GET') {
	mlog($url);
	mlog($data);
	if (!isset($url)) throw new gs_exception('html_fetch: empty url');

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	if (strtoupper($scheme)=='POST') {
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	}

	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 180);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));


	$result=curl_exec($ch);
	if (curl_errno($ch)>0) {
		throw new gs_exception(sprintf("html_fetch (%s) : CURL ERROR: %s : %s",$url,curl_errno($ch),curl_error($ch)));
	}
	curl_close($ch);
	return $result;

}
if (!function_exists('pmail')) {
	function pmail($recipients, $body="",$subject="",$add_headers=false,$from=false,$debug=1) {
		include_once("Mail.php");
		$recipients=is_array($recipients) ? $recipients : array($recipients);

		$pr_recipients=array();
		foreach ($recipients as $rec) {
			$pr_r=explode("\n",$rec);
			foreach ($pr_r as $pr_rec) {
				$pr_recipients[]=$pr_rec;
			}
		}
		$recipients=array_filter($pr_recipients);

		$params["host"] = cfg('mail_smtp_host');
		$params["port"] = cfg('mail_smtp_port');
		$params["auth"] = cfg('mail_smtp_auth');
		$params["username"] = cfg('mail_smtp_username');
		$params["password"] = cfg('mail_smtp_password');
		if ($debug) $params["debug"]=1;


		$headers['From']    = !empty($from) ? $from : cfg('mail_from');
		$headers['From'] =  (preg_replace_callback('/(.*)(<.+>)/',create_function('$a','return str_replace("."," ",$a[1]).$a[2];'),$headers['From']));
		$headers['Subject'] = $subject;
		$headers['Content-Type'] = 'text/plain; charset="UTF-8"';

		foreach ($recipients as $key=> $recipient) {
			$recipient=(preg_replace_callback('/(.*)(<.+>)/',create_function('$a','return str_replace("."," ",$a[1]).$a[2];'),$recipient));

			if (is_array($add_headers)) foreach ($add_headers as $name=>$value) {
				$headers[$name] = $value;
			}

			$headers['To']      = $recipient;


			$mail_object =& Mail::factory(cfg('mail_type'), $params);
			$ret=$mail_object->send($recipient, $headers, $body);

		}
		if ($debug) {
			md($ret,1);
		}
		return $ret;
	}
}


function record_by_id($id=0,$classname='gs_null') {
	$r=new $classname;
	return $r->get_by_id($id);
}

function string_to_params($inp) {
	$arr=is_array($inp) ? $inp : array($inp);
	$ret=array();
	$arr=preg_replace('|=\s*([^\'\"][^\s]*)|i','=\'\1\'',$arr);
	foreach ($arr as $k=>$s) {
		$s.=' ';
		preg_match_all(':(\s*(([a-z_]+)=)?[\'\"](.+?)[\'\"]\s|([^\s]+)):i',$s,$out);
		$r=array();
		$j=0;
		foreach ($out[3] as $i => $v) {
			$key= $v ? $v : $j++;
			$value = $out[4][$i] ? $out[4][$i] : $out[1][$i];
			//if (strtolower($value)=='false') $value=false;
			//if (strtolower($value)=='true') $value=true;
			$prefix=explode(':',$value,2);
			if(strtoupper($prefix[0])=='ARRAY') $value=explode(':',$prefix[1]);
			$r[$key]=$value;
		}
		$ret[$k]=$r;
	}
	return is_array($inp) ? $ret : reset($ret);
}

function empty_array($a,$b) {
	return is_array($b) ? $a || array_reduce($b,'empty_array') : $a || ($b && $b!=4);
}

function xml_print($xml) {
	$dom = new DOMDocument('1.0');
	$dom->preserveWhiteSpace = false;
	$dom->formatOutput = true;
	$dom->loadXML($xml);
	return $dom->saveXML();
}

/**
* Compatible functions
**/

if (PHP_VERSION_ID>=50300 && !function_exists('mb_split')) {
	function mb_split($pattern, $string , $limit = -1) {
		return preg_split(sprintf("/%s/",preg_quote($pattern)), $string , $limit);
	}
}

if (PHP_VERSION_ID>=50300 && !function_exists('mb_strlen')) {
	/*function mb_strlen($string) {
	    return strlen($string);
	}

	function mb_detect_encoding($string,$encoding_list = null ,$strict = false) {
	    return 'UTF-8';
	}

	function mb_substr($string,$start,$length, $encoding='UTF-8') {
	    return substr($string,$start,$length);
	}*/
}



function load_submodules($parent_name,$dirname) {
	$files = glob($dirname.DIRECTORY_SEPARATOR.'*'.DIRECTORY_SEPARATOR.'*.phps');
	foreach ($files as $f) {
		$pf=str_replace(basename($f),'___'.basename($f),$f);
		$pf=preg_replace('/.phps$/','.xphp',$pf);
		if (!file_exists($pf) || filemtime($pf) < filemtime($f)) {
			$s=file_get_contents($f);
			$s=str_replace('{PARENT_MODULE}',$parent_name.'_',$s);
			file_put_contents($pf,$s);
		}
		load_file($pf);
	}
}

function rrmdir($dir) {
	if (is_dir($dir)) {
		$objects = scandir($dir);
		foreach ($objects as $object) {
			if ($object != "." && $object != "..") {
				if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object);
				else unlink($dir."/".$object);
			}
		}
		reset($objects);
		rmdir($dir);
	}
}

function gsdict($t) {
	return gs_dict::get($t);
}

function copy_directory($src,$dst) {
	check_and_create_dir($dst);
	$dst=realpath($dst);
	$files=glob(realpath($src).DIRECTORY_SEPARATOR.'*');
	foreach ($files as $f) {
		$newname=$dst.DIRECTORY_SEPARATOR.basename($f);
		if (is_dir($f)) {
			copy_directory($f,$newname);
		} else {
			copy($f,$newname);
		}
	}
}

?>
