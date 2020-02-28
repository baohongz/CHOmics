<?php

if(!function_exists('get_folder_children_list')) {
	function get_folder_children_list($dir, $parent_id=0) {

		$names_excluded = array('.', '..');
		$all_files = dir($dir);
		$all_children_list = array();
		$all_children_list_folders = array();
		$all_children_list_files = array();

		$i = 0;
		while (($file = $all_files->read()) !== false){

			if(is_dir($dir . '/' . $file)){
				$isParent = 'true';
			} else {
				$isParent = 'false';
			}

			if (!in_array($file, $names_excluded)){
				if($isParent == 'true'){
					$all_children_list_folders[] = array(
						'id' => $parent_id . '_' . $i,
						'name' => $file,
						'isParent' => $isParent,
						'path' =>  $dir
					);
					$i++;
				} else {
					$all_children_list_files[] = array(
						'id' => $parent_id . '_' . $i,
						'name' => $file,
						'isParent' => $isParent,
						'path' =>  $dir
					);
					$i++;
				}
			}
		}
		$all_files->close();




		// Sort by inner value 'name'
		$keys = array_map(function($val) { return $val['name']; }, $all_children_list_folders);
		array_multisort($keys, $all_children_list_folders);

		$keys = array_map(function($val) { return $val['name']; }, $all_children_list_files);
		array_multisort($keys, $all_children_list_files);

		$all_children_list = array_merge($all_children_list_folders, $all_children_list_files);


		return $all_children_list;
	}
}



if(!function_exists('files_table_view')) {
	function files_table_view($CURRENT_DIR) {
		global $DESTINATION_SUBFOLDER_DIR, $BXAF_ENCRYPTION_KEY, $BXAF_LOGOUT_PAGE, $BXAF_BXFILES_PREFIX, $BXFILES_READONLY, $BXAF_USER_CONTACT_ID;

		//Make sure $CURRENT_DIR is not ended with '/'
		$CURRENT_DIR = rtrim($CURRENT_DIR, '/');

		// Folder Structure
		$DIR_FOLDERS_ARRAY = explode('/', substr($CURRENT_DIR, strlen( rtrim($DESTINATION_SUBFOLDER_DIR, '/')  ) ));
		$folder_children_list = get_folder_children_list($CURRENT_DIR);

		echo '<div class="w-100 d-flex justify-content-start">';

			echo '<div class="p-2">
					<a href="tree.php"><i class="fas fa-home text-success"></i> Treeview</a>
					<a class="mr-2" href="folder.php?f='.bxaf_encrypt($DESTINATION_SUBFOLDER_DIR, $BXAF_ENCRYPTION_KEY).'"><i class="fas fa-list-ul text-success ml-1"></i> All Files</a>';

				if(is_array($DIR_FOLDERS_ARRAY) && count($DIR_FOLDERS_ARRAY) > 0){
					$dir_temp = rtrim($DESTINATION_SUBFOLDER_DIR, '/');
					foreach($DIR_FOLDERS_ARRAY as $key => $value){
						if(trim($value) != ''){
							$dir_temp .= '/' . $value;
							echo ' <i class="fas fa-caret-right ml-1"></i> <a href="folder.php?f='.bxaf_encrypt($dir_temp, $BXAF_ENCRYPTION_KEY).'"><i class="fas fa-folder-open text-success"></i> ' . $value . '</a>';
						}
					}
				}
			echo '</div>';

			// Top Action Buttons
			if(! $BXFILES_READONLY){
				echo '<div class="ml-auto p-2">
					<button class="btn btn-sm btn-info mr-1" id="create_folder_btn"><i class="fas fa-folder-open"></i> New Folder</button>
					<button class="btn btn-sm btn-warning mr-1" id="create_file_btn"><i class="fas fa-file-alt"></i> New File</button>
					<button class="btn btn-sm btn-success mr-1" id="upload_file_btn"><i class="fas fa-upload"></i> Upload File</button>
					<button class="btn btn-sm btn-primary mr-1" id="batch_upload_btn"><i class="fas fa-copy"></i> Batch Upload</button>
				</div>';
			}

		echo '</div>';


		if(!is_array($folder_children_list) || count($folder_children_list)==0){
			echo '<div class="w-100 p-2"><h3>No files uploaded yet.</h3></div>';
		}
		else {
			echo '<div class="w-100 p-2">
				<table class="table table-sm table-hover dataTable">
					<thead>
						<tr class="">';

						if(! $BXFILES_READONLY) echo '<th class="text-center" style="width: 1rem;"><input type="checkbox" id="select_all_checkbox"></th>';
						else echo '<th></th>';

						echo '
							<th>Name</th>
							<th>Size</th>
							<th>Last Modified</th>
							<th>Type</th>
							<th>External Link (Share among applications)</th>';

						if(! $BXFILES_READONLY) echo '<th>Actions</th>';

						echo '
						</tr>
					</thead>
					<tbody>';

				foreach($folder_children_list as $key => $value){

					$value['path'] = rtrim($value['path'], '/');
					$current_file = $value['path'] . '/' . $value['name'];

					$name_encrytped = bxaf_encrypt($value['name'], $BXAF_ENCRYPTION_KEY);
					$path_encrytped = bxaf_encrypt($value['path'], $BXAF_ENCRYPTION_KEY);

					$current_file_encrytped = bxaf_encrypt(substr($current_file, strlen($DESTINATION_SUBFOLDER_DIR)), $BXAF_ENCRYPTION_KEY);

					$finfo = finfo_open(FILEINFO_MIME);

					$file_url_link = "";
					if(is_dir($current_file)){
						$file_type = 'folder';
						$link = 'folder.php?f=' . bxaf_encrypt($current_file, $BXAF_ENCRYPTION_KEY);
						$icon_option = '<i class="fas fa-folder-open text-success"></i> ';

						$file_url_link = substr($current_file, strlen($DESTINATION_SUBFOLDER_DIR) - 1);
					} else {
						$file_type = 'file';
						$link = 'download.php?f=' . $current_file_encrytped . "&u=$BXAF_USER_CONTACT_ID";
						$icon_option = '<i class="fas fa-file-alt text-warning"></i> ';

						$file_url_link = '<a href="' . $BXAF_BXFILES_PREFIX . $current_file_encrytped . "&u=$BXAF_USER_CONTACT_ID" . '" target="_blank"><i class="fas fa-external-link-alt hidden-xl-up"></i> <span class="hidden-lg-down fix_width_font">' . substr($current_file, strlen($DESTINATION_SUBFOLDER_DIR) - 1) . '</span></a>';
					}

					echo '<tr>';

					if(! $BXFILES_READONLY) echo '<td class="text-center"><input type="checkbox" class="checkbox_row" filename="' . $name_encrytped . '"></td>';
					else echo '<td></td>';

					echo '<td>' . $icon_option . ' <a href="' . $link . '">' . $value['name'] . '</a></td>
						<td>' . format_size(@filesize($current_file)) . '</td>
						<td>' . date("Y-m-d H:i", @filemtime($current_file)) . '</td>
						<td>' . file_ext($current_file) . '</td>';

					echo '<td>' . $file_url_link . '</td>';


						if(! $BXFILES_READONLY){
							echo '<td>
							<a href="javascript:void(0);" class="rename_link text_no_decoration" filename="' . htmlentities($value['name'], ENT_QUOTES | ENT_IGNORE, "UTF-8") . '" file_dir="' . $path_encrytped . '" title="Rename">
								<i class="fas fa-edit text-success"></i>
							</a>
							<a href="javascript:void(0);" class="delete_link text_no_decoration" file_dir="' . $path_encrytped . '" file_type="' . $file_type . '" filename="' . htmlentities($value['name'], ENT_QUOTES | ENT_IGNORE, "UTF-8") . '" title="Delete">
								<i class="fas fa-times text-danger"></i>
							</a>';

							if(substr(finfo_file($finfo, $current_file), 0, 4) == 'text'){
								echo '<a href="javascript:void(0);" class="edit_txt_file_link text_no_decoration" file_dir="' . $path_encrytped . '" file_type="' . $file_type . '" filename="' . htmlentities($value['name'], ENT_QUOTES | ENT_IGNORE, "UTF-8") . '" title="Edit">
									<i class="fas fa-edit text-success"></i>
								</a>';
							}
							echo '</td>';
						}

					echo '</tr>';
				}


			echo '
					</tbody>
				</table></div>';

			if(! $BXFILES_READONLY)
			echo '<div class="w-100 p-2">
				<button action_type="delete_checked" id="delete_checked_btn" class="btn btn-sm btn-danger checked_action_button mr-1" file_dir="' . bxaf_encrypt($value['path'], $BXAF_ENCRYPTION_KEY) . '" disabled><i class="fas fa-times"></i> Delete</button>
				<button action_type="move_checked" id="move_checked_btn" class="btn btn-sm btn-primary checked_action_button mr-1" file_dir="' . bxaf_encrypt($value['path'], $BXAF_ENCRYPTION_KEY) . '" disabled><i class="fas fa-exchange-alt"></i> Move</button>
				<button action_type="copy_checked" id="copy_checked_btn" class="btn btn-sm btn-success checked_action_button mr-1" file_dir="' . bxaf_encrypt($value['path'], $BXAF_ENCRYPTION_KEY) . '" disabled><i class="fas fa-copy"></i> Copy</button>
				<button action_type="duplicate_checked" id="duplicate_checked_btn" class="btn btn-sm btn-warning checked_action_button mr-1" file_dir="' . bxaf_encrypt($value['path'], $BXAF_ENCRYPTION_KEY) . '" disabled><i class="fas fa-copy"></i> Duplicate</button>
				<button action_type="download_checked" id="download_checked_btn" class="btn btn-sm btn-warning checked_action_button mr-1" file_dir="' . bxaf_encrypt($value['path'], $BXAF_ENCRYPTION_KEY) . '" disabled><i class="fas fa-download"></i> Download</button>
				</div>';
		}

		return;
	}
}




if (!function_exists('hasSubDirectory')){
	function hasSubDirectory($fulldir){
		$return = false;
		if(is_readable($fulldir) && $handle = dir($fulldir)){
			while ( false !== ($dirfile = $handle->read() )) {
				if( $dirfile!='..' && $dirfile!='.' && is_dir($fulldir . '/' . $dirfile)) {
					$return = true;
					break;
				}
			}
			$handle->close();
		}
		return $return;
	}
}


if (!function_exists('findFilesInDirectory')){
	function findFilesInDirectory($fulldir, $search){
		$return = false;
		if(is_readable($fulldir) && $handle = dir($fulldir)){
			while ( false !== ($dirfile = $handle->read() )) {
				if( $dirfile!='..' && $dirfile!='.' && ! is_dir($fulldir . '/' . $dirfile)) {
					if(strpos(strtolower($dirfile), strtolower($search)) !== false) $return = true;
					break;
				}
			}
			$handle->close();
		}
		return $return;
	}
}

if (!function_exists('format_size')){
	function format_size($size) {
	  $index = 0;
	  $units = array("&nbsp;B", "KB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB");
	  while((($size/1024) > 1) && ($index < 8)) {
		$size = $size/1024;
		$index++;
	  }
	  return sprintf("%01.2f %s", $size, $units[$index]);
	}
}



if(!function_exists('isZipFile')) {
	function isZipFile($filename) {
		if (!file_exists($filename)) { return false; }
		$file = @fopen($filename, 'rb');
		if (!$file) { return ''; }
		$test = fread($file, 3);
		fclose($file);
		if ($test[0] == chr(80) && $test[1] == chr(75) && $test[2] == chr(03))
			return true;
		else
			return false;
	}
}


if(!function_exists('format_perms')) {
	function format_perms($mode) {
	  $perms  = ($mode & 00400) ? "r" : "-";
	  $perms .= ($mode & 00200) ? "w" : "-";
	  $perms .= ($mode & 00100) ? "x" : "-";
	  $perms .= ($mode & 00040) ? "r" : "-";
	  $perms .= ($mode & 00020) ? "w" : "-";
	  $perms .= ($mode & 00010) ? "x" : "-";
	  $perms .= ($mode & 00004) ? "r" : "-";
	  $perms .= ($mode & 00002) ? "w" : "-";
	  $perms .= ($mode & 00001) ? "x" : "-";
	  return $perms;
	}
}

//This function only works in Linux
if(!function_exists('getdirsize')) {
	function getdirsize($path, $opt = 'hs'){
		$result=explode("\t",exec("du -$opt \"".$path . "\""),2);
		return ($result[1]==$path ? $result[0] : '');
	}
}

if(!function_exists('dir_size')) {
	function dir_size($path){

		if(!is_dir($path)) return filesize($path);

		$size = getdirsize($path, 'b');
		if($size == ''){
			if ($handle = opendir($path)) {
				$size = 0;
				while (false !== ($file = readdir($handle)))
					if($file!='.' && $file!='..') $size += dir_size($path.'/'.$file);

				closedir($handle);
			}
		}

		return $size;
	}
}


if(!function_exists('file_ext')) {
	function file_ext($filename) {
		if(is_dir($filename)){
			return 'Directory';
		} else {
			$tmp = explode(".", $filename);
			return (count($tmp) > 1) ? strtoupper($tmp[count($tmp) - 1]) : "";
		}
	}
}

if (!function_exists('format_size')){
	function format_size($size) {
	  $index = 0;
	  $units = array("&nbsp;B", "KB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB");
	  while((($size/1024) > 1) && ($index < 8)) {
		$size = $size/1024;
		$index++;
	  }
	  return sprintf("%01.2f %s", $size, $units[$index]);
	}
}


///////////////////////////////////////////////////////////////////////
// Function: delete files or directory recursively
// Usage: bxaf_delete_all($target);
// Example: bxaf_delete_all("C:/MyDoc/www/temp2/");
///////////////////////////////////////////////////////////////////////
if (!function_exists('bxaf_delete_all')){
	function bxaf_delete_all($file) {
		if (file_exists($file)) {
			chmod($file,0777);
			if (is_dir($file) && is_readable($file)) {
				$handle = opendir($file);
				while($filename = readdir($handle)) {
					if ($filename != "." && $filename != "..") bxaf_delete_all($file."/".$filename);
				}
				closedir($handle);
				return rmdir($file);
			} else { return unlink($file);	}
		}
	}
}

///////////////////////////////////////////////////////////////////////
// Function: copy files or directory recursively
// Usage: bxaf_copy_all($from, $to);
// Example: bxaf_copy_all("C:/MyDoc/www/temp2/", "C:/MyDoc/www/temp1/temp3/");
///////////////////////////////////////////////////////////////////////
if (!function_exists('bxaf_copy_all')){
	function bxaf_copy_all($oldname, $newname){
		if(is_file($oldname) && is_readable($oldname)){
			$perms = fileperms($oldname);
			return copy($oldname, $newname) && chmod($newname, $perms);
		}
		else if(is_dir($oldname) && is_readable($oldname)){ return bxaf_copy_folder($oldname, $newname); }
		else return false;
	}
}
	///////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////

if (!function_exists('bxaf_copy_folder')){
	function bxaf_copy_folder($oldname, $newname){
		if(! file_exists($oldname) || ! is_dir($oldname)) return false;
		if(file_exists($newname) && ! is_dir($newname)) return false;

		//Do not copy a folder to a folder inside it!
		if(strpos(dirname(rtrim($newname, '/')), rtrim($oldname, '/') ) === 0) return false;

		$return = true;
		$hasDir = false;
		if(! is_dir($newname)){ if(mkdir($newname)) $hasDir = true;  }
		else $hasDir = true;
		$dir = opendir($oldname);
		while($hasDir && $dir && ($file = readdir($dir))){
			if($file == "." || $file == ".."){ continue; }
			$return = $return && bxaf_copy_all("$oldname/$file", "$newname/$file");
		}
		closedir($dir);

		return $return;
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



if(!function_exists('file_basename')){
	function file_basename($file) {
		$newfile= rtrim(array_shift(explode('?', $file) ), '/');
		return array_pop(explode('/', $newfile));
	}
}






if(!function_exists('get_unique_name')){
	function get_unique_name($display_name, $fulldir = ''){
		global $DESTINATION_SUBFOLDER_DIR;

		$display_name = preg_replace('/([|*?\\\:"<\/>])/','', $display_name);

		if($fulldir == '')  $fulldir = $DESTINATION_SUBFOLDER_DIR;
		if(! file_exists($fulldir) || ! is_dir($fulldir)) return $display_name;

		$names = array();
		if(is_readable($fulldir) && $handle = dir($fulldir)){
			while ( false !== ($dirfile = $handle->read() )) {
				if( $dirfile != '..' && $dirfile != '.') {
					$names[$dirfile] = 1;
				}
			}
			$handle->close();
		}

		if(! is_array($names)  || count($names) <= 0 || ! array_key_exists($display_name, $names)){
			return $display_name;
		}


		if(file_exists(rtrim($fulldir, '/') . '/' . $display_name) && is_dir(rtrim($fulldir, '/') . '/' . $display_name) ){
			$name = $display_name;
			$ext = '';
		}
		else {
			$array = explode('.', $display_name);
			$ext = array_pop( $array );
			$name = implode('', $array);
		}

		$matches = array();
		preg_match("/^(.*)\s\(\d+\)$/i", $name, $matches);

		if(is_array($matches) && count($matches) > 0){
			$name = $matches[1];
		}

		$n = 0;
		do{
			$n++;
			$display_name = $name . " ($n)" . ($ext != '' ? ".$ext" : "");
		}while( array_key_exists($display_name, $names) );

		return $display_name;
	}
}


?>