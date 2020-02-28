<?php

//To disable login requirement
//$BXAF_CONFIG_CUSTOM['PAGE_LOGIN_REQUIRED'] = false;

include_once(dirname(__FILE__) . "/config/config.php");


if(! $_SESSION['BXAF_ADVANCED_USER']){
	header("Location: index.php");
	exit();
}


$file_types = array(
	'fastq' => 'fastq files (.fastq.gz)',
	'bam' => 'Sorted and indexed bam files (.sorted.bam)',
	'gene_counts' => 'Gene counts (.txt)',
);



// Check experiment exists
$experiment_id = intval($_GET['expid']);

$sql = "SELECT `Name` FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_EXPERIMENT'] . "` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `ID` = ?i";
$experiment_name = $BXAF_MODULE_CONN->get_one($sql, $experiment_id);
if($experiment_name == ''){
	header("Location: experiments.php");
	exit();
}


$exp_dir = $BXAF_CONFIG['BXGENOMICS_SERVER_FILES_PRIVATE'] . "Experiments" . DIRECTORY_SEPARATOR . $experiment_id . DIRECTORY_SEPARATOR;
$link = $BXAF_CONFIG['BXAF_SYSTEM_URL'] . 'bxfiles/folder.php?f=' . bxaf_encrypt($exp_dir, $BXAF_CONFIG['BXAF_KEY']);


// List all files
$exp_files = bxaf_list_files_only($exp_dir);
sort($exp_files);

$files_grouped = array();
foreach($file_types as $type=>$tname) $files_grouped[$type] = array();
foreach($exp_files as $i=>$file){
	$found = false;
	foreach($file_types as $type=>$tname){
		if(
			$type == 'fastq' && preg_match("/\.fastq\.gz$/", $file) ||
			$type == 'bam' && preg_match("/\.sorted\.bam$/", $file) ||
			$type == 'gene_counts' && preg_match("/\.txt$/", $file) )
		{
			$files_grouped[$type][] = $file;
			$found = true;
		}
	}
	if(! $found) unset($exp_files[$i]);
}
foreach($files_grouped as $type=>$files){
	if(! is_array($files) || count($files) <= 0){
		unset($files_grouped[$type]);
	}
}
// echo "files_grouped<pre>" . print_r($files_grouped, true) . "</pre>"; exit();


$sample_info_all = array();

// fastq
	$sample_info = array();
	if(array_key_exists('fastq', $files_grouped) && is_array($files_grouped['fastq']) && count($files_grouped['fastq']) > 0){

		// Find data files only
		$data_file_names = array();
		$n_files = 0;
		$data_files = array();
		foreach($files_grouped['fastq'] as $file){
			$n_files++;
			$name = basename($file);
			$data_file_names[$n_files] = $name;
			$name = preg_replace("/(\.fastq|\.fq)?\.gz$/", "", $name);
			$array = explode('_', $name);
			$data_files[$n_files] = $array;
		}

		$processed_files = array();

		// Find pairs
		$data_files_paired = array();
		$processed_files = array();
		$n_pairs = 0;
		foreach($data_file_names as $i=>$name){
			if(in_array($i, $processed_files)) continue;

			foreach($data_file_names as $j=>$name1){
				if(in_array($j, $processed_files)) continue;

				if(count($data_files[$i]) == count($data_files[$j])){
					$n_diff = 0;
					$n_diff_k = -1;

					for($k = count($data_files[$i])-1; $k >= 0; $k--){
						if($data_files[$i][$k] != $data_files[$j][$k]){
							$n_diff++;
							$n_diff_k = $k;
						}
					}

					if($n_diff == 1 && preg_match("/^R?[12]$/", $data_files[$i][$n_diff_k]) && preg_match("/^R?[12]$/", $data_files[$j][$n_diff_k])){
						$n_pairs++;
						$data_files_paired[$n_pairs] = array($i, $j);
						$processed_files[] = $i;
						$processed_files[] = $j;

						break;
					}
				}
			}
		}

		$data_files_not_paired = array_values(array_diff(array_keys($data_file_names), array_values($processed_files)));

		foreach($data_files_paired as $i=>$pair){
			if( preg_match("/_\d+_R1(\.fastq|\.fq)?\.gz$/", $data_file_names[$pair[0]]) && preg_match("/_\d+_R1(\.fastq|\.fq)?\.gz$/", $data_file_names[$pair[1]]) ||
				preg_match("/_\d+_R2(\.fastq|\.fq)?\.gz$/", $data_file_names[$pair[0]]) && preg_match("/_\d+_R2(\.fastq|\.fq)?\.gz$/", $data_file_names[$pair[1]])
			){
				$data_files_not_paired[] = $pair[0];
				$data_files_not_paired[] = $pair[1];
				unset($data_files_paired[$i]);
			}
		}

		$samples = array_values(array_merge($data_files_not_paired, $data_files_paired));

		foreach($samples as $i=>$v){

			$name = '';
			if(is_numeric($v)) $name = $data_file_names[$v];
			else if(is_array($v) && count($v) == 2) $name = $data_file_names[$v[0]];

			$name = preg_replace("/[_\.]R[12]\.fastq\.gz$/", "", $name);

			if($name != ''){
				$sample_info[$i]['Name'] = $name;
				$sample_info[$i]['Description'] = $name;
				$sample_info[$i]['Treatment_Name'] = array_shift(explode("_", $name));
				$sample_info[$i]['Data_Type'] = is_numeric($v) ? 'SE' : 'PE';

				$sample_info[$i]['File1'] = is_numeric($v) ? $data_file_names[$v] : $data_file_names[$v[0]];
				$sample_info[$i]['File2'] = is_numeric($v) ? '' : $data_file_names[$v[1]];
			}
		}
	}
	$sample_info_all['fastq'] = $sample_info;



// Bam
	$sample_info = array();
	if(array_key_exists('bam', $files_grouped) && is_array($files_grouped['bam']) && count($files_grouped['bam']) > 0){
		foreach($files_grouped['bam'] as $i=>$file){

			$name = basename($file);
			$name = preg_replace("/\.sorted\.bam$/", "", $name);

			if($name != ''){
				$sample_info[$i]['Name'] = $name;
				$sample_info[$i]['Description'] = $name;
				$sample_info[$i]['Treatment_Name'] = array_shift(explode("_", $name));

				$sample_info[$i]['Data_Type'] = 'bam';
				$sample_info[$i]['File1'] = basename($file);
				$sample_info[$i]['File2'] = '';

			}

		}
	}
	$sample_info_all['bam'] = $sample_info;


// gene counts
	$sample_info = array();
	if(array_key_exists('gene_counts', $files_grouped) && is_array($files_grouped['gene_counts']) && count($files_grouped['gene_counts']) > 0){
		$i = 0;
		foreach($files_grouped['gene_counts'] as $file){
			if (($handle = fopen($file, "r")) !== FALSE) {
			    while (($row = fgetcsv($handle, 0, "\t")) !== FALSE) {

					if(count($row) < 6) continue;

					$names = array_slice($row, 6);

					foreach($names as $name){
						$name = preg_replace("/\.sorted\.bam$/", "", $name);

						if($name != ''){

							$sample_info[$i]['Name'] = $name;
							$sample_info[$i]['Description'] = $name;
							$sample_info[$i]['Treatment_Name'] = array_shift(explode("_", $name));

							$sample_info[$i]['Data_Type'] = 'gene_counts';
							$sample_info[$i]['File1'] = basename($file);
							$sample_info[$i]['File2'] = '';

							$i++;
						}
					}

					break;

			    }
			    fclose($handle);
			}
		}
	}
	$sample_info_all['gene_counts'] = $sample_info;


$settings_file_type = '';
$sample_info = array();
if(isset($_GET['type']) && array_key_exists($_GET['type'], $file_types) ){
	$settings_file_type = $_GET['type'];
	$sample_info = $sample_info_all[ $settings_file_type ];
}
else {
	foreach($sample_info_all as $settings_file_type=>$sample_info){
		if(is_array($sample_info) && count($sample_info) > 0){
			break;
		}
	}
}


if(! is_array($sample_info) || count($sample_info) <= 0){
	header("Location: experiment.php?id=$experiment_id");
}


?><!DOCTYPE html>
<html lang="en">
<head>
	<?php include_once($BXAF_CONFIG['BXAF_PAGE_HEADER']); ?>
	<script src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/jquery/jquery.form.min.js"></script>

</head>
<body>
	<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_MENU'])) include_once($BXAF_CONFIG['BXAF_PAGE_MENU']); ?>

	<div id="bxaf_page_wrapper" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_WRAPPER']; ?>">

		<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_LEFT'])) include_once($BXAF_CONFIG['BXAF_PAGE_LEFT']); ?>

		<div id="bxaf_page_right" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT']; ?>">

			<div id="bxaf_page_right_content" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT_CONTENT']; ?>">





<div class="container-fluid">

	<h3 class="my-4">Create Samples Based on Available Data Files</h3>

	<div class="w-100 my-3 w-100">
		<span class=""><i class='fas fa-star text-success'></i> Current Experiment: <a class='lead' href='experiment.php?id=<?php echo $experiment_id; ?>' target='_blank'><?php echo $experiment_name; ?></a></span>
		<span class="ml-5"><a href='<?php echo $link; ?>' target='_blank'><i class='fas fa-file'></i> Manage experiment files</a></span>
		<span class="ml-5"><a href='<?php echo $_SERVER['PHP_SELF'] . '?expid=' . $_GET['expid']; ?>'><i class='fas fa-sync'></i> Refresh</a></span>
	</div>



	<hr class="w-100 my-2" />

	<form id="form_options" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get" class="form-inline my-3 table-warning p-2">
		<input name="expid" value="<?php echo $experiment_id; ?>" hidden>
		<div id="div_data_type" class="form-inline">
			<label class="font-weight-bold">Data file type: </label>
			<select name="type" id="type" class="custom-select mx-2">
				<?php
					foreach($files_grouped as $type=>$files) {
						$name =  $file_types[$type];
						echo "<option value='$type' " . ($_GET['type'] == $type ? 'selected' : '') . ">$name</option>";
					}
				?>
			</select>
		</div>
		<button class="mx-2 btn btn-info" type="submit">Change Options</button>
	</form>




	<hr class="w-100 my-2" />

	<div class="w-100 my-2"><span class="text-danger">Important</span>: <strong>Name</strong> and <strong>Treatment_Name</strong> are required and can only contain letters, digits, underscore, and dot. You can copy/paste into Excel to edit.</div>


	<form id="form_sample_auto" role="form">
		<input name="experiment_id" value="<?php echo $experiment_id; ?>" hidden>

		<div class="w-100 my-2">
			<input id="fileupload" name="fileupload" type="file" />
		</div>
		<div class="w-100 my-2">
			<textarea name="sample_info" id="sample_info" class="form-control" rows="10"><?php
				$sample_keys = array('Name', 'Description', 'Treatment_Name', 'Data_Type', 'File1', 'File2');
				echo implode("\t", $sample_keys) . "\n";
				foreach($sample_info as $i=>$vals){
					echo implode("\t", $vals) . "\n";
				}
			?></textarea>
		</div>

		<div class="w-100 my-2">
			<button type="submit" class="btn btn-primary">Create Sample and Data File Records</button>
		</div>
	</form>



<script type="text/javascript">

$(document).ready(function(){

	$('#fileupload').change(function(){
		var reader = new FileReader();
		reader.onload = function (e) {
			$("#sample_info").text( e.target.result );
		};
		reader.readAsText( $("#fileupload").prop('files')[0] );
	});

	var options = {
		url: 'bxgenomics_exe.php?action=new_sample_auto',
		type: 'post',
		beforeSubmit: function(formData, jqForm, options) {
			if( $('#sample_info').html() == ''){
				bootbox.alert("Please enter sample information.");
				return false;
			}
			return true;
		},
		success: function(responseText){
			if( responseText == '' ){
				bootbox.alert('<h2><i class="fas fa-check-square text-success"></i> Message</h2><div class="lead p-3">The sample information has been saved.</div>', function(){
					window.location = "experiment.php?id=<?php echo $experiment_id; ?>";
				});
			}
			else {
				bootbox.alert('<h2><i class="fas fa-exclamation-triangle text-warning"></i> Warning</h2><div class="lead p-3">' + responseText + '</div>');
			}
			return true;
		}
	};
	$('#form_sample_auto').ajaxForm(options);

});

</script>



</div>





		    <?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>

		</div>

	</div>

</body>
</html>