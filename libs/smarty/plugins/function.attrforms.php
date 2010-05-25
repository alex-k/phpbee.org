<?php
function smarty_function_attrforms($params, &$smarty)
{
	$item=$params['item'];
	$debug=$params['debug'];
	$att_values=$item->get_recordset()->get_attribute_values($item->get_id());
	$tags=$params['tags'] ? $params['tags'] : $item->t2i->get_elements_by_name('tags');
	$field_name_prefix=isset($params['field_name_prefix']) ? $params['field_name_prefix'] : false;
	if(!$tags) return;
	$attributes=$tags->get_attributes();
	foreach($attributes as $attr) {
		$ret.=gs_show_attribute(array('attribute'=>$attr,'values'=>$att_values[$attr->get_id()],'item'=>$item,'field_name_prefix'=>$field_name_prefix),$smarty);
	}
	return $ret;

}

function gs_show_attribute($params, &$smarty) {
	load_file(cfg('tpl_plugins_dir').'/smarty_function_htmlforms_input.php');

	$attr=$params['attribute'];
	$nolabel=0;

	$values=$params['values'];

	$tpl=gs_tpl::get_instance();


	$options=array();
	$multiselect=0;
	if($attr->attributeSelectable) {
		$op=$attr->a2av->get_elements_by_name('values');
		foreach($op as $o) {
			$options[$o->get_id()]=$o->Lang->current()->Text;
		}
		$multiselect=1;
	}
	$structure=array('options'=>$options);

	
	$field_name=sprintf('values:%d:%s',$attr->get_id(), $multiselect ? 'attributeValueID' : 'attributeValue');

	if($attr->attributeMultilang && !$multiselect) {
		$multilang=1;
		$langs=languages::get_htmloptions();
	} else if($attr->attributeEditType=='image') {
		$multilang=0;
		$langs=array(1);
		$value=$params['item']->values[$attr->get_id()]->imageID;
		$field_name=sprintf('values:%d:Image',$attr->get_id());
	} else {
		$multilang=0;
		$langs=array(1);
		$value=$values ? current($values) : '';
	}
	$mret='';
	foreach ($langs as $lang=>$langName) {
		$ret='';
		if ($multilang) {
			$field_name=sprintf('values:%d:%s:%s',$attr->get_id(),$multiselect ? 'attributeValueID' : 'attributeValue' , $lang);
			$value=$values[$lang];
		}
		if ($params['field_name_prefix']) $field_name=$params['field_name_prefix'].$field_name;
		switch ($attr->attributeEditType) {
		case 'input':
			$ret=smarty_function_htmlforms_input($field_name,$value,$params,$options);
			break;
		case 'datetime':
			$ret=smarty_function_htmlforms_datetime($field_name,$value,$params);
			break;
		case 'select':
			$sel_value=array_search($value,$structure['options']);
			$ret=smarty_function_htmlforms_select($field_name,$sel_value,$params,$structure);
			break;
		case 'textarea':
			$ret=smarty_function_htmlforms_textarea($field_name,$value,$params,$structure);
			break;
		case 'image':
			$ret=smarty_function_htmlforms_image($field_name,$value,$params,$structure);
			break;
		case 'checkbox':
			$ret=smarty_function_htmlforms_checkbox($field_name,$value,$params,$structure);
			break;
		case 'link':
			
			$tag_id=$attr->a2av->first()->values->first()->Lang->first()->Text;

			$items=new rs_items;
			$i2i=$params['item']->i2i->find(array('attributeID'=>$attr->get_id()));
			if ($i2i) $items=$i2i->get_elements_by_name('childrens');
			$items->new_record(array());

			$tags=new rs_tags();
			$tags=$tags->find_records(array('tagID'=>$tag_id));
			foreach ($items as $i) {
				$link_prefix="i2i:".$i->get_id().":";
				$ret1='';

				$ret1.=smarty_function_htmlforms_hidden($link_prefix."childrens:t2i:$tag_id:tagID",$tag_id);
				$ret1.=smarty_function_htmlforms_hidden($link_prefix."attributeID",$attr->get_id());

				$ret1.=smarty_function_attrforms(array('item'=>$i,'tags'=>$tags,'field_name_prefix'=>$link_prefix."childrens:"),$smarty);
				$ret.=sprintf("<fieldset><legend>%s</legend>%s</fieldset>\n",$attr->Lang->first()->Text,$ret1);
			}
			$nolabel=1;
			break;
		default:
			return;
		}

		if ($multilang && !$multiselect) $mret.=sprintf("<label class='label_multilang'><span>%s</span> %s</label>\n",$lang,$ret);
			else $mret=$ret;
	}
	return $nolabel ? $ret : sprintf("<label>%s%s</label>\n",$attr->Lang->first()->Text,$mret);
}
?>
