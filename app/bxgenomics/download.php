<?php

//To disable login requirement
$BXAF_CONFIG_CUSTOM['PAGE_LOGIN_REQUIRED']	= false;
include_once(dirname(dirname(__DIR__)) . "/bxaf_lite/config.php");


if (!function_exists('bxaf_readFileChunked')){

	function bxaf_readFileChunked($filename,$retbytes=true) {

		$chunksize = 1*(1024*1024);
		$buffer = '';
		$cnt = 0;

		$handle = fopen($filename, 'rb');
		if ($handle === false) { return false; }

		while (!feof($handle)) {
			$buffer = fread($handle, $chunksize);
			echo $buffer;
			ob_flush();
			flush();
			if ($retbytes) { $cnt += strlen($buffer); }
		}
		$status = fclose($handle);
		if ($retbytes && $status) { return $cnt; }
		return $status;
	}

}


if (isset($_GET["dl"]) && $_GET["dl"] != "") {
	$src = base64_decode(urldecode($_GET['dl']));

	$f_name = '';
	if( is_numeric($src) ) {
		$sql = "SELECT `Directory`, `Stored_Name`, `Name` FROM `" . $BXAF_CONFIG['TBL_BXAF_FILE'] . "` WHERE `ID` = " . intval($src) ;
		$file = $BXAF_MODULE_CONN->GetRow($sql);

		if(is_array($file) && count($file) > 0){
			$src = rtrim($BXAF_CONFIG['BXAF_DIR'], '/') . $file['Directory'] . "/" . $file['Stored_Name'];
			$f_name = $file['Name'];
		}
	}
}
else if (isset($_GET["fileid"]) && $_GET["fileid"] != "") {
	$fileid = bxaf_decrypt($_GET['fileid'], $BXAF_CONFIG['BXAF_KEY']);

	$sql_existing_files = "SELECT `Dir` FROM `".$BXAF_CONFIG['TBL_BXGENOMICS_DATA']."` WHERE `ID`='".$fileid."'";
	$data_existing_files = $BXAF_MODULE_CONN->get_one($sql_existing_files);

	$src = $BXAF_CONFIG['SAMPLE_DIR'] . $data_existing_files['Dir'];

}
else if (isset($_GET["f"]) && $_GET["f"] != "") {
	$src = $BXAF_CONFIG['BXAF_DIR'] . bxaf_decrypt($_GET['f'], $BXAF_CONFIG['BXAF_KEY']);
}


if (file_exists($src) && is_file($src) && is_readable($src)) {

	$download_size = filesize($src);

	if($f_name != '') $filename = $f_name;
	else $filename = preg_replace("/^\d+\_/", '', array_pop(explode('/', $src)) );

	@ob_end_clean();
	@ini_set('zlib.output_compression', 'Off');

	header("Pragma: public");
	header("Expires: 0");
	header('Last-Modified: '.gmdate('D, d M Y H:i:s') . ' GMT');
	header("Cache-Control: private");
	header('Cache-Control: no-store, no-cache, must-revalidate'); // HTTP/1.1
	header('Cache-Control: pre-check=0, post-check=0, max-age=0'); // HTTP/1.1
	header('Content-Transfer-Encoding: none');

	header('Content-Type: application/octetstream'); //This should work for IE & Opera
	header("Content-Type: application/octet-stream"); //This should work for the rest
	header("Content-Disposition: attachment; filename=\"$filename\"");
	header("Accept-Ranges: bytes");
	header("Content-Length: $download_size");

	@bxaf_readFileChunked($src);

}
else echo "Not readable.";

?>