<?php
abstract class tw_images extends gs_recordset_short {
	function src($params,$record=null) {
		$records=$record ? array($record) : $this;
		$ret=array();
		foreach ($records as $rec) {
			$ret=array_merge($ret,$rec->File->src($params));
		}
		return $ret;
	}
	function img($params,$record=null) {
		$records=$record ? array($record) : $this;
		$ret=array();
		foreach ($records as $rec) {
			$ret=array_merge($ret,$rec->File->img($params));
		}
		return $ret;
	}
}
abstract class tw_file_images extends gs_recordset_short{
	var $gs_connector_id='file_public';
	var $config=array();
	var $fields=array(
		'File'=> "fFile 'Файл'",
		//'Name'=> "fString 'Названице' ",
		//'desc'=> "fText",
	);
	function __construct($init_opts=false) {
		parent::__construct($this->fields,$init_opts);
		$this->config_previews();
		$this->structure['triggers']['after_insert']='resize';
		$this->structure['triggers']['after_update']='resize';
	}
	/*
        function record_as_string($rec) {
                if (strpos($rec->File_mimetype,'image')===0) {
                        return sprintf('<img src="/img/s/%s" alt="%s" title="%s">',(sprintf('%s/b/100/100/%s',get_class($this),$rec->get_id())),$rec->File_filename,$rec->File_filename);
                }
                return parent::record_as_string($rec);
        }
        public function __toString() {
                return implode(' ',$this->recordset_as_string_array());
        }
	*/

	function img($params,$record=null) {
		$ret=$this->src($params,$record);
		foreach($ret as $k=>$v) {
			$ret[$k]=sprintf('<img src="%s" alt="">',$v);
		}
		return $ret;
	}
	function src($params,$record=null) {
		$records=$record ? array($record) : $this;
		$ret=array();
		$fname=$this->get_connector()->www_root.'/'.$this->db_tablename;
		foreach ($records as $rec) {
			$ret[]=$fname.'/'.$this->get_connector()->split_id($rec->get_id()).'/'.'File_data';	
		}
		return $ret;
	}
	
	function config_previews() {
		$this->config=array(
			'orig'=>array('width'=>0,'height'=>0,'method'=>'copy'),
		);
	}
	
	function resize($rec,$type) {
		$fname=$this->get_connector()->root.DIRECTORY_SEPARATOR.$this->db_tablename.DIRECTORY_SEPARATOR.$this->get_connector()->split_id($rec->get_id()).DIRECTORY_SEPARATOR;
		$gd=new vpa_gd($rec->first()->File_data,false);
		foreach ($this->config as $key => $data) {
			$sname=$fname.'File_data';
			$iname=$fname.$key.'.jpg';
			if ($data['width']>0  && ($data['width']<$rec->first()->File_width || $data['heigt']<$rec->first()->File_height)) {
				if ($data['bgcolor']) $gd->set_bg_color($data['bgcolor'][0],$data['bgcolor'][0],$data['bgcolor'][0]);
				$gd->resize($data['width'],$data['height'],$data['method']);
			}
			if (isset($data['method']) && $data['method']=='copy') {
				copy($sname,$iname);
			} else {
				$gd->save($iname,100);
			}
		}
	}

}




?>