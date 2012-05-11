<?php
/*
login/form/author
oauth2_handler.login:classname:author:login_field:email:full_name_field:fullName:return:not_false		
gs_base_handler.post_login:return:gs_record:classname:author:name:login_form_author.html:form_class:g_forms_html:fields:email,password,active		
gs_base_handler.redirect		
*/
/*
class module_oauth2 extends gs_base_module implements gs_module {
	function __construct() {
	}
	function install() {
		foreach(array('oauth2_config') as $r){
			$this->$r=new $r;
			$this->$r->install();
		}
	}
    function get_menu() {
        $ret = array();
        $item = array();
        $item[] = '<a href="/admin/oauth2/">oauth2</a>';
        $item[] = '<a href="/admin/oauth2/config">oauth2 config</a>';
        $ret[] = $item;
        return $ret;
    }

static function get_handlers() {
		$data=array(
		'get_post'=>array(
			''=>'oauth2_handler.startlogin',
		),
		);
	return self::add_subdir($data,dirname(__FILE__));
	}
}
class oauth2_config extends gs_recordset_short {
	public $no_urlkey=1;
	function __construct($init_opts=false) { parent::__construct(array(
		'class'=> "fSelect options='oauth2_vk,oauth2_google,oauth2_facebook,oauth2_twitter'",
		'APP_ID'=> "fString APP_ID required=false",
		'APP_SECRET'=> "fString APP_SECRET required=false",
		'SCOPE'=> "fString SCOPE required=false",
		'CONSUMER_KEY'=>"fString CONSUMER_KEY required=false",
		),$init_opts);
	}
}
*/
class oauth2_handler extends gs_handler {
	function startlogin($ret) {
		$classname=$this->data['gspgid_va'][0];
		if (!class_exists($classname)) throw new gs_exception('oauth2_handler:startlogin no class found '.$classname);
		$d=parse_url($this->data['url']);
		$this->data['data']['oa2c']=$classname;
		$d['query']=http_build_query($this->data['data']);
		$callback=http_build_url($d);
		$oauth=new $classname;
		$url=$oauth->authorize($callback);
		header('Location: '.$url);
	}

	function login($ret) {
		$ds=new gs_data_driver_get();
		$data=$ds->import();
		if (!isset($data['oa2c'])) return true;
		$classname=$data['oa2c'];
		if (!class_exists($classname)) throw new gs_exception('oauth2_handler:login no class found '.$classname);
		$oauth=new $classname;
		$token=$oauth->token($data);
		if(!$token) return true;
		$profile=$oauth->profile($token);
		if (!$profile['uid']) return true;

		$rs=new $this->params['classname'];
		$options=array(
			$this->params['login_field']=>$profile['uid']
			);
		$rec=$rs->find_records($options)->first();
		if (!$rec) {
			$rec=$rs->find_records($options)->first(true);
			foreach ($rs->structure['fields'] as $k=>$f) {
				if ($f['type']=='password') $rec->$k=md5(rand());
			}
			if (isset($this->params['first_name_field'])) $rec->{$this->params['first_name_field']}=$profile['first_name'];
			if (isset($this->params['last_name_field'])) $rec->{$this->params['last_name_field']}=$profile['last_name'];
			if (isset($this->params['full_name_field'])) $rec->{$this->params['full_name_field']}=$profile['first_name'].' '.$profile['last_name'];
			$rec->commit();
		}
		foreach ($this->data['handler_params'] as $n=>$v) {
			if (isset($rs->structure['fields'][$n])) $options[$n]=$v;
		}
		$rec=$rs->find_records($options)->first();
		if (!$rec) return false;
		gs_session::save($rec->get_id(),'login_'.$this->params['classname']);
		return $rec;
	}
}
class oauth2_twitter{
	/*
	http://habrahabr.ru/post/114955/
	*/
	const APP_ID='2152827';
	const APP_SECRET='qFzjfqj57t5s3VklenConmamcMCmM8XEbQVuyRh7f3E';
	const CONSUMER_KEY='j01djlRk7RQwtoDthZ8ejw';
	function __construct() {
		load_file(dirname(__FILE__) . DIRECTORY_SEPARATOR. 'lib'.DIRECTORY_SEPARATOR.'twitter'.DIRECTORY_SEPARATOR.'twitteroauth'.DIRECTORY_SEPARATOR.'twitteroauth.php');
		load_file(dirname(__FILE__) . DIRECTORY_SEPARATOR. 'lib'.DIRECTORY_SEPARATOR.'twitter'.DIRECTORY_SEPARATOR.'config.php');

	}
	function authorize($callback) {

		$connection = new TwitterOAuth(self::CONSUMER_KEY, self::APP_SECRET);
		$request_token = $connection->getRequestToken($callback);
		gs_session::save($request_token,'oauth2_twitter_token');
		$url=$connection->getAuthorizeURL($request_token);
		return $url;
	}
	function token($data) {
		$request_token=gs_session::load('oauth2_twitter_token');
		$connection = new TwitterOAuth(self::CONSUMER_KEY, self::APP_SECRET,$request_token['oauth_token'],$request_token['oauth_token_secret']);
		$access_token = $connection->getAccessToken($data['oauth_verifier']);
		return $connection;
	}
	function profile($connection) {
		$ret=array('uid'=>null,'first_name'=>null,'last_name'=>null,'type'=>'twitter');
		$d=$connection->get('account/verify_credentials');
		if (!$d || !$d->id) return $ret;
		$ret['uid']='tw-'.$d->id;
		list($ret['first_name'],$ret['last_name'])=array_map(trim,explode(' ',$d->name,2));
		return $ret;
	}
}

class oauth2_vk {
	const APP_ID='2934735';
	const APP_SECRET='lnTik6NHIL87zbR78Bfr';
	const SCOPE='notify';
	function authorize($callback) {
		$callback=urlencode($callback);
		return "http://oauth.vk.com/authorize?client_id=".self::APP_ID."&scope=".self::SCOPE."&redirect_uri=$callback&response_type=code";
	}
	function token($data) {
		$code=$data['code'];
		$url="https://oauth.vk.com/access_token?client_id=".self::APP_ID."&client_secret=".self::APP_SECRET."&code=$code";
		$d=json_decode(html_fetch($url));
		return $d;
	}
	function profile($token) {
		$ret=array('uid'=>null,'first_name'=>null,'last_name'=>null,'type'=>'vk');
		$url=sprintf("https://api.vk.com/method/getProfiles?uid=%d&access_token=%s&fields=nickname,screen_name",$token->user_id,$token->access_token);
		$d=json_decode(html_fetch($url));
		if (!$d) return $ret;
		$d=reset($d->response);
		if (!$d->uid) return $ret;
		$ret['uid']='vk-'.$d->uid;
		$ret['first_name']=$d->first_name;
		$ret['last_name']=$d->last_name;
		return $ret;
	}
}
class oauth2_google{
	/*
	https://developers.google.com/accounts/docs/OAuth2Login?hl=ru
	https://developers.google.com/accounts/docs/OAuth2WebServer
	*/
	const APP_ID='222759715716.apps.googleusercontent.com';
	const APP_SECRET='kWmMQh1WOgq97I-GJew0mIBb';
	const SCOPE='https://www.googleapis.com/auth/userinfo.profile';
	function authorize($callback) {
		$r=array();
		$r['response_type']='code';
		$r['client_id']=self::APP_ID;
		$r['scope']=self::SCOPE;
		$r['redirect_uri']=$callback;
		$r['state']=$callback;
		return "https://accounts.google.com/o/oauth2/auth?".http_build_query($r);
	}
	function token($data) {
		$r=array();
		$r['code']=$data['code'];
		$r['client_id']=self::APP_ID;
		$r['client_secret']=self::APP_SECRET;
		$r['grant_type']='authorization_code';
		$r['redirect_uri']=$data['state'];

		$url="https://accounts.google.com/o/oauth2/token";
		$d=json_decode(html_fetch($url,$r,'POST'));
		return $d;
	}
	function profile($token) {
		$ret=array('uid'=>null,'first_name'=>null,'last_name'=>null,'type'=>'google');
		$url=sprintf("https://www.googleapis.com/oauth2/v1/userinfo?access_token=%s",$token->access_token);
		$d=json_decode(html_fetch($url));
		if (!$d || !$d->id) return $ret;
		$ret['uid']='google-'.$d->id;
		$ret['first_name']=$d->given_name;
		$ret['last_name']=$d->family_name;
		return $ret;
	}
}
class oauth2_facebook{
	/*
	http://developers.facebook.com/docs/authentication/server-side/
	*/
	const APP_ID='434528456575944';
	const APP_SECRET='8cb72fa583b8dbe36029c6a29ff94268';
	const SCOPE='offline_access,user_checkins,friends_checkins';
	function authorize($callback) {
		$r=array();
		$r['client_id']=self::APP_ID;
		$r['redirect_uri']=$callback;
		gs_session::save($r,'oauth2_facebook_request');

		$url="https://www.facebook.com/dialog/oauth?".http_build_query($r);
		return $url;
	}
	function token($data) {
		$request=gs_session::load('oauth2_facebook_request');
		$r=array();
		$r['code']=$data['code'];
		$r['client_id']=self::APP_ID;
		$r['client_secret']=self::APP_SECRET;
		$r['redirect_uri']=$request['redirect_uri'];

		$url="https://graph.facebook.com/oauth/access_token";
		$d=array();
		$d=html_fetch($url,$r,'POST');
		parse_str($d,$d);
		return $d;
	}
	function profile($token) {
		$ret=array('uid'=>null,'first_name'=>null,'last_name'=>null,'type'=>'facebook');
		$url=sprintf("https://graph.facebook.com/me?access_token=%s",$token['access_token']);
		$d=html_fetch($url);
		$d=json_decode($d,1);
		if (!$d || !$d['id']) return $ret;
		$ret['uid']='fb-'.$d['id'];
		$ret['first_name']=$d['first_name'];
		$ret['last_name']=$d['last_name'];
		return $ret;
	}
}
