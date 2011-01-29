<?
function html_redirect($gspgid=null,$data=null,$type='302') {
	$config=gs_config::get_instance();
	if($gspgid===null) $gspgid=$config->referer_path;
	$scheme=parse_url($gspgid,PHP_URL_SCHEME);
	$url=$scheme ? $gspgid : $config->www_dir.$gspgid;
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
		preg_match_all(':(\s*(([a-z_]+)=)?[\'\"](.+?)[\'\"]|([^\s]+)):i',$s,$out);
		$r=array();
		$j=0;
		foreach ($out[3] as $i => $v) {
			$key= $v ? $v : $j++;
			$value = $out[4][$i] ? $out[4][$i] : $out[1][$i];
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




?>
