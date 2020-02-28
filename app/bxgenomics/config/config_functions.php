<?php
if (!function_exists('format_size')) {
    function format_size($size)
    {
        $index = 0;
        $units = array(
            "&nbsp;B",
            "KB",
            "MB",
            "GB",
            "TB",
            "PB",
            "EB",
            "ZB",
            "YB"
        );
        while ((($size / 1024) > 1) && ($index < 8)) {
            $size = $size / 1024;
            $index++;
        }
        return sprintf("%01.2f %s", $size, $units[$index]);
    }
}



// Return PID

if (!function_exists('bxaf_execute_in_background')) {
    function bxaf_execute_in_background($Command, $outputfile = '', $logfile = '', $Priority = 19)
    {

        $Priority = intval($Priority);
        if ($Priority <= 0 || $Priority > 19)
            $Priority = 19;
        if ($outputfile == '')
            $outputfile = '/dev/null';
        if ($logfile == '')
            $logfile = '/dev/null';
        return shell_exec("nohup nice -n $Priority $Command 1> $outputfile 2> $logfile & echo $!");
    }
}
if (!function_exists('bxaf_check_process_status')) {
    function bxaf_check_process_status($PID)
    {
        $PID = intval($PID);
        exec("ps $PID", $ProcessState);
        return (count($ProcessState) >= 2);
    }
}
if (!function_exists('bxaf_get_child_processes')) {
    function bxaf_get_child_processes($PID)
    {
        $pstree    = shell_exec("pstree -plA " . intval($PID));
        $pstree    = explode("\n", trim($pstree));
        $processes = array();
        foreach ($pstree as $n => $row) {
            for ($i = 0; $i < strlen($row); $i++) {
                if ($row[$i] == ' ')
                    $pstree[$n][$i] = $pstree[$n - 1][$i];
            }
            $pstree[$n] = preg_replace("/[\|\+\`]/", "-", $pstree[$n]);
            $cols       = explode("---", $pstree[$n]);
            for ($j = 1; $j < count($cols); $j++) {
                $pp = $cols[$j - 1];
                list($pp_name, $ppid) = explode("(", $pp);
                $ppid = str_replace(')', '', $ppid);
                $p    = $cols[$j];
                list($p_name, $pid) = explode("(", $p);
                $pid                       = str_replace(')', '', $pid);
                $processes['Names'][$ppid] = $pp_name;
                $processes['Names'][$pid]  = $p_name;
                $processes['List'][$pid]   = $ppid;
            }
        }
        return $processes;
    }
}
if (!function_exists('bxaf_kill_process')) {
    function bxaf_kill_process($PID)
    {
        $PID = intval($PID);
        passthru("kill -9 $PID", $return);
        return $return;
    }
}
if (!function_exists('bxaf_kill_child_processes')) {
    function bxaf_kill_child_processes($PID)
    {
        $processes = bxaf_get_child_processes(intval($PID));
        $errors    = array();
        foreach ($processes['Names'] as $id => $name) {
            $return = bxaf_kill_process($id);
            if ($return > 0)
                $errors[$id] = $name;
        }
        return $errors;
    }
}
if (!function_exists('bxaf_run_command_in_background')) {
    function bxaf_run_command_in_background($command, $command_input = '', $OUTPUT_FILE = '', $ERROR_FILE = '', $QUIET = '')
    {
        if ($command == '')
            return '';
        $cmd = "$command $QUIET";
        if ($command_input != '' && file_exists($command_input))
            $cmd .= " < $command_input ";
        return bxaf_execute_in_background($cmd, $OUTPUT_FILE, $ERROR_FILE);
    }
}
if (!function_exists('bxaf_run_command_now')) {
    function bxaf_run_command_now($command, $command_input, $QUIET = '')
    {
        $results = array();
        $cmd     = "$command $QUIET";
        if (file_exists($command_input))
            $cmd .= " < $command_input ";
        exec($cmd, $results);
        return $results;
    }
}




///////////////////////////////////////////////////////////////////////
// Function: copy files or directory recursively
// Usage: bxaf_copy_all($from, $to);
// Example: bxaf_copy_all("C:/MyDoc/www/temp2/", "C:/MyDoc/www/temp1/temp3/");
///////////////////////////////////////////////////////////////////////

if(!function_exists('bxaf_copy_all')) {
	function bxaf_copy_all($oldname, $newname, $permissions = 0775){
		if(is_file($oldname) && is_readable($oldname)){
			$perms = fileperms($oldname);
			return copy($oldname, $newname) && chmod($newname, $perms);
		}
		else if(is_dir($oldname) && is_readable($oldname)){ bxaf_copy_folder($oldname, $newname, $permissions); }
		else{ }
	}
}


if(!function_exists('bxaf_copy_folder')) {
	function bxaf_copy_folder($oldname, $newname, $permissions = 0775){
		//Do not copy a folder to a folder inside it!
		if(strpos($newname, $oldname) === 0) return;

		$hasDir = false;
		if(! is_dir($newname)){ if(mkdir($newname, $permissions)) $hasDir = true;  }
		else $hasDir = true;
		$dir = opendir($oldname);
		while($hasDir && $dir && ($file = readdir($dir))){
			if($file == "." || $file == ".."){ continue; }
			bxaf_copy_all("$oldname/$file", "$newname/$file", $permissions);
		}
		closedir($dir);
	}
}


///////////////////////////////////////////////////////////////////////
// Function: delete files or directory recursively
// Usage: bxaf_delete_all($target);
// Example: bxaf_delete_all("C:/MyDoc/www/temp2/");
///////////////////////////////////////////////////////////////////////

if(!function_exists('bxaf_delete_all')) {
	function bxaf_delete_all($file) {
		if (file_exists($file)) {
			chmod($file,0777);
			if (is_dir($file) && is_readable($file)) {
				$handle = opendir($file);
				while($filename = readdir($handle)) {
					if ($filename != "." && $filename != "..") {	bxaf_delete_all($file."/".$filename);	}
				}
				closedir($handle);
				rmdir($file);
			} else { unlink($file);	}
		}
	}
}


if(!function_exists('bxaf_clear_folder')) {
	function bxaf_clear_folder($file) {
		if (file_exists($file)) {
			chmod($file,0777);
			if (is_dir($file) && is_readable($file)) {
				$handle = opendir($file);
				while($filename = readdir($handle)) {
					if ($filename != "." && $filename != "..") {	bxaf_clear_folder($file."/".$filename);	}
				}
				closedir($handle);
			} else { unlink($file);	}
		}
	}
}



if(!function_exists('bxaf_delete_folder')) {
	function bxaf_delete_folder($dir){
	  $current_dir = false;
	  if(is_readable("$dir")) $current_dir = opendir($dir);
	  while($current_dir && $entryname = readdir($current_dir)){
		if($entryname != "." and $entryname!=".."){
		  if(is_dir($dir."/".$entryname) && is_writable($dir."/".$entryname)){  bxaf_delete_folder($dir."/".$entryname);  }
		  else if(is_writable($dir."/".$entryname)){  unlink($dir."/".$entryname);     }
		}
	  }
	  closedir($current_dir);
	  unset($php_errormsg);
	  if(is_writable($dir)) rmdir($dir);
	  if(isset($php_errormsg)){ return 1; } else { return 0; }
	}
}



if(!function_exists('bxaf_list_all')) {
	function bxaf_list_all($rootdir='/', $initdir='', $type='') {
		$fileArray = array();

		$rootdir = str_replace('\\', '/', trim($rootdir));
		if($rootdir == ''){ $rootdir = '/'; }
		else if(substr($rootdir, -1) != '/'){ $rootdir .= '/'; }

		$initdir = str_replace('\\', '/', trim($initdir));
		if($initdir != '' && substr($initdir, -1) != '/'){ $initdir .= '/'; }

		$fulldir = $rootdir . $initdir;

		if($handle = dir($fulldir)){
			while ( false !== ($dirfile = $handle->read() )) {
			  if ( $dirfile!='..' && $dirfile!='.' ) {
				$newfulldirfile = $fulldir . $dirfile;
				if (is_dir($newfulldirfile) && is_readable($newfulldirfile)) {
					if($type == '' || $type == 'dir') $fileArray[] = $newfulldirfile;
					$fileArray = array_merge($fileArray, bxaf_list_all($rootdir, $initdir . $dirfile, $type));
				}
				else if($type == '' || $type == 'file') $fileArray[] = $newfulldirfile;

			  }
			}
			$handle->close();
		}
		return $fileArray;
	}
}


if(!function_exists('bxaf_list_files')) {
	function bxaf_list_files($rootdir='/', $initdir='', $fileArray = array()) {
		return array_merge($fileArray, bxaf_list_all($rootdir, $initdir, ''));
	}
}


if(!function_exists('bxaf_list_files_only')) {
	function bxaf_list_files_only($rootdir = '/', $initdir='', $fileArray = array()) {
		return array_merge($fileArray, bxaf_list_all($rootdir, $initdir, 'file'));
	}

}


if(!function_exists('bxaf_list_folders_only')) {
	function bxaf_list_folders_only($rootdir = '/', $initdir='', $folderArray = array()) {
		return array_merge($folderArray, bxaf_list_all($rootdir, $initdir, 'dir'));
	}
}


if(!function_exists('bxaf_sync_tables')) {
	function bxaf_sync_tables() {
        global $BXAF_CONFIG;
		global $BXAF_MODULE_CONN;

        // Sync platform information
        $sql = "UPDATE `{$BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS']}` AS S, `{$BXAF_CONFIG['TBL_BXGENOMICS_PLATFORMS']}` AS P SET S.`Platform_Type` = P.`Type`, S.`Platform` = P.`GEO_Accession`, S.`PlatformName` = P.`Name`, S.`Species` = P.`Species` WHERE S.`_Platforms_ID` = P.`ID`";
        $BXAF_MODULE_CONN -> execute($sql);
        $sql = "UPDATE `{$BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES']}` AS S, `{$BXAF_CONFIG['TBL_BXGENOMICS_PLATFORMS']}` AS P SET S.`Platform_Type` = P.`Type`, S.`Platform` = P.`GEO_Accession`, S.`PlatformName` = P.`Name`, S.`Species` = P.`Species` WHERE S.`_Platforms_ID` = P.`ID`";
        $BXAF_MODULE_CONN -> execute($sql);
        $sql = "UPDATE `{$BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS']}` AS S, `{$BXAF_CONFIG['TBL_BXGENOMICS_PLATFORMS']}` AS P SET S.`Platform_Type` = P.`Type`, S.`Platform` = P.`GEO_Accession`, S.`PlatformName` = P.`Name`, S.`Species` = P.`Species` WHERE S.`_Platforms_ID` = P.`ID`";
        $BXAF_MODULE_CONN -> execute($sql);

        // Sync Project information
        $sql = "UPDATE `{$BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES']}` AS S,     `{$BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS']}` AS P SET S.`Project_Name` = P.`Name` WHERE S.`_Projects_ID` = P.`ID`";
        $BXAF_MODULE_CONN -> execute($sql);
        $sql = "UPDATE `{$BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS']}` AS S, `{$BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS']}` AS P SET S.`Project_Name` = P.`Name` WHERE S.`_Projects_ID` = P.`ID`";
        $BXAF_MODULE_CONN -> execute($sql);

	}
}


?>