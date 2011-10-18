<?php
class wz_link {}

class wz_link_list extends wz_link {
}

class wz_link_images extends wz_link {

	function lMany2Many($rec) { return; }

	function lOne2One($rec) { return; }

	function lMany2One($rec) { 
		md($rec->get_values(),1);

		$name_rs_images=$rec->Recordset->first().'_images';
		$name_rs_images_files=$name_rs_images.'_files';

		$module=$rec->Recordset->first()->Module->first();
		md($name_rs_images,1);
		
		$wz_rs=new wz_recordsets();

		$rec_images=$wz_rs->find_records(array('name'=>$name_rs_images))->first(true);
		$rec_images_files=$wz_rs->find_records(array('name'=>$name_rs_images_files))->first(true);

		$rec_images->fill_values(array(
			'name'=>$name_rs_images,'title'=>$rec->verbose_name,'Module_id'=>$module->get_id(),
			'extends'=>'tw_images'));

		$rec_images_files->fill_values(array(
			'name'=>$name_rs_images_files,
			'title'=>'Image',
			'Module_id'=>$module->get_id(),
			'extends'=>'tw_file_images',
			));

		$rec_images->Links->new_record(array(
				 'name'=>'Parent',
				 'type'=>'lOne2One',
				 'classname'=>$rec->Recordset->first()->name,
				 'linkname'=>'',
				 'extra_options'=>'mode=link',
				 'fkey_on_delete'=>'CASCADE',
				 'fkey_on_update'=>'CASCADE',
				 ));
		$rec_images->Links->new_record(array(
				 'name'=>'File',
				 'type'=>'lOne2One',
				 'classname'=>$name_rs_images_files,
				 'verbose_name'=>'File',
				 'widget'=>'include_form',
				 'extra_options'=>'hidden=false',
				 'fkey_on_delete'=>'CASCADE',
				 'fkey_on_update'=>'CASCADE',
				 ));

		$arr=array('type'=>'handler','gspgid_value'=>'/admin/form/'.$name_rs_images);
		$url=$module->urls->find_records($arr)->first(true);
		$url->fill_values($arr);
		$url->Handlers->new_record(array(
			'cnt'=>1,
			'handler_keyname'=>0,
			'handler_value'=>'gs_base_handler.post:{name:admin_form.html:classname:'.$name_rs_images.':form_class:g_forms_table:return:gs_record}',
			));
		$url->Handlers->new_record(array(
			'cnt'=>2,
			'handler_keyname'=>0,
			'handler_value'=>'gs_base_handler.redirect',
			));
		//$url->Handlers->delete();

		$rec->fill_values(array(
			'classname'=>$name_rs_images,
			'linkname'=>'Parent',
			));

		$rec_images->commit();
		$rec_images_files->commit();
	}


}

?>