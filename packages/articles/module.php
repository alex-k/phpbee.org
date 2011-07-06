<?php
gs_dict::append(array(
	'LOAD_IMAGES'=>'Загрузить картинки',
	'LOAD_RECORDS'=>'Добавить',
	'SUBMIT_FORM'=>'Сохранить',
	'GALLERY_MANAGE_RECORDS'=>'Редактировать картинки',
));


class module_articles extends gs_base_module implements gs_module {
	function __construct() {
	}
	function install() {
		foreach(array('tw_articles') as $r){
			$this->$r=new $r;
			$this->$r->install();
		}
	}
	
	function get_menu() {
		return '<a href="/admin/articles/">Статьи</a>';
	}
	
	static function get_handlers() {
		$data=array(
		'handler'=>array(
			'*'=>'gs_base_handler.show:{name:articles_show.html}',
		),
		'get_post'=>array(
			''=>'gs_base_handler.show:{name:articles.html}',
			'/admin/articles/delete'=>'admin_handler.deleteform:{classname:tw_articles}',
			'/admin/articles'=>'gs_base_handler.show:{name:adm_articles.html:classname:tw_articles}',
			'/admin/form/tw_articles'=>array(
				'gs_base_handler.post:{return:gs_record:name:form.html:form_class:g_forms_table:classname:tw_articles:href:/admin/articles/:form_class:form_admin}',
				'gs_base_handler.redirect',
			),
			'images'=>'admin_handler.many2one:{name:images.html}',
		),
	);
	return self::add_subdir($data,dirname(__file__));
	}
}

class tw_articles extends gs_recordset_short {
	const superadmin = 1;
	function __construct($init_opts=false) { parent::__construct(array(
		'name'=> "fString 'Название'",
		'description'=> "fText 'Содержание' widget=wysiwyg images_key=Images required=false",
		'pid'=> "lOne2One tw_articles",
		//'Images'=> "lMany2One tw_articles_images:Parent 'Картинки' widget=lMany2One",
		'text_id'=> "fString 'Идентификатор статьи' required=false",
		),$init_opts);
	}
}


//load_submodules('article',dirname(__FILE__));
load_submodules(basename(dirname(__FILE__)),dirname(__FILE__));
