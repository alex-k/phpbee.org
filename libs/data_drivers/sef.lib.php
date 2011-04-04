<?php

class gs_data_driver_sef implements gs_data_driver {
	
	private $gspgid;
	
	function test_type()
	{
		$dir=gs_config::get_instance()->script_dir;
		$gspgid=preg_replace("|^".preg_quote ($dir)."|s",'',$_SERVER["REQUEST_URI"]);
		$gspgid=preg_replace('|\?.*$|is','',$gspgid);
		$this->gspgid=trim($gspgid,'/');
		return !empty($this->gspgid);
	}
	
	function import ()
	{
		return $this->test_type() ? array('gspgid'=>$this->gspgid,'gspgtype'=>GS_DATA_GET) : array();
	}
}

?>