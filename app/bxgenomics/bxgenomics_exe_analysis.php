<?php
include_once('config/config.php');



/**
 * Select Comparison For DEG
 */

if(isset($_GET['action']) && $_GET['action'] == 'DEG_select_comparison'){

	// echo "_POST<pre>" . print_r($_POST, true) . "</pre>";

	$analysis_id = $_POST['analysis_id'];

	$sql_analysis = "SELECT `Samples` FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_ANALYSIS'] . "` WHERE `ID`=" . intval($analysis_id) . "";
	$analysis_samples = $BXAF_MODULE_CONN -> get_one($sql_analysis);
	$samples_array = explode(",", $analysis_samples);

	// If samples are selected
	if($_GET['type'] == 'selected_sample' && isset($_POST['sample_list']) && is_array($_POST['sample_list']) && count($_POST['sample_list']) > 0){
		$samples_array = $_POST['sample_list'];
	}

	$sql_analysis = "SELECT `Comparisons` FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_ANALYSIS'] . "` WHERE `ID`=" . intval($analysis_id) . "";
	$analysis_comparisons = $BXAF_MODULE_CONN -> get_one($sql_analysis);
	$comparison_list = unserialize($analysis_comparisons);

	$treatment_name_array = array();
	$treatment_name_count = array();
	foreach($samples_array as $sample_id){
		$sql_sample = "SELECT `Treatment_Name` FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_SAMPLE'] . "` WHERE `ID`=" . intval($sample_id) . " ";
		$sample_treatment_name = $BXAF_MODULE_CONN -> get_one($sql_sample);

		if(! array_key_exists($sample_treatment_name, $treatment_name_count)){
			$treatment_name_count[$sample_treatment_name] = 1;
		}
		else {
			if(! in_array($sample_treatment_name, $treatment_name_array)){
				$treatment_name_array[] = $sample_treatment_name;
			}
		}
	}
	natsort($treatment_name_array);
	$treatment_name_array = array_values($treatment_name_array);

	if($_GET['sub'] == 'new'){

		if(is_array($comparison_list) && count($comparison_list) > 0){

			echo '<div class="py-1 form-inline align-middle">';
				echo '<input class="mx-2" type="checkbox" name="rerun_comparisons" value="1"> <label class="form-check-label" for="">Re-run previous comparisons</label>';
			echo '</div>';

			foreach($comparison_list as $comparison){

				list($c1, $c2) = explode('.vs.', $comparison);

				echo '<div class="py-1 form-inline align-middle">';
					echo '<select style="width: 20rem;" class="custom-select form-control-sm comparison_1" name="comparison_1[]">';
					foreach($treatment_name_array as $i=>$treatment){
						echo '<option value="' . $treatment . '" ' . ($treatment == $c1 ? 'selected' : '') . '>'.$treatment.'</option>';
					}
					echo '</select>';

					echo '<span class="mx-3 text-center" style="padding-top: 0.5rem;"> vs </span>';

					echo '<select style="width: 20rem;" class="custom-select form-control-sm comparison_2" name="comparison_2[]">';
					foreach($treatment_name_array as $i=>$treatment){
						echo '<option value="' . $treatment . '" ' . ($treatment == $c2 ? 'selected' : '') . '>'.$treatment.'</option>';
					}
					echo '</select>';
				echo '</div>';

			}
		}
		else {
			echo '<div class="py-1 form-inline align-middle">';
				echo '<select style="width: 20rem;" class="custom-select form-control-sm comparison_1" name="comparison_1[]">';
				foreach($treatment_name_array as $i=>$treatment){
					echo '<option value="' . $treatment . '" ' . ($i == 1 ? 'selected' : '') . '>'.$treatment.'</option>';
				}
				echo '</select>';

				echo '<span class="mx-3 text-center" style="padding-top: 0.5rem;"> vs </span>';

				echo '<select style="width: 20rem;" class="custom-select form-control-sm comparison_2" name="comparison_2[]">';
				foreach($treatment_name_array as $i=>$treatment){
					echo '<option value="' . $treatment . '" ' . ($i == 0 ? 'selected' : '') . '>'.$treatment.'</option>';
				}
				echo '</select>';
			echo '</div>';
		}

	}

	// Add comparison row
	else if($_GET['sub'] == 'add'){

		echo '<div class="py-1 form-inline align-middle">';
			echo '<select style="width: 20rem;" class="custom-select form-control-sm comparison_1" name="comparison_1[]">';
			foreach($treatment_name_array as $i=>$treatment){
				echo '<option value="' . $treatment . '" ' . ($i == 1 ? 'selected' : '') . '>'.$treatment.'</option>';
			}
			echo '</select>';

			echo '<span class="mx-3 text-center" style="padding-top: 0.5rem;"> vs </span>';

			echo '<select style="width: 20rem;" class="custom-select form-control-sm comparison_2" name="comparison_2[]">';
			foreach($treatment_name_array as $i=>$treatment){
				echo '<option value="' . $treatment . '" ' . ($i == 0 ? 'selected' : '') . '>'.$treatment.'</option>';
			}
			echo '</select>';
			echo "<a title='Remove this comparison' onClick=\"$(this).parent().addClass('hidden'); $(this).parent().html(''); \" href='Javascript: void(0);' class='text-danger ml-2' style='width: 2rem; padding-top: 0.5rem;'><i class='fas fa-times'></i></a>";
		echo '</div>';

	}

	exit();
}




/**
 * Start Running Analysis
 */

else if(isset($_GET['action']) && $_GET['action'] == 'start_analysis'){

	// echo "_POST<pre>" . print_r($_POST, true) . "</pre>"; exit();

	// Array
	// (
	//     [sample_list_saved_for_DEG] => 5939,5940,5941,5942,5943,5944,5945,5946
	//     [steps] => Array
	//         (
	//             [0] => 3
	//         )
	//
	//     [TMM] => T
	//     [minimum_gene_number] => 15
	//     [maximum_gene_number] => 1000
	//     [comparison_1] => Array
	//         (
	//             [0] => Drug.A
	//             [1] => Drug.B
	//         )
	//
	//     [comparison_2] => Array
	//         (
	//             [0] => Control
	//             [1] => Control
	//         )
	//
	//     [analysis_id] => 39
	// )



	$analysis_id = intval($_POST['analysis_id']);


	$analysis_id_encrypted = $analysis_id . '_' . bxaf_encrypt($analysis_id, $BXAF_CONFIG['BXAF_KEY']);
	$analysis_dir = $BXAF_CONFIG['ANALYSIS_DIR'] . $analysis_id_encrypted;

	$dirs = array(
		$analysis_dir . '/',
		$analysis_dir . '/raw_data/',
		$analysis_dir . '/fastQC/',
		$analysis_dir . '/alignment/',
		$analysis_dir . '/alignment/Sorted_Bam/',
		$analysis_dir . '/alignment/QC/',
		$analysis_dir . '/alignment/DEG/',
		$analysis_dir . '/alignment/DEG/GSEA_Summary/',
		$analysis_dir . '/alignment/DEG/GO_Summary/',
	);
	foreach($dirs as $dir){
		if(! file_exists($dir)) mkdir($dir, 0777, true);
	}



	$class_analysis  = new SingleAnalysis($analysis_id);
	$analysis_status = array_pop( array_keys($class_analysis -> showAnalysisStatus() ) );

	$analysis_status_all = array();
	foreach($BXAF_CONFIG['RNA_SEQ_WORKFLOW'] as $i=>$name){
		$s = $class_analysis -> showAnalysisStepStatus($i);
		$analysis_status_all[$i] = key( $s );
	}



	$datetime = date("Y-m-d H:i:s");

	$sql = "SELECT * FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_ANALYSIS'] . "` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `ID`= ?i";
	$analysis_info = $BXAF_MODULE_CONN -> get_row($sql, $analysis_id);

	// Update experiment 'Last_Updated'
	$info_update_experiment = array('Last_Updated' => $datetime);
	$BXAF_MODULE_CONN -> update($BXAF_CONFIG['TBL_BXGENOMICS_EXPERIMENT'], $info_update_experiment, "`ID`=" . intval($analysis_info['Experiment_ID']));


	$analysis_data_type = $analysis_info['Data_Type'];
	$analysis_species = $analysis_info['Species'];
	$comparison_list = unserialize($analysis_info['Comparisons']); if(! is_array($comparison_list)) $comparison_list = array();
	$detail_array = unserialize($analysis_info['Step_Detail']); if(! is_array($detail_array)) $detail_array = array();
	$samples_array = explode(",",$analysis_info['Samples']); if(! is_array($samples_array)) $samples_array = array();
	$analysis_data_array = explode(",",$analysis_info['Data']); if(! is_array($analysis_data_array)) $analysis_data_array = array();



	$all_steps = $_POST['steps'];
	$comparison_list_new = array();
	if(in_array(3, $all_steps)){
		for ($i=0; $i<count($_POST['comparison_1']); $i++){
			if($_POST['comparison_1'][$i] == $_POST['comparison_2'][$i]) {
				echo 'Error: Comparison can not be performed for a treatment against itself. Please update your comparisons.';
				exit();
			}
		}
		for ($i=0; $i<count($_POST['comparison_1']); $i++){
			$c = $_POST['comparison_1'][$i] . '.vs.' . $_POST['comparison_2'][$i];
			if(! in_array($c, $comparison_list_new) ) {
				if(isset($_POST['rerun_comparisons']) && $_POST['rerun_comparisons'] == 1){
					$comparison_list_new[] = $c;
				}
				else if(! in_array($c, $comparison_list)){
					$comparison_list_new[] = $c;
				}
			}
		}
		if( count($comparison_list_new) <= 0) {
			echo 'Error: No new comparisons are set.';
			exit();
		}
	}


	// 4 Useful Arrays
	$treatment_name_array = array();         // Of treatment names
	$data_info_array = array();              // Of complete data file info
	$samples_name_array = array();           // Of sample names

	$sql = "SELECT * FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_SAMPLE'] . "` WHERE `ID` IN (" . $analysis_info['Samples'] . ") AND `bxafStatus`<5";
	$sample_info = $BXAF_MODULE_CONN -> get_all($sql);
	$sql = "SELECT * FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_DATA'] . "` WHERE `Sample_ID` IN (" . $analysis_info['Samples'] . ") AND `bxafStatus`<5";
	$data_info = $BXAF_MODULE_CONN -> get_all($sql);

	// $csv_array = array('Name, Treatment, Read Type, Description');
	foreach($sample_info as $sample){

		$samples_name_array[$sample['ID']] = $sample['Name'];

		// $csv_array[] = array($sample['Name'], $sample['Treatment_Name'], $sample['Data_Type'], preg_replace('/\s+/S', " ", $sample['Description']));

		foreach($data_info as $data){
			if($data['Sample_ID'] == $sample['ID']){
				if(in_array($data['ID'], $analysis_data_array)){
					if(!in_array($sample['Treatment_Name'], $treatment_name_array)){
						$treatment_name_array[] = $sample['Treatment_Name'];
					}
					$data_info_temp = array(
						'Treatment_Name' => $sample['Treatment_Name'],
						'Name' => $data['Name'],
						'Dir' => $data['Dir'],
						'Read_Number' => $data['Read_Number'],
						'Reads_Type' => $data['Reads_Type'],
						'Sample_Name' => $sample['Name'],
						'Sample_ID' => $sample['ID']
					);
					$data_info_array[] = $data_info_temp;
				}
			}
		}

	}


	if($analysis_data_type == 'PE' || $analysis_data_type == 'SE'){
		$min_step = min($all_steps);
		for($i = 0; $i < $min_step; $i++) {
			if( $analysis_status_all[$i] != 'Finished') {
				echo 'Error: You can not start an analysis while previous steps were not finished successfully.';
				exit();
			}
		}
		// Copy .fastq.gz files to correct folder
		foreach($data_info_array as $info){
			if(!file_exists($analysis_dir . '/raw_data/' . $info['Name'])) {
				$command = 'ln -s ' . $info['Dir'] . ' ' . $analysis_dir . '/raw_data/' . $info['Name'];
				shell_exec($command);
			}
		}
	}
	else if($analysis_data_type == 'bam'){
		$min_step = min($all_steps);
		for($i = 2; $i < $min_step; $i++) {
			if( $analysis_status_all[$i] != 'Finished') {
				echo 'Error: You can not start an analysis while previous steps were not finished successfully.';
				exit();
			}
		}
		// Copy .bam files to correct folder
		foreach($data_info_array as $info){
			if(!file_exists($analysis_dir . '/alignment/Sorted_Bam/' . $info['Sample_Name'] . '.sorted.bam' ) ) {
				$command = 'ln -s ' . $info['Dir'] . ' ' . $analysis_dir . '/alignment/Sorted_Bam/' . $info['Sample_Name'] . '.sorted.bam';
				shell_exec($command);
			}
		}
	}
	else if($analysis_data_type == 'gene_counts'){
		// Copy .txt files to correct folder
		foreach($data_info_array as $info){
			if(!file_exists($analysis_dir . '/alignment/gene_counts.txt')) {
				$command = 'ln -s ' . $info['Dir'] . ' ' . $analysis_dir . '/alignment/gene_counts.txt';
				shell_exec($command);
			}
		}
	}




	/**
	 * Step 0. fastQC
	 */
	$step_number = 0;
	if(in_array($step_number, $all_steps)){

		chdir($analysis_dir . '/');

		// Delete result
		$last_step = array_pop(array_keys($BXAF_CONFIG['RNA_SEQ_WORKFLOW']));
		for($i = $step_number; $i<= $last_step; $i++){
			if(file_exists($BXAF_CONFIG['RNA_SEQ_WORKFLOW_LOG'][$i])) shell_exec("rm " . $BXAF_CONFIG['RNA_SEQ_WORKFLOW_LOG'][$i]);
			if(file_exists($BXAF_CONFIG['RNA_SEQ_WORKFLOW_SCRIPT'][$i])) shell_exec("rm " . $BXAF_CONFIG['RNA_SEQ_WORKFLOW_SCRIPT'][$i]);
			if(file_exists($BXAF_CONFIG['RNA_SEQ_WORKFLOW_CHECK_FINISHED'][$i])) shell_exec("rm " . $BXAF_CONFIG['RNA_SEQ_WORKFLOW_CHECK_FINISHED'][$i]);
		}

		$files_to_delete = array(
			//From step 0
			'sample_info.csv',
			'fastQC',

			// from step 1
			'alignment',

			// from step 3
			'comparison.txt',
			'DEG.R',
			'pheno.txt',
		);
		foreach($files_to_delete as $f){
			// if(file_exists($f)) shell_exec("rm -rf $f");
		}
		sleep(1);

		// Compose command
		$command_sh = "#!/usr/bin/bash\n";
		$command_sh .= "cd $analysis_dir/ \n";
		$command_sh .= "echo `date +'%Y-%m-%d %H:%M:%S'` > $analysis_dir/started_step_$step_number\n";

		foreach($data_info_array as $info){
			$command_sh .= $BXAF_CONFIG['BIN_fastqc'] . " raw_data/" . $info['Name'] . " --extract -o fastQC/ -t 6\n";
		}

		$command_sh .= "cd $analysis_dir/ \n";
		$command_sh .= "echo `date +'%Y-%m-%d %H:%M:%S'` > $analysis_dir/finished_step_$step_number\n\n";
		file_put_contents($analysis_dir . '/' . $BXAF_CONFIG['RNA_SEQ_WORKFLOW_SCRIPT'][$step_number], $command_sh);

		shell_exec('chmod 777 ' . $analysis_dir . '/' . $BXAF_CONFIG['RNA_SEQ_WORKFLOW_SCRIPT'][$step_number]);

		$command = '/usr/bin/bash ' . $analysis_dir . '/' . $BXAF_CONFIG['RNA_SEQ_WORKFLOW_SCRIPT'][$step_number];

		// Save Process
		$info_process = array(
			'Analysis_ID' => $analysis_id,
			'Complete_Analysis' => implode(',', $all_steps),
			'Command' => $command,
			'Log_File' => $BXAF_CONFIG['RNA_SEQ_WORKFLOW_LOG'][$step_number],
			'Dir' => $analysis_dir,
			'Files' => $analysis_dir . '/fastQC/',
			'Pipeline_Index' => $step_number,
			'_Owner_ID' => $BXAF_CONFIG['BXAF_USER_CONTACT_ID'],
			'Notes' => $BXAF_CONFIG['RNA_SEQ_WORKFLOW'][$step_number]
		);

		$BXAF_MODULE_CONN->insert($BXAF_CONFIG['TBL_BXGENOMICS_PROCESS'], $info_process);

	}




	/******************
	 * Step 1. Alignment with Subread
	 */

	$step_number = 1;
 	if(in_array($step_number, $all_steps)) {

		chdir($analysis_dir . '/');

		// Delete result
		$last_step = array_pop(array_keys($BXAF_CONFIG['RNA_SEQ_WORKFLOW']));
		for($i = $step_number; $i<= $last_step; $i++){
			if(file_exists($BXAF_CONFIG['RNA_SEQ_WORKFLOW_LOG'][$i])) shell_exec("rm " . $BXAF_CONFIG['RNA_SEQ_WORKFLOW_LOG'][$i]);
			if(file_exists($BXAF_CONFIG['RNA_SEQ_WORKFLOW_SCRIPT'][$i])) shell_exec("rm " . $BXAF_CONFIG['RNA_SEQ_WORKFLOW_SCRIPT'][$i]);
			if(file_exists($BXAF_CONFIG['RNA_SEQ_WORKFLOW_CHECK_FINISHED'][$i])) shell_exec("rm " . $BXAF_CONFIG['RNA_SEQ_WORKFLOW_CHECK_FINISHED'][$i]);
		}

		$files_to_delete = array(

			// from step 1
			'alignment',

			// from step 3
			'comparison.txt',
			'DEG.R',
			'pheno.txt',
		);
		foreach($files_to_delete as $f){
			// if(file_exists($f)) shell_exec("rm -rf $f");
		}

		// Update detail first
		$detail_array[$step_number][$datetime] = array(
			'Alignment data type' => $analysis_data_type,
			'Phred Score' => $_POST['select_phred']
		);
		$info_detail_update = array('Step_Detail' => serialize( $detail_array ) );
		$BXAF_MODULE_CONN->update($BXAF_CONFIG['TBL_BXGENOMICS_ANALYSIS'], $info_detail_update, "`ID`='$analysis_id'");


		// Compose command
		$command_sh = "#!/usr/bin/bash\n";
		$command_sh .= "cd $analysis_dir/ \n";
		$command_sh .= "echo `date +'%Y-%m-%d %H:%M:%S'` > $analysis_dir/started_step_$step_number\n";

		foreach($samples_name_array as $sample_id => $sample_name){

			$command_sh .= $BXAF_CONFIG['PROGRAM_DIR']['subjunc'] . ' -T ' . $BXAF_CONFIG['PROCESS_THREAD_ALLOWED'] . ' -i ' . $BXAF_CONFIG['NECESSARY_FILES'][$analysis_species]['genome_index'] . ' ';

			// File type parameter
			if($analysis_data_type == 'bam') $command_sh .= ' --BAMinput ';

			// Phred score parameter
			$command_sh .= ' -P ' . $_POST['select_phred'] . ' ';

			if($analysis_data_type == 'bam'){
				$command_sh .= ' -r ' . $analysis_dir . '/alignment/Sorted_Bam/' . $sample_name . '.sorted.bam';
			}
			else {
				foreach($data_info_array as $info){
					if($info['Sample_Name'] == $sample_name){
						if($info['Read_Number'] == 2) $command_sh .= ' -R ' . $analysis_dir . '/raw_data/' . $info['Name'];
						else $command_sh .= ' -r ' . $analysis_dir . '/raw_data/' . $info['Name'];
					}
				}
			}

			$command_sh .= " -o $analysis_dir/alignment/{$sample_name}.bam\n";

			$command_sh .= $BXAF_CONFIG['PROGRAM_DIR']['samtools'] . " sort -@ " . $BXAF_CONFIG['PROCESS_THREAD_ALLOWED'] . " $analysis_dir/alignment/{$sample_name}.bam -o alignment/{$sample_name}.sorted.bam\n";
			$command_sh .= $BXAF_CONFIG['PROGRAM_DIR']['samtools'] . " index -@ " . $BXAF_CONFIG['PROCESS_THREAD_ALLOWED'] . " $analysis_dir/alignment/{$sample_name}.sorted.bam \n";
		}


		$command_sh .= "rm $analysis_dir/alignment/Sorted_Bam/*.bam\n";
		$command_sh .= "mv $analysis_dir/alignment/*.sorted.bam* $analysis_dir/alignment/Sorted_Bam/. \n";
		$command_sh .= "rm $analysis_dir/alignment/*.bam\n";


		$command_sh .= "cd $analysis_dir/ \n";
		$command_sh .= "echo `date +'%Y-%m-%d %H:%M:%S'` > $analysis_dir/finished_step_$step_number\n\n";
		file_put_contents($analysis_dir . '/' . $BXAF_CONFIG['RNA_SEQ_WORKFLOW_SCRIPT'][$step_number], $command_sh);

		shell_exec('chmod 777 ' . $analysis_dir . '/' . $BXAF_CONFIG['RNA_SEQ_WORKFLOW_SCRIPT'][$step_number]);

		$command = '/usr/bin/bash ' . $analysis_dir . '/' . $BXAF_CONFIG['RNA_SEQ_WORKFLOW_SCRIPT'][$step_number];

		// Save Process
		$info_process = array(
			'Analysis_ID' => $analysis_id,
			'Complete_Analysis' => implode(',', $all_steps),
			'Command' => $command,
			'Log_File' => $BXAF_CONFIG['RNA_SEQ_WORKFLOW_LOG'][$step_number],
			'Dir' => $analysis_dir,
			'Files' => $analysis_dir . '/alignment/',
			'Pipeline_Index' => $step_number,
			'_Owner_ID' => $BXAF_CONFIG['BXAF_USER_CONTACT_ID'],
			'Notes' => $BXAF_CONFIG['RNA_SEQ_WORKFLOW'][$step_number]
		);

		$BXAF_MODULE_CONN->insert($BXAF_CONFIG['TBL_BXGENOMICS_PROCESS'], $info_process);

	}





	/******************
	 * Step 2. Create Gene Counts and QC Based On Gene Count Result
	 */

 	$step_number = 2;
  	if(in_array($step_number, $all_steps)){

		chdir($analysis_dir . '/');

		// Delete result
		$last_step = array_pop(array_keys($BXAF_CONFIG['RNA_SEQ_WORKFLOW']));
		for($i = $step_number; $i<= $last_step; $i++){
			if(file_exists($BXAF_CONFIG['RNA_SEQ_WORKFLOW_LOG'][$i])) shell_exec("rm " . $BXAF_CONFIG['RNA_SEQ_WORKFLOW_LOG'][$i]);
			if(file_exists($BXAF_CONFIG['RNA_SEQ_WORKFLOW_SCRIPT'][$i])) shell_exec("rm " . $BXAF_CONFIG['RNA_SEQ_WORKFLOW_SCRIPT'][$i]);
			if(file_exists($BXAF_CONFIG['RNA_SEQ_WORKFLOW_CHECK_FINISHED'][$i])) shell_exec("rm " . $BXAF_CONFIG['RNA_SEQ_WORKFLOW_CHECK_FINISHED'][$i]);
		}

		$files_to_delete = array(
			// from step 2
			'alignment/gene_counts.txt',
			'alignment/gene_counts.txt.summary',
			'alignment/QC',

			// from step 3
			'comparison.txt',
			'DEG.R',
			'pheno.txt',
			'alignment/comparison.txt',
			'alignment/counts4DEG.csv',
			'alignment/cpm_annot.csv',
			'alignment/DEG',
			'alignment/DEG_Process.log',
			'alignment/DEG.R',
			'alignment/DEG.Rout',
			'alignment/normalize_summary.csv',
			'alignment/pheno.txt',
			'alignment/rpkm_annot.csv',
		);
		foreach($files_to_delete as $f){
			// if(file_exists($f)) shell_exec("rm -rf $f");
		}

		$strand_info = array(0=>'Not stranded', 1=>'Same strand as mRNA', 2=>'Opposite strand as mRNA');
		$current_strand = 1;
		if(array_key_exists($_POST['analysis_strand'], $strand_info)) $current_strand = $_POST['analysis_strand'];

		// Update detail first
		$detail_array[$step_number][$datetime] = array(
			'Strand' => $strand_info[ $current_strand ]
		);
		$info_detail_update = array('Step_Detail' => serialize($detail_array));
		$BXAF_MODULE_CONN->update($BXAF_CONFIG['TBL_BXGENOMICS_ANALYSIS'], $info_detail_update, "`ID`='$analysis_id'");


		// Compose command
		$command_sh = "#!/usr/bin/bash\n";
		$command_sh .= "cd $analysis_dir/ \n";
		$command_sh .= "echo `date +'%Y-%m-%d %H:%M:%S'` > $analysis_dir/started_step_$step_number\n";

		$command_sh .= "cd $analysis_dir/alignment/Sorted_Bam\n";
		$command_sh .= $BXAF_CONFIG['PROGRAM_DIR']['featureCounts'] . " -T " . $BXAF_CONFIG['PROCESS_THREAD_ALLOWED'] . " -t exon -g gene_id";
		if($analysis_info['Data_Type'] == 'PE') $command_sh .= " -p ";

		$command_sh .= " -s " . $current_strand . " -a ".$BXAF_CONFIG['NECESSARY_FILES'][$analysis_species]['gtf']." -o ../gene_counts.txt ";

		foreach($samples_name_array as $sample_name) $command_sh .= $sample_name . ".sorted.bam ";

		$command_sh .= "\n";


		$command_sh .= "cd $analysis_dir/alignment\n";

		$command_sh .= "cp gene_counts.txt QC/.\n";
		$command_sh .= "cp gene_counts.txt.summary QC/.\n";
		$command_sh .= "cd QC\n";
		$command_sh .= $BXAF_CONFIG['RSCRIPT_BIN'] . " " . $BXAF_CONFIG['SCRIPT_QC_DIR'] . " gene_counts.txt\n";

		$command_sh .= "cd $analysis_dir/ \n";
		$command_sh .= "echo `date +'%Y-%m-%d %H:%M:%S'` > $analysis_dir/finished_step_$step_number\n\n";
		file_put_contents($analysis_dir . '/' . $BXAF_CONFIG['RNA_SEQ_WORKFLOW_SCRIPT'][$step_number], $command_sh);

		shell_exec('chmod 777 ' . $analysis_dir . '/' . $BXAF_CONFIG['RNA_SEQ_WORKFLOW_SCRIPT'][$step_number]);

		$command = '/usr/bin/bash ' . $analysis_dir . '/' . $BXAF_CONFIG['RNA_SEQ_WORKFLOW_SCRIPT'][$step_number];

		// Save Process
		$info_process = array(
			'Analysis_ID' => $analysis_id,
			'Complete_Analysis' => implode(',', $all_steps),
			'Command' => $command,
			'Log_File' => $BXAF_CONFIG['RNA_SEQ_WORKFLOW_LOG'][$step_number],
			'Dir' => $analysis_dir,
			'Files' => $analysis_dir . '/alignment/QC',
			'Pipeline_Index' => $step_number,
			'_Owner_ID' => $BXAF_CONFIG['BXAF_USER_CONTACT_ID'],
			'Notes' => $BXAF_CONFIG['RNA_SEQ_WORKFLOW'][$step_number]
		);

		$BXAF_MODULE_CONN->insert($BXAF_CONFIG['TBL_BXGENOMICS_PROCESS'], $info_process);

	}





	/******************
	 * Step 3. DEG, GSEA and GO Analysis
	 */


 	$step_number = 3;
  	if(in_array($step_number, $all_steps)){

		chdir($analysis_dir . '/');

		// Delete result
		$last_step = array_pop(array_keys($BXAF_CONFIG['RNA_SEQ_WORKFLOW']));
		for($i = $step_number; $i<= $last_step; $i++){
			if(file_exists($BXAF_CONFIG['RNA_SEQ_WORKFLOW_LOG'][$i])) shell_exec("rm " . $BXAF_CONFIG['RNA_SEQ_WORKFLOW_LOG'][$i]);
			if(file_exists($BXAF_CONFIG['RNA_SEQ_WORKFLOW_SCRIPT'][$i])) shell_exec("rm " . $BXAF_CONFIG['RNA_SEQ_WORKFLOW_SCRIPT'][$i]);
			if(file_exists($BXAF_CONFIG['RNA_SEQ_WORKFLOW_CHECK_FINISHED'][$i])) shell_exec("rm " . $BXAF_CONFIG['RNA_SEQ_WORKFLOW_CHECK_FINISHED'][$i]);
		}

		$files_to_delete = array(
			// from step 3
			'comparison.txt',
			'DEG.R',
			'pheno.txt',
			'alignment/comparison.txt',
			'alignment/counts4DEG.csv',
			'alignment/cpm_annot.csv',
			'alignment/DEG',
			'alignment/DEG_Process.log',
			'alignment/DEG.R',
			'alignment/DEG.Rout',
			'alignment/normalize_summary.csv',
			'alignment/pheno.txt',
			'alignment/rpkm_annot.csv',
		);
		foreach($files_to_delete as $f){
			// if(file_exists($f)) shell_exec("rm -rf $f");
		}


		// Generate comparison.txt
		$filename = "$analysis_dir/alignment/comparison.txt";
		$filename_bak = "$analysis_dir/alignment/comparison.txt_bak" . date('YmdHis', strtotime($datetime));
		if(file_exists($filename)) rename($filename, $filename_bak);

		$comparison_list_all = array_values(array_unique(array_merge($comparison_list, $comparison_list_new)));
		$comparison_txt = array();
		foreach($comparison_list_all as $c) $comparison_txt[] = str_replace('.vs.', "\t", $c);
		file_put_contents($filename, implode("\n", $comparison_txt) . "\n" );


		$info_comparison = array(
			'Comparisons' => serialize( array_values( $comparison_list_all ) )
		);
		$BXAF_MODULE_CONN->update($BXAF_CONFIG['TBL_BXGENOMICS_ANALYSIS'], $info_comparison, "`ID`=" . intval($analysis_id) . "");

		foreach($comparison_list_all as $comparison){
			if(! file_exists("$analysis_dir/alignment/DEG/$comparison/GSEAinfo/"))     mkdir("$analysis_dir/alignment/DEG/$comparison/GSEAinfo/", 0777, true);
			if(! file_exists("$analysis_dir/alignment/DEG/$comparison/GOinfo/"))       mkdir("$analysis_dir/alignment/DEG/$comparison/GOinfo/", 0777, true);
			if(! file_exists("$analysis_dir/alignment/DEG/$comparison/Downstream/"))   mkdir("$analysis_dir/alignment/DEG/$comparison/Downstream/", 0777, true);
		}

		// Update detail
		if(isset($_POST['sample_list_saved_for_DEG']) && trim($_POST['sample_list_saved_for_DEG']) != ''){
			$sample_name_selected_for_DEG = explode(',', $_POST['sample_list_saved_for_DEG']);
			foreach($samples_name_array as $key => $value){
				if(!in_array($key, $sample_name_selected_for_DEG)){
					unset($samples_name_array[$key]);
				}
			}
		}

		// Get sample and treatment information
		$samples_treatment_info = array();
		foreach($samples_name_array as $id => $info){
			$sql_sample_temp = "SELECT * FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_SAMPLE'] . "` WHERE `ID`=" . intval($id) . " AND `bxafStatus`<5";
			$data_sample_temp = $BXAF_MODULE_CONN -> get_row($sql_sample_temp);
			if(in_array($data_sample_temp['Treatment_Name'], array_keys($samples_treatment_info))){
				$samples_treatment_info[$data_sample_temp['Treatment_Name']][] = $data_sample_temp['Name'];
			} else {
				$samples_treatment_info[$data_sample_temp['Treatment_Name']] = array($data_sample_temp['Name']);
			}
		}

		$samples_treatment_info_string = '';
		foreach($samples_treatment_info as $id => $value){
			$samples_treatment_info_string .= '<br>' . $id . '(' . implode(', ', $value) . ')';
		}

		$detail_array[$step_number][$datetime] = array(
			'TMM Normalization' => $_POST['TMM'],
			'Samples Used' => $samples_treatment_info_string,
			'Comparisons' => implode(', ', $comparison_list_all),
			'Number of GSEA graphs' => $_POST['graph_number'],
			'Minimum number of Genes in a set' => $_POST['minimum_gene_number'],
			'Maximum number of Genes in a set' => $_POST['maximum_gene_number']
		);

		$info_detail_update = array('Step_Detail' => serialize($detail_array));
		$BXAF_MODULE_CONN->update($BXAF_CONFIG['TBL_BXGENOMICS_ANALYSIS'], $info_detail_update, "`ID`=" . intval($analysis_id) . "");


		// Generate pheno.txt
		$file = fopen("$analysis_dir/alignment/pheno.txt","w");
		fwrite($file, "Sample\tPhenotype\n");
		$used_temp = array();
		foreach ($data_info_array as $info){
			if (!in_array($info['Sample_Name'], $used_temp)){
				fwrite($file, $info['Sample_Name'] . "\t" . $info['Treatment_Name'] . "\n");
				$used_temp[] = $info['Sample_Name'];
			}
		}
		fclose($file);


		// Generate DEG.R
		$R_batch_file = "options(stringsAsFactors=F);\n";
		$R_batch_file .= "library('stringr');\n";
		$R_batch_file .= "suppressPackageStartupMessages(library(optparse));\n";
		$R_batch_file .= "suppressPackageStartupMessages(library(stringr));\n";
		$R_batch_file .= "suppressPackageStartupMessages(library(limma));\n";
		$R_batch_file .= "suppressPackageStartupMessages(library(edgeR));\n";
		$R_batch_file .= "suppressPackageStartupMessages(library(made4));\n";
		$R_batch_file .= "suppressPackageStartupMessages(library(genefilter));\n";
		$R_batch_file .= "source('" . $BXAF_CONFIG['NECESSARY_FILES'][$analysis_species]['comparison_source'] . "');\n";
		$R_batch_file .= "output_dir='$analysis_dir/alignment/DEG/';\n";
		$R_batch_file .= "annot=read.csv('" . $BXAF_CONFIG['NECESSARY_FILES'][$analysis_species]['annotation'] . "', row.names=1)\n";
		$R_batch_file .= "file1='gene_counts.txt';\n";
		$R_batch_file .= "gene_counts=read.table(file1, sep=\"\\t\", header=T, row.names=1);\n";
		$R_batch_file .= "counts=gene_counts[, 6:dim(gene_counts)[2]];\n";
		$R_batch_file .= "colnames(counts)=str_replace(colnames(counts), '.sorted.bam', '');\n";
		$R_batch_file .= "colnames(counts)=str_replace(colnames(counts), '.bam', '');\n";
		$R_batch_file .= "counts=counts[,order(colnames(counts))];\n";
		$R_batch_file .= "fileLog=\"DEG_Process.log\";\n";
		$R_batch_file .= "cpm_cutoff=1/(mean(colSums(counts))/1e7);\n";
		$R_batch_file .= "cpm_cutoff=min(cpm_cutoff, 1);\n";
		$R_batch_file .= "cpms = cpm(counts);\n";
		$R_batch_file .= "keep = rowSums(cpms>cpm_cutoff)>=2;\n";
		$R_batch_file .= "cat(\"Mean Number of Reads\\t\", round(mean(colSums(counts))), \"\\nCPM cutoff for Expression\\t\", cpm_cutoff, \"\\n\", file=fileLog);\n";
		$R_batch_file .= "cat(\"keeping\", sum(keep), \"genes from total of\", dim(counts)[1], \"genes.\\n\")\n";
		$R_batch_file .= "cat(\"Total Genes\\t\", nrow(counts), \"\\nSelected Genes\\t\", sum(keep), \"\\n\", file=fileLog,append=T);\n";
		$R_batch_file .= "counts=counts[keep, ];\n";
		$R_batch_file .= "phenos = read.table(\"pheno.txt\", header=T, sep=\"\\t\");\n";
		$R_batch_file .= "comparisons = read.table(\"comparison.txt\", header=F, sep=\"\\t\");\n";
		$R_batch_file .= "phenos=phenos[order(phenos\$Sample), ];\n";
		$R_batch_file .= "if (!all(colnames(counts) == phenos\$Sample)){\n";
		$R_batch_file .= "stop(\"Pheno sample names don't line up with your count matrix... Aborting\")\n";
		$R_batch_file .= "};\n";
		$R_batch_file .= "d2=DGEList(counts=counts);\n";
		$R_batch_file .= "d2=calcNormFactors(d2);\n";
		$R_batch_file .= "write.csv(d2\$sample, \"normalize_summary.csv\");\n";
		$R_batch_file .= "gene.length=gene_counts\$Length[keep];\n";
		$R_batch_file .= "TMM=" . $_POST['TMM'] . ";\n";
		$R_batch_file .= "g_rpkm <- rpkm(d2,gene.length, normalized.lib.sizes=TMM);\n";
		$R_batch_file .= "cat (\"RPKM Norm\\t\", TMM, \"\\n\");\n";
		$R_batch_file .= "cat (\"RPKM Norm\\t\", TMM, \"\\n\", file=fileLog,append=T);\n";
		$R_batch_file .= "alldata=cbind(g_rpkm, annot[rownames(g_rpkm), ] );\n";
		$R_batch_file .= "write.csv(alldata, \"rpkm_annot.csv\");\n";
		$R_batch_file .= "allCPM=cbind(cpms[keep, ], annot[rownames(g_rpkm), ]);\n";
		$R_batch_file .= "write.csv(allCPM, 'cpm_annot.csv');\n";
		$R_batch_file .= "write.csv(counts, 'counts4DEG.csv');\n";
		$R_batch_file .= "if (!all(colnames(counts) == colnames(rpkm))){\n";
		$R_batch_file .= "stop(\"RPKM sample names don't line up with your count matrix... Aborting\")\n";
		$R_batch_file .= "};\n";
		$R_batch_file .= "for (j in 1:nrow(comparisons)){\n";
		$R_batch_file .= "sel = which(phenos\$Phenotype %in% comparisons[j, ])\n";
		$R_batch_file .= "pairwise_limma_runner_GTF(\n";
		$R_batch_file .= "count_matrix = counts[ ,sel],\n";
		$R_batch_file .= "pheno_mapper = phenos[sel, 1:2],\n";
		$R_batch_file .= "compare= as.character(comparisons[j, ]),\n";
		$R_batch_file .= "output_loc = str_c(output_dir, str_c(comparisons[j, 1], \".vs.\", comparisons[j, 2], \"/\")),\n";
		$R_batch_file .= "annot = annot,\n";
		$R_batch_file .= "rpkm_sub=g_rpkm[, sel],\n";
		$R_batch_file .= "Filter=F, TMM=" . $_POST['TMM'] . "\n";
		$R_batch_file .= ")\n";
		$R_batch_file .= "}\n";

		file_put_contents("$analysis_dir/alignment/DEG.R", $R_batch_file);


		// Compose command
		$command_sh = "#!/usr/bin/bash\n";
		$command_sh .= "cd $analysis_dir/ \n";
		$command_sh .= "echo `date +'%Y-%m-%d %H:%M:%S'` > $analysis_dir/started_step_$step_number\n";

		$command_sh .= "cd $analysis_dir/alignment/ \n";
		$command_sh .= "chmod -R 777 $analysis_dir/alignment/DEG/ \n";
		$command_sh .= $BXAF_CONFIG['RSCRIPT_BIN'] . " $analysis_dir/alignment/DEG.R \n";

		$command_sh .= "cd $analysis_dir/alignment/DEG/ \n";
		$command_sh .= "chmod -R 777 $analysis_dir/alignment/DEG/ \n";
		$command_sh .= $BXAF_CONFIG['RSCRIPT_BIN'] . " " . dirname(__FILE__) . "/analysis_scripts/DEG_GenomicDB.R\n";

		$command_sh .= "PATH=\$PATH:" . rtrim($BXAF_CONFIG['DIR_homer'], '/') . "\n";
		$command_sh .= "export PATH\n";

		$command_sh .= "cd $analysis_dir/alignment/DEG/ \n";

		// 01. Run GSEA and GO analysis for each comparison
		foreach($comparison_list_new as $comparison){
			// GSEA Analysis
			if(trim($BXAF_CONFIG['NECESSARY_FILES'][$analysis_species]['gtf']) != ''){
				$command_sh .= "cd $analysis_dir/alignment/DEG/$comparison/Downstream/ \n";

				$command_sh .= "rm -R GSEA_$comparison \n";

				$command_sh .= "java -cp " . $BXAF_CONFIG['PROGRAM_DIR']['gsea'] . " -Xmx5048m xtools.gsea.GseaPreranked -gmx " .
				$BXAF_CONFIG['NECESSARY_FILES'][$analysis_species]['gmt_file'] . " -collapse false -mode Max_probe -norm meandiv -nperm 1000 -rnk " . $comparison .
				"_GSEA.rnk -scoring_scheme weighted -rpt_label " . $comparison . " -include_only_symbols true -make_sets true -plot_top_x " . $_POST['graph_number'] .
				" -rnd_seed timestamp -set_max " . $_POST['maximum_gene_number'] . " -set_min " . $_POST['minimum_gene_number'] . " -zip_report false -out GSEA_" . $comparison . " -gui false\n";
			}
			// GO Analysis
			if(trim($BXAF_CONFIG['NECESSARY_FILES'][$analysis_species]['GO_enrichment_genome']) != ''){
				$command_sh .= "cd $analysis_dir/alignment/DEG/$comparison/Downstream/ \n";
				$command_sh .= $BXAF_CONFIG['DIR_homer'] . "findGO.pl " . $comparison . "_up_list.txt " . trim($BXAF_CONFIG['NECESSARY_FILES'][$analysis_species]['GO_enrichment_genome']) . " GO_Analysis_Up\n";
				$command_sh .= $BXAF_CONFIG['DIR_homer'] . "findGO.pl " . $comparison . "_down_list.txt " . trim($BXAF_CONFIG['NECESSARY_FILES'][$analysis_species]['GO_enrichment_genome']) . " GO_Analysis_Down\n";
			}
		}

		// 02. Perform analysis info for each comparison
		foreach($comparison_list_new as $comparison){
			// GSEAinfo
			if(trim($BXAF_CONFIG['NECESSARY_FILES'][$analysis_species]['gtf']) != ''){
				$command_sh .= "cd $analysis_dir/alignment/DEG/$comparison/GSEAinfo/ \n";

				$command_sh .= "find ../ -name \"gsea_report*.xls\" > gsea_reportfiles.txt \n";
				$command_sh .= "cp /public/scripts/php/GSEAinfo_files/* . \n";

				$command_sh .= $BXAF_CONFIG['RSCRIPT_BIN'] . " " . __DIR__ . "/analysis_scripts/GSEA_Batch_inDEG.R\n";
			}
			// GOinfo
			if(trim($BXAF_CONFIG['NECESSARY_FILES'][$analysis_species]['GO_enrichment_genome']) != ''){

				$command_sh .= "cd $analysis_dir/alignment/DEG/$comparison/GOinfo/ \n";

				$command_sh .= "find ../ -name \"*GO_Analysis*\" > GO_folders.txt \n";
				$command_sh .= "cp /public/scripts/php/GOinfo_files/* . \n";

				$command_sh .= $BXAF_CONFIG['RSCRIPT_BIN'] . " " . __DIR__ . "/GO_Batch_Graph_inDEG.R\n";
			}
		}

		// 03. Make analysis summary for all
		// GSEA Summary
		if(trim($BXAF_CONFIG['NECESSARY_FILES'][$analysis_species]['gtf']) != ''){
			$command_sh .= "cd $analysis_dir/alignment/DEG/GSEA_Summary/\n";

			$command_sh .= "find ../ -name \"gsea_report*.xls\" > gsea_reportfiles.txt \n";
			$command_sh .= "cp /public/scripts/php/GSEA_Report/* . \n";

			$command_sh .= $BXAF_CONFIG['RSCRIPT_BIN'] . " " . __DIR__ . "/analysis_scripts/GSEA_Batch_Graph.R\n";
		}
		// GO Summary
		if(trim($BXAF_CONFIG['NECESSARY_FILES'][$analysis_species]['GO_enrichment_genome']) != ''){
			$command_sh .= "cd $analysis_dir/alignment/DEG/GO_Summary/ \n";

			$command_sh .= "find ../ -name \"*GO_Analysis*\" > GO_folders.txt \n";
			$command_sh .= "cp /public/scripts/php/GO_Report/* . \n";

			$command_sh .= $BXAF_CONFIG['RSCRIPT_BIN'] . " " . __DIR__ . "/analysis_scripts/GO_Batch_Graph_up1.R\n";
		}


		$command_sh .= "cd $analysis_dir/ \n";
		$command_sh .= "echo `date +'%Y-%m-%d %H:%M:%S'` > $analysis_dir/finished_step_$step_number\n\n";
		file_put_contents($analysis_dir . '/' . $BXAF_CONFIG['RNA_SEQ_WORKFLOW_SCRIPT'][$step_number], $command_sh);

		shell_exec('chmod 777 ' . $analysis_dir . '/' . $BXAF_CONFIG['RNA_SEQ_WORKFLOW_SCRIPT'][$step_number]);

		$command = '/usr/bin/bash ' . $analysis_dir . '/' . $BXAF_CONFIG['RNA_SEQ_WORKFLOW_SCRIPT'][$step_number];

		// Save Process
		$info_process = array(
			'Analysis_ID' => $analysis_id,
			'Complete_Analysis' => implode(',', $all_steps),
			'Command' => $command,
			'Log_File' => $BXAF_CONFIG['RNA_SEQ_WORKFLOW_LOG'][$step_number],
			'Dir' => $analysis_dir,
			'Files' => $analysis_dir . '/alignment/DEG',
			'Pipeline_Index' => $step_number,
			'_Owner_ID' => $BXAF_CONFIG['BXAF_USER_CONTACT_ID'],
			'Notes' => $BXAF_CONFIG['RNA_SEQ_WORKFLOW'][$step_number]
		);

		$BXAF_MODULE_CONN->insert($BXAF_CONFIG['TBL_BXGENOMICS_PROCESS'], $info_process);

	}

	run_process_in_order();

	exit();
}






/**
 * Terminate Running Analysis Process
 */

if(isset($_GET['action']) && $_GET['action'] == 'terminate_process'){

	$linux = new bxaf_linux('/');
	$analysis_id = $_POST['analysis_id'];
	//print_r($_POST); exit();

	if(isset($_POST['process_id']) && $_POST['process_id'] != 0){
		$process_id = $_POST['process_id'];
	}

	if(isset($_POST['process_processid']) && $_POST['process_processid'] != 0){
		$process_processid = $_POST['process_processid'];
		$linux -> kill_child_processes($process_processid);
	}

	if(isset($_POST['process_id']) && $_POST['process_id'] != 0){
		$BXAF_MODULE_CONN -> delete($BXAF_CONFIG['TBL_BXGENOMICS_PROCESS'], "`ID`=" . intval($process_id) . "");
	}

	$BXAF_MODULE_CONN -> delete($BXAF_CONFIG['TBL_BXGENOMICS_PROCESS'], "`Analysis_ID`=" . intval($analysis_id) . " AND `End_Time`<'2015-01-01 00:00:00'");

	exit();
}





/**
 * Mark Analysis As Finished
 */

if(isset($_GET['action']) && $_GET['action'] == 'mark_as_finished'){

	$analysis_id = intval($_POST['analysis_id']);

	$sql = "SELECT `ID` FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_ANALYSIS'] . "` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `ID` = ?i";
	$found_id = $BXAF_MODULE_CONN->get_one($sql, $analysis_id);

	if($found_id > 0){
		$info = array('bxafStatus' => 4);
		$BXAF_MODULE_CONN -> update($BXAF_CONFIG['TBL_BXGENOMICS_ANALYSIS'], $info, "`ID`=$analysis_id");
	}

	exit();
}





/**
 * Get Each Step Detail
 */

if(isset($_GET['action']) && $_GET['action'] == 'get_step_detail'){

	$step = $_POST['step'];
	$analysis_id = $_POST['analysis_id'];

	$sql = "SELECT `Step_Detail` FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_ANALYSIS'] . "` WHERE `ID` = ?i";
	$Step_Detail = $BXAF_MODULE_CONN->get_one($sql, $analysis_id);

	$detail_array = unserialize( $Step_Detail );
	$details = $detail_array[$step];

	if(! is_array($details) || count($details) == 0){
		echo 'No details available for current analysis step';
	}
	else {
		foreach($details as $key=>$value){
			if(is_array($value)){
				echo "<hr /><div class='my-3 text-success lead'>Analysis Date: $key</div>";
				foreach($value as $k => $v){
					echo '<div class="my-1"><strong>' . $k . ': </strong> '. $v . '</div>';
				}
			}
			else {
				echo '<div class="my-1"><strong>' . $key . ': </strong> '. $value . '</div>';
			}
		}
	}

	exit();
}







/**
 * Duplicate Analysis
 */

if(isset($_GET['action']) && $_GET['action'] == 'duplicate_analysis'){

	$analysis_id = intval($_POST['analysis_id']);

	// $analysis_id = 22;

	// 01. Duplicate analysis record
	$sql = "SELECT * FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_ANALYSIS'] . "` WHERE `ID` = ?i";
	$analysis_info = $BXAF_MODULE_CONN->get_row($sql, $analysis_id);

	$info_new_analysis = array();
	foreach($analysis_info as $key=>$val){
		if($key == 'ID' || $key == 'PID'){

		}
		else if($key == 'Name'){
			$info_new_analysis[$key] = "$val copy" . date('YmdHis');
		}
		else if($key == 'Time_Added' || $key == 'Status_Time'){
			$info_new_analysis[$key] = date("Y-m-d H:i:s");
		}
		else {
			$info_new_analysis[$key] = $val;
		}
	}


	$new_analysis_id = $BXAF_MODULE_CONN -> insert($BXAF_CONFIG['TBL_BXGENOMICS_ANALYSIS'], $info_new_analysis);


	// 02. Copy analysis folder
	$old_folder = $analysis_id . '_' . bxaf_encrypt($analysis_id, $BXAF_CONFIG['BXAF_KEY']);
	$new_folder = $new_analysis_id . '_' . bxaf_encrypt($new_analysis_id, $BXAF_CONFIG['BXAF_KEY']);
	bxaf_copy_all($BXAF_CONFIG['ANALYSIS_DIR'] . '/' . $old_folder, $BXAF_CONFIG['ANALYSIS_DIR'] . '/' . $new_folder);


	// 03. Duplicate process records (so that auto-run check will not have errors.)
	for ($i = 0; $i <= 3; $i++){
		$sql = "SELECT * FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_PROCESS'] . "` WHERE `Analysis_ID` = ?i AND `Pipeline_Index`= ?i";
		$process_info = $BXAF_MODULE_CONN -> get_row($sql, $analysis_id, $i);

		if(is_array($process_info) && count($process_info) > 0){
			$process_info_new = array();
			foreach($process_info as $key=>$val){
				if($key == 'ID' || $key == 'Parent_ID'){

				}
				else if($key == 'Command' || $key == 'Dir' || $key == 'Files'){
					$process_info_new[$key] = str_replace($old_folder, $new_folder, $val);
				}
				else if($key == 'Analysis_ID'){
					$process_info_new[$key] = $new_analysis_id;
				}
				else {
					$process_info_new[$key] = $val;
				}
			}
			$BXAF_MODULE_CONN -> insert($BXAF_CONFIG['TBL_BXGENOMICS_PROCESS'], $process_info_new);
		}
	}

	// Return new analysis ID
	echo $new_analysis_id;

	exit();
}










/**
 * Check Running Process & Refresh Analysis
 */

if(isset($_GET['action']) && $_GET['action'] == 'analysis_process_refresh'){

	$sql = "SELECT * FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_PROCESS'] . "` WHERE `Analysis_ID`= ?i AND `Start_Time` NOT LIKE '0000%' AND `End_Time` LIKE '0000%'";
	$running_process_info = $BXAF_MODULE_CONN->get_row($sql, intval($_POST['analysisID']) );

	if( ! is_array($running_process_info) || count($running_process_info) <= 0 || $running_process_info['ID'] != intval($_POST['currentRunningProcess']) ) {
		echo 'refresh';
		exit();
	}
	else {
		$startTime = strtotime($running_process_info['Start_Time']);
		echo format_duration( time() - $startTime );
		exit();
	}

	exit();
}




?>