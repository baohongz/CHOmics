<?php
if (!function_exists("get_file_size")) {
    function get_file_size($file_dir)
    {
		if (!file_exists($file_dir)) {
        	return 'The file does\'t exist.';
		}

		$size = shell_exec("du " . $file_dir);
		$info = explode("\t", $size);
		return $info[0];
    }
}


// Return a list of all running processes in server.
if (!function_exists("get_all_running_process")) {
    function get_all_running_process()
    {
		return explode("\n", shell_exec("ps -o pid="));
    }
}




if (!function_exists("run_process_in_order")) {
    function run_process_in_order()
    {
		global $BXAF_CONFIG;
		global $BXAF_MODULE_CONN;

		/** ------
		 *  1. Mark finished processes.
		 *  2. Get all running processes from databases.
		 *  3. Figure out how many processes are running.
		 *  4. If < 4 cores are using, run the next command.
		 *  5. Otherwise, return.
		 */

		// STEP 1
		update_process();

		//STEP 2
		$sql_process_not_started = "SELECT * FROM `".$BXAF_CONFIG['TBL_BXGENOMICS_PROCESS']."` WHERE `bxafStatus`<5 AND `Start_Time`<'2015-01-01 00:00:00' ORDER BY `ID` LIMIT 1000";
		$data_process_not_started = $BXAF_MODULE_CONN->get_all($sql_process_not_started);
		$sql_process_running = "SELECT * FROM `".$BXAF_CONFIG['TBL_BXGENOMICS_PROCESS']."` WHERE `bxafStatus`<5 AND `Start_Time`>'2015-01-01 00:00:00' LIMIT 1000";
		$data_process_running = $BXAF_MODULE_CONN->get_all($sql_process_running);

        // Remember all running analysis id
		$all_running_analysis_array = array();
		foreach($data_process_running as $key => $value){
			if(!in_array($value['Analysis_ID'], $all_running_analysis_array)){
				$all_running_analysis_array[] = $value['Analysis_ID'];
			}
		}



		//STEP3
		$process_running_array = count($data_process_running);
		foreach($data_process_running as $key=>$value){
			$child_process = bxaf_get_child_processes($value['Process_ID']);
			if (is_array($child_process) && isset($child_process['List'])){
				$process_running_array += count($child_process['List']);
			}
		}


		//STEP4. If < 4 cores are using, run the next command.

		if ($process_running_array <= $BXAF_CONFIG['PROCESS_NUMBER_ALLOWED']){

			if (is_array($data_process_not_started) && count($data_process_not_started)>0){
				$next_index = 0;
				for($i = 0; $i < count($data_process_not_started); $i++){
					if(!in_array($data_process_not_started[$i]['Analysis_ID'], $all_running_analysis_array)){
						$next_index = $i;
						break;
					}
				}
				$rowid = $data_process_not_started[$next_index]['ID'];

				// Check whether previous process of the same analysis have finished.
				$sql_next_step = "SELECT `Pipeline_Index`, `Analysis_ID` FROM `".$BXAF_CONFIG['TBL_BXGENOMICS_PROCESS']."` WHERE `ID`='$rowid'";
				$data_next_step = $BXAF_MODULE_CONN->get_row($sql_next_step);
                $next_step_index = intval($data_next_step['Pipeline_Index']);
				$next_step_analysis = $data_next_step['Analysis_ID'];


				if($next_step_index > 0){
					$sql_previous_step = "SELECT `End_Time` FROM `".$BXAF_CONFIG['TBL_BXGENOMICS_PROCESS']."` WHERE `Pipeline_Index`='" . ($next_step_index - 1) . "' AND `Analysis_ID`='".$next_step_analysis."' ORDER BY `Start_Time` DESC";
					$data_previous_step_end_time = $BXAF_MODULE_CONN->get_one($sql_previous_step);
				}

				// If the previous step of the same analysis has finished:
				if($next_step_index <= 0 || $data_previous_step_end_time != '0000-00-00 00:00:00'){

					$uploads_dir = $data_process_not_started[$next_index]['Dir'];
					chdir($uploads_dir);
					$command = $data_process_not_started[$next_index]['Command'];
					$output_file = $data_process_not_started[$next_index]['Output_File'];
					$log_file = $data_process_not_started[$next_index]['Log_File'];
					$command_notes = $data_process_not_started[$next_index]['Notes'];

					passthru('set -e');
					$process_id = bxaf_execute_in_background($command, $output_file, $log_file);

					// Real process id was 1 smaller for some steps.
					if (!in_array($command_notes, $BXAF_CONFIG['RNA_SEQ_WORKFLOW'])){
						$process_id -= 1;
					}

					$info_process = array(
						'Process_ID' => $process_id,
						'Start_Time' => date("Y-m-d H:i:s")
					);

					$BXAF_MODULE_CONN->update($BXAF_CONFIG['TBL_BXGENOMICS_PROCESS'], $info_process, "`ID`='$rowid'");
				}

			}
		}
		return;
    }
}



if (!function_exists("update_process")) {
    function update_process($process_id = "")
    {
		global $BXAF_CONFIG;
		global $BXAF_MODULE_CONN;

		$owner_id = $BXAF_CONFIG['BXAF_USER_CONTACT_ID'];

        $all_process_server = get_all_running_process();

		// Update a Particular Process
		if ($process_id != ""){

			if(in_array($process_id, $all_process_server)){
				return "The process ".$process_id." is running.";
			} else {
				$info = array(
					'End_Time' => date("Y-m-d H:i:s"),
					'bxafStatus' => 9
				);
				$BXAF_MODULE_CONN->update($BXAF_CONFIG['TBL_BXGENOMICS_PROCESS'], $info, "`Process_ID`='$process_id'");
				return "The process ".$process_id." has finished.";
			}

		} else {

			$sql_running_process_database = "SELECT `Process_ID` FROM `".$BXAF_CONFIG['TBL_BXGENOMICS_PROCESS']."` WHERE `bxafStatus`<5 AND `Start_Time` != '0000-00-00 00:00:00'";
			$data_running_process_database = $BXAF_MODULE_CONN->get_col($sql_running_process_database);

			foreach($data_running_process_database as $temp_process_id){

				if(!in_array($temp_process_id, $all_process_server)){

					$info = array(
						'End_Time' => date("Y-m-d H:i:s"),
						'bxafStatus' => 9
					);
					$BXAF_MODULE_CONN->update($BXAF_CONFIG['TBL_BXGENOMICS_PROCESS'], $info, "`Process_ID`='$temp_process_id'");
				}
			}
			return;

		}
    }
}







if (!function_exists("terminate_process")) {
    function terminate_process($process_id = "")
    {
		global $BXAF_CONFIG;
		global $BXAF_MODULE_CONN;

		$owner_id = $BXAF_CONFIG['BXAF_USER_CONTACT_ID'];

		if ($process_id == ""){
			return "Please enter a process ID to continue";
		}


		// Kill Descendent Process First
		$descendent_info = bxaf_get_child_processes($process_id);
		foreach($descendent_info['List'] as $key=>$value){
			if($value == $process_id){
				terminate_process($key);
			}
		}
		bxaf_kill_process($process_id);

		$sql_current_process = "SELECT * FROM `".$BXAF_CONFIG['TBL_BXGENOMICS_PROCESS']."` WHERE `Process_ID`=".$process_id." AND `_Owner_ID`=".$owner_id." AND `bxafStatus`<5";
		$data_current_process = $BXAF_MODULE_CONN->get_row($sql_current_process);

		if (is_array($data_current_process) || count($data_current_process) > 1){

			$files_dir = $data_current_process['Files'];
			if(file_exists($files_dir)) bxaf_delete_all($files_dir);

			$BXAF_MODULE_CONN->delete($BXAF_CONFIG['TBL_BXGENOMICS_DATA'], "`Dir`='" . addcslashes($files_dir). "'");

            $info = array(
				'End_Time' => date("Y-m-d H:i:s"),
				'bxafStatus' => 9
			);
			$BXAF_MODULE_CONN->update($BXAF_CONFIG['TBL_BXGENOMICS_PROCESS'], $info, "`ID` = " . $data_current_process['ID']);
		}

		return "The process ".$process_id." has been terminated.";

    }
}

?>