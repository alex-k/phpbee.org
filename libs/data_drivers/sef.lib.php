<?php

class gs_data_driver_sef implements gs_data_driver {
	
	private $gspgid;
	
	function test_type()
	{


		$dir=gs_config::get_instance()->script_dir;
		$gspgid=preg_replace("|^".preg_quote ($dir)."|s",'',$_SERVER["REQUEST_URI"]);
		$gspgid=preg_replace('|\?.*$|is','',$gspgid);
		$gspgid=trim($gspgid,'/');


		if (class_exists('urlprefix_cfg')) {
			$gspgid_old=$gspgid;
			$px=new urlprefix_cfg();
			foreach ($px->find_records(array()) as $pf) {
				if (stripos($gspgid,$pf->prefix)===0) {
					gs_var_storage::save($pf->variable_name,$pf->value);
					$gspgid=substr($gspgid,strlen($pf->prefix));
					$gspgid=trim($gspgid,'/');
				}
			}

			gs_var_storage::save('urlprefix',str_replace($gspgid,'',$gspgid_old));
		}

		$this->gspgid=trim($gspgid,'/');
		return !empty($this->gspgid);
	}
	
	function import ()
	{
		gs_var_storage::save('gspgid',$this->gspgid);
		return $this->test_type() ? array('gspgid'=>$this->gspgid,'gspgtype'=>GS_DATA_GET) : array();
	}
}

?>
