<?

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
			

		$params=array();
		$params['recordset']=$this->params['options']['recordset'];
		$params['linkname']=$this->params['linkname'];
		$params['foreign_field_name']=$this->params['options']['foreign_field_name'];
		$params['rid']=$rid;
		$params['hash']=$hash;


		$images=$r->find_records($find);
		$s='<ul class="many2one_gallery" id="gallery_'.$hash.'">';
			foreach($images as $i) {
				$s.=sprintf('<li><a href="/admin/many2one/%s/%s/%d/%s/as_gallery/delete/%d"
					onClick="return confirm(\'delete?\');">
				<img src="%s"></a></li>',
				$params['recordset'],
				$params['foreign_field_name'],
				$params['rid'],
				$params['hash'],
				$i->get_id(),
				$i->src1('admin')
				);
			}
		$s.='</ul><div class="clear"></div>';


		/*
		$s.=sprintf('<a href="/admin/many2one/%s/%s/%d/%s/as_gallery" target="_blank" onclick="window.open(this.href,\'_blank\',\'width=800,height=400,scrollbars=yes, resizable=yes\'); return false;" id="lMany2One_%s">%s</a>',$this->params['options']['recordset'],$this->params['options']['foreign_field_name'],$rid,$hash,$this->params['linkname'],gs_dict::get('LOAD_RECORDS'));
		$s.=sprintf('<input type="hidden" name="%s" value="%s">', $this->params['linkname'].'_hash',$hash);
		*/

		$filename=dirname(__FILE__).DIRECTORY_SEPARATOR.'public_html'.DIRECTORY_SEPARATOR.'include.html';

		$tpl=gs_tpl::get_instance();
		$tpl->assign('params',$params);
		//$tpl->force_compile=true;
		//$out=$tpl->fetch('string:'.file_get_contents($filename));
		$out=$tpl->fetch($filename);

		$s.=$out;


		return $s;

	}
}

