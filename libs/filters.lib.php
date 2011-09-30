<?php
class gs_filters_handler extends gs_handler {
	function init() {
		$classname="gs_filter_".$this->data['handler_params']['class'];
		$filter=new $classname($this->data);
		$filters=gs_var_storage::load('filters');
		if (!$filters) $filters=array();
		$filters[$filter->name]=$filter;
		gs_var_storage::save('filters',$filters);
		return;
	}
	function show() {
		$ps=$this->data['handler_params'];
		$filters=gs_var_storage::load('filters');
		$filter=$filters[$ps['name']];
		return $filter->getHtmlBlock($ps);
	

	}
}
class gs_filter {
	function __construct($data) {
		$this->data=$data;
		$this->params=$data['handler_params'];
		$this->name=$this->params['name'];
		$this->loadValues();
	}
	function loadValues() {
		$d=$this->data['gspgid_handler_va'];
		$arr=array();
		for($i=0;$i<count($d);$i+=2) {
			$j=$i+1;
			if (isset($d[$j])) $arr[$d[$i]]=$d[$j];
		}
		$this->va=$arr;
		$this->value=isset($arr[$this->name]) ? $arr[$this->name] : null;
	}
	function applyFilter($options) {
		return $options;
	}
	function getHtmlBlock($ps) {
		return $this->name;
	}
}


class gs_filter_select_by_links extends gs_filter {
	function __construct($data) {
		parent::__construct($data);
		list($recordsetname,$linkname)=explode('.',$this->params['link']);
		list($this->linkname,$this->fieldname)=explode(':',$linkname);
		$rs=new $recordsetname();
		$this->link=$rs->structure['recordsets'][$this->linkname];


	}
	function applyFilter($options,$rs) {
		if (empty($this->value)) return $options;
		$this->recordset=$rs;

		$fieldname=$this->fieldname;
		$link=$this->link;

		$rec_rs_name=$link['recordset'];
		$rec_rs=new $rec_rs_name();
		$rec=$rec_rs->find_records(array($fieldname=>$this->value))->first();
		$options[]=array(
				'type'=>'value',
				'field'=>$link['local_field_name'],
				'value'=>$rec->{$link['foreign_field_name']},
				);
		return $options;
	}
	function getHtmlBlock($ps) {
		if (isset($ps['exlusive']) && $ps['exlusive']) return $this->getHtmlBlockExlusive($ps);
		return $this->getHtmlBlockNonExlusive($ps);
	}
	function getHtmlBlockNonExlusive($ps) {
	
		/*
		$link=$ps['recordset']->structure['recordsets'][$linkname];
		*/

		$recordsetname=$this->link['recordset'];
		$rec_rs=new $recordsetname();
		$rec_rs=$rec_rs->find_records(array());

		$tpl=gs_tpl::get_instance();

		$links=array();
		foreach ($rec_rs as $rec) {
			$arr=$this->va;
			$key=$rec->{$this->fieldname};
			$id=$rec->{$this->link['foreign_field_name']};
			
			if ($ps['recordset']) {
				$rs=$ps['recordset'];
				$count=$rs->find(array($this->link['local_field_name']=>$id))->count();
			}

			$name=trim($rec);
			$arr[$this->name]=$key;
			$href=$this->data['handler_key_root'];
			foreach ($arr as $k=>$v) $href.="/$k/$v";
			$links[$href]=array('name'=>$name,'key'=>$key,'count'=>$count);
		}
		$href=$this->data['handler_key_root'];
		unset($arr[$this->name]);
		foreach ($arr as $k=>$v) $href.="/$k/$v";
		$tpl->assign('link_all',$href);


		$tpl->assign('links',$links);
		$tpl->assign('current',$this->value);
		$tplname=isset($ps['tpl']) ? $ps['tpl'] : str_replace('gs_filter_','',get_class($this)).'.html';
		$out=$tpl->fetch('filters'.DIRECTORY_SEPARATOR.$tplname);
		return $out;
	}
}




?>
