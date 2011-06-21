<?php

abstract class gs_base_module {
	static function add_subdir($data,$dir) {
		$subdir=trim(str_replace(cfg('lib_modules_dir'),'',clean_path($dir).'/'),'/');
		$d=array();
		foreach($data as $k=>$a) {
			foreach($a as $t=>$v) {
				if (strpos($t,'/')===0) {
					$d[$k][trim($t,'/')]=$v;
				} else {
					$d[$k][trim($subdir.'/'.$t,'/')]=$v;
				}
			}
		}
		return $d;
	}
	
	static function admin_auth($data,$params) {
		if (strpos($data['gspgid'],'admin')===0) {
			if(in_array($_SERVER['REMOTE_ADDR'],array('127.0.0.1','192.168.1.102'))) return true;
			$o=new admin_handler($data,array('name'=>'auth_error.html'));
			$o->show();
			return false;
		}
		return true;
	}
}

class module extends gs_base_module implements gs_module {
	function __construct() {}
	
	function install() {}
	
	static function get_handlers() {
		$data=array(
			'default'=>array(
				'default'=>'gs_base_handler.show404:{name:404.html}',
			),
			'get_post'=>array(
				''=>'gs_base_handler.show:{name:index.html}',
				'/admin'=>'admin_handler.show:{name:admin_page.html}',
				'/admin/menu'=>'admin_handler.show_menu',
				'img/show'=>'images_handler.show',
				'img/s'=>'images_handler.s',
				'/admin/window_form'=>'admin_handler.many2one:{name:window_form.html}',
				'/admin/many2one'=>'admin_handler.many2one:{name:many2one.html}',
				'/admin/images'=>'admin_handler.many2one:{name:images.html}',
				'a'=>'gs_base_handler.show',
				'b'=>'gs_base_handler.show',
			),
		);
		return self::add_subdir($data,dirname(__file__));
	}
}

class images_handler extends gs_base_handler {
	function show( $data=null) {
		if (!$data) {
			$data=base64_decode($this->data['gspgid_va'][0]);
			$data=preg_replace("|\..+|is","",$data);
			$data=explode("/",$data);
		}
		$method=array(
			'w'=>'use_width',
			'h'=>'use_height',
			'b'=>'use_box',
			'f'=>'use_fields',
			'c'=>'use_crop',
		);
		$rec=new $data[0]();
		$rec=$rec->get_by_id($data[4]);
		$gd=new vpa_gd($rec->File_data,false);
		if ($data[2]>0  && ($data[2]<$rec->File_width || $data[2]<$rec->File_height)) {
			$gd->set_bg_color(255,255,255);
			$gd->resize($data[2],$data[3],$method[$data[1]]);
		}
		$gd->show();
		exit();
	}
	function s() {
		return ($this->show($this->data['gspgid_va']));
	}

}


class admin_handler extends gs_base_handler {
	function show_menu () {
		$cfg=gs_config::get_instance();
		$modules=$cfg->get_registered_modules();
		$menu=array();
		if (is_array($modules)) foreach ($modules as $m) {
			$mod=new $m;
			if (method_exists($mod,'get_menu') && $mod->get_menu()) {
				$menu[]=$mod->get_menu();
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
	
	function many2one() {
		if ($this->data['gspgid_va'][4]=='delete') {
			$rid=intval($this->data['gspgid_va'][5]);
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
		$params=array(
			$this->data['gspgid_va'][1]=>$this->data['gspgid_va'][2],
		);
		$url=$this->data['gspgid_va'][0].'/'.$this->data['gspgid_va'][1].'/'.$this->data['gspgid_va'][2].'/'.$this->data['gspgid_va'][3];
		if ($this->data['gspgid_va'][2]==0) {
			$params[$this->data['gspgid_va'][1].'_hash']=$this->data['gspgid_va'][3];
		}
		$tpl=gs_tpl::get_instance();
		$tpl->assign('url',$url);
		$tpl->assign('params',$params);
		parent::show();
	}
}


class form_admin extends  g_forms_html {
	function __construct($h,$data=array(),$rec=null)  {
		parent::__construct($h,$data,$rec);
		$this->view = new gs_glyph('helper',array('class'=>'table_admin'));
		$this->view->addNode('helper',array('class'=>'tr'),array_keys($h));
	}
}
class form_table extends  g_forms_html {
	function __construct($h,$data=array(),$rec=null)  {
		parent::__construct($h,$data,$rec);
		$this->view = new gs_glyph('helper',array('class'=>'table_submit'));
		$this->view->addNode('helper',array('class'=>'tr'),array_keys($h));
	}
}

