<?php
include_once(dirname(__FILE__) . "/config.php");

if(isset($_GET['action']) && $_GET['action'] == 'dirfile_rename'){

	$file_name = file_basename(urldecode($_POST['new_name']));
	$old_name = urldecode($_POST['old_name']);
	$file_dir = bxaf_decrypt(urldecode($_POST['file_dir']), $BXAF_ENCRYPTION_KEY);

	if($file_name == ''){
		echo "Error: Please enter a file name.";
		exit();
	}

	$source = $file_dir . '/' . $old_name;
	$target = $file_dir . '/' . $file_name;


	if(! file_exists($source)) {
		echo "Failed: Source is not found.";
		exit();
	}

	else if(file_exists($target)) {
		echo "Failed: same name is already in the folder.";
		exit();
	}

	else if(rename($source, $target)){
		if(! file_exists($source) && file_exists($target)){
			echo 1;
		}
		else echo "Failed in renaming: unknown errors.";
	}
	else echo "Failed in renaming: unknown errors.";

	exit();
}








if(isset($_GET['action']) && $_GET['action'] == 'dirfile_delete'){

	$file_dir = bxaf_decrypt($_POST['file_dir'], $BXAF_ENCRYPTION_KEY);

	// Delete single record
	if($_POST['file_type'] == 'single'){
		$file_name = urldecode($_POST['file_name']);

		$source = $file_dir . '/' . $file_name;
		if(!file_exists($source)){
			echo "Failed to delete " . htmlentities($source, ENT_QUOTES | ENT_IGNORE, "UTF-8") . ": not found.<BR>";
			exit();
		}
		if(is_dir($source)) {
			bxaf_delete_all($source);
		} else {
			unlink($source);
		}
	}
	// Delete checked records
	else if ($_POST['file_type'] == 'checked') {
		$file_name_list = explode(':', urldecode($_POST['file_name']));
		foreach($file_name_list as $key => $value){
			if(trim($value) != ''){
				$source = $file_dir . '/' . bxaf_decrypt($value, $BXAF_ENCRYPTION_KEY);
				if(! file_exists($source)){
					echo "Failed to delete " . htmlentities($source, ENT_QUOTES | ENT_IGNORE, "UTF-8") . ": not found.<BR>";
					exit();
				}
			}
		}
		foreach($file_name_list as $key => $value){
			if(trim($value) != ''){
				$source = $file_dir . '/' . bxaf_decrypt($value, $BXAF_ENCRYPTION_KEY);
				if(is_dir($source)) {
					bxaf_delete_all($source);
				} else {
					unlink($source);
				}
			}
		}
	}

	exit();
}





if(isset($_GET['action']) && ($_GET['action'] == 'dirfile_copy' || $_GET['action'] == 'dirfile_move')){

	$file_dir = trim(bxaf_decrypt($_POST['file_dir'], $BXAF_ENCRYPTION_KEY));
	$target_folder = trim($_POST['move_to_dir']);
	$file_name_list = explode(':', trim($_POST['file_name']));

	foreach($file_name_list as $key => $value){

		if(trim($value) != ''){
			$source = $file_dir . '/' . bxaf_decrypt($value, $BXAF_ENCRYPTION_KEY);
			$target = $target_folder . '/' . bxaf_decrypt($value, $BXAF_ENCRYPTION_KEY);
			if(!file_exists($source)){
				echo "Failed to " . ($_GET['action'] == 'dirfile_copy' ? "copy" : "move") . " <span class='green'>" . htmlentities($source, ENT_QUOTES | ENT_IGNORE, "UTF-8") . "</span>: not found.<BR>";
				exit();
			}
			if(file_exists($target)){
				echo "Failed to " . ($_GET['action'] == 'dirfile_copy' ? "copy" : "move") . " <span class='green'>" . htmlentities($source, ENT_QUOTES | ENT_IGNORE, "UTF-8") . "</span>: same name is already in the target folder.<BR>";
				exit();
			}
		}
	}



	foreach($file_name_list as $key => $value){
		if(trim($value) != ''){
			$source = $file_dir . '/' . bxaf_decrypt($value, $BXAF_ENCRYPTION_KEY);
			$target = $target_folder . '/' . bxaf_decrypt($value, $BXAF_ENCRYPTION_KEY);
			if($_GET['action'] == 'dirfile_copy') {
				if(is_dir($source)) bxaf_copy_all($source, $target);
				else copy($source, $target);
			}
			else if($_GET['action'] == 'dirfile_move') {
				rename($source, $target);
			}
		}
	}


	exit();
}








if(isset($_GET['action']) && ($_GET['action'] == 'dirfile_duplicate')){
	$file_dir = trim(bxaf_decrypt($_POST['file_dir'], $BXAF_ENCRYPTION_KEY));

	$file_name_list = explode(':', trim($_POST['file_name']));

	foreach($file_name_list as $key => $value){

		if(trim($value) != ''){

			$file_name = bxaf_decrypt($value, $BXAF_ENCRYPTION_KEY);

			$dir_from = $file_dir . '/' . $file_name;
			$dir_to = $file_dir . '/' . get_unique_name($file_name, $file_dir);

			bxaf_copy_all($dir_from, $dir_to);
		}
	}

	exit();
}








if(isset($_GET['action']) && ($_GET['action'] == 'dirfile_download')){

	$file_dir = trim(bxaf_decrypt($_POST['file_dir'], $BXAF_ENCRYPTION_KEY));

	$download_name = 'download_' . date('YmdHis');
	$download_folder_name = get_unique_name($download_name, $file_dir);
	$download_zip_name = get_unique_name($download_folder_name . '.zip', $file_dir);

	$file_name_list = explode(':', urldecode($_POST['file_name']));

	$file_list = array();
	foreach($file_name_list as $key => $value){
		if(trim($value) != ''){
			$file_list[] = bxaf_decrypt($value, $BXAF_ENCRYPTION_KEY);
		}
	}

	$url = '';
	if(count($file_list) == 1 && is_file($file_list[0])){
		$url = 'download.php?f=' . bxaf_encrypt(substr($file_list[0], strlen($DESTINATION_SUBFOLDER_DIR)), $BXAF_ENCRYPTION_KEY) . "&u=$BXAF_USER_CONTACT_ID";
	}
	else {

		if(!file_exists($file_dir . '/' . $download_folder_name)) mkdir($file_dir . '/' . $download_folder_name, 0777, true);

		foreach($file_list as $file_name){

			$dir_from = $file_dir . '/' . $file_name;
			$dir_to = $file_dir . '/' . $download_folder_name . '/' . $file_name;

			bxaf_copy_all($dir_from, $dir_to);

		}
		chdir($file_dir . '/' . $download_folder_name);
		shell_exec ('zip -r -q ' . $download_zip_name . ' *');

		rename($file_dir . '/' . $download_folder_name . '/' . $download_zip_name, $file_dir . '/' . $download_zip_name);
		bxaf_delete_all($file_dir . '/' . $download_folder_name);

		$url = 'download.php?f=' . bxaf_encrypt( substr($file_dir . '/' . $download_zip_name, strlen($DESTINATION_SUBFOLDER_DIR)), $BXAF_ENCRYPTION_KEY) . "&u=$BXAF_USER_CONTACT_ID";
	}


	echo "<p class='text-warning'>Please click the following link to download:</p> <p class='ml-3'><a id='download_zipfile' zipfile_name='$download_zip_name' href='$url'><i class='fas fa-download'></i> $download_zip_name</a></p>";

	exit();
}



if(isset($_GET['action']) && ($_GET['action'] == 'dirfile_download_delete')){

	$file_dir = trim(bxaf_decrypt($_POST['file_dir'], $BXAF_ENCRYPTION_KEY));

	if($_POST['file_name'] != '' && file_exists($file_dir . '/' . $_POST['file_name'])) unlink($file_dir . '/' . $_POST['file_name']);

	exit();
}








if(isset($_GET['action']) && $_GET['action'] == 'create_folder'){

	if(file_basename(urldecode($_POST['folder_name'])) == ''){
		echo "Failed: Please enter a folder name.";
		exit();
	}
	$target = $DESTINATION_SUBFOLDER_DIR . urldecode($_POST['current_dir']) . '/' . file_basename(urldecode($_POST['folder_name']));

	if(file_exists($target)) {
		echo "Failed: same name is already in the folder.";
		exit();
	}
	else {
		mkdir($target);
		if(file_exists($target)){
			echo 1;
		}
		else {
			echo "Failed in creating a new file: unknown error.";
			exit();
		}
	}
	exit();
}




if(isset($_GET['action']) && $_GET['action'] == 'create_file'){

	if(file_basename(urldecode($_POST['file_name'])) == ''){
		echo "Failed: Please enter a file name.";
		exit();
	}

	$target = $DESTINATION_SUBFOLDER_DIR . urldecode($_POST['current_dir']) . '/' . file_basename(urldecode($_POST['file_name']));

	if(file_exists($target)) {
		echo "Failed: same name is already in the folder.";
		exit();
	}
	else {

		file_put_contents($target, urldecode($_POST['file_content']));

		if(file_exists($target)) {
			echo 1;
		} else {
			echo "Failed in creating a new file: unknown error.";
			exit();
		}
	}

	exit();
}






if(isset($_GET['action']) && $_GET['action'] == 'upload_files'){

	$uploads_dir = bxaf_decrypt($_POST['current_dir'], $BXAF_ENCRYPTION_KEY);

	foreach ($_FILES["Files"]["error"] as $key => $error) {
		if ($error == UPLOAD_ERR_OK) {
			$tmp_name = $_FILES["Files"]["tmp_name"][$key];
			$name = $_FILES["Files"]["name"][$key];

			$target = $uploads_dir . '/' . urldecode($name);

			if(file_exists($target)){
				echo "Failed to upload <span class='green'>" . htmlentities(urldecode($name), ENT_QUOTES | ENT_IGNORE, "UTF-8") . "</span>: same name is already in the target folder.";
				exit();
			}
		}
	}


	foreach ($_FILES["Files"]["error"] as $key => $error) {
		if ($error == UPLOAD_ERR_OK) {
			$tmp_name = $_FILES["Files"]["tmp_name"][$key];
			$name = $_FILES["Files"]["name"][$key];
			$target = $uploads_dir . '/' . urldecode($name);
			move_uploaded_file($tmp_name, $target);
		}
	}

	exit();
}








else if(isset($_GET['action']) && $_GET['action'] == 'batch_upload'){
	$ds          = DIRECTORY_SEPARATOR;  //1

	$storeFolder = bxaf_decrypt($_GET['f'], $BXAF_ENCRYPTION_KEY);   //2

	if (!empty($_FILES)) {

		$tempFile = $_FILES['file']['tmp_name'];          //3

		$targetPath = $storeFolder . $ds;  //4

		$targetFile =  $targetPath. urldecode($_FILES['file']['name']);  //5

		move_uploaded_file($tempFile,$targetFile); //6
	}

	exit();
}





else if(isset($_GET['action']) && $_GET['action'] == 'get_txt_file_info'){

	$file_dir = bxaf_decrypt($_POST['file_dir'], $BXAF_ENCRYPTION_KEY);

	$CURRENT_FILE = $file_dir . '/' . trim($_POST['file_name']);
	if(!file_exists($CURRENT_FILE)){
		echo 'Error: File not found.';
		exit();
	}
	else {

		echo '
		<input id="edit_txt_file_full_dir" value="' . bxaf_encrypt($CURRENT_FILE, $BXAF_ENCRYPTION_KEY) . '" hidden>
		<input id="edit_txt_file_name" value="' . trim($_POST['file_name']) . '" hidden>
		<textarea class="form-control" id="edit_txt_file_content" style="height: 300px;">' . file_get_contents($CURRENT_FILE) . '</textarea>';

	}

	exit();
}





else if(isset($_GET['action']) && $_GET['action'] == 'edit_txt_file'){

	$file_dir = bxaf_decrypt($_POST['current_dir'], $BXAF_ENCRYPTION_KEY);

	file_put_contents($file_dir, urldecode($_POST['file_content']));

	if(file_exists($file_dir)) {
		echo 1;
	} else {
		echo "Failed in editing afile: unknown error.";
		exit();
	}

	exit();
}







?>