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
		$arr=array();
		switch ($this->data['handler_params']['urltype']) {
			case 'get': 
				$ds=new gs_data_driver_get();
				$arr=$ds->import();
				unset($arr['gspgtype']);
				break;
			default:
				$d=$this->data['gspgid_handler_va'];
				for($i=0;$i<count($d);$i+=2) {
					$j=$i+1;
					if (isset($d[$j])) $arr[$d[$i]]=$d[$j];
				}
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
		
		if (!isset($ps['params']) || empty($ps['params'])) $ps['params']=array();
		
		$tpl=gs_tpl::get_instance();
		$links=array();
		$count_all=0;
		foreach ($rec_rs as $rec) {
			$arr=$this->va;
			$key=$rec->{$this->fieldname};
			$id=$rec->{$this->link['foreign_field_name']};
			
			if ($ps['recordset']) {
				$rs=$ps['recordset'];
				$count_array=$rs->query_options['options'];
				foreach ($count_array as $ca_key=>$ca) {
					if ($ca_key===$this->link['local_field_name'] 
						|| (is_array($ca) && isset($ca['field']) && $ca['field']==$this->link['local_field_name'])
						) {

						unset($count_array[$ca_key]);
					}
				}
				$count_array[]=array('type'=>'value',
							    'field'=>$this->link['local_field_name'],
							    'value'=>$id);
				$rsname=$ps['recordset']->get_recordset_name();
				$rs=new $rsname();
				$count=$rs->count_records($count_array);
				$count_all+=$count;
			}

			$name=trim($rec);
			$arr[$this->name]=$key;

			$links[]=array('name'=>$name,'key'=>$key,'count'=>$count, 'va'=>$arr,'rec'=>$rec,);
		}
		
		$current_name='';
		
		foreach($links as $key=>$l) {
			switch ($this->data['handler_params']['urltype']) {
				case 'get':
					$link=$this->data['gspgid_root'].'?'.http_build_query($l['va']);	
					unset($l['va'][$this->name]);
					$link_all=$this->data['gspgid_root'].'?'.http_build_query($l['va']);	
					break;
				default:
					$link=$this->data['handler_key_root'];
					foreach ($l['va'] as $k=>$v) $link.="/$k/$v";

					unset($l['va'][$this->name]);
					$link_all=$this->data['handler_key_root'];
					foreach ($l['va'] as $k=>$v) $link_all.="/$k/$v";
			}
			$l['href']=$link;
			unset($l['va']);
			$links[$key]=$l;
			if ($l['key']==$this->value) $current_name=$l['name'];
		}
		$link_all_array=array('name'=>'all','key'=>'all','href'=>$link_all,'count'=>$count_all, 'va'=>null,'rec'=>null);

		$tpl->assign('link_all',$link_all_array);
		$tpl->assign('links',$links);
		$tpl->assign('current',$this->value);
		$tpl->assign('current_name',$current_name);
		$tpl->assign('filter_params',$ps['params']);
		$tplname=isset($ps['tpl']) ? $ps['tpl'] : str_replace('gs_filter_','',get_class($this)).'.html';
		$out=$tpl->fetch('filters'.DIRECTORY_SEPARATOR.$tplname);
		return $out;
	}
}




?>
