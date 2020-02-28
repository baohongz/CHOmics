<?php

$BXAF_CONFIG_CUSTOM['PAGE_LOGIN_REQUIRED']	= false;

include_once(dirname(__FILE__) . "/config.php");


if(!function_exists('file_basename')){
	function file_basename($file) {
		$newfile= rtrim(array_shift(explode('?', $file) ), '/');
		return array_pop(explode('/', $newfile));
	}
}

if (!function_exists('readfile_chunked')){
	function readfile_chunked($filename,$retbytes=true) {
		$chunksize = 1*(1024*1024); // how many bytes per chunk
		$buffer = '';
		$cnt =0;

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





$BXAF_ENCRYPTION_KEY = $BXAF_CONFIG['BXAF_KEY'];
$BXAF_USER_CONTACT_ID = intval($_GET['u']);
$DESTINATION_SUBFOLDER_DIR = $BXAF_CONFIG['BXAF_ROOT_DIR'] . rtrim($BXAF_CONFIG['BXAF_BXFILES_SUBDIR'], '/') . '/';
if(! array_key_exists('BXAF_BXFILES_SHARED', $BXAF_CONFIG) || ! $BXAF_CONFIG['BXAF_BXFILES_SHARED']){
	$DESTINATION_SUBFOLDER_DIR = $DESTINATION_SUBFOLDER_DIR . $BXAF_USER_CONTACT_ID . '_' . bxaf_encrypt($BXAF_USER_CONTACT_ID, $BXAF_ENCRYPTION_KEY) . '/';
}

$src = bxaf_decrypt($_GET['f'], $BXAF_ENCRYPTION_KEY);
if(file_exists($DESTINATION_SUBFOLDER_DIR . $src)) $src = $DESTINATION_SUBFOLDER_DIR . $src;

if (file_exists($src) && is_file($src) && is_readable($src)) {

	$download_size = filesize($src);
	$filename = file_basename( $src );

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

	@readfile_chunked($src);

}
//	else echo "File not found.";

?>