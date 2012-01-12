<?php


class module extends gs_base_module implements gs_module {
	function __construct() {}
	
	function install() {
		$n=new tw_handlers;
		$n->install();
		$n=new tw_handlers_cache;
		$n->install();
	}
	
	static function get_handlers() {
		$data=array(
			'get_post'=>array(
				''=>'gs_base_handler.show:{name:index.html}',
				'/admin'=>'admin_handler.show:{name:admin_page.html}',
				'/admin/window_form'=>'admin_handler.many2one:{name:window_form.html}',
				'/admin/many2one'=>'admin_handler.many2one:{name:many2one.html}',
				'/admin/logout'=>array(
					  'admin_handler.post_logout:return:true',
					  'gs_base_handler.redirect',
					),
				'*'=>'gs_base_handler.show404:{name:404.html}',
			),
			'handler'=>array(
				'/admin/menu'=>'admin_handler.show_menu',
				'/admin/login'=>array(        
					  'admin_handler.check_login:return:true^show', 
					  'show'=> 'gs_base_handler.show:name:admin_login.html', 
				  ),              
				 '/admin/form/login'=>array(
					  'admin_handler.post_login:return:true:form_class:form_admin_login',
					  'gs_base_handler.redirect',
				  ),


				'/filter'=>'gs_filters_handler.init',
				'/filter/show'=>'gs_filters_handler.show',
				'/debug'=>'debug_handler.show',
			),
		);
		return self::add_subdir($data,dirname(__file__));
	}
	static function gl($name,$record,$data) {
		switch ($name) {
			case 'save_cancel':
				return $data['handler_key_root'];
			case 'save_continue':
				return $data['gspgid_root'];
			case 'save_return':
				return $data['handler_key_root'];
			break;
			}
		return null;
	}
}


class form_admin_login extends g_forms_html {
	function __construct($hh,$params=array(),$data=array()) {
		$hh=array(
			'admin_user_name' => Array
				(
					'type' => 'input',
					'verbose_name'=>'login',
				),
			'admin_password' => Array
				(
					'type' => 'password',
					'verbose_name'=>'password',
				),

		 );
		 return parent::__construct($hh,$params,$data);
	}
}



class admin_handler extends gs_base_handler {
	function check_login($data) {
		$rec=gs_session::load('login_gs_admin');
		if(isset($this->data['handler_params']['assign'])) {
			gs_var_storage::save($this->data['handler_params']['assign'],$rec);
		}
		if(isset($this->params['assign'])) {
			gs_var_storage::save($this->params['assign'],$rec);
		}
		return $rec;
	}
	function post_logout($data) {
		$rec=$this->check_login();
		gs_session::clear('login_gs_admin');
		return true;
	}

	function post_login($data) {
		$bh=new gs_base_handler($this->data,$this->params);
		$f=$bh->validate();
		if (!is_object($f) || !is_a($f,'g_forms')) return $f;

		$rec=FALSE;

		$d=$f->clean();
		if (cfg('admin_user_name')==$d['admin_user_name'] && cfg('admin_password')==$d['admin_password']) {
			$rec=$d;
		}
		if (!$rec) return $this->showform();
		gs_session::save($rec,'login_gs_admin');
		return true;
	}
	function show_menu () {
		$init=new gs_init('auto');
		$init->load_modules();

		$cfg=gs_config::get_instance();
		$modules=$cfg->get_registered_modules();
		$pr_modules=array();
		if (cfg('modules_priority')) foreach (explode(',',cfg('modules_priority')) as $pm) {
			$pm='module_'.$pm;
			if (in_array($pm,$modules)) {
				$pr_modules[$pm]=$pm;
			}
		}
		$modules=array_merge($pr_modules,$modules);
		$menu=array();
		if (is_array($modules)) foreach ($modules as $m) {
			$mod=new $m;
			if (method_exists($mod,'get_menu') && $menuitem=$mod->get_menu()) {
				if (!is_array($menuitem)) $menuitem=array($menuitem);
				$menu=array_merge($menu,$menuitem);
			}
		}
		$tpl=gs_tpl::get_instance();
		$tpl->assign('menu',$menu);
		return $tpl->fetch('admin_menu.html');
	}
	
	function show() {
		parent::show();
		return false;
	}
	
	function deleteform() {
		$id=$this->data['gspgid_va'][0];
		$res=preg_replace("|/delete/\d+|is","/",$this->data['gspgid']);
		$rs=new $this->params['classname'];
		$rec=$rs->get_by_id($id);
		$rec->delete();
		$rec->commit();
		$query=array();
		parse_str(parse_url(cfg('referer'),PHP_URL_QUERY),$query);
		return html_redirect($res,$query);
	}
	
	function many2one($ret) {
		if (isset($this->data['gspgid_va'][5]) && $this->data['gspgid_va'][5]=='delete') {
			$rid=intval($this->data['gspgid_va'][6]);
			$rs_name=$this->data['gspgid_va'][0];
			$rs=new $rs_name;
			$rec=$rs->get_by_id($rid);
			if ($rec) {
				$rec->delete();
				$rec->commit();
			}
			$res=preg_replace("|/delete/\d+|is","//",$this->data['gspgid']);
			return html_redirect($res);
		}
		
		if ($this->data['action']=='delete') {
			$ids=$this->data['act'];
			$rs_name=$this->data['gspgid_va'][0];
			$rs=new $rs_name;
			$recs=$rs->find_records(array('id'=>$ids));
			foreach ($recs as $rec) {
				$rec->delete();
				$rec->commit();
			}
			return html_redirect($this->data['gspgid']);
		}
		
		
		$params=array(
			$this->data['gspgid_va'][1]=>$this->data['gspgid_va'][2],
		);
		$g=array_slice($this->data['gspgid_va'],0,5);
		$url=implode('/',$g);
		if ($this->data['gspgid_va'][2]==0) {
			$params[$this->data['gspgid_va'][1].'_hash']=$this->data['gspgid_va'][3];
		}
		$tpl=gs_tpl::get_instance();
		$tpl->assign('url',$url);
		$tpl->assign('params',$params);
		parent::show($ret);
	}
}


class form_admin extends  g_forms_html {
	function __construct($h,$data=array(),$rec=null)  {
		parent::__construct($h,$data,$rec);
		$this->view = new gs_glyph('helper',array('class'=>'table_admin'));
		$this->addNode(array_keys($h));
	}
	function addNode($name) {
		$this->view->addNode('helper',array('class'=>'tr'),$name);
	}
}
class form_table extends  g_forms_html {
	function __construct($h,$data=array(),$rec=null)  {
		parent::__construct($h,$data,$rec);
		$this->view = new gs_glyph('helper',array('class'=>'table_submit'));
		$this->addNode(array_keys($h));
	}
	function addNode($name) {
		$this->view->addNode('helper',array('class'=>'tr'),$name);
	}
}


class debug_handler extends gs_handler {
	function show() {
		$tpl=gs_tpl::get_instance();
		$log=gs_logger::get_instance();
		$tpl->assign('gmessages',$log->gmessages());
		return $tpl->fetch('debug.html');
	}
}
?>
