<?
require_once(dirname(__FILE__).'/../libs/config.lib.php');
$init=new gs_init();
$init->init(LOAD_CORE);

//$rec=record_by_id('2','wz_modules');
$rec=new wz_modules();
$rec->find_records(array());


header("Content-type: text/xml");
$x=$rec->xml_export();
$xml=$x->asXML();

//echo($xml);
//die();

$new_rec=reset(recordset_import_xml($xml,true));
$x=$new_rec->xml_export();
$xml=$x->asXML();
echo($xml);

$new_rec->commit();



?>
