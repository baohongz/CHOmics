<?php
include_once('config/config.php');



if (!function_exists("auto_assign_read_number")) {
    function auto_assign_read_number($sample_id){
		global $BXAF_CONFIG;
		global $BXAF_MODULE_CONN;

		// All sample files
		$sql = "SELECT * FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_DATA'] . "` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `Sample_ID`= ?i";
		$data_my_files = $BXAF_MODULE_CONN->get_all($sql, $sample_id);

		// Get 'fastq.gz', 'fq.gz', 'fq', 'fastq' and 'bam' files only
		$file_data_array = array();
		foreach($data_my_files as $key => $value){
			if (strpos($value['Name'], 'fastq.gz') !== false || strpos($value['Name'], 'fq.gz') !== false || strpos($value['Name'], 'fq') !== false || strpos($value['Name'], 'fastq') !== false || strpos($value['Name'], '.bam') !== false){
				$file_data_array[] = $value;
			}
		}


		// If only one data file is uploaded.
		if(count($file_data_array) == 1){
			$info_updated = array('Read_Number' => '1');
			$BXAF_MODULE_CONN -> update($BXAF_CONFIG['TBL_BXGENOMICS_DATA'], $info_updated, "`ID`=" . intval($file_data_array[0]['ID']) . "");
		}
		// If two data files are uploaded
		else if(count($file_data_array) == 2){
			foreach($file_data_array as $key => $value){

				if (strpos($value['Name'], '_R1') !== false
					|| strpos($value['Name'], '_r1') !== false
					|| strpos($value['Name'], '_1.fa') !== false
					|| strpos($value['Name'], '_1.fastq') !== false
					|| strpos($value['Name'], '_1.fq') !== false) {
					$info_updated = array('Read_Number' => '1');
					$BXAF_MODULE_CONN -> update($BXAF_CONFIG['TBL_BXGENOMICS_DATA'], $info_updated, "`ID`=" . intval($value['ID']) . "");
				}
				if (strpos($value['Name'], '_R2') !== false
					|| strpos($value['Name'], '_r2') !== false
					|| strpos($value['Name'], '_2.fa') !== false
					|| strpos($value['Name'], '_2.fastq') !== false
					|| strpos($value['Name'], '_2.fq') !== false) {
					$info_updated = array('Read_Number' => '2');
					$BXAF_MODULE_CONN -> update($BXAF_CONFIG['TBL_BXGENOMICS_DATA'], $info_updated, "`ID`=" . intval($value['ID']) . "");
				}

			}

		}

		return;

    }
}




/**
 * TABLE OF CONTENTS
 * ------------------------------------------------
 * 01. New Experiment
 * 02. New Sample
 * 03. Upload Sample
 * 04. Edit Sample Info
 * 05. Delete Sample
 * 06. New Analysis
 * 07. Upload File -- 1. Drag & Drop
 * 08. Upload File -- 2. Enter URL
 * 09. Upload File -- 3. Enter Location In Server
 * 10. Upload File -- 4. Select Server File
 * 11. Get File Uploading Status
 * 12. Terminate Process
 * 13. Merge Files
 * 14. Get File Info Before Editing
 * 15. Update File Info
 * 16. Admin Update Program Dir
 * 17. Admin Update File Dir
 * 18. Delete Sample & Analysis
 * 19. Get Single Sample Info
 * 20. Update Single Sample Info
 * 21. Update Public File
 * 22. Edit Public File
 * 23. Edit Experiment Information
 * 24. Edit Analysis Information
 * 25. Remove Data Record
 * ------------------------------------------------
 */


if (isset($_GET['action']) && $_GET['action'] == 'sign_in_as_advanced_user') {

     if($_POST['admin_password'] == $BXAF_CONFIG['BXAF_ADMIN_PASSWORD']){
         $_SESSION['BXAF_ADVANCED_USER'] = true;
     }
     else {
         unset($_SESSION['BXAF_ADVANCED_USER']);
         echo "Wrong Password!";
     }

 	exit();
 }



// New Project
else if(isset($_GET['action']) && $_GET['action'] == 'new_project') {

 	header('Content-Type: application/json');
 	$OUTPUT['type'] = 'Error';

 	$name = trim($_POST['project_name']);
 	if($name == ''){
 		$OUTPUT['detail'] = "Project Name is required!";
 		echo json_encode($OUTPUT);
 		exit();
 	}

 	// Check Duplicate Names
 	$sql = "SELECT `ID` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS']}` WHERE `Species` = '{$_SESSION['SPECIES_DEFAULT']}' AND {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `Name` = ?s";
 	$found_id = $BXAF_MODULE_CONN->get_row($sql, $name);

 	if($found_id > 0){
 		$OUTPUT['detail'] = "<h2 class='text-danger'>Error</h2><div class='my-3'>The project name '$name' is taken. Please enter a unique name.</div>";
 		echo json_encode($OUTPUT);
 		exit();
 	}

 	$info = array(
 		'Name'         => $name,
 		'Description'  => $_POST['project_description'],
        'Species'      => $_SESSION['SPECIES_DEFAULT'],
 		'_Owner_ID'    => $BXAF_CONFIG['BXAF_USER_CONTACT_ID'],
 		'Time_Created' => date("Y-m-d H:i:s")
 	);

    if($_SESSION['SPECIES_DEFAULT'] == 'Mouse'){

        $sql = "SELECT * FROM ?n WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND ?n = ?i";
        $platform_info1 = $BXAF_MODULE_CONN -> get_row($sql, $BXAF_CONFIG['TBL_BXGENOMICS_PLATFORMS'], 'ID', 2 );

        $info['_Platforms_ID'] = $platform_info1['ID'];
        $info['Platform']      = $platform_info1['GEO_Accession'];
        $info['PlatformName']  = $platform_info1['Name'];
        $info['Platform_Type'] = $platform_info1['Type'];
    }
    else { // Human
        $sql = "SELECT * FROM ?n WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND ?n = ?i";
        $platform_info1 = $BXAF_MODULE_CONN -> get_row($sql, $BXAF_CONFIG['TBL_BXGENOMICS_PLATFORMS'], 'ID', 1 );

        $info['_Platforms_ID'] = $platform_info1['ID'];
        $info['Platform']      = $platform_info1['GEO_Accession'];
        $info['PlatformName']  = $platform_info1['Name'];
        $info['Platform_Type'] = $platform_info1['Type'];
    }

 	$id = $BXAF_MODULE_CONN -> insert($BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS'], $info);

    $BXAF_MODULE_CONN -> execute("UPDATE `{$BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS']}` SET `ProjectIndex` = `ID` WHERE `ID` = " . intval($id) );


 	$OUTPUT['type'] = 'Success';
 	$OUTPUT['id']   = $id;
 	echo json_encode($OUTPUT);

 	exit();
 }


// New Experiment
else if(isset($_GET['action']) && $_GET['action'] == 'new_experiment'){

	if($_POST['Name'] == ''){
		echo "Experiment name is required!";
		exit();
	}

	$sql = "SELECT `ID` FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_EXPERIMENT'] . "` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `Name` = ?s";
	$found = $BXAF_MODULE_CONN->get_one($sql, $_POST['Name']);

    if($found > 0){
		echo "An experiment with name '" . $_POST['Name'] . "' is already created!";
		exit();
	}

	$info = array(
		'Name' => addslashes($_POST['Name']),
		'Description' => $_POST['Description'],
		'_Owner_ID' => $BXAF_CONFIG['BXAF_USER_CONTACT_ID'],
		'Time_Created' => date("Y-m-d H:i:s"),
		'Last_Updated' => date("Y-m-d H:i:s")
	);
	$id = $BXAF_MODULE_CONN->insert($BXAF_CONFIG['TBL_BXGENOMICS_EXPERIMENT'], $info);

	if($id <= 0){
		echo "Error in creating new experiment information!";
		exit();
	}

	exit();
}



if(isset($_GET['action']) && $_GET['action'] == 'process_experiment_files'){

    $experiment_id = intval($_GET['experiment_id']);

	$uploads_dir = $BXAF_CONFIG['BXGENOMICS_SERVER_FILES_PRIVATE'] . "Experiments" . DIRECTORY_SEPARATOR . $experiment_id;

    $command_sh = "#!/usr/bin/bash\n";
	$command_sh .= "cd $uploads_dir \n";
    $commands = array();

    $to_be_merged = array();
    $files = bxaf_list_files_only($uploads_dir);
    asort($files);
	foreach($files as $i=>$file){

		if(preg_match("/\.fq$/", $file)){
			$new_file = preg_replace("/\.fq$/", '.fastq', $file);
			if(file_exists($new_file)) unlink($new_file);
			rename($file, $new_file );
			$file = $new_file;
		}

		if(preg_match("/\_R[12]\_\d{3}\.fastq$/", $file)){
			$new_file = preg_replace("/\_\d{3}\.fastq$/", '.fastq', $file);
			if(file_exists($new_file)) unlink($new_file);
			rename($file, $new_file );
			$file = $new_file;
		}

		if(preg_match("/\.fastq$/", $file)){
            $commands[] = $BXAF_CONFIG['BGZIP_BIN'] . " $file ";
		}

		if(preg_match("/\.fq\.gz$/", $file)){
			$new_file = preg_replace("/\.fq\.gz$/", '.fastq.gz', $file);
			if(file_exists($new_file)) unlink($file);
			else rename($file, $new_file );
			$file = $new_file;
		}

		if(preg_match("/\_R[12]\_\d{3}\.fastq\.gz$/", $file)){
			$new_file = preg_replace("/\_\d{3}\.fastq\.gz$/", '.fastq.gz', $file);
			if(file_exists($new_file)) unlink($new_file);
			rename($file, $new_file );
			$file = $new_file;
		}

		if(preg_match("/\_L\d{3}\_R[12]\.fastq\.gz$/", $file)){
			$to_be_merged[$file] = basename($file);
		}

		if(preg_match("/\.bam$/", $file) && ! preg_match("/\.sorted\.bam$/", $file)){

			$new_file = preg_replace("/\.bam$/", '.sorted.bam', $file);
			if(file_exists($new_file)) unlink($file);
			else {
				$commands[] = $BXAF_CONFIG['PROGRAM_DIR']['samtools'] . " sort $file -o $new_file ";
                $commands[] = "rm -rf $file \n";
			}
			if(! file_exists($new_file . '.bai')){
                $commands[] = $BXAF_CONFIG['PROGRAM_DIR']['samtools'] . " index $file ";
			}
		}

		if(preg_match("/\.sorted\.bam$/", $file)){
			if(! file_exists($file . '.bai')){
                $commands[] = $BXAF_CONFIG['PROGRAM_DIR']['samtools'] . " index $file ";
			}
		}
	}

    if(count($to_be_merged) > 0){
        $files_to_merge = array();
        foreach($to_be_merged as $file=>$name){
            $name = str_replace('.fastq.gz', '', $name);
            $name_array = explode('_', $name);
            $r = array_pop($name_array);
            $l = array_pop($name_array);
            $name = implode('_', $name_array);
            $files_to_merge[$name][$r][$l] = $file;
        }

        foreach($files_to_merge as $name=>$rl){
            foreach($rl as $r=>$lf){
                if(count($lf) > 0){
                    $commands[] = "cat " . implode(" ", $lf) . " > {$name}_{$r}.fastq.gz ";
                    $commands[] = "rm -rf " . implode(" ", $lf) . " ";
                }
            }
        }
    }

    if(count($commands) > 0){
        $command_sh .= implode("\n", $commands);
    }

    $filename = "process_experiment_files_" . date('YmdHis');
    $command_folder = $BXAF_CONFIG['USER_FILES']['TOOL_CACHE'] . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'];
    if(! file_exists($command_folder)) mkdir($command_folder, 0777, true);
    file_put_contents("$command_folder/$filename", $command_sh);
    chmod("$command_folder/$filename", 0777);

    // Save Process
    $info_process = array(
        'Command' => "$command_folder/$filename",
        'Log_File' => "$command_folder/$filename" . ".log",
        'Dir' => $uploads_dir,
        '_Owner_ID' => $BXAF_CONFIG['BXAF_USER_CONTACT_ID']
    );
    $process_id = $BXAF_MODULE_CONN->insert($BXAF_CONFIG['TBL_BXGENOMICS_PROCESS'], $info_process);

    if($process_id > 0){
        echo $process_id;
        run_process_in_order();
    }

    exit();

}




/**
 * Get File Uploading Status
 */

if(isset($_GET['action']) && $_GET['action'] == 'check_experiment_files'){

    $experiment_id = intval($_GET['experiment_id']);
    if($experiment_id <= 0) echo 0;

    $uploads_dir = $BXAF_CONFIG['BXGENOMICS_SERVER_FILES_PRIVATE'] . "Experiments" . DIRECTORY_SEPARATOR . $experiment_id;

    $to_be_processed = array();
	// rename all .fq.gz to .fastq.gz
	$files = bxaf_list_files_only($uploads_dir);
	foreach($files as $i=>$file){

		if(preg_match("/\.fastq$/", $file)){
			$to_be_processed[] = $file;
		}

		if(preg_match("/\_L\d{3}\_R[12]\.fastq\.gz$/", $file)){
			$to_be_processed[] = $file;
		}

		if(preg_match("/\.bam$/", $file) && ! preg_match("/\.sorted\.bam$/", $file)){
			$to_be_processed[] = $file;
		}

		if(preg_match("/\.sorted\.bam$/", $file)){
			if(! file_exists($file . '.bai')){
				$to_be_processed[] = $file;
			}
		}
	}

    echo count($to_be_processed);

	exit();
}





if(isset($_GET['action']) && $_GET['action'] == 'new_sample_auto'){
    // echo "_POST<pre>" . print_r($_POST, true) . "</pre>"; exit();

    $experiment_id = intval($_POST['experiment_id']);
    $exp_dir = $BXAF_CONFIG['BXGENOMICS_SERVER_FILES_PRIVATE'] . "Experiments" . DIRECTORY_SEPARATOR . $experiment_id . DIRECTORY_SEPARATOR;

    $sample_info = array();

    if(isset($_POST['sample_type']) && $_POST['sample_type'] == 'single'){
        $sample_info[] = array(
            'Name' => $_POST['Name'],
            'Treatment_Name' => $_POST['Treatment_Name'],
            'Data_Type' => $_POST['Data_Type'],
            'Description' => $_POST['Description'],
            'File1' => $_POST['File1'],
            'File2' => $_POST['File2']
        );
    }

    if((! isset($_POST['sample_info']) || $_POST['sample_info'] == '') && isset($_FILES['fileupload']['tmp_name']) && file_exists($_FILES['fileupload']['tmp_name'])){
        $_POST['sample_info'] = file_get_contents($_FILES['fileupload']['tmp_name']);
    }

    if(isset($_POST['sample_info']) && $_POST['sample_info'] != ''){
        $rows = explode("\n", $_POST['sample_info']);
        $sample_header = explode("\t", array_shift($rows));
        foreach($rows as $i=>$row){
            $cols = explode("\t", $row);
            $row = array_pad($cols, count($sample_header), '');
            foreach($sample_header as $j=>$h){
                $sample_info[$i][$h] = $row[$j];
            }
        }
    }


    // Convert Sample Name and Treatment Name be valid for sure
    foreach($sample_info as $i=>$sample){
        $name = preg_replace("/[^\w\.]/", "", $sample['Name']);
        if($name == ''){
            unset($sample_info[$i]);
            continue;
        }

        $sample_info[$i]['Name'] = $name;
        $sample_info[$i]['Treatment_Name'] = preg_replace("/[^\w\.]/", "", $sample['Treatment_Name']);

        if($sample['Data_Type'] == 'PE') $sample_info[$i]['Files'] = 2;
        else $sample_info[$i]['Files'] = 1;

    }

    // Check if sample info are available in the database
    $errors = array();
    if(count($sample_info) <= 0){
        $errors[] = "<li>No valid sample information found.</li>";
    }
    else {
        foreach($sample_info as $i=>$sample){
            if($sample['Name'] == ''){
                $errors[] = "<li>The name of sample " . ($i+1) . " is missing.</li>";
            }
            else {
                $sql = "SELECT `ID` FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_SAMPLE'] . "` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `Name`= ?s";
            	$sample_id = intval($BXAF_MODULE_CONN->get_one($sql, $sample['Name']));

            	if($sample_id > 0){
            		$errors[] = "<li>Sample '" . $sample['Name'] . "' exists in the experiment.</li>";
            	}
            }
            if($sample['Treatment_Name'] == ''){
                $errors[] = "<li>Treatment_Name for sample " . ($i+1) . " is missing.</li>";
            }
        }
    }

    if(is_array($errors) && count($errors) > 0){
        echo "<h5 class='text-warning'>Errors found:</h5><ol>" . implode("", $errors) . "</ol>";
        exit();
    }

    $time = date("Y-m-d H:i:s");
    foreach($sample_info as $i=>$sample){

        //Create Sample Record
        $info = array(
        	'Experiment_ID' => $experiment_id,
        	'Name' => $sample['Name'],
        	'Treatment_Name' => $sample['Treatment_Name'],
        	'Data_Type' => $sample['Data_Type'],
        	'Description' => $sample['Description'],
            'Files' => $sample['Files'],
        	'_Owner_ID' => $BXAF_CONFIG['BXAF_USER_CONTACT_ID'],

            'Custom_Field1' => serialize($sample),

        	'Time_Added' => $time,
        	'Status_Time' => $time
        );
        $sample_id = $BXAF_MODULE_CONN->insert($BXAF_CONFIG['TBL_BXGENOMICS_SAMPLE'], $info);

        if($sample_id > 0){
            // Create data file record
            $file_size = 0;
            if($sample['File1'] != '' && file_exists($exp_dir . $sample['File1'])) {
                $file_size = shell_exec("stat -c %s " . realpath($exp_dir . $sample['File1']));
            }

            $name = $sample['File1'];

            $Category = '';
            if(preg_match("/\.fastq\.gz$/", $name))        $Category = 'fastq';
            else if(preg_match("/\.sorted\.bam$/", $name)) $Category = 'bam';
            else if(preg_match("/\.txt$/", $name))         $Category = 'gene_counts';

            $info = array(
                'Name' => $name,
                'Category' => $Category,
                'Dir' => $exp_dir . $sample['File1'],
                'Sample_ID' => $sample_id,
                'Read_Number' => 1,
                'Size' => $file_size,
                'Phred_Score' => 33,
                '_Owner_ID' => intval($BXAF_CONFIG['BXAF_USER_CONTACT_ID']),
                'Time_Added' => $time,
                'Status_Time' => $time,
                'Status' => ''
            );
            $BXAF_MODULE_CONN->insert($BXAF_CONFIG['TBL_BXGENOMICS_DATA'], $info);

            if($sample['Data_Type'] == 'PE'){

                $file_size = 0;
                if($sample['File2'] != '' && file_exists($exp_dir . $sample['File2'])) {
                    $file_size = shell_exec("stat -c %s " . realpath($exp_dir . $sample['File2']));
                }

                $name = $sample['File2'];

                $Category = '';
                if(preg_match("/\.fastq\.gz$/", $name))        $Category = 'fastq';
                else if(preg_match("/\.sorted\.bam$/", $name)) $Category = 'bam';
                else if(preg_match("/\.txt$/", $name))         $Category = 'gene_counts';

                $info = array(
                    'Name' => $name,
                    'Category' => $Category,
                    'Dir' => $exp_dir . $sample['File2'],
                    'Sample_ID' => $sample_id,
                    'Replicates' => 0,
                    'Read_Number' => 2,
                    'Size' => $file_size,
                    'Phred_Score' => 33,
                    '_Owner_ID' => intval($BXAF_CONFIG['BXAF_USER_CONTACT_ID']),
                    'Time_Added' => $time,
                    'Status_Time' => $time,
                    'Status' => ''
                );
                $BXAF_MODULE_CONN->insert($BXAF_CONFIG['TBL_BXGENOMICS_DATA'], $info);
            }

        }
    }


	exit();
}




/**
 * Edit Sample Info
 */

if(isset($_GET['action']) && $_GET['action'] == 'edit_sample_info') {

	$sample_id = intval($_POST['sample_id']);
    $experiment_id = intval($_POST['experiment_id']);

    $name = preg_replace("/[^\w\.]/", "", $_POST['Name']);
    $_POST['Treatment_Name'] = preg_replace("/[^\w\.]/", "", $_POST['Treatment_Name']);

	// Check Replicate Names
	$sql = "SELECT `ID` FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_SAMPLE'] . "` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `Experiment_ID` = ?i AND `Name`= ?s AND `ID` != ?i";
	$found_id = $BXAF_MODULE_CONN->get_one($sql, $experiment_id, $name, $sample_id);

	if($found_id > 0){
		echo "Error: the sample name '$name' is already used in current experiment.";
        exit();
	}

	$info = array(
		'Name' => $name,
		'Treatment_Name' => $_POST['Treatment_Name'],
		'Description' => $_POST['Description'],
		'Data_Type' => $_POST['Data_Type'],
		'Status_Time' => date("Y-m-d H:i:s")
	);
	$BXAF_MODULE_CONN->update($BXAF_CONFIG['TBL_BXGENOMICS_SAMPLE'], $info, "`ID`='$sample_id'");

	exit();
}




/**
 * Select Sample For Analysis
 */

if(isset($_GET['action']) && $_GET['action'] == 'select_analysis_sample'){

	// echo "_POST<pre>" . print_r($_POST, true) . "</pre>"; exit();

	$analysis_id = intval($_POST['analysis_id']);

    $data_ids = $_POST['data_ids'];
    if( ! is_array($data_ids) || count($data_ids) <= 0){
    	echo "Error: No data files selected.";
    	exit();
    }

    $data_type = '';
    $sql = "SELECT DISTINCT `Category` FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_DATA'] . "` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `ID` IN (?a) ";
	$data_id_categories = $BXAF_MODULE_CONN->get_col($sql, $data_ids );
    if(count($data_id_categories) != 1){
        echo "Error: You can not use different types of data files in a single analysis.";
    	exit();
    }
    else {
        $data_type = array_pop($data_id_categories);
        if($data_type == 'fastq'){
            $sql = "SELECT DISTINCT `Read_Number` FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_DATA'] . "` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `ID` IN (?a) ";
        	$read_numbers = $BXAF_MODULE_CONN->get_col($sql, $data_ids );
            if( count($read_numbers) == 1) $data_type = 'SE';
            else $data_type = 'PE';
        }
    }

    $sql = "SELECT `ID`, `Sample_ID` FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_DATA'] . "` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `ID` IN (?a) ";
	$data_sample_ids = $BXAF_MODULE_CONN->get_assoc('ID', $sql, $data_ids );

    $sample_ids = array_unique(array_values($data_sample_ids));
    $data_ids = array_keys($data_sample_ids);
    sort($sample_ids);
    sort($data_ids);

    if( ! is_array($data_ids) || count($data_ids) <= 0){
    	echo "Error: No data files selected.";
    	exit();
    }

	$info = array(
		'Samples' => implode(",", $sample_ids),
		'Data' => implode(",", $data_ids),
        'Data_Type' => $data_type
	);

	$result = $BXAF_MODULE_CONN->update($BXAF_CONFIG['TBL_BXGENOMICS_ANALYSIS'], $info, "`ID`=$analysis_id");

	if(! $result) echo 'The analysis can not be updated.';

	exit();
}



/**
 * New Analysis
 */

if(isset($_GET['action']) && $_GET['action'] == 'new_analysis'){

    // echo "_POST<pre>" . print_r($_POST, true) . "</pre>"; exit();

	$datetime = date("Y-m-d H:i:s");

    $data_ids = $_POST['data_ids'];
    if( ! is_array($data_ids) || count($data_ids) <= 0){
    	echo "Error: No data files selected.";
    	exit();
    }

    $sql = "SELECT DISTINCT `Sample_ID` FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_DATA'] . "` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `ID` IN (?a)";
    $sample_ids = $BXAF_MODULE_CONN -> get_col($sql, $data_ids);

	$step_detail = array( 0 => array(), 1 => array(), 2 => array(), 3 => array());

	$info = array(
		'Experiment_ID' => intval($_POST['experiment_id']),
		'Name' => $_POST['Name'],

		'Samples' => implode(",", $sample_ids),
		'Data' => implode(",", $data_ids),
        'Step_Detail' => serialize($step_detail),

		'Data_Type' => $_POST['Data_Type'],
		'Description' => $_POST['Description'],
		'Species' => $_POST['Species'],
		'_Owner_ID' => $BXAF_CONFIG['BXAF_USER_CONTACT_ID'],
        'Status' => 'Pending',
		'Time_Added' => $datetime,
		'Status_Time' => $datetime
	);

	$analysis_id = $BXAF_MODULE_CONN->insert($BXAF_CONFIG['TBL_BXGENOMICS_ANALYSIS'], $info);

	if($analysis_id > 0){
        $_SESSION['RECENT_ANALYSIS'] = $analysis_id;
        echo $analysis_id;
    }
    else echo "Error: can not save analysis information.";

	exit();

}





/**
 * Upload File -- 1. Drag & Drop
 */

else if(isset($_GET['action']) && $_GET['action'] == 'drop_file'){

    // Drop files for a sample
    if(isset($_GET['sampleid']) && intval($_GET['sampleid']) > 0 && ! empty($_FILES)){

        $record_id = intval($_GET['sampleid']);
        $uploads_dir = $BXAF_CONFIG['SAMPLE_DIR'] . $record_id . DIRECTORY_SEPARATOR;
        if (!file_exists( $uploads_dir )) {
    		mkdir($uploads_dir, 0777, true);
    	}

		$targetFile =  $uploads_dir . $_FILES['file']['name'];
        if(file_exists($targetFile)) unlink($targetFile);

		move_uploaded_file($_FILES['file']['tmp_name'], $targetFile);

        $name = $_FILES['file']['name'];

        $Category = '';
        if(preg_match("/\.fastq\.gz$/", $name))        $Category = 'fastq';
        else if(preg_match("/\.sorted\.bam$/", $name)) $Category = 'bam';
        else if(preg_match("/\.txt$/", $name))         $Category = 'gene_counts';

    	$info = array(
    		'Name' => $name,
            'Category' => $Category,
    		'Dir' => $targetFile,
    		'Sample_ID' => $record_id,
    		'Data_Type' => $_FILES['file']['type'],
    		'Size' => $_FILES['file']['size'],
    		'_Owner_ID' => $BXAF_CONFIG['BXAF_USER_CONTACT_ID'],
    		'Time_Added' => date("Y-m-d H:i:s"),
    		'Status_Time' => date("Y-m-d H:i:s"),
    		'Status' => ''
    	);
    	$BXAF_MODULE_CONN->insert($BXAF_CONFIG['TBL_BXGENOMICS_DATA'], $info);

    	// Auto assign read number
    	auto_assign_read_number($record_id);

    }

    // Drop files for an experiment
    else if(isset($_GET['expid']) && intval($_GET['expid']) > 0 && ! empty($_FILES)){

        $record_id = intval($_GET['expid']);
        $uploads_dir = $BXAF_CONFIG['BXGENOMICS_SERVER_FILES_PRIVATE'] . "Experiments" . DIRECTORY_SEPARATOR . $record_id . DIRECTORY_SEPARATOR;

    	if (!file_exists($uploads_dir)) {
    		mkdir($uploads_dir, 0777, true);
    	}

		$targetFile =  $uploads_dir . $_FILES['file']['name'];
        if(file_exists($targetFile)) unlink($targetFile);

		move_uploaded_file($_FILES['file']['tmp_name'], $targetFile);

    }

	exit();
}





/**
 * Upload File -- 2. Enter URL
 */

if(isset($_GET['action']) && $_GET['action'] == 'file_url'){

    $URLs = preg_split("/[\s,]+/", $_POST["URLs"], NULL, PREG_SPLIT_NO_EMPTY);
    foreach($URLs as $i=>$url){
        $url = trim($url);
        if(! filter_var($url, FILTER_VALIDATE_URL) || ! in_array(substr($url, 0, 4), array('ftp:', 'http')) ) unset($URLs[$i]);
    }

    if(count($URLs) <= 0) exit();

    //For single sample
    if(isset($_POST['sampleid']) && intval($_POST['sampleid']) > 0) {
        $record_id = intval($_POST['sampleid']);
        $uploads_dir = $BXAF_CONFIG['SAMPLE_DIR'] . $record_id . DIRECTORY_SEPARATOR;
    }

    //For single experiment
    else if(isset($_POST['expid']) && intval($_POST['expid']) > 0){
        $record_id = intval($_POST['expid']);
        $uploads_dir = $BXAF_CONFIG['BXGENOMICS_SERVER_FILES_PRIVATE'] . "Experiments" . DIRECTORY_SEPARATOR . $record_id . DIRECTORY_SEPARATOR;
    }

    else {
        exit();
    }


    if (!file_exists($uploads_dir)) {
        mkdir($uploads_dir, 0777, true);
    }


    $command_sh = "#!/usr/bin/bash\n";
	$command_sh .= "cd $uploads_dir \n";

    $file_names = array();
    foreach($URLs as $url){
        $name = basename(urldecode($url));
        $target = $uploads_dir . $name;
        if(file_exists($target)) continue;

        $file_names[] = $name;

        $Category = '';
        if(preg_match("/\.fastq\.gz$/", $name))        $Category = 'fastq';
        else if(preg_match("/\.sorted\.bam$/", $name)) $Category = 'bam';
        else if(preg_match("/\.txt$/", $name))         $Category = 'gene_counts';

        $command_sh .= "wget " . escapeshellcmd($url) . " > /dev/null 2>&1 \n";

        if(isset($_POST['sampleid']) && intval($_POST['sampleid']) > 0) {
            // Update Data Table
			$info = array(
				'Name' => $name,
                'Category' => $Category,
				'Dir' => $target,
				'Sample_ID' => $record_id,
				'_Owner_ID' => $BXAF_CONFIG['BXAF_USER_CONTACT_ID'],
				'Time_Added' => date("Y-m-d H:i:s"),
				'Status_Time' => date("Y-m-d H:i:s"),
				'Status' => ''
			);
			$BXAF_MODULE_CONN->insert($BXAF_CONFIG['TBL_BXGENOMICS_DATA'], $info);
        }
    }

    $filename = "wget_" . date('YmdHis');
    $command_folder = $BXAF_CONFIG['USER_FILES']['TOOL_CACHE'] . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'];
    if(! file_exists($command_folder)) mkdir($command_folder, 0777, true);
    file_put_contents("$command_folder/$filename", $command_sh);
    chmod("$command_folder/$filename", 0777);

    // Save Process
    $info_process = array(
        'Command' => "$command_folder/$filename",
        'Log_File' => "$command_folder/$filename" . ".log",
        'Dir' => $uploads_dir,
        'Files' => implode("\n", $file_names),
        '_Owner_ID' => $BXAF_CONFIG['BXAF_USER_CONTACT_ID']
    );
    $process_id = $BXAF_MODULE_CONN->insert($BXAF_CONFIG['TBL_BXGENOMICS_PROCESS'], $info_process);

    if($process_id > 0){
        echo $process_id;
        run_process_in_order();
    }

	exit();
}





/**
 * Upload File -- 3. Enter Location In Server
 */

if(isset($_GET['action']) && $_GET['action'] == 'file_server'){

    if(! array_key_exists("server_files", $_POST) || ! is_array($_POST["server_files"]) || count($_POST["server_files"]) <= 0) exit();

    //For single sample
    if(isset($_POST['sampleid']) && intval($_POST['sampleid']) > 0) {
        $record_id = intval($_POST['sampleid']);
        $uploads_dir = $BXAF_CONFIG['SAMPLE_DIR'] . $record_id . DIRECTORY_SEPARATOR;
    }
    //For single experiment
    else if(isset($_POST['expid']) && intval($_POST['expid']) > 0){
        $record_id = intval($_POST['expid']);
        $uploads_dir = $BXAF_CONFIG['BXGENOMICS_SERVER_FILES_PRIVATE'] . "Experiments" . DIRECTORY_SEPARATOR . $record_id . DIRECTORY_SEPARATOR;
    }
    else {
        exit();
    }

    if (!file_exists($uploads_dir)) {
        mkdir($uploads_dir, 0777, true);
    }


    $linux = new bxaf_linux($uploads_dir);

	foreach ($_POST['server_files'] as $file){

		if (file_exists($file)){
            $name = basename($file);

            $Category = '';
            if(preg_match("/\.fastq\.gz$/", $name))        $Category = 'fastq';
            else if(preg_match("/\.sorted\.bam$/", $name)) $Category = 'bam';
            else if(preg_match("/\.txt$/", $name))         $Category = 'gene_counts';

			// Make symbolic link
			$linux->execute("ln -s $file $name");

            if(isset($_POST['sampleid']) && intval($_POST['sampleid']) > 0) {
    			$info = array(
    				'Name' => $name,
                    'Category' => $Category,
    				'Dir' => $uploads_dir . $name,
    				'Sample_ID' => $record_id,
    				'Size' => filesize($file),
    				'_Owner_ID' => intval($BXAF_CONFIG['BXAF_USER_CONTACT_ID']),
    				'Time_Added' => date("Y-m-d H:i:s"),
    				'Status_Time' => date("Y-m-d H:i:s"),
    				'Status' => ''
    			);
    			$BXAF_MODULE_CONN->insert($BXAF_CONFIG['TBL_BXGENOMICS_DATA'], $info);

    			// Auto assign read number
    			auto_assign_read_number($record_id);
            }
		}
	}

	exit();
}





/**
 * Upload File -- 4. Select Server File
 */

if(isset($_GET['action']) && $_GET['action'] == 'file_server_select'){


    //For single sample
    if(isset($_POST['sampleid']) && intval($_POST['sampleid']) > 0) {
        $record_id = intval($_POST['sampleid']);
        $uploads_dir = $BXAF_CONFIG['SAMPLE_DIR'] . $record_id . DIRECTORY_SEPARATOR;
    }
    //For single experiment
    else if(isset($_POST['expid']) && intval($_POST['expid']) > 0){
        $record_id = intval($_POST['expid']);
        $uploads_dir = $BXAF_CONFIG['BXGENOMICS_SERVER_FILES_PRIVATE'] . "Experiments" . DIRECTORY_SEPARATOR . $record_id . DIRECTORY_SEPARATOR;
    }
    else {
        exit();
    }

    if (!file_exists($uploads_dir)) {
        mkdir($uploads_dir, 0777, true);
    }


    $server_files = array();
    $files = bxaf_list_files_only($BXAF_CONFIG['BXGENOMICS_SERVER_FILES_SHARED']);
    foreach($files as $key=>$value){
        if(is_array($_POST['server_files_selected_shared']) && in_array($key, $_POST['server_files_selected_shared'])) $server_files[] = $value;
    }
    $files = bxaf_list_files_only($BXAF_CONFIG['BXGENOMICS_SERVER_FILES_PRIVATE']);
    foreach($files as $key=>$value){
        if(is_array($_POST['server_files_selected_private']) && in_array($key, $_POST['server_files_selected_private'])) $server_files[] = $value;
    }

    if(count($server_files) > 0) {
        $linux = new bxaf_linux($uploads_dir);

    	// Loop Through 1st Level Folders and Files
    	foreach($server_files as $full_name){

            $name = basename($full_name);

            $Category = '';
            if(preg_match("/\.fastq\.gz$/", $name))        $Category = 'fastq';
            else if(preg_match("/\.sorted\.bam$/", $name)) $Category = 'bam';
            else if(preg_match("/\.txt$/", $name))         $Category = 'gene_counts';

    		$linux -> execute("ln -s $full_name $name");

            if(isset($_POST['sampleid']) && intval($_POST['sampleid']) > 0) {

    			$sql = "SELECT `ID` FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_DATA'] . "` WHERE `Sample_ID` = $record_id AND `Name` = ?s";
    			$data_check = $BXAF_MODULE_CONN -> get_one($sql, $name);

    			if($data_check <= 0){
                    $file_size = 0;
                    if(file_exists($full_name)) {
                        $file_size = shell_exec("stat -c %s " . realpath($full_name));
                    }

        			$info = array(
        				'Name' => $name,
                        'Category' => $Category,
        				'Dir' => $uploads_dir . $name,
        				'Sample_ID' => $record_id,
        				'Size' => $file_size,
        				'_Owner_ID' => intval($BXAF_CONFIG['BXAF_USER_CONTACT_ID']),
        				'Time_Added' => date("Y-m-d H:i:s"),
        				'Status_Time' => date("Y-m-d H:i:s"),
        				'Status' => ''
        			);

    				$BXAF_MODULE_CONN->insert($BXAF_CONFIG['TBL_BXGENOMICS_DATA'], $info);
    			}

    			// Auto assign read number
    			auto_assign_read_number($record_id);

            }
    	}

        echo '<h2><i class="fas fa-check-square text-success"></i> Message</h2><div class="lead p-3">' . count($server_files) . ' server files copied.</div>';
    }

	exit();
}





/**
 * Get File Uploading Status
 */

if(isset($_GET['action']) && $_GET['action'] == 'get_file_log'){

    $process_id = intval($_GET['process_id']);
    if($process_id <= 0) echo 1;

    $sql = "SELECT `Dir` FROM {$BXAF_CONFIG['TBL_BXGENOMICS_PROCESS']} WHERE `ID` = $process_id";
    $dir = $BXAF_MODULE_CONN->get_one($sql);

    $sql = "SELECT `Files` FROM {$BXAF_CONFIG['TBL_BXGENOMICS_PROCESS']} WHERE `ID` = $process_id";
    $files = explode("\n", $BXAF_MODULE_CONN->get_one($sql) );

    $finished = true;
    foreach($files as $file){
        if(! file_exists($dir . $file)) $finished = false;
    }

    $output = explode("\n", shell_exec("ps -A | grep wget"));
    if(count($output) >= 2) $finished = false;

    if($finished ) echo 1;

	exit();
}





/**
 * Terminate Process
 */

if(isset($_GET['action']) && $_GET['action'] == 'terminate_process'){

	terminate_process(intval($_POST['process_id']));

	exit();
}





/**
 * Get File Info Before Editing
 */

if(isset($_GET['action']) && $_GET['action'] == 'get_data_info'){

	$data_id = intval($_GET['data_id']);
    $sample_id = intval($_GET['sample_id']);
    $experiment_id = intval($_GET['experiment_id']);

	$sql = "SELECT * FROM `".$BXAF_CONFIG['TBL_BXGENOMICS_DATA']."` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `ID` = ?i";
	$data = $BXAF_MODULE_CONN->get_row($sql, $data_id);

    if(! is_array($data) || count($data) <= 0){
        $data = array();
        $data['Name'] = '';
        $data['Read_Number'] = 1;
        $data['Phred_Score'] = 33;
    }

	$uploads_dir = $BXAF_CONFIG['BXGENOMICS_SERVER_FILES_PRIVATE'] . "Experiments" . DIRECTORY_SEPARATOR . $experiment_id;

	$file_types = array(
		'fastq' => 'fastq files (.fastq.gz)',
		'bam' => 'Sorted and indexed bam files (.sorted.bam)',
		'gene_counts' => 'Gene counts (.txt)',
	);

	$files_grouped = array();
	foreach($file_types as $type=>$tname){
		$files_grouped[$type] = array();
	}

    $files = bxaf_list_files_only($uploads_dir);
	sort($files);
	foreach($files as $i=>$file){
		$found = false;
		foreach($file_types as $type=>$tname){
			if(
				$type == 'fastq' && preg_match("/\.fastq\.gz$/", $file) && ! preg_match("/\_L\d{3}\_R[12]\.fastq\.gz$/", $file) ||
				$type == 'bam' && preg_match("/\.sorted\.bam$/", $file) ||
				$type == 'gene_counts' && preg_match("/\.txt$/", $file) )
			{
				$files_grouped[$type][] = $file;
				$found = true;
			}
		}
		if(! $found) unset($files[$i]);
	}


    echo '<div class="w-100 text-danger">Note: You can <a class="font-weight-bold" href="experiment.php?id=' . $experiment_id . '">upload more data files</a> to current experiment.</div>';

    echo '<div class="w-100 mt-3 font-weight-bold">Data File: </div>';
    echo '<select class="custom-select" name="Name" id="Name_Data">';
    foreach($files_grouped as $type=>$files){
        echo "<optgroup label='" . $file_types[$type] . "'>";
        foreach($files as $file){
            $name = basename($file);
            echo "<option value='$name' " . ($data['Name'] == $name ? "selected" : "") . ">$name</option>";
        }
        echo '</optgroup>';
    }
    echo '</select>';

    echo '<div class="w-100 mt-3 font-weight-bold">Read Number: </div>';
    echo '<select class="custom-select" name="Read_Number" id="Read_Number">';
        echo "<option value='1' " . ($data['Read_Number'] == 1 ? "selected" : "") . ">1</option>";
        echo "<option value='2' " . ($data['Read_Number'] == 2 ? "selected" : "") . ">2</option>";
    echo '</select>';

    echo '<div class="w-100 mt-3 font-weight-bold">Phred Score: </div>';
    echo '<select class="custom-select" name="Phred_Score" id="Phred_Score">';
        echo "<option value='33' " . ($data['Phred_Score'] == 33 ? "selected" : "") . ">+33</option>";
        echo "<option value='64' " . ($data['Phred_Score'] == 64 ? "selected" : "") . ">+64</option>";
    echo '</select>';

	echo '<input name="data_id" id="data_id" value="'. $data_id .'" hidden>';
    echo '<input name="sample_id" id="sample_id" value="'. $sample_id .'" hidden>';
    echo '<input name="experiment_id" id="experiment_id" value="'. $experiment_id .'" hidden>';

	exit();

}






/**
 * Update File Info
 */

if(isset($_GET['action']) && $_GET['action'] == 'edit_data_info'){

    // echo "<pre>" . print_r($_POST, true) . "</pre>"; exit();

    $data_id = intval($_POST['data_id']);
    $sample_id = intval($_POST['sample_id']);
    $experiment_id = intval($_POST['experiment_id']);

    $name = $_POST['Name'];

    $Category = '';
    if(preg_match("/\.fastq\.gz$/", $name))        $Category = 'fastq';
    else if(preg_match("/\.sorted\.bam$/", $name)) $Category = 'bam';
    else if(preg_match("/\.txt$/", $name))         $Category = 'gene_counts';


    if($data_id > 0){
        $sql = "SELECT `ID` FROM `".$BXAF_CONFIG['TBL_BXGENOMICS_DATA']."` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `ID` = ?i";
    	$new_data_id = $BXAF_MODULE_CONN->get_one($sql, $data_id);
        if($new_data_id == ''){
            echo "<div class='w-100 my-3 text-danger'>Error: the data record is not found.</div>";
            exit();
        }
    }


	$uploads_dir = $BXAF_CONFIG['BXGENOMICS_SERVER_FILES_PRIVATE'] . "Experiments" . DIRECTORY_SEPARATOR . $experiment_id;

    if(! file_exists("$uploads_dir/$name")){
        echo "<div class='w-100 my-3 text-danger'>File '$name' is not found in current experiment.</div>";
        exit();
    }

	$info = array(
        'Name'=>$name,
        'Category'=>$Category,
        'Sample_ID'=>$sample_id,
        'Dir'=>"$uploads_dir/$name",
        'Read_Number'=>intval($_POST['Read_Number']),
        'Phred_Score'=>intval($_POST['Phred_Score']),
        'Size'=>filesize(realpath("$uploads_dir/$name")),
    );

    if($data_id > 0){
        $BXAF_MODULE_CONN->update($BXAF_CONFIG['TBL_BXGENOMICS_DATA'], $info, "`ID`=$data_id");
    }
    else {
        $BXAF_MODULE_CONN->insert($BXAF_CONFIG['TBL_BXGENOMICS_DATA'], $info);
    }

	exit();
}





/**
 * Admin Update Program Dir
 */

if(isset($_GET['action']) && $_GET['action'] == 'admin_change_dir'){

	$keyword = $_POST['pk'];
	$value = $_POST['value'];
	$info = array(
		'Detail' => trim($value)

	);
	$BXAF_MODULE_CONN -> update($BXAF_CONFIG['TBL_BXGENOMICS_SETTING'], $info, "`Keyword`='$keyword'");

	exit();
}





/**
 * Admin Update File Dir
 */

if(isset($_GET['action']) && $_GET['action'] == 'admin_change_dir_file'){

	$pos = strpos($_POST['pk'], ", ");
	$category = substr($_POST['pk'], 0, $pos);
	$keyword = substr($_POST['pk'], $pos + 2);

	$value = $_POST['value'];
	$info = array(
		'Detail' => $value
	);

	$BXAF_MODULE_CONN -> update($BXAF_CONFIG['TBL_BXGENOMICS_SETTING'], $info, "`Keyword`='$keyword' AND `Category`='$category'");

	exit();
}





// Delete Experiment, Sample & Analysis
if(isset($_GET['action']) && $_GET['action'] == 'delete_record'){

    $record_id = intval($_POST['rowid']);
    if($record_id <= 0){
        exit();
    }

    $record_ids = array($record_id);

    $info = array( 'bxafStatus' => 9 );

    if ($_POST['type'] == 'experiment'){

        $sql = "SELECT `ID` FROM `".$BXAF_CONFIG['TBL_BXGENOMICS_SAMPLE']."` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `Experiment_ID` IN (?a)";
        $sample_ids = $BXAF_MODULE_CONN -> get_col($sql, $record_ids);
        if(is_array($sample_ids) && count($sample_ids) > 0){
            $sql = "SELECT `ID` FROM `".$BXAF_CONFIG['TBL_BXGENOMICS_DATA']."` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `Sample_ID` IN (?a)";
            $data_ids = $BXAF_MODULE_CONN -> get_col($sql, $sample_ids);
            if(is_array($data_ids) && count($data_ids) > 0){
                $BXAF_MODULE_CONN->update($BXAF_CONFIG['TBL_BXGENOMICS_DATA'], $info, "`ID` IN (" . implode(",", $data_ids) . ")");
            }
            $BXAF_MODULE_CONN->update($BXAF_CONFIG['TBL_BXGENOMICS_SAMPLE'], $info, "`ID` IN (" . implode(",", $sample_ids) . ")");
        }

        $sql = "SELECT `ID` FROM `".$BXAF_CONFIG['TBL_BXGENOMICS_ANALYSIS']."` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `Experiment_ID` IN (?a)";
        $analysis_ids = $BXAF_MODULE_CONN -> get_col($sql, $record_ids);
        if(is_array($analysis_ids) && count($analysis_ids) > 0){
            $BXAF_MODULE_CONN->update($BXAF_CONFIG['TBL_BXGENOMICS_ANALYSIS'], $info, "`ID` IN (" . implode(",", $analysis_ids) . ")");
        }

        $BXAF_MODULE_CONN->update($BXAF_CONFIG['TBL_BXGENOMICS_EXPERIMENT'], $info, "`ID` IN (" . implode(",", $record_ids) . ")");
    }

    else if ($_POST['type'] == 'sample'){
        $sql = "SELECT `ID` FROM `".$BXAF_CONFIG['TBL_BXGENOMICS_DATA']."` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `Sample_ID` IN (?a)";
        $data_ids = $BXAF_MODULE_CONN -> get_col($sql, $record_ids);
        if(is_array($data_ids) && count($data_ids) > 0){
            $BXAF_MODULE_CONN->update($BXAF_CONFIG['TBL_BXGENOMICS_DATA'], $info, "`ID` IN (" . implode(",", $data_ids) . ")");
        }
        $BXAF_MODULE_CONN->update($BXAF_CONFIG['TBL_BXGENOMICS_SAMPLE'], $info, "`ID` IN (" . implode(",", $record_ids) . ")");
    }

    else if ($_POST['type'] == 'analysis'){
        $BXAF_MODULE_CONN->update($BXAF_CONFIG['TBL_BXGENOMICS_ANALYSIS'], $info, "`ID` IN (" . implode(",", $record_ids) . ")");
    }

    else if ($_POST['type'] == 'data'){
        $BXAF_MODULE_CONN->update($BXAF_CONFIG['TBL_BXGENOMICS_DATA'], $info, "`ID` IN (" . implode(",", $record_ids) . ")");
    }

	exit();
}

if(isset($_GET['action']) && $_GET['action'] == 'delete_multiple_records'){

    $record_ids = explode(",", $_POST['ids']);

    foreach($record_ids as $i=>$id){
        if(intval($id) <= 0) unset($record_ids[$i]);
        else $record_ids[$i] = intval($id);
    }
    if(! is_array($record_ids) || count($record_ids) <= 0){
        exit();
    }

    $info = array( 'bxafStatus' => 9 );
    if ($_POST['type'] == 'experiment'){

        $sql = "SELECT `ID` FROM `".$BXAF_CONFIG['TBL_BXGENOMICS_SAMPLE']."` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `Experiment_ID` IN (?a)";
        $sample_ids = $BXAF_MODULE_CONN -> get_col($sql, $record_ids);
        if(is_array($sample_ids) && count($sample_ids) > 0){
            $sql = "SELECT `ID` FROM `".$BXAF_CONFIG['TBL_BXGENOMICS_DATA']."` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `Sample_ID` IN (?a)";
            $data_ids = $BXAF_MODULE_CONN -> get_col($sql, $sample_ids);
            if(is_array($data_ids) && count($data_ids) > 0){
                $BXAF_MODULE_CONN->update($BXAF_CONFIG['TBL_BXGENOMICS_DATA'], $info, "`ID` IN (" . implode(",", $data_ids) . ")");
            }
            $BXAF_MODULE_CONN->update($BXAF_CONFIG['TBL_BXGENOMICS_SAMPLE'], $info, "`ID` IN (" . implode(",", $sample_ids) . ")");
        }

        $sql = "SELECT `ID` FROM `".$BXAF_CONFIG['TBL_BXGENOMICS_ANALYSIS']."` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `Experiment_ID` IN (?a)";
        $analysis_ids = $BXAF_MODULE_CONN -> get_col($sql, $record_ids);
        if(is_array($analysis_ids) && count($analysis_ids) > 0){
            $BXAF_MODULE_CONN->update($BXAF_CONFIG['TBL_BXGENOMICS_ANALYSIS'], $info, "`ID` IN (" . implode(",", $analysis_ids) . ")");
        }

        $BXAF_MODULE_CONN->update($BXAF_CONFIG['TBL_BXGENOMICS_EXPERIMENT'], $info, "`ID` IN (" . implode(",", $record_ids) . ")");
    }

    else if ($_POST['type'] == 'sample'){
        $sql = "SELECT `ID` FROM `".$BXAF_CONFIG['TBL_BXGENOMICS_DATA']."` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `Sample_ID` IN (?a)";
        $data_ids = $BXAF_MODULE_CONN -> get_col($sql, $record_ids);
        if(is_array($data_ids) && count($data_ids) > 0){
            $BXAF_MODULE_CONN->update($BXAF_CONFIG['TBL_BXGENOMICS_DATA'], $info, "`ID` IN (" . implode(",", $data_ids) . ")");
        }
        $BXAF_MODULE_CONN->update($BXAF_CONFIG['TBL_BXGENOMICS_SAMPLE'], $info, "`ID` IN (" . implode(",", $record_ids) . ")");
    }

    else if ($_POST['type'] == 'analysis'){
        $BXAF_MODULE_CONN->update($BXAF_CONFIG['TBL_BXGENOMICS_ANALYSIS'], $info, "`ID` IN (" . implode(",", $record_ids) . ")");
    }

    else if ($_POST['type'] == 'data'){
        $BXAF_MODULE_CONN->update($BXAF_CONFIG['TBL_BXGENOMICS_DATA'], $info, "`ID` IN (" . implode(",", $record_ids) . ")");
    }

	exit();
}





/**
 * Edit Experiment Information
 */

if(isset($_GET['action']) && $_GET['action'] == 'edit_experiment_info'){

	$experiment_id = intval($_POST['experiment_id']);

	if(trim($_POST['experiment_name']) == ''){
		echo "Experiment Name is required!";
		exit();
	}
	else {
		$sql = "SELECT `ID`
				FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_EXPERIMENT'] . "`
				WHERE " . $BXAF_CONFIG['QUERY_OWNER_FILTER'] . " AND `Name` = ?s AND `ID` != ?i";
		$found = $BXAF_MODULE_CONN->get_one($sql, $_POST['experiment_name'], $experiment_id);
		if($found > 0){
			echo "An experiment with same name is already in the system! Please update with a different name.";
			exit();
		}
	}

	$info = array(
		'Name' => $_POST['experiment_name'],
		'Description' => $_POST['experiment_description'],
		'Last_Updated' => date("Y-m-d H:i:s")
	);

    $result = $BXAF_MODULE_CONN->update($BXAF_CONFIG['TBL_BXGENOMICS_EXPERIMENT'], $info, "`ID` = $experiment_id");
	if(! $result){
		echo "Error in updating experiment information.";
	}

	exit();
}




/**
 * Edit Analysis Information
 */

if(isset($_GET['action']) && $_GET['action'] == 'edit_analysis_info'){

	$analysis_id = intval($_POST['analysis_id']);

	if(trim($_POST['analysis_name']) == ''){
		echo "Analysis Name is required!";
		exit();
	}
	else {
		$sql = "SELECT `ID` FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_ANALYSIS'] . "` WHERE " . $BXAF_CONFIG['QUERY_OWNER_FILTER'] . " AND `Name` = ?s AND `ID` != ?i";
		$found = $BXAF_MODULE_CONN->get_one($sql, $_POST['analysis_name'], $analysis_id );
		if($found > 0){
			echo "An analysis with same name is already in the system! Please update with a different name.";
			exit();
		}
	}

	$info = array(
		'Name' => $_POST['analysis_name'],
		'Description' => $_POST['analysis_description'],
		'Species' => $_POST['analysis_species'],
		'Status_Time' => date("Y-m-d H:i:s")
	);

	if($BXAF_MODULE_CONN->update($BXAF_CONFIG['TBL_BXGENOMICS_ANALYSIS'], $info, "`ID`=$analysis_id")){
		echo $analysis_id;
	}
	else {
		echo "Error in updating analysis information.";
	}

	exit();
}




if (isset($_GET['action']) && $_GET['action'] == 'save_dashboard_options') {

    // echo "<pre>" . print_r($_POST, true) . "</pre>"; exit();

    $_SESSION['Dashboard'] = array();

    $_SESSION['Dashboard']['ComparisonCategory_Show_Top_15'] = 0;
    $_SESSION['Dashboard']['Case_Tissue_Show_Top_15'] = 0;
    $_SESSION['Dashboard']['Case_DiseaseState_Show_Top_15'] = 0;
    $_SESSION['Dashboard']['Case_Treatment_Show_Top_15'] = 0;
    $_SESSION['Dashboard']['PlatformName_Show_Top_15'] = 0;

    $_SESSION['Dashboard']['Case_Tissue_Hide_Unknown'] = 0;
    $_SESSION['Dashboard']['Case_Tissue_Hide_Others'] = 0;
    $_SESSION['Dashboard']['Case_DiseaseState_Hide_Unknown'] = 0;
    $_SESSION['Dashboard']['Case_DiseaseState_Hide_Normal_Control'] = 0;
    $_SESSION['Dashboard']['Case_DiseaseState_Hide_Others'] = 0;
    $_SESSION['Dashboard']['Case_Treatment_Hide_Unknown'] = 0;
    $_SESSION['Dashboard']['Case_Treatment_Hide_Others'] = 0;
    $_SESSION['Dashboard']['PlatformName_Hide_Generic'] = 0;

    foreach($_POST['Dashboard'] as $k=>$v){
        $_SESSION['Dashboard'][$k] = $v;
    }

    $info = array(
        '_Owner_ID'   => $BXAF_CONFIG['BXAF_USER_CONTACT_ID'],
        'Category'    => 'Dashboard Options',
        'Detail'      => serialize($_SESSION['Dashboard']),
        'Time'        => time()
    );
    $BXAF_MODULE_CONN -> insert($BXAF_CONFIG['TBL_BXGENOMICS_USERPREFERENCE'], $info);

 	exit();
 }




 if (isset($_GET['action']) && $_GET['action'] == 'save_View_NGS_in_TPM') {

     if($_GET['value'] == 'true') $_SESSION['View_NGS_in_TPM'] = 'TPM';
     else $_SESSION['View_NGS_in_TPM'] = 'FPKM';

     $info = array(
         '_Owner_ID'   => $BXAF_CONFIG['BXAF_USER_CONTACT_ID'],
         'Category'    => 'View_NGS_in_TPM',
         'Detail'      => $_SESSION['View_NGS_in_TPM'],
         'Time'        => time()
     );
     $BXAF_MODULE_CONN -> insert($BXAF_CONFIG['TBL_BXGENOMICS_USERPREFERENCE'], $info);

     exit();
 }


?>