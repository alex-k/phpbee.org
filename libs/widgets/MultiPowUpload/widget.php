<?php

class gs_widget_MultiPowUpload extends gs_widget{
	function html() {
		$hash_field_name=$this->params['linkname'].'_hash';
		$hash=isset($this->data[$hash_field_name]) ? $this->data[$hash_field_name] : time().rand(10,99);
		$rid_name=$this->params['options']['local_field_name'];
		$rid=isset ($this->data[$rid_name]) ? $this->data[$rid_name] : 0;
		$r=new $this->params['options']['recordset'];

		$images=$r->find_records(array(
				$this->params['options']['foreign_field_name']=>0,
				array('field'=>'_ctime','case'=>'<=','value'=>date(DATE_ATOM,strtotime('now -1 day'))),
				));
		$images->delete();
		$images->commit();

		$find=array();
		if (isset ($this->data[$rid_name])) {
			$find[$this->params['options']['foreign_field_name']]=$this->data[$rid_name];
		} else {
			$find[$this->params['options']['foreign_field_name'].'_hash']=$hash;
		}
			
		$tpl=gs_tpl::get_instance();
		$tpl->template_dir[]=dirname(__FILE__).DIRECTORY_SEPARATOR.'templates';

		$params=array();
		$params['recordset']=$this->params['options']['recordset'];
		$params['linkname']=$this->params['linkname'];
		$params['foreign_field_name']=$this->params['options']['foreign_field_name'];
		$params['rid']=$rid;
		$params['hash']=$hash;
		$params[$params['linkname'].'_hash']=$hash;

		$params_str=json_encode(str_replace(array('&','='),array('|',';'),http_build_query($params)));
		$tpl->assign('params',$params);
		$tpl->assign('params_str',$params_str);

		$images=$r->find_records($find)->orderby('group_key');
		$g_images=array();
		foreach($images as $i) {
			$key=$i->group_key;
			if (!$key) $key='nogrp';
			$g_images[$key][]=$i;
		}
		$tpl->assign('images',$images);
		$tpl->assign('g_images',$g_images);

		return $tpl->fetch('widget.html');

	}
	function clean() {
		return array();
	}
}

class gs_widget_MultiPowUpload_module extends gs_base_module implements gs_module {
	function __construct() {}
	function install() {}
	function get_menu() {}
	static function get_handlers() {
		$data=array(
		'handler'=>array(
			'/widgets/MultiPowUpload/action'=>array(
				'gs_widget_MultiPowUpload_handler.action',
				'gs_base_handler.redirect',
			),
		),
		);
		return self::add_subdir($data,dirname(__file__));
	}
}

class gs_widget_MultiPowUpload_handler extends gs_handler {
	function action() {
		$this->handler_params=$this->data['handler_params'];
		if ($this->data['gspgtype']!==GS_DATA_POST) return '';

		$rs=new$this->handler_params['recordset'];
		$options=array(
			$this->handler_params['foreign_field_name']=>$this->handler_params['rid'],
			'id'=>$this->data['checked_items'],
		);
		$this->recs=$rs->find_records($options);
		if ($this->data['checked_items_action']=='group') $this->action_group();
		if ($this->data['checked_items_action']=='delete') $this->action_delete();
		return $this->recs->first();
	}
	function action_group() {
		$id=array_keys($this->recs->get_values());
		asort($id);
		$key=implode('-',$id);
		foreach ($this->recs as $rec) $rec->group_key=$key;
		$this->recs->commit();
	}
	function action_delete() {
		foreach ($this->recs as $rec) $rec->delete();
		$this->recs->commit();
	}
}
