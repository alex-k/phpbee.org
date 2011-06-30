<?

$urls=array(
	'',
	'news',
	'news/show',
	'news/*/comments',
	'news/show/*',
	'*/show',
	'news/3/comments',
	'news/3/comments/4',
	);

$gspgid='news/show/3';


$result='404';
$max_c=-1;
foreach ($urls as $url) {
	$c=url_compare(trim($gspgid,'/'),trim($url,'/'));
	if ($c>$max_c) {
		$max_c=$c;
		$result=$url;
	}
}
var_dump($gspgid);
var_dump($urls);
var_dump($result);

function url_compare($gspgid,$url) {
	$g=explode('/',$gspgid);
	$u=empty($url) ? array() : explode('/',$url);
	if (count($g)<count($u)) return -1;
	
	$cnt=0;
	for ($k=0;$k<min(count($g),count($u));$k++) {
		$cnt++;
		if($u[$k]=='*') continue;
		if ($u[$k]!=$g[$k]) return -1;
		$cnt+=10;
	}
	return $cnt;
}

?>
