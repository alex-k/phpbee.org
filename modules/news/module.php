<?php
gs_dict::append(array(
		'LOAD_IMAGES'=>'добавить картинки',
	));

class module_news implements gs_module {
	function __construct() {
	}
	function install() {
		foreach(array('tw_news','tw_news_images') as $r){
			$this->$r=new $r;
			$this->$r->install();
		}
	}
	
	function get_menu() {
		return '<a href="/admin/news">Новости</a>';
	}
	
	static function get_handlers() {
		$data=array(
		'default'=>array(
			'default'=>'gs_base_handler.show404:{name:404.html}',
		),
		'get_post'=>array(
			''=>'gs_base_handler.show:{name:news.html}',
			'*'=>'gs_base_handler.show:{name:news_show.html}',
			'/admin/news'=>'gs_base_handler.show:{name:adm_news.html:classname:tw_news}',
			'/admin/form/tw_news'=>'gs_base_handler.postform:{name:form.html:form_class:g_forms_table:classname:tw_news:href:admin/news}',
			'/admin/news/delete'=>'handler_news.deleteform',
			'images'=>'handler_news.many2one:{name:images.html}',
			'images/show'=>'handler_news.show_images',
			'img/show'=>'handler_news.show_images',
			'many2one'=>'handler_news.many2one:{name:many2one.html}',
			'form/tw_news_images'=>'gs_base_handler.postform:{name:form.html:classname:tw_news_images}',
		),
	);
	return self::add_subdir($data);
	}
	static function add_subdir($data) {
		$subdir=trim(str_replace(cfg('lib_modules_dir'),'',clean_path(dirname(__file__)).'/'),'/');
		$d=array();
		foreach($data as $k=>$a) {
			foreach($a as $t=>$v) {
				if (strpos($t,'/')===0) {
					$d[$k][trim($t,'/')]=$v;
				} else {
					$d[$k][rtrim($subdir.'/'.$t,'/')]=$v;
				}
			}
		}
		return $d;
	}
}

class handler_news extends gs_base_handler {
	function deleteform() {
		$id=intval($this->data['gspgid_va'][0]);
		$res=preg_replace("|/delete/\d+|is","//",$this->data['gspgid']);
		$rs=new tw_news;
		$rec=$rs->get_by_id($id);
		$rec->delete();
		$rec->commit();
		return html_redirect($res);
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
 function show_images() {
		 $rs_name=$this->data['gspgid_va'][0];
		 $size=$this->data['gspgid_va'][1];
		 $img_id=$this->data['gspgid_va'][2];
		 $rec=new $rs_name();
		 $rec=$rec->get_by_id($img_id);
		 $gd=new vpa_gd($rec->file_data,false);
		 if ($size>0) {
			  $gd->set_bg_color(255,255,255);
			  $gd->resize($size,$size,'use_box');
		 }
		 $gd->show();
		 exit();
	}
}

class tw_news extends gs_recordset_short {
	const superadmin=1;
	function __construct($init_opts=false) { parent::__construct(array(
		'date'=>"fDatetime дата",
		'subject'=>"fString заголовок",
		'text'=>"fText текст widget=wysiwyg images_key=Images",
	'Images'=>"lMany2One tw_news_images:Parent 'Картинки' widget=lMany2One",
		'hot'=>"fCheckbox горячая",
		'hidden'=>"fCheckbox спрятать",
	),$init_opts);
	}
}

class tw_news_images extends gs_recordset_short {
		function __construct($init_opts=false) {
				parent::__construct(array(
				'file'=>"fFile verbose_name='File' required=false ",
				'Parent'=>"lOne2One tw_news mode=link",
				),$init_opts);
				$this->structure['fkeys']=array(
										array('link'=>'Parent','on_delete'=>'CASCADE','on_update'=>'CASCADE'),
										);
		}
		function record_as_string($rec) {
				if (strpos($rec->file_mimetype,'image')===0) {
						$subdir=trim(str_replace(cfg('lib_modules_dir'),'',dirname(__FILE__).'/'),'/');
						$www_subdir=trim(cfg('www_dir').$subdir.'/','/');
						$www_subdir=$www_subdir ? "/$www_subdir/" : '/';
						$name=parent::record_as_string($rec);
						return sprintf('<img src="/img/show/tw_news_images/50/%d" alt="%s">',$rec->get_id(),$name);
				}
				return parent::record_as_string($rec);
		}
}








