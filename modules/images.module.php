<?php
require_fullpath(__FiLE__,'module.php');
abstract class tw_images extends gs_recordset_handler {
	var $no_urlkey=1;
	function src1($params,$record=null) {
		return trim(reset($this->src($params,$record)));
	}
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
	function record_as_string($rec) {
		$res=$rec->File->img('small');
		$res=trim(implode(' ',$res));
		return $res;
	}
	public function __toString() {
		return implode(' ',$this->recordset_as_string_array());
		//return 'image';
	}
}
abstract class tw_file_images extends gs_recordset_short{
	var $no_urlkey=1;
	var $gs_connector_id='file_public';
	var $config=array();
	var $fields=array(
		'File'=> "fFile 'Файл'",
	);
	function __construct($f=array(),$init_opts=false) {
		parent::__construct(array_merge($f,$this->fields),$init_opts);
		$this->config_previews();
		$this->structure['triggers']['after_insert']='resize';
		$this->structure['triggers']['after_update']='resize';
	}

	function img($params,$record=null) {
		$ret=$this->src($params,$record);
		foreach($ret as $k=>$v) {
			$ret[$k]=sprintf('<img src="%s" alt="">',$v);
		}
		return $ret;
	}
	function src($params,$record=null) {
		if (is_array($params)) {
			$type=$params[0];
		} else {
			$type=$params;
		}
		
		$records=$record ? array($record) : $this;
		$ret=array();
		$fname=$this->get_connector()->www_root.'/'.$this->db_tablename;
		foreach ($records as $rec) {
			$ret[]=$fname.'/'.$this->get_connector()->split_id($rec->get_id(),true).'/'.(($type=='') ? 'File_data' : $type.'.jpg');
		}
		return $ret;
	}
	
	function config_previews() {
		$this->config=array(
			'orig'=>array('width'=>0,'height'=>0,'method'=>'copy'),
			'admin'=>array('width'=>100,'height'=>100,'method'=>'use_fields','bgcolor'=>array(255,255,255)),
			'small'=>array('width'=>100,'height'=>75,'method'=>'use_crop','bgcolor'=>array(255,255,255)),
		);
	}
	
	function show($type,$rec) {
		$fname=$this->get_connector()->root.DIRECTORY_SEPARATOR.$this->db_tablename.DIRECTORY_SEPARATOR.$this->get_connector()->split_id($rec->get_id()).DIRECTORY_SEPARATOR.$type.'.jpg';
		ob_end_clean();
		header ('Content-Type: image/jpeg');
		readfile($fname);
		die();
	}
	function resize($rec,$type,$ret=null,$no_rewrite=false) {
		$fname=$this->get_connector()->root.DIRECTORY_SEPARATOR.$this->db_tablename.DIRECTORY_SEPARATOR.$this->get_connector()->split_id($rec->get_id()).DIRECTORY_SEPARATOR;
		$sname=$fname.'File_data';
		foreach ($this->config as $key => $data) {
			$gd=new vpa_gd($sname);
			$iname=$fname.$key.'.jpg';
			if ($data['width']>0  && ($data['width']<$rec->first()->File_width || $data['height']<$rec->first()->File_height)) {
				if ($data['bgcolor']) $gd->set_bg_color($data['bgcolor'][0],$data['bgcolor'][0],$data['bgcolor'][0]);
				if ($data['modifier']) $gd->modifier($data['width'],$data['height'],$data['modifier']);

				$gd->resize($data['width'],$data['height'],$data['method']);
			}
			if (!file_exists($iname) || ($no_rewrite==false && file_exists($iname))) {
				if (isset($data['method']) && $data['method']=='copy') {
					copy($sname,$iname);
				} else {
					$gd->save($iname,100);
				}
			}
		}
	}

}
class images_module extends gs_base_module implements gs_module {
	function __construct() {}
	
	function install() {
	}
	
	static function get_handlers() {
		$data=array(
			'get_post'=>array(
				'img/show'=>'images_handler.show',
				'img/s'=>'images_handler.s',
				'/admin/images'=>'admin_handler.many2one:{name:images.html}',
			),
		);
                $ckey=$path=false;
                $c=cfg('gs_connectors');
                foreach ($c as $key => $v) {
                        if ($v['db_type']=='file') {
                                $path=$v['www_root'];
                                $ckey=$key;
                                break;
                        }
                }
                if ($path) {
                        $data['get'][$path]='images_handler.resize:{key:'.$ckey.'}';
                }
		return self::add_subdir($data,dirname(__file__));
	}
}
class images_handler extends gs_base_handler {
	
	function resize($data=null) {
		$c=cfg('gs_connectors');
		$cinfo=$c[$this->params['key']];
		$d=$this->data['gspgid_va'];
		$rs=reset($d);
		$t=pathinfo(array_pop($d));
		$type=$t['filename'];
		$key=array_pop($d);
		$o=new $rs;
		$c=new gs_dbdriver_file($cinfo);
		$id=$c->id2int($key);
		$rec=$o->get_by_id($id);
		if (!$rec) {
			header ('HTTP/1.1 404 Not Found');
			die();
		}
		$o->resize($rec,'');
		$o->show($type,$rec);
	}
	
	function show($data) {
		if (count($this->data['gspgid_va'])<5) {
			$data=base64_decode($this->data['gspgid_va'][0]);
			
			$data=preg_replace("|\..+|is","",$data);
			$data=explode("/",$data);
		}
		$method=array(
			'w'=>'use_width',
			'h'=>'use_height',
			'b'=>'use_box',
			'f'=>'use_fields',
			'c'=>'use_crop',
		);
		$data[4]=preg_replace("|\..+|is","",$data[4]);
		$rec=new $data[0]();
		$rec=$rec->get_by_id($data[4]);
		$file=$rec->File->first();
		$txt=get_output();
		$gd=new vpa_gd($file->File_data,false);
		if ($data[2]>0  && ($data[2]<$file->File_width || $data[3]<$file->File_height)) {
			$gd->set_bg_color(255,255,255);
			$gd->resize($data[2],$data[3],$method[$data[1]]);
		}
		$gd->show();
		//gs_logger::dump();
		exit();
	}
	function s() {
		return ($this->show($this->data['gspgid_va']));
	}

}




?>
