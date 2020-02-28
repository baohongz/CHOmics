<?php

class SingleAnalysis
{

	// Variables
	public $ID;
	public $analysisInfo;
	public $exist;



	// Constructor
	function SingleAnalysis($analysisID) {
		global $BXAF_CONFIG, $BXAF_MODULE_CONN;
		$this -> ID = $analysisID;

		$sql = "SELECT * FROM `".$BXAF_CONFIG['TBL_BXGENOMICS_ANALYSIS']."` WHERE " . $BXAF_CONFIG['QUERY_ACTIVE_FILTER'] . " AND `ID`=" . intval($analysisID);
		$currentAnalysis = $BXAF_MODULE_CONN -> get_row($sql);

		// If not exist
		if(!is_array($currentAnalysis) || count($currentAnalysis)<1){
			$this -> exist = false;
			return;
		}

		$this -> analysisInfo = $currentAnalysis;
		return;
	}





	/**
	 * Show Analysis Status
	 *
	 * @param
	 * @return String Analysis status
	 */

	function showAnalysisStatus(){
		global $BXAF_CONFIG, $BXAF_MODULE_CONN;

		$analysis_id = $this -> ID;

		$sql = "SELECT * FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_ANALYSIS'] . "` WHERE `ID`= ?i";
		$analysis_info = $BXAF_MODULE_CONN->get_row($sql, $analysis_id);
		if (! is_array($analysis_info) || count($analysis_info) == 0){
			return array(''=>'');
		}


		$analysis_id_encrypted = $analysis_id . '_' . bxaf_encrypt($analysis_id, $BXAF_CONFIG['BXAF_KEY']);
		$analysis_dir = $BXAF_CONFIG['ANALYSIS_DIR'] . $analysis_id_encrypted . '/';
		// Last finished step
		$analysis_last_step_finished = -1;
		foreach($BXAF_CONFIG['RNA_SEQ_WORKFLOW'] as $i=>$name){
			if (file_exists($analysis_dir . $BXAF_CONFIG['RNA_SEQ_WORKFLOW_CHECK_FINISHED'][$i] ) ){
				$analysis_last_step_finished = $i;
			}
		}


		// get last process
		$sql = "SELECT * FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_PROCESS'] . "` WHERE `Analysis_ID`= ?i ORDER BY `ID` DESC";
		$process_info = $BXAF_MODULE_CONN->get_row($sql, $analysis_id);

		$analysis_all_steps = array(-1);

		if(is_array($process_info) && count($process_info) > 0){
			if($process_info['Complete_Analysis'] == 'yes'){
				$analysis_all_steps = array_keys($BXAF_CONFIG['RNA_SEQ_WORKFLOW']);
			}
			else if($process_info['Complete_Analysis'] == 'no'){
				$analysis_all_steps = array( $process_info['Pipeline_Index'] );
			}
			else {
				$analysis_all_steps = explode(',', $process_info['Complete_Analysis']);
			}
			if (! is_array($analysis_all_steps) || count($analysis_all_steps) == 0){
				$analysis_all_steps = array(-1);
			}
		}


		// 1. If no process exists, or no result exists, show 'Not Started'.
		if (! is_array($process_info) || count($process_info) == 0 || ($analysis_last_step_finished == -1 && max($analysis_all_steps) == -1 ) ){
			return array('Not Started' => '<span class="text-muted"><i class="fas fa-bed"></i> Not Started</span>');
		}

		// 2. If analysis is pending (in queue to start)
		else if(is_array($process_info) && $process_info['Start_Time'] == '0000-00-00 00:00:00'){
			return array('Pending' => '<span class="text-warning"><i class="fas fa-clock"></i> Pending at step ' . intval($process_info['Pipeline_Index'] + 1) . '</span>');
		}

		// 3. If analysis is running, show which step is running.
		else if(is_array($process_info) && $process_info['Start_Time'] != '0000-00-00 00:00:00' && $process_info['End_Time'] == '0000-00-00 00:00:00'){
			return array('Ongoing' => '<span class="text-warning"><i class="fas fa-clock"></i> Ongoing at step ' . intval($process_info['Pipeline_Index'] + 1) . '</span>');
		}

		// 4. If all steps are finished. Show 'Finished'
		else if ($analysis_last_step_finished != -1 && $analysis_last_step_finished == max($analysis_all_steps)){
			return array('Finished' => '<span  class="text-success"><i class="fas fa-check"></i> Finished</span>');
		}

		// 5. If no step is running or pendding and not finished. Show 'Stopped at step 4/6'
		else if (is_array($process_info) && $process_info['Start_Time'] != '0000-00-00 00:00:00' && $process_info['End_Time'] != '0000-00-00 00:00:00' && $analysis_last_step_finished != max($analysis_all_steps) ){
			return array('Failed' => '<span class="text-danger"><i class="fas fa-close"></i> Failed at step ' . intval($process_info['Pipeline_Index'] + 1) . "</span>");
		}

		return array(''=>'');
	}








	/**
	 * Show Analysis Step Status
	 *
	 * @param
	 * @return String Step status
	 */

	function showAnalysisStepStatus($stepNumber){
		global $BXAF_CONFIG, $BXAF_MODULE_CONN;

		$analysisIdEncrypted = $this -> ID . '_' . bxaf_encrypt($this -> ID, $BXAF_CONFIG['BXAF_KEY']);
		$analysis_dir = $BXAF_CONFIG['ANALYSIS_DIR'] . $analysisIdEncrypted . '/';

		$all_status = array(
			'Finished'=>'<span  class="text-success"><i class="fas fa-check"></i> Finished</span>',
			'Failed'=>'<span class="text-danger"><i class="fas fa-close"></i> Failed</span>',
			'Pending'=>'<span class="text-warning"><i class="fas fa-clock"></i> Pending</span>',
			'Running'=>'<span  class="text-warning"><i class="fas fa-hourglass"></i> Running</span>',
			'Not Started'=>'<span class="text-muted"><i class="fas fa-bed"></i> Not Started</span>'
		);

		// Check Status First
		$sql= "SELECT * FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_PROCESS'] . "` WHERE `Analysis_ID`=" . intval($this -> ID) . " AND `Pipeline_Index`='" . $stepNumber . "' ORDER BY `ID` DESC";
		$data = $BXAF_MODULE_CONN->get_row($sql);

		$status = '';
		// 01. Finished
		if (file_exists($analysis_dir . $BXAF_CONFIG['RNA_SEQ_WORKFLOW_CHECK_FINISHED'][$stepNumber])){
			$status = 'Finished';

			// Check if logfile has "Error"
			$log_file = $analysis_dir . $BXAF_CONFIG['RNA_SEQ_WORKFLOW_LOG'][$stepNumber];
			if (file_exists($log_file)){
				$handle = @fopen($log_file, "r");
				if ($handle) {
				    while (($buffer = fgets($handle, 4096)) !== false) {
				        if(strpos($buffer, 'Error') !== false){ $status = 'Failed'; break; }
				    }
				    fclose($handle);
				}
			}

			// Check Results for each step for failures
			if($stepNumber == 0){ // fastQC
				$dir = $analysis_dir . "fastQC/";
				if(! file_exists($dir)){
					$status = 'Failed';
				}
				else {
					$found_file = false;
					$d = dir($dir);
					while (false !== ($f = $d->read())) {
					   if(is_file($dir . $f) && preg_match("/fastqc\.html$/", $f)){
						   if(filesize($dir . $f) == 0) {
							   $status = 'Failed';
							   break;
						   }
						   else{
							   $found_file = true;
						   }
					   }
					}
					$d->close();
					if(! $found_file) $status = 'Failed';
				}
			}
			else if($stepNumber == 1){ // Aligment with Subread
				$dir = $analysis_dir . "alignment/Sorted_Bam/";
				if(! file_exists($dir)){
					$status = 'Failed';
				}
				else {
					$found_file = false;
					$d = dir($dir);
					while (false !== ($f = $d->read())) {
					   if(is_file($dir . $f) && preg_match("/\.sorted\.bam$/", $f)){
						   if(filesize($dir . $f) == 0) {
							   $status = 'Failed';
							   break;
						   }
						   else{
							   $found_file = true;
						   }
					   }
					}
					$d->close();
					if(! $found_file) $status = 'Failed';
				}
			}
			else if($stepNumber == 2){ // Gene Count and QC
				$dir = $analysis_dir . "alignment/QC/";
				if(! file_exists($dir)){
					$status = 'Failed';
				}
				else {
					$found_file1 = false;
					$found_file2 = false;
					$d = dir($dir);
					while (false !== ($f = $d->read())) {
					   if(is_file($dir . $f) && preg_match("/^Overall_Heatmap\.png$/", $f)){
						   if(filesize($dir . $f) == 0) {
							   $status = 'Failed';
							   break;
						   }
						   else{
							   $found_file1 = true;
						   }
					   }
					   else if(is_file($dir . $f) && preg_match("/^norm_MDSplot\.png$/", $f)){
						   if(filesize($dir . $f) == 0) {
							   $status = 'Failed';
							   break;
						   }
						   else{
							   $found_file2 = true;
						   }
					   }
					}
					$d->close();
					if(! $found_file1 || ! $found_file2) $status = 'Failed';
				}
			}
			else if($stepNumber == 3){ // DEG, GSEA and GO Analysis
				$dir = $analysis_dir . "alignment/DEG/GSEA_Summary/";
				if(! file_exists($dir)){
					$status = 'Failed';
				}
				else {
					$found_file = false;
					$d = dir($dir);
					while (false !== ($f = $d->read())) {
					   if(is_file($dir . $f) && preg_match("/^GSEA_summary\.csv$/", $f)){
						   if(filesize($dir . $f) == 0) {
							   $status = 'Failed';
							   break;
						   }
						   else{
							   $found_file = true;
						   }
					   }
					}
					$d->close();
					if(! $found_file) $status = 'Failed';
				}
			}

		}

		// 02. Pending
		else if($data['Start_Time'] == '0000-00-00 00:00:00'){
			$status = 'Pending';
		}

		// 03. Running
		else if($data['End_Time'] == '0000-00-00 00:00:00' && $data['Start_Time'] != '0000-00-00 00:00:00'){
			$status = 'Running';
		}

		// 04. Not Started
		else {
			$status = 'Not Started';
		}

		// $return = '';
		// if($_SESSION['BXAF_ADVANCED_USER']){
		// 	if(file_exists($BXAF_CONFIG['ANALYSIS_DIR'] . $analysisIdEncrypted . '/' . $BXAF_CONFIG['RNA_SEQ_WORKFLOW_LOG'][$stepNumber])){
		// 		$return .= "<a href='" . $BXAF_CONFIG['ANALYSIS_URL'] . $analysisIdEncrypted . '/' . $BXAF_CONFIG['RNA_SEQ_WORKFLOW_LOG'][$stepNumber] . "' class='mx-2' title='View Execution Log'><i class='fas fa-file-alt'></i> </a> ";
		// 	}
		// }
		// $return .= $all_status[$status];

		return array($status => $all_status[$status]);
	}






	/**
	 * Show Analysis Step Files
	 *
	 * @param
	 * @return String HTML code for file links
	 */

	function showAnalysisStepFiles($stepNumber){
		global $BXAF_CONFIG, $BXAF_MODULE_CONN;

		$sql = "SELECT * FROM `".$BXAF_CONFIG['TBL_BXGENOMICS_EXPERIMENT']."` WHERE `ID`=" . intval($this -> analysisInfo['Experiment_ID']) . " AND `bxafStatus`<5";
		$experiment = $BXAF_MODULE_CONN->get_row($sql);


		$analysisIdEncrypted = $this -> ID . '_' . bxaf_encrypt($this -> ID, $BXAF_CONFIG['BXAF_KEY']);

		// Check Status First
		$sql= "SELECT * FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_PROCESS'] . "` WHERE `Analysis_ID`=" . intval($this -> ID) . " AND `Pipeline_Index`='" . $stepNumber . "' ORDER BY `ID` DESC";
		$data = $BXAF_MODULE_CONN->get_row($sql);


		$return = '';

		// If Finished
		if (file_exists($BXAF_CONFIG['ANALYSIS_DIR'] . $analysisIdEncrypted . '/' . $BXAF_CONFIG['RNA_SEQ_WORKFLOW_CHECK_FINISHED'][$stepNumber])){

			//  Raw Data QC
			if ($stepNumber == 0){
				$return .= '<a href="report_fastqc.php?analysis=' . $analysisIdEncrypted . '" target="_blank"> <i class="fas fa-chart-bar"></i> Report </a>';
			}

			// Alignment with Subread
			else if ($stepNumber == 1){
				$return .= '<a href="report_alignment.php?analysis=' . $analysisIdEncrypted . '" target="_blank"> <i class="fas fa-chart-bar"></i> Report </a>';
			}

			// Gene Counts and QC
			else if ($stepNumber == 2){
				$return .= '<a href="report_qc.php?analysis=' . $analysisIdEncrypted . '" target="_blank"> <i class="fas fa-chart-bar"></i> Report </a>';
			}

			// DEG, GSEA and GO Analysis
			else if ($stepNumber == 3){

				foreach(unserialize($this -> analysisInfo['Comparisons']) as $comparison){

					// Check whether this comparison has been saved
					$sql = "SELECT * FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS']}`
					WHERE `Name`='{$comparison}'
					AND `_Analysis_ID`=" . $this -> ID;

					$saved_comp = $BXAF_MODULE_CONN -> get_row($sql);
					$return .= '<a href="report_comparison.php?analysis=' . $analysisIdEncrypted . '&comp=' . urlencode($comparison) . '" target="_blank"><i class="fas fa-chart-bar"></i> ' . $comparison . '</a>';

					$return .= '<a href="report_deg.php?analysis=' . $analysisIdEncrypted . '&comp=' . urlencode($comparison) . '" target="_blank" class="btn btn-sm btn-outline-success mb-1 mx-2"><i class="fas fa-angle-double-right"></i> GO Enrichment</a>';

					if($_SESSION['BXAF_ADVANCED_USER']){

						$return .= '<a href="tool_pathway/index.php?analysis=' . $analysisIdEncrypted . '&comp=' . urlencode($comparison) . '" target="_blank" class="btn btn-sm btn-outline-success mb-1 mx-2"><i class="fas fa-angle-double-right"></i> Pathway</a>';

						if (!is_array($saved_comp) || count($saved_comp) <= 1) {
							// $return .= '<a href="edit_comparison.php?analysis=' . $analysisIdEncrypted . '&comp=' . urlencode($comparison) . '" target="_blank" class="btn btn-sm btn-outline-success mb-1"><i class="fas fa-save"></i> Save Comparison</a>';
						}
						else {
							$return .= '<a href="tool_search/view.php?type=comparison&id=' . $saved_comp['ID'] . '"><i class="fas fa-angle-double-right" aria-hidden="true"></i> View Detail</a>';
						}
					}

					$return .= '<br />';

				}

				if(trim($BXAF_CONFIG['NECESSARY_FILES'][$this -> analysisInfo['Species']]['gtf']) != ''){
					$return .= '
					<a href="report_gsea.php?analysis=' . $analysisIdEncrypted . '" target="_blank">
						<i class="fas fa-chart-bar"></i> GSEA Analysis Report
					</a><br>';
				}

				if(trim($BXAF_CONFIG['NECESSARY_FILES'][$this -> analysisInfo['Species']]['GO_enrichment_genome']) != ''){
					$return .= '
					<a href="report_deg.php?analysis=' . $analysisIdEncrypted . '" target="_blank">
						<i class="fas fa-chart-bar"></i> GO Enrichment Analysis Report
					</a>';
				}
			}

		}

		return $return;
	}
}

?>