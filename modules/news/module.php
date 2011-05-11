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
		return '<a href="/admin/news/">Новости</a>';
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
			'/admin/form/tw_news'=>'gs_base_handler.postform:{name:form.html:form_class:g_forms_table:classname:tw_news:href:/admin/news}',
			'/admin/news/delete'=>'admin_handler.deleteform:{classname:tw_news}',
			'images'=>'handler_news.many2one:{name:images.html}',
			'images/show'=>'handler_news.show_images',
			'img/show'=>'handler_news.show_images',
			'/admin/form/tw_news_images'=>'gs_base_handler.postform:{name:form.html:classname:tw_news_images}',
			'/admin/news/iframe_gallery'=>'gs_base_handler.many2one:{name:iframe_gallery.html}',
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
		'Images'=>"lMany2One tw_news_images:Parent 'Картинки' widget=iframe_gallery",
		'hot'=>"fCheckbox горячая",
		'hidden'=>"fCheckbox спрятать",
	),$init_opts);
	}
}

class tw_news_images extends tw_images {
	function __construct($init_opts=false) {
		$this->fields['Parent']="lOne2One tw_news mode=link";
		parent::__construct($this->fields,$init_opts);
		$this->structure['fkeys']=array(
			array('link'=>'Parent','on_delete'=>'CASCADE','on_update'=>'CASCADE'),
		);
	}
        function record_as_string($rec) {
                if (strpos($rec->File_mimetype,'image')===0) {
                        return sprintf('<img src="/img/show/%s" alt="%s" title="%s">',base64_encode(sprintf('tw_news_images/b/100/100/%d',$rec->get_id())),$rec->File_filename,$rec->File_filename);
                }
                return parent::record_as_string($rec);
        }
        public function __toString() {
                return implode(' ',$this->recordset_as_string_array());
        }

}







