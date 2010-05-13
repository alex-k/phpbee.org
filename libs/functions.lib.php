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
   if (!isset($url)) throw new gs_exception('html_fetch: empty url');

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    if (strtoupper($scheme)=='POST') {
	    curl_setopt($ch, CURLOPT_POST, 1);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    }

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
	md($mail_object,1);
        $ret=$mail_object->send($recipient, $headers, $body);

    }
    if ($debug) {
                md($ret,1);
    }
    return $ret;
}

function fetch($url,$sleep=0, $force=true) {
        $COOK="";
        $base_url="";
        $url=$base_url.$url;
        md("fetch $url");
        $m=md5($url,1);
        $ret=gs_cacher::load($m,'fetch');
        if (!$ret || $force) {
                md("-------------downloading $url----------",1);
                $c = curl_init();
                curl_setopt($c, CURLOPT_URL, $url);
                curl_setopt($c, CURLOPT_RETURNTRANSFER,1);
                curl_setopt($c, CURLOPT_COOKIE,$COOK);
                $ret=curl_exec($c);
                gs_cacher::save($ret,'fetch',$m);
                usleep(rand(0,$sleep*1000000));
        }
        return $ret;
}



?>
