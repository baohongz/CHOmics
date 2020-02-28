<?php

include_once(__DIR__ . "/config/config.php");

if(! $_SESSION['BXAF_ADVANCED_USER']){
	header("Location: index.php");
	exit();
}


$experiment_id = intval($_GET['expid']);
$sql = "SELECT `Name` FROM `".$BXAF_CONFIG['TBL_BXGENOMICS_EXPERIMENT']."` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `ID` = ?i";
$experiment_name = $BXAF_MODULE_CONN -> get_one($sql, $experiment_id);

if($experiment_name == ''){
	header("Location: experiments.php");
	exit();
}

$sample_types = array(
	'PE' => 'fastq, Paired-end (PE)',
	'SE' => 'fastq, Single-end (SE)',
	'bam' => 'bam, sorted and indexed (.sorted.bam)',
	'gene_counts' => 'Gene counts (.txt)',
);



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




	<h3 class="my-2">Add samples in experiment <a class="" href="experiment.php?id=<?php echo $experiment_id; ?>"><?php echo $experiment_name; ?></a></h3>
	<hr class="w-100" />

	<div class="card-deck">

		<div class="card" id="card_new_sample">
			<div class="card-header bg-info text-white lead">Enter Sample Information</div>

			<div class="card-body">

				<form id="form_new_sample" role="form">

					<div class="form-group">
						<label class="font-weight-bold">* Sample Name:</label>

						<div class="">
							<input name="Name" id="Name" placeholder="Alphanumeric characters and underline only" class="form-control" required>
						</div>
					</div>

					<div class="form-group">
						<label class="font-weight-bold">* Treatment Name:</label>

						<div class="">
							<input name="Treatment_Name" id="Treatment_Name" placeholder="Alphanumeric characters and underline only" class="form-control" required>
						</div>
					</div>

					<div class="form-group">
						<label class="font-weight-bold">* Data Type:</label>

						<div class="">
							<select name="Data_Type" id="Data_Type" class="custom-select">
								<?php
									foreach($sample_types as $k=>$v){
										echo '<option value="'. $k .'">'. $v .'</option>';
									}
								?>
							</select>
						</div>
					</div>

					<div class="form-group">
						<label class="font-weight-bold">* Data File 1: <a class="ml-5" href="experiment.php?id=<?php echo $experiment_id; ?>"><i class="fas fa-angle-double-right"></i> Manage Experiment Files</a></label>

						<div class="">
							<select name="File1" id="File1" class="custom-select">
								<option value='' selected>(Select a file available in current experiment)</option>
								<?php
								foreach($files_grouped as $type=>$files){
							        echo "<optgroup label='" . $file_types[$type] . "'>";
							        foreach($files as $file){
							            $name = basename($file);
							            echo "<option value='$name'>$name</option>";
							        }
							        echo '</optgroup>';
							    }
								?>
							</select>
						</div>
					</div>

					<div class="form-group">
						<label class="font-weight-bold">Data File 2: <a class="ml-5" href="experiment.php?id=<?php echo $experiment_id; ?>"><i class="fas fa-angle-double-right"></i> Manage Experiment Files</a></label>

						<div class="">
							<select name="File2" id="File2" class="custom-select">
								<option value='' selected>(Select a file available in current experiment)</option>
								<?php
								foreach($files_grouped as $type=>$files){
							        echo "<optgroup label='" . $file_types[$type] . "'>";
							        foreach($files as $file){
							            $name = basename($file);
							            echo "<option value='$name'>$name</option>";
							        }
							        echo '</optgroup>';
							    }
								?>
							</select>
						</div>
					</div>

					<div class="form-group">
						<label class="font-weight-bold">Description:</label>
						<div class="">
							<textarea name="Description" id="Description" class="form-control p-2" rows="3"></textarea>
						</div>
					</div>


					<div class="">
						<input name="sample_type" value="single" hidden>
						<input name="experiment_id" value="<?php echo $experiment_id; ?>" hidden>
						<button type="submit" class="btn btn-primary mr-3 btn_submit"><i class="fas fa-save"></i> Submit</button>
						<i class="fas fa-spinner fa-pulse icon_busy" hidden></i>
					</div>

				</form>

			</div>
		</div>

		<div class="card" id="card_new_sample_bulk">
			<div class="card-header bg-success text-white lead">Bulk Upload</div>
			<div class="card-body">

				<form id="form_new_sample_bulk" enctype="multipart/form-data" role="form">

					<div class="form-group">
						<label class="font-weight-bold">Upload File (<span class="text-danger">Tab-delimited</span>):</label>

						<div class="">
							<a href="files/upload_samples.txt" download><i class="fas fa-hand-point-right"></i> Download Example File</a></p>
							<input id="fileupload" name="fileupload" type="file" />
						</div>
					</div>

					<div class="form-group mt-5">
						<label class="font-weight-bold">Or, copy and paste from Excel:</label>
						<div class="">
							You can also copy from Excel and paste it below (<span class="text-danger">include header row</span>):
						</div>
						<div class="">
							<textarea name="sample_info" id="sample_info" rows="14" class="form-control mt-3"></textarea>
						</div>
					</div>

					<div class="form-group">
						<input name="sample_type" value="bulk" hidden>
						<input name="experiment_id" value="<?php echo $experiment_id; ?>" hidden>
						<button type="submit" class="btn btn-success mr-3 btn_submit"><i class="fas fa-upload"></i> Upload</button>
						<i class="fas fa-spinner fa-pulse icon_busy" hidden></i>
						<a href="javascript: void(0);" id="upload_textarea_example"><i class="fas fa-hand-point-right"></i> Use Example</a>
						<a class="mx-2" href="javascript: void(0);" onclick="$('#sample_info').val('');"><i class="fas fa-times"></i> Clear</a>

					</div>

				</form>

			</div>

		</div>

	</div>











<script type="text/javascript">

function is_alphanumeric(str) {
	var code, i, len;

	for (i = 0, len = str.length; i < len; i++) {
		code = str.charCodeAt(i);

		if (!(code > 45 && code < 58) && // numeric (0-9), dot
			!(code > 64 && code < 91) && // upper alpha (A-Z)
			!(code > 96 && code < 123) && // lower alpha (a-z)
			!(code == 95))
		{
			return false;
		}
	}
	return true;
}


$(document).ready(function(){

	$('#fileupload').change(function(){
		var reader = new FileReader();
		reader.onload = function (e) {
			$("#sample_info").text( e.target.result );
		};
		reader.readAsText( $("#fileupload").prop('files')[0] );
	});

	$(document).on('click', '#upload_textarea_example', function(){
		$('#sample_info').val('Name\tTreatment_Name\tData_Type\tDescription\tFile1\tFile2\nGEN01\tGEN\tPE\tThis is a test sample for GEN with replicates 01.\tfile1a.fastq.gz\tfile2a.fastq.gz\nGEN02\tGEN\tPE\tThis is a test sample for GEN with replicates 02.\tfile1b.fastq.gz\tfile2b.fastq.gz');
	});

	var options_new_sample = {
		url: 'bxgenomics_exe.php?action=new_sample_auto',
		type: 'post',
		beforeSubmit: function(formData, jqForm, options) {
			if(is_alphanumeric($('#Name').val()) == false){
				bootbox.alert('<h2><i class="fas fa-exclamation-triangle text-warning"></i> Warning</h2><div class="lead p-3">Please enter a sample name with only alphanumeric characters.</div>');
				$('#Name').focus();
				return false;
			}
			if($('#Name').val().charCodeAt(0) > 47 && $('#Name').val().charCodeAt(0) < 58){
				bootbox.alert('<h2><i class="fas fa-exclamation-triangle text-warning"></i> Warning</h2><div class="lead p-3">Sample name can not start with numbers due to restrictions of R.</div>');
				$('#Name').focus();
				return false;
			}
			if(is_alphanumeric($('#Treatment_Name').val()) == false){
				bootbox.alert('<h2><i class="fas fa-exclamation-triangle text-warning"></i> Warning</h2><div class="lead p-3">Please enter a treatment name with only alphanumeric characters.</div>');
				$('#Treatment_Name').focus();
				return false;
			}

			return true;
		},
		success: function(responseText, statusText){

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
	$('#form_new_sample').ajaxForm(options_new_sample);


	var options_new_sample_bulk = {
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
	$('#form_new_sample_bulk').ajaxForm(options_new_sample_bulk);


});


</script>




				</div>
            </div>
		    <?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>
		</div>
	</div>
</body>
</html>