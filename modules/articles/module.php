<?php
gs_dict::append(array(
	'LOAD_IMAGES'=>'Загрузить картинки',
	'LOAD_RECORDS'=>'Добавить',
	'SUBMIT_FORM'=>'Сохранить',
	'GALLERY_MANAGE_RECORDS'=>'Редактировать картинки',
));


class module_articles implements gs_module {
	function __construct() {
	}
	function install() {
		foreach(array('tw_articles','tw_article_images') as $r){
			$this->$r=new $r;
			$this->$r->install();
		}
	}
	
	function get_menu() {
		return '<a href="/admin/articles">Статьи</a>';
	}
	
	static function get_handlers() {
		$data=array(
		'default'=>array(
			'default'=>'gs_base_handler.show404:{name:404.html}',
		),
		'get_post'=>array(
			''=>'gs_base_handler.show:{name:articles.html}',
			'*'=>'gs_base_handler.show:{name:articles_show.html}',
			'/admin/articles'=>'gs_base_handler.show:{name:adm_articles.html:classname:tw_articles}',
			'/admin/form/tw_articles'=>'gs_base_handler.postform:{name:form.html:form_class:g_forms_table:classname:tw_articles:href:/admin/articles:form_class:form_admin}',
			'/admin/articles/delete'=>'admin_handler.deleteform:{classname:tw_articles}',
			'images'=>'admin_handler.many2one:{name:images.html}',
			'/admin/form/tw_article_images'=>'gs_base_handler.postform:{name:form.html:classname:tw_article_images:form_class:form_admin}',
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

class tw_articles extends gs_recordset_short {
	const superadmin = 1;
	function __construct($init_opts=false) { parent::__construct(array(
		'Name'=> "fString 'Название'",
		'Description'=> "fText 'Содержание' widget=wysiwyg images_key=Images required=false",
		'pid'=> "lOne2One tw_articles",
		'Images'=> "lMany2One tw_article_images:Parent 'Картинки' widget=lMany2One",
		'text_id'=> "fString 'Идентификатор статьи' required=false",
		),$init_opts);
	}
}

class tw_article_images extends tw_images {
	function __construct($init_opts=false) {
		$this->fields['Parent']="lOne2One tw_articles mode=link";
		parent::__construct($this->fields,$init_opts);
		$this->structure['fkeys']=array(
			array('link'=>'Parent','on_delete'=>'CASCADE','on_update'=>'CASCADE'),
		);
	}
}