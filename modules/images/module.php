<?php
class tw_file_images extends gs_recordset_short{
	var $gs_connector_id='file_public';
	var $fields=array(
		'File'=> "fFile 'Файл'",
		//'Name'=> "fString 'Названице' ",
		//'desc'=> "fText",
	);
	function __construct($init_opts=false) {
		parent::__construct($this->fields,$init_opts);
	}
        function record_as_string($rec) {
                if (strpos($rec->File_mimetype,'image')===0) {
                        return sprintf('<img src="/img/s/%s" alt="%s" title="%s">',(sprintf('%s/b/100/100/%s',get_class($this),$rec->get_id())),$rec->File_filename,$rec->File_filename);
                }
                return parent::record_as_string($rec);
        }
        public function __toString() {
                return implode(' ',$this->recordset_as_string_array());
        }

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
		$fname=$this->get_connector()->www_root.DIRECTORY_SEPARATOR.$this->db_tablename;
		foreach ($records as $rec) {
			$ret[]=$fname.DIRECTORY_SEPARATOR.$this->get_connector()->split_id($rec->get_id()).DIRECTORY_SEPARATOR.'File_data';	
		}
		return $ret;
	}

}



?>
