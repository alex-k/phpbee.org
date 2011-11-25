<?php


if (!class_exists('gs_base_handler',0)) {
	date_default_timezone_set('GMT');
	require_once(dirname(__FILE__).'/../../../../../config.lib.php');
	$cfg=gs_config::get_instance();
	$init=new gs_init('auto');
	$init->init(LOAD_CORE | LOAD_STORAGE | LOAD_TEMPLATES | LOAD_EXTRAS);
}

if(isset($_REQUEST['reset_session'])) {
	gs_session::save(null,'widget_MultiPowUpload');
}
$files=gs_session::load('widget_MultiPowUpload');
if(!is_array($files)) $files=array();

if (isset($_REQUEST['preview_thumb'])) {
	$file=$files[$_REQUEST['preview_thumb']];
	$gd=new vpa_gd($file['Filedata']['tmp_name']);
	$gd->resize(200,200,'use_box');
	$gd->show();

	die();
}
if (isset($_REQUEST['preview'])) {
	$file=$files[$_REQUEST['preview']];
	$gd=new vpa_gd($file['Filedata']['tmp_name']);
	$gd->show();
	die();
}


if(isset($_FILES['Filedata'])) {
	$rs_name=$_REQUEST['recordset'];
	$f_name=$_REQUEST['foreign_field_name'];
	$f_hash_name=$f_name.'_hash';
	

	$f=new $rs_name;
	$f=$f->new_record();

	$f->$f_hash_name=$_REQUEST['hash'];
	$f->$f_name=$_REQUEST['rid'];

	$values=$_FILES['Filedata'];

	$ret=array(
			'File_data'=>file_get_contents($values['tmp_name']),
			'File_filename'=>$values['name'],
			'File_mimetype'=>$values['type'],
			'File_size'=>$values['size'],
			'File_width'=>$_REQUEST['thumbnailWidth'],
			'File_height'=>$_REQUEST['thumbnailHeight'],
		 );
	
	$ff=$f->File->new_record($ret);

	$f->commit();
	
	echo $f->src1('admin');
	die();

	$file['Fileinfo']=$_REQUEST;
	$files[$_REQUEST['fileId']]=$file;
	gs_session::save($files,'widget_MultiPowUpload');
	echo ($_REQUEST['fileId']);
	die();
}


var_dump($files);

die();


$uploaddir = dirname(__FILE__)."/UploadedFiles/";

/*-------------------------------------------------------------------------
* First part of this script is for regular upload method (RFC based) 
*/
if(!isset($_REQUEST['chunkedUpload']))
{
	// In PHP versions earlier than 4.1.0, $HTTP_POST_FILES should be used instead
	// of $_FILES.

	//trying restore browser cookie
	if(isset($_POST['MultiPowUpload_browserCookie']))
	{
		$cookies = split(";", $_POST['MultiPowUpload_browserCookie']);
		foreach($cookies as $value)
		{
			$namevalcookies = split("=", $value);	
			$browsercookie[trim($namevalcookies[0])] =  trim($namevalcookies[1]);
		}
		$_COOKIE = $browsercookie;
	}
	//restore session if possible
	if(isset($browsercookie) && isset($browsercookie['PHPSESSID']))
	{	
		session_id($browsercookie['PHPSESSID']);
		session_start();
	}
	//Flash send file name in UTF-8 encoding. And in most cases you need not any conversion.
	//But php for Windows have bug related to file name encoding in move_uploaded_file function.
	// http://bugs.php.net/bug.php?id=47096

	// If you use file names in national encodings, change the $uploadfile assignment consider
	// encoding conversion by functions 'iconv()' or 'mb_convert_encoding()' as shown below:
	//$target_encoding = "ISO-8859-1";
	// $uploadfile = $uploaddir . mb_convert_encoding(basename($arrfile['name']), $target_encoding , 'UTF-8');
	// $uploadfile = $uploaddir . iconv("UTF-8", $target_encoding,basename($arrfile['name']));
	
	if(count($_FILES) > 0)
	{
		$arrfile = pos($_FILES);
		$uploadfile = $uploaddir . basename($arrfile['name']);

		if (move_uploaded_file($arrfile['tmp_name'], $uploadfile))
		   echo "File " . basename($arrfile['name']) . " was successfully uploaded.";
	}
	echo '<br>'; // At least one symbol should be sent to response!!!
}
/*-------------------------------------------------------------------------
* The second part is for chunked upload method used by silverlight uploader
*/
else
{
	error_reporting(E_ERROR);
	set_error_handler ('err_handler');

	$filename = isset($_GET["FileName"]) ? str_replace("../", "", $_GET["FileName"]) : "";
	$complete = isset($_GET["Complete"]) ? strtolower($_GET["Complete"]) == "true" ? true : false : true;
	$querySize = isset($_GET["QuerySize"]) ? strtolower($_GET["QuerySize"]) == "true" ? true : false : false;
	$startByte = isset($_GET["StartByte"]) ? (int)$_GET["StartByte"] : 0;
	$comment = isset($_GET["Comment"]) ? $_GET["Comment"] : "";
	$tag = isset($_GET["Tag"]) ? $_GET["Tag"] : "";
	$isMultiPart = isset($_GET["isMultiPart"]) ? $_GET["isMultiPart"] == "true" : false;
	$dirPath = $uploaddir;

	$filePath = $dirPath . "/" . $filename;

	// If you use file names in national encodings, change the $filePath assignment consider
	// encoding conversion by functions 'iconv()' or 'mb_convert_encoding()' as shown below:
	// $codepage = "windows-1251";
	// $filePath = $dirPath . "/" . mb_convert_encoding($filename, $codepage , 'UTF-8');
	// $filePath = $dirPath . "/" . iconv("utf-8", $codepage, $filename);

	if ($querySize)
	{
		if (file_exists($dirPath) && is_dir($dirPath))
		{
			if (file_exists($filePath))
			{
				print filesize($filePath);
			}
			else
				print "0";
		}
		else print "The path for file storage not found on the server.";
	}
	else
	{
		//if mulltipart mode and there is no file form field in request , then write error
		if($isMultiPart && count($_FILES) <= 0)
		{
			echo "Error: No chunk for save.";	
			exit;
		}
		if ($startByte > 0 && file_exists($filePath))
		{
			$isCreate = false;
			$file = fopen($filePath, "a");
		}
		else
		{
			$isCreate = true;
			$file = fopen($filePath, "w");
		}
			
		if (!is_writable($filePath))
		{
			print "Error: cannot write to the specified directory.";
			exit;
		}
			
		//logic to read and save chunk posted with multipart
		//Multipart allow us to send form data in request body
		if($isMultiPart)
		{
			$filearr = pos($_FILES);		
			if(!$input = file_get_contents($filearr['tmp_name']))
			{
				echo "Error: Can't read from file.";
				exit;
			}
		}
		//raw data
		else
			$input = file_get_contents("php://input");
		if(!fwrite($file, $input)) 
			echo "Error: Can't write to file.";
		fclose($file);
			
		if ($complete)
		{
			
			echo "File " . basename($filePath) . " was successfully uploaded.<br/>";
			// Place here the code making postprocessing of the uploaded file (moving to other location, database, etc).
		}
		else
		{
			if ($isCreate) print "Creating file..." ;
			else print "Write chunk since byte " . $startByte;
		}
	}

	function err_handler ($errno, $errstr, $errfile, $errline)
	{
		print "Write error: " . $errstr;
	}
}

?> 
