<?php

class gs_data_driver_post implements gs_data_driver {
	
	function test_type()
	{
		return $_SERVER['REQUEST_METHOD']=='POST';
	}
	
	function import ()
	{
		if ($this->test_type()) {
			$_POST['gspgtype']=GS_DATA_POST;
			$_POST['gspgid']=isset($_POST['gspgid']) ? trim($_POST['gspgid'],'/') : '';
			$_POST=array_merge($_POST,$_FILES);
			return get_magic_quotes_gpc() ? stripslashes_deep($_POST) : $_POST;
		}
		return array();
	}
}

?>
