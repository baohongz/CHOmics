<?php

$BXAF_CONFIG_CUSTOM['BXAF_LOGIN_REQUIRED']	= true;
include_once(dirname(dirname(__FILE__)) . "/config.php");





/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//This is a shared folder
$BXAF_CONFIG['BXAF_BXFILES_SHARED'] = true;
$BXAF_CONFIG['BXAF_BXFILES_SUBDIR'] = $BXAF_CONFIG['BXAF_BXFILES_SUBDIR_SHARED'];






$BXAF_BXFILES_PREFIX = $BXAF_CONFIG['BXAF_SYSTEM_URL'] . 'bxfiles_shared/f.php?f=';
if(isset($BXAF_CONFIG['BXAF_BXFILES_SHARED_PREFIX']) && $BXAF_CONFIG['BXAF_BXFILES_SHARED_PREFIX'] != ''){
	$BXAF_BXFILES_PREFIX = $BXAF_CONFIG['BXAF_BXFILES_SHARED_PREFIX'];
}

$BXFILES_READONLY = false;
if(array_key_exists('BXFILES_SHARED_READONLY', $BXAF_CONFIG) && $BXAF_CONFIG['BXFILES_SHARED_READONLY']){
	$BXFILES_READONLY = true;
}
if(isset($_SESSION[$BXAF_CONFIG['BXAF_LOGIN_KEY']]) && $_SESSION[$BXAF_CONFIG['BXAF_LOGIN_KEY']] == 'admin'){
	$BXFILES_READONLY = false;
}
$BXAF_CONFIG['BXAF_PAGE_TITLE'] = "Shared Files";







// The following codes are the same for BxFiles and BxFiles_shared

$BXAF_ENCRYPTION_KEY = $BXAF_CONFIG['BXAF_KEY'];

$BXAF_USER_CONTACT_ID = intval($_SESSION[$BXAF_CONFIG['BXAF_LOGIN_KEY']]);

// $BXAF_CONFIG['BXAF_BXFILES_SUBDIR'] has to be defined.
if(! is_array($BXAF_CONFIG) || ! array_key_exists('BXAF_BXFILES_SUBDIR', $BXAF_CONFIG) || $BXAF_CONFIG['BXAF_BXFILES_SUBDIR'] == ''){
	echo 'Error: The destination folder is not defined.';
	exit();
}


$DESTINATION_SUBFOLDER_SUBDIR = rtrim($BXAF_CONFIG['BXAF_BXFILES_SUBDIR'], '/') . '/';
if(! array_key_exists('BXAF_BXFILES_SHARED', $BXAF_CONFIG) || ! $BXAF_CONFIG['BXAF_BXFILES_SHARED']){
	$DESTINATION_SUBFOLDER_SUBDIR = $DESTINATION_SUBFOLDER_SUBDIR . $BXAF_USER_CONTACT_ID . '_' . bxaf_encrypt($BXAF_USER_CONTACT_ID, $BXAF_ENCRYPTION_KEY) . '/';
}

$DESTINATION_SUBFOLDER_DIR = $BXAF_CONFIG['BXAF_ROOT_DIR'] . $DESTINATION_SUBFOLDER_SUBDIR;

if(! file_exists($DESTINATION_SUBFOLDER_DIR)){
	if (!mkdir($DESTINATION_SUBFOLDER_DIR, 0777, true)) {
		die('Error: Failed to create user folders.');
	}
}
if(!is_dir($DESTINATION_SUBFOLDER_DIR) || !is_writable($DESTINATION_SUBFOLDER_DIR)){
	echo 'Error: The destination folder does not exist, or is not writable.' . $DESTINATION_SUBFOLDER_DIR;
	exit();
}


$BXAF_LOGIN_PAGE = $BXAF_CONFIG['BXAF_LOGIN_PAGE'];
$BXAF_LOGOUT_PAGE = $BXAF_CONFIG['BXAF_LOGOUT_PAGE'];


include_once('functions.php');

?>