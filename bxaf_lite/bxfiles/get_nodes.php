<?php
include_once(dirname(__FILE__) . "/config.php");

$pId = "0";
$pName = "";
$pLevel = "";
$pCheck = "";
$pPath= $DESTINATION_SUBFOLDER_DIR;
if(array_key_exists( 'id',$_REQUEST)) {
	$pId=$_REQUEST['id'];
}
if(array_key_exists( 'lv',$_REQUEST)) {
	$pLevel=$_REQUEST['lv'];
}
if(array_key_exists('n',$_REQUEST)) {
	$pName=$_REQUEST['n'];
}
if(array_key_exists('chk',$_REQUEST)) {
	$pCheck=$_REQUEST['chk'];
}
if(array_key_exists('path',$_REQUEST)) {
	$pPath=$_REQUEST['path'];
}

if ($pId==null || $pId=="") $pId = "0";
if ($pLevel==null || $pLevel=="") $pLevel = "0";
if ($pName==null) $pName = "";
$pId = htmlspecialchars($pId);
$pName = htmlspecialchars($pName);

// Remove slash
if(substr($pPath, strlen($pPath) - 1) == '/'){
	$pPath = substr($pPath, 0, strlen($pPath) - 1);
}
if(substr($pName, 0) == '/'){
	$pName = substr($pName, 1);
}
if(substr($pName, strlen($pName) - 1) == '/'){
	$pName = substr($pName, 0, strlen($pName) - 1);
}





//Make sure there is no ending '/'
$dir = rtrim(rtrim($pPath, '/') . '/' . $pName, '/');
$subdir = substr($dir, strlen($DESTINATION_SUBFOLDER_DIR));

$treeNodes_list = get_folder_children_list($dir, $pId);
$treeNodes_list_array = array();

foreach($treeNodes_list as $treeNodes){

	$treeNodes['path'] = rtrim($treeNodes['path'], '/');

	// Select folder only, with checkbox implemented
	if(isset($_GET['type']) && $_GET['type'] == 'checkbox_folder'){
		if(is_dir($dir . '/' . $treeNodes['name'])){
			$treeNodes_list_array[] = "{ id:'" . $treeNodes['id'] . "', name:'" . $treeNodes['name'] . "', isParent:" . $treeNodes['isParent'] . ", \"path\": '" . $treeNodes['path'] . "'}";
		}
	}

	// Select all folders, without checkbox and files
	else if(isset($_GET['type']) && $_GET['type'] == 'folder'){
		if((! isset($_GET['type']) || $_GET['type'] != 'file') && is_dir($dir . '/' . $treeNodes['name'])){
			$url = 'folder.php?f=' . bxaf_encrypt($dir . '/' . $treeNodes['name'], $BXAF_ENCRYPTION_KEY);

			$treeNodes_list_array[] = "{ id:'" . $treeNodes['id'] . "', name:'" . $treeNodes['name'] . "', isParent:" . $treeNodes['isParent'] . ", target: '_self',  url:'" . $url . "', \"path\": '" . $treeNodes['path'] . "'}";
		}

	}

	// Select all files and folders, without checkbox
	else {
		if(is_dir($dir . '/' . $treeNodes['name'])){
			$url = 'folder.php?f=' . bxaf_encrypt($dir . '/' . $treeNodes['name'], $BXAF_ENCRYPTION_KEY);
		} else {
			$url = 'download.php?f=' . bxaf_encrypt($subdir . '/' . $treeNodes['name'], $BXAF_ENCRYPTION_KEY) . "&u=$BXAF_USER_CONTACT_ID";
		}

		$treeNodes_list_array[] = "{ id:'" . $treeNodes['id'] . "', name:'" . $treeNodes['name'] . "', isParent:" . $treeNodes['isParent'] . ", target: '_self',  url:'" . $url . "', \"path\": '" . $treeNodes['path'] . "'}";
	}
}

$test = implode(',', $treeNodes_list_array);

echo '[' . $test . ']';


?>