<?php
if (!function_exists("analysis_status_format")) {
    function analysis_status_format($status)
    {
		if ($status == 'Success'){
			return '
				<span style="color: #009966;">
					<i class="fas fa-check-square"></i> Success
				</span>';
		}
		if ($status == 'Pending'){
			return '
				<span style="color: #DC9811;">
					<i class="fas fa-hourglass-half"></i> Pending
				</span>';
		}
		return;
		if ($status == 'Failed'){
			return '
				<span style="color: red;">
					<i class="fas fa-ban"></i> Failed
				</span>';
		}
		return;
    }
}






if (!function_exists("is_new_user")) {
    function is_new_user()
    {
		global $BXAF_CONFIG;
		global $BXAF_MODULE_CONN;

		// New user or not
		$sql_my_experiments = "SELECT * FROM `".$BXAF_CONFIG['TBL_BXGENOMICS_EXPERIMENT']."` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']}";
		$data_my_experiments = $BXAF_MODULE_CONN->get_all($sql_my_experiments);
		if (!is_array($data_my_experiments) || count($data_my_experiments) == 0){
			return TRUE;
		} else {
			return FALSE;
		}
    }
}






if (!function_exists("check_system_programs_exist")) {
    function check_system_programs_exist()
    {
		global $BXAF_CONFIG;
		global $BXAF_MODULE_CONN;

		foreach($BXAF_CONFIG['PROGRAM_DIR'] as $key => $value){
			if(!file_exists($value)){
				return 'Program "' . $key . '" does not exist.';
			}
		}

		foreach($BXAF_CONFIG['SCRIPT_DIR'] as $key => $value){
			if(!file_exists($value)){
				return 'Script "' . $value . '" does not exist.';
			}
		}

		return 'Ready';

    }
}





if (!function_exists("check_system_writable")) {
    function check_system_writable()
    {
		global $BXAF_CONFIG;
		global $BXAF_MODULE_CONN;

		$dir_parent = dirname(dirname(__FILE__));
		$writable_folders = array(
			// $dir_parent . '/analysis_result',
			// $dir_parent . '/files',
			// $dir_parent . '/server_files_public',
			// $dir_parent . '/server_files_private',
		);

		foreach($writable_folders as $key => $value){
			if(!is_writable($value)){
				return 'Directory ' . $value . ' not writable, please change the permission.';
			}
		}

		return 'Ready';

    }
}




if (!function_exists("format_duration")) {
	function format_duration($duration_in_seconds) {

	  $duration = '';
	  $days = floor($duration_in_seconds / 86400);
	  $duration_in_seconds -= $days * 86400;
	  $hours = floor($duration_in_seconds / 3600);
	  $duration_in_seconds -= $hours * 3600;
	  $minutes = floor($duration_in_seconds / 60);
	  $seconds = $duration_in_seconds - $minutes * 60;

	  if($days > 0) {
		$duration .= $days . ' days';
	  }
	  if($hours > 0) {
		$duration .= ' ' . $hours . ' hours';
	  }
	  if($minutes > 0) {
		$duration .= ' ' . $minutes . ' minutes';
	  }
	  if($seconds > 0) {
		$duration .= ' ' . $seconds . ' seconds';
	  }
	  return $duration;
	}
}




?>