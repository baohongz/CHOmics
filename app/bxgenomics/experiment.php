<?php

include_once(__DIR__ . "/config/config.php");

if(!isset($_GET['id']) || intval($_GET['id']) <= 0){
	header("Location: index.php");
}

$experiment_id = intval($_GET['id']);

$sql = "SELECT * FROM `".$BXAF_CONFIG['TBL_BXGENOMICS_EXPERIMENT']."` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `ID` = ?i";
$experiment_info = $BXAF_MODULE_CONN -> get_row($sql, $experiment_id);
if( ! is_array($experiment_info) || count($experiment_info) <= 0){
	header("Location: experiments.php");
	exit();
}

$sql = "SELECT * FROM `".$BXAF_CONFIG['TBL_BXGENOMICS_SAMPLE']."` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `Experiment_ID` = ?i";
$experiment_samples = $BXAF_MODULE_CONN -> get_assoc('ID', $sql, $experiment_id);

$sql = "SELECT * FROM `".$BXAF_CONFIG['TBL_BXGENOMICS_DATA']."` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `Sample_ID` IN (?a)";
$results = $BXAF_MODULE_CONN -> get_assoc('ID', $sql, array_keys($experiment_samples));
$experiment_datafiles = array();
foreach($results as $id=>$info){
	$experiment_datafiles[ $info['Sample_ID'] ][ $id ] = "&bull; <strong>" . $info['Name'] . "</strong> (Read Number: " . $info['Read_Number'] . ", Phred Score: " . $info['Phred_Score'] . ")";
}

$sql = "SELECT * FROM `".$BXAF_CONFIG['TBL_BXGENOMICS_ANALYSIS']."` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `Experiment_ID` = ?i";
$experiment_analyses = $BXAF_MODULE_CONN -> get_assoc('ID', $sql, $experiment_id);



if($_SESSION['BXAF_ADVANCED_USER']){

	$uploads_dir = $BXAF_CONFIG['BXGENOMICS_SERVER_FILES_PRIVATE'] . "Experiments" . DIRECTORY_SEPARATOR . $experiment_id;
	$link = $BXAF_CONFIG['BXAF_SYSTEM_URL'] . 'bxfiles/folder.php?f=' . bxaf_encrypt($uploads_dir, $BXAF_CONFIG['BXAF_KEY']);

	$file_types = array(
		'fastq' => 'fastq files (.fastq.gz)',
		'bam' => 'Sorted and indexed bam files (.sorted.bam)',
		'gene_counts' => 'Gene counts (.txt)',
	);

	$files_grouped = array();
	foreach($file_types as $type=>$tname){
		$files_grouped[$type] = array();
	}

	$to_be_processed = array();
	// rename all .fq.gz to .fastq.gz
	$files = bxaf_list_files_only($uploads_dir);
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
			$to_be_processed[] = $file;
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

}

?><!DOCTYPE html>
<html lang="en">
<head>
	<?php include_once($BXAF_CONFIG['BXAF_PAGE_HEADER']); ?>

	<script src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/jquery/jquery.form.min.js"></script>

	<link   href='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/datatables/datatables.min.css.php' rel='stylesheet' type='text/css'>
	<script src='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/datatables/datatables.min.js.php'></script>

	<script src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/dropzone/dropzone.js"></script>
	<link rel="stylesheet" href="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/dropzone/dropzone.css">

	<script type="text/javascript">

		$(document).ready(function(){

			var table = $('.datatables').DataTable({
				'pageLength': 10,
				'lengthMenu': [[10, 25, 100, 500], [10, 25, 100, 500]], "dom": 'Blfrtip', "buttons": ['colvis','copy','csv']
				// ,"order": [[ 1, 'asc' ]],
				// ,"columnDefs": [ { "targets": 0, "orderable": false } ]
			});

			$('.toggle-columns').on( 'click', function (e) {
				var column = table.column( $(this).val() );
				column.visible( $(this).prop('checked') );
			});


			$(document).on('click', '.process_experiment_files', function(){
				$.ajax({
					method: 'GET',
					url: 'bxgenomics_exe.php?action=process_experiment_files&experiment_id=<?php echo $experiment_id; ?>',
					success: function(responseText){

						if(bxaf_is_number(responseText)){

							bootbox.alert('<h2><i class="fas fa-check-square text-success"></i> Message</h2><div class="lead p-3">The experiment files are being processed. Pleae wait and refresh this page later until the button "Click here to process files now" is removed. </div><div class="my-3 text-danger text-center" id="processing_time5" value="0"></div>', function(){
								location.reload(true);
							});

							var interval5 = setInterval(function(){
								var processingTime = parseInt($('#processing_time5').attr('value')) + 1;
			    				$('#processing_time5').attr('value', processingTime);
			    				$('#processing_time5').html('Processing in progress ... ' + processingTime + ' sec');
							}, 1000);

							var interval6 = setInterval(function(){
								$.ajax({
									type: 'GET',
									url: 'bxgenomics_exe.php?action=check_experiment_files&experiment_id=<?php echo $experiment_id; ?>',
									success: function(responseText){
										if(responseText == 0){
											clearInterval(interval5);
											clearInterval(interval6);
											$('#processing_time5').html('Processing finished successfully.');
										}
									}
								});
							}, 5000);
						}
						else {
							bootbox.alert('<h2><i class="fas fa-exclamation-triangle text-danger"></i> Error</h2><div class="lead p-3">Your files can not be downloaded. ' + responseText + '</div>');
						}

					}
				});
			});


			// Delete Experiment, Sample & Analysis
			$(document).on('click', '.btn_delete', function(){
				var type = $(this).attr('type');
				var rowid = $(this).attr('rowid');

				bootbox.confirm(
					'<h2><i class="fas fa-exclamation-triangle text-warning"></i> Warning</h2><div class="lead p-3 text-danger">Are you sure you want to delete this ' + type + '?</div><div class="p-3 text-muted">All related records will be deleted completely and there is no way to recover them!</div>',
					function(result){
						if(result){
							$.ajax({
								method: 'POST',
								url: 'bxgenomics_exe.php?action=delete_record',
								data: {type: type, rowid: rowid},
								success: function(responseText){
									if(responseText != ''){
										bootbox.alert('<h2><i class="fas fa-exclamation-triangle text-warning"></i> Warning</h2><div class="lead p-3">' + responseText + '</div>');
										return false;
									}
									else {
										if(type == 'experiment'){
											bootbox.alert('<h2><i class="fas fa-check-square text-success"></i> Message</h2><div class="lead p-3">The ' + type + ' has been deleted.</div>', function(){ window.location = "experiments.php"; });
										}
										else {
											bootbox.alert('<h2><i class="fas fa-check-square text-success"></i> Message</h2><div class="lead p-3">The ' + type + ' has been deleted.</div>', function(){ location.reload(true); });
										}
									}
								}
							});
						}
					}
				);

			});


			$(document).on('click', '.btn_import_as_project', function() {

				var rowid = $(this).attr('rowid');
				bootbox.confirm(
					'<h2><i class="fas fa-exclamation-triangle text-warning"></i> Warning</h2><div class="lead p-3 text-danger">Are you sure you want to import this analysis as a new project?</div><div class="p-3 text-muted">All related experiment, sample, comparison, and expression data will be imported.</div>',
					function(result){
						if(result) window.location = "tool_import/import_analysis.php?id=" + rowid;
					}
				);

			});


			/**----------------------------------------------------
			 * Edit Experiment Info
			 */

			$(document).on('click', '.edit_experiment_info', function(){
				$('#myModal_edit_experiment_info').modal();
			});

			var options_edit_experiment_info = {
				url: 'bxgenomics_exe.php?action=edit_experiment_info',
				type: 'post',
				beforeSubmit: function(formData, jqForm, options) {

					if($('#experiment_name').val() == ''){
						$('#myModal_edit_experiment_info').modal('hide');
						bootbox.alert('<h2><i class="fas fa-exclamation-triangle text-warning"></i> Warning</h2><div class="lead p-3">Please enter the experiment name.</div>');
						return false;
					}
					return true;
				},
				success: function(responseText, statusText){
					$('#myModal_edit_experiment_info').modal('hide');

					if(responseText != ''){
						bootbox.alert('<h2><i class="fas fa-exclamation-triangle text-warning"></i> Warning</h2><div class="lead p-3">' + responseText + '</div>');
						return false;
					}
					else {
						bootbox.alert('<h2><i class="fas fa-check-square text-success"></i> Message</h2><div class="lead p-3">The experiment information has been updated.</div>', function(){ location.reload(true); });
						return true;
					}
				}
			};
			$('#form_edit_experiment_info').ajaxForm(options_edit_experiment_info);




			$(document).on('click', '#upload_files_btn', function(){
				var attr = $('#upload_files_div').attr('hidden');
				if(typeof attr !== typeof undefined && attr !== false){
					$('#upload_files_div').removeAttr('hidden');
				} else {
					$('#upload_files_div').attr('hidden', '');
				}
			});


			/**----------------------------------------------------
			 * Add New File
			 */

			$(document).on('click', '#upload_file_btn1', function(){
				$('#myModal_file_url').modal({
					fadeDuration: 1000,
					fadeDelay: 0.50
				});
				$('#myModal_file_url').on('shown.bs.modal', function (e) {
					$('#URLs').focus();
				});
			});

			$(document).on('click', '#upload_file_btn2', function(){
				$('#myModal_file_server_select').modal();
			});

			$(document).on('click', '#upload_file_btn3', function(){
				$('.dropzone').trigger('click');
			});


			var options_file_url = {
				url: 'bxgenomics_exe.php?action=file_url',
				type: 'post',
				beforeSubmit: function(formData, jqForm, options){
					if($('#URLs').val() == ''){
						bootbox.alert("Please enter some urls to continue.");
						return false;
					}
					return true;
				},
				success: function(responseText, statusText){

					if(bxaf_is_number(responseText)){
						$('#myModal_file_url').modal('hide');

						bootbox.alert('<h2><i class="fas fa-comments text-success"></i> Message</h2><div class="lead p-3">Your files are being downloaded in the background. It is safe to close this window. </div><div class="p-3"><a target="_blank" href="<?php echo $BXAF_CONFIG['BXAF_SYSTEM_URL'] . 'bxfiles/folder.php?f=' . bxaf_encrypt($BXAF_CONFIG['BXGENOMICS_SERVER_FILES_PRIVATE'] . "Experiments/$experiment_id/", $BXAF_CONFIG['BXAF_KEY']); ?>">Click here</a> to check the downloaded files.</div><div class="my-3 text-danger text-center" id="processing_time" value="0"></div>');

						var interval = setInterval(function(){
							var processingTime = parseInt($('#processing_time').attr('value')) + 1;
		    				$('#processing_time').attr('value', processingTime);
		    				$('#processing_time').html('Downlad in progress ... ' + processingTime + ' sec');
						}, 1000);

						var interval2 = setInterval(function(){
							$.ajax({
								type: 'GET',
								url: 'bxgenomics_exe.php?action=get_file_log&process_id=' + responseText,
								success: function(responseText){
									if(responseText != ''){
										clearInterval(interval);
										clearInterval(interval2);
										$('#processing_time').html('Download finished successfully.');
									}
								}
							});
						}, 5000);

					}
					else {
						bootbox.alert('<h2><i class="fas fa-exclamation-triangle text-danger"></i> Error</h2><div class="lead p-3">Your files can not be downloaded. ' + responseText + '</div>');
					}
				}
			}
			$('#form_file_url').ajaxForm(options_file_url);



			var options_file_server_select = {
				url: 'bxgenomics_exe.php?action=file_server_select',
				type: 'post',
				beforeSubmit: function(formData, jqForm, options){
					return true;
				},
				success: function(responseText, statusText){
					$('#myModal_file_server_select').modal('hide');
					bootbox.alert(responseText, function(){ location.reload(true); });
				}
			}
			$('#form_file_server_select').ajaxForm(options_file_server_select);


		});
	</script>

</head>
<body>
	<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_MENU'])) include_once($BXAF_CONFIG['BXAF_PAGE_MENU']); ?>
	<div id="bxaf_page_wrapper" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_WRAPPER']; ?>">
		<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_LEFT'])) include_once($BXAF_CONFIG['BXAF_PAGE_LEFT']); ?>
		<div id="bxaf_page_right" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT']; ?>">
			<div id="bxaf_page_right_content" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT_CONTENT']; ?>">
				<div class="container-fluid">




					<div class="w-100 d-flex flex-row my-3">

						<p class="align-self-baseline mr-2">Experiment: </p>

						<h3 class="align-self-baseline"><?php echo $experiment_info['Name']; ?></h3>

						<?php if($_SESSION['BXAF_ADVANCED_USER']) echo '<a class="align-self-baseline ml-3 text-danger btn_delete" type="experiment" rowid="' . $experiment_id . '" href="javascript:void(0);"><i class="fas fa-times"></i> Delete Experiment</a>'; ?>

						<?php if($_SESSION['BXAF_ADVANCED_USER'] && count($experiment_samples) > 0 ) echo '<a class="ml-3 align-self-baseline btn btn-sm btn-success" href="new_analysis.php?id=' . $experiment_id . '" target="_blank"><i class="fas fa-plus-square"></i> Start New Analysis</a>'; ?>

					</div>



					<?php
					if($_SESSION['BXAF_ADVANCED_USER']){

						echo "<div class='w-100 my-2'>";

							echo "<div class='card'>";
								echo "<div class='card-header'>";
									echo "<h4 class='card-title'>";
										echo "Valid data files in current experiment: " . count($files) . "";
										if(count($files) > 0) echo '<a style="font-size: 1rem;" class="mx-5" href="Javascript: void(0);" onClick="if( $(\'#current_exp_files\').hasClass(\'hidden\') ) $(\'#current_exp_files\').removeClass(\'hidden\'); else $(\'#current_exp_files\').addClass(\'hidden\'); "><i class="fas fa-angle-double-right"></i> Show/Hide Available Files</a> ';
									echo "</h4>";
								echo "</div>";
								echo "<div class='card-body'>";

									echo '<a class="mx-2" href="javascript: void(0);" id="upload_files_btn"> <i class="fas fa-upload"></i> Upload Files </a>';
									echo "<a class='mx-2' href='$link' target='_blank'><i class='fas fa-file text-warning'></i> Manage files in Private Files</a>";
									if(count($files) > 0) echo '<a class="mx-2 w-100" href="new_sample_auto.php?expid=' . $experiment_id . '" target="_blank"><i class="fas fa-plus-square text-danger"></i> Add Samples Based on Available Data Files</a> ';

									if(count($to_be_processed) > 0 ) {
										echo "<div class='my-3 bg-warning p-2 text-center'><span class='text-danger mx-3'>Warning: you have files needed to be processed (gzip or merge of fastq files, sort or index of bam files)</span> <a class='btn btn-sm btn-primary process_experiment_files' href='javascript: void(0);'><i class='fas fa-angle-double-right'></i> Click here to process files now</a></div>";
									}

									if(is_array($files) && count($files) > 0){
										echo "<div class='w-100 row mx-0 my-3 hidden' id='current_exp_files'>";
											foreach($file_types as $type=>$tname){
												echo "<div class='col'>";
													echo "<div class='w-100 my-3 lead text-success'>" . $tname . "</div>";
													echo "<ol class=''>";
													foreach($files_grouped[$type] as $file){
														echo "<li class='text-muted'>" . basename($file) . "</li>";
													}
													echo "</ol>";
												echo "</div>";
											}
										echo "</div>";
									}
									else {
										echo "<div class='my-3 w-100 text-warning lead'>No files added to this experiment yet.</div>";
									}
								echo "</div>";
							echo "</div>";

						echo "</div>";
						?>

						<div class="my-3 w-100" id="upload_files_div" hidden>
							<form action="bxgenomics_exe.php?action=drop_file&expid=<?php echo $experiment_id; ?>" class="dropzone text-center" style="width: 40rem;">

								<div class="my-3"><i class="fas fa-cloud-upload-alt fa-4x text-muted"></i></div>
								<h3>Drag &amp; Drop a File</h3>
								<p>Or, select an option below:</p>

								<div class="row my-3">

									<div class="col">
										<a href="javascript: void(0);" class="btn btn-info file_btn" id="upload_file_btn1">
											<i class="fas fa-link fa-2x"></i><br>
											Remote URLs
										</a>
									</div>

									<div class="col">
										<a href="javascript: void(0);" class="btn btn-warning file_btn" id="upload_file_btn2">
											<i class="fas fa-database fa-2x"></i><br>
											Server Files
										</a>
									</div>

									<div class="col">
										<a href="javascript: void(0);" class="btn btn-success file_btn" id="upload_file_btn3">
											<i class="fas fa-upload fa-2x"></i><br>
											Local Files
										</a>
									</div>

								</div>

								<div class="dz-message" data-dz-message><span></span></div>

							</form>
						</div>

						<div id="debug"></div>

					<?php } // if($_SESSION['BXAF_ADVANCED_USER']){ ?>


					<div class="w-100 mt-5">
						<h3>Experiment Details  <?php if($_SESSION['BXAF_ADVANCED_USER']) echo '<a style="font-size: 1rem;" href="javascript:void(0);" class="ml-2 edit_experiment_info"><i class="fas fa-edit"></i> Edit</a>'; ?></h3>

						<div class='my-1'><span class='font-weight-bold'>Time Created: </span> <span class=''><?php echo substr($experiment_info['Time_Created'], 0, 10); ?></span>  </div>
						<div class='my-1'><span class='font-weight-bold'>Description: </span> <span class=''><?php echo $experiment_info['Description'] == '' ? "(not set)" : $experiment_info['Description']; ?></span>  </div>

						<h3 class="w-100 mt-5">Experiment Samples <?php if($_SESSION['BXAF_ADVANCED_USER']) echo '<a style="font-size: 1rem;" class="lead mx-2" href="new_sample.php?expid=' . $experiment_id . '" target="_blank"><i class="fas fa-plus-square"></i> Add Samples One by One or In Batch</a>'; ?></h3>

						<?php if(count($experiment_samples) <= 0) echo '<div class="text-danger my-3">No samples found.</div>';  ?>

						<?php if(count($experiment_samples) > 0){  ?>
						<div class="w-100 my-4">
							<table class="datatables table table-bordered table-hover mt-3">
						        <thead>
						            <tr class="table-info">
						                <th>Sample Name</th>
						                <th>Treatment</th>
						                <th>Data Type</th>
						                <th>Description</th>
						                <th>Data Files</th>
						                <th>Actions</th>
						            </tr>
						        </thead>
						        <tbody>
								<?php
							        foreach($experiment_samples as $sample_id=>$sample){
										$actions = '';
										if($_SESSION['BXAF_ADVANCED_USER']){
											$actions .= '<a href="sample.php?id=' . $sample_id . '" class="mr-2"><i class="fas fa-edit"></i> Edit</a>';
											$actions .= '<a href="javascript:void(0);" class="ml-2 text-danger btn_delete" type="sample" rowid="' . $sample_id . '"><i class="fas fa-times"></i> Delete</a>';
										}

							            echo '<tr>';
							                echo '<td><a href="sample.php?id=' . $sample_id . '">' . $sample['Name'] . '</a></td>';
							                echo '<td>' . $sample['Treatment_Name'] . '</td>';
							                echo '<td>' . $sample['Data_Type'] . '</td>';
							                echo '<td>' . $sample['Description'] . '</td>';
							                echo '<td>' . implode("<BR>", $experiment_datafiles[$sample_id] ) . '</td>';
							                echo '<td class="text-nowrap">' . $actions . '</td>';
							            echo '</tr>';
							        }
								?>
							    </tbody>
							</table>
						</div>
						<?php } // if(count($experiment_samples) > 0){  ?>

						<h3 class="w-100 mt-5">Experiment Analyses <?php if($_SESSION['BXAF_ADVANCED_USER'] && count($experiment_samples) > 0 ) echo '<a style="font-size: 1rem;" class="lead mx-2 btn btn-sm btn-success" href="new_analysis.php?id=' . $experiment_id . '" target="_blank"><i class="fas fa-plus-square"></i> Start New Analysis</a>'; ?></h3>

						<?php if(count($experiment_analyses) <= 0) echo '<div class="text-danger my-3">No analysis found.</div>';  ?>

						<?php if(count($experiment_analyses) > 0){  ?>
						<div class="w-100 my-4">
							<table class="datatables table table-bordered table-hover mt-3">
						        <thead>
						            <tr class="table-info">
						                <th>Analysis Name</th>
						                <th>Species</th>
						                <th>Samples</th>
						                <th>Status</th>
						                <th>Actions</th>
						            </tr>
						        </thead>
						        <tbody>
								<?php

									$sql = "SELECT `_Analysis_ID`, `ID` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS']}` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} ";
									$imported_analyses = $BXAF_MODULE_CONN -> get_assoc('_Analysis_ID', $sql);

							        foreach($experiment_analyses as $analysis_id=>$analysis){

										$class_analysis = new SingleAnalysis($analysis_id);
										$status = $class_analysis -> showAnalysisStatus() ;

										$s_ids = explode(",", $analysis['Samples']);

										$actions = array();
										if($_SESSION['BXAF_ADVANCED_USER']){
											$actions[] = '<a href="javascript: void(0);" title="Delete" class="text-danger btn_delete mx-1" type="analysis" rowid="' . $analysis_id . '"><i class="fas fa-times"></i> Delete Analysis</a>';
										}

										if(array_key_exists($analysis_id, $imported_analyses) ) {
											$actions[] = '<a target="_blank" href="project.php?id=' . $imported_analyses[ $analysis_id ] . '" title="View Imported Project" class=""> <i class="fas fa-list"></i> View Imported Project </a>';
										}
										else if( key($status) == 'Finished' ){
											$actions[] = '<a href="Javascript: void(0);" title="Import information into Projects" class="btn_import_as_project mx-1" rowid="' . $analysis_id . '"> <i class="fas fa-cloud-upload-alt"></i> Import as Project</a>';
										}


							            echo '<tr>';
							                echo '<td><a href="analysis.php?id=' . $analysis_id . '">' . $analysis['Name'] . '</a></td>';
							                echo '<td>' . $analysis['Species'] . '</td>';
							                echo '<td>' . count($s_ids) . '</td>';
							                echo '<td>' . current( $status ) . '</td>';
							                echo '<td>' . implode("<BR>", $actions ) . '</td>';
							            echo '</tr>';
							        }
								?>
							    </tbody>
							</table>
						</div>
					<?php } // if(count($experiment_analyses) > 0){  ?>

					</div>


					<div class="w-100" id='debug'></div>




					<!-- Edit Experiment Info Modal -->
					<form id="form_edit_experiment_info" role="form">
						<div class="modal fade" id="myModal_edit_experiment_info" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
							<div class="modal-dialog" role="document">
								<div class="modal-content">

								    <div class="modal-header">
										<h4 class="modal-title">Edit Experiment Information</h4>
										<button type="button" class="close" data-dismiss="modal" aria-label="Close">
										    <span aria-hidden="true">&times;</span>
										    <span class="sr-only">Close</span>
										</button>
								    </div>

								  	<div class="modal-body">

										<input name="experiment_id" value="<?php echo $experiment_id; ?>" hidden>

										<div class="w-100 mt-3">
											<div class="font-weight-bold">
												Name:
											</div>
											<div class="my-3">
												<input name="experiment_name" id="experiment_name" class="form-control" value="<?php echo $experiment_info['Name']; ?>" required>
											</div>
										</div>
										<div class="w-100 mt-3">
											<div class="font-weight-bold">
												Description:
											</div>
											<div class="my-3">
												<textarea name="experiment_description" rows="10" id="experiment_description" class="form-control"><?php echo $experiment_info['Description']; ?></textarea>
											</div>
										</div>

								  	</div>

								  	<div class="modal-footer">
										<button type="submit" class="btn btn-primary" id="confirm_edit_experiment_info_btn">Save</button>
										<button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
										<button type="reset" class="btn btn-link">Reset</button>
								  	</div>

								</div>
							</div>
						</div>
					</form>



					<!-- Url File Upload Modal -->
					<form id="form_file_url" role="form">
						<div class="modal fade" id="myModal_file_url" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
							<div class="modal-dialog" role="document">
								<div class="modal-content">

								    <div class="modal-header">
										<h4 class="modal-title" id="myModalLabel">Download Files from Remote Sites</h4>
										<button type="button" class="close" data-dismiss="modal" aria-label="Close">
										    <span aria-hidden="true">&times;</span>
										    <span class="sr-only">Close</span>
										</button>
								    </div>

								  	<div class="modal-body" id="myModal_content">

										<input name="expid" value="<?php echo $experiment_id; ?>" hidden>

										<div class="mb-3">Remote URLS (starting with http://, https://, or ftp://):</div>

										<div class="w-100 mb-3">
											<textarea class="w-100 p-2" wrap="off" rows="8" id="URLs" name="URLs" placeholder="Enter URLs, one per row" required></textarea>
										</div>
								  	</div>

								  	<div class="modal-footer">
										<button type="submit" class="btn btn-primary">Submit</button>
										<button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
										<button type="reset" class="btn btn-link">Reset</button>
								  	</div>

								</div>
							</div>
						</div>
					</form>



					<!-- Server File Select Modal -->
					<form id="form_file_server_select" enctype="multipart/form-data" role="form">
						<div class="modal fade bd-example-modal-lg" id="myModal_file_server_select" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
							<div class="modal-dialog modal-lg" role="document">
								<div class="modal-content">

								    <div class="modal-header">
										<h4 class="modal-title">Select Server Files:</h4>
										<button type="button" class="close" data-dismiss="modal" aria-label="Close">
										    <span aria-hidden="true">&times;</span>
										    <span class="sr-only">Close</span>
										</button>
								    </div>

								  	<div class="modal-body">
										<input name="expid" value="<?php echo $experiment_id; ?>" hidden>

										<?php

											$file_types = array(
												'BXGENOMICS_SERVER_FILES_PRIVATE'=>'private',
												'BXGENOMICS_SERVER_FILES_SHARED' =>'shared',
											);

											$file_type_names = array(
												'BXGENOMICS_SERVER_FILES_PRIVATE'=>'My Private Server Files',
												'BXGENOMICS_SERVER_FILES_SHARED'=>'Shared Server Files',
											);

											echo '<div class="p-2" style="overflow-y: scroll; max-height: 30rem;">';
											foreach($file_types as $fkey=>$ftype){
												$files_list = bxaf_list_files_only($BXAF_CONFIG[$fkey]);

												$last_folder_name = '';
												$files_list_grouped = array();
												foreach($files_list as $key=>$value){
													$name = substr($value, strlen($BXAF_CONFIG[$fkey]) - 1);
													$folder_name = dirname($name);
													if($last_folder_name != $folder_name){
														$last_folder_name = $folder_name;
													}
													$files_list_grouped[$folder_name][$key] = basename($name);
												}

												$html_contents = '';
												if(count($files_list_grouped) > 0){
													$html_contents .= '<h3 class="w-100 mb-2 text-success">' . $file_type_names[$fkey] . '</h3>';
													foreach($files_list_grouped as $folder_name=>$list){
														$k = md5($folder_name);
														$html_contents .= "<div class='my-3'>";

															$html_contents .= "<div class='m-2'><span class='lead font-weight-bold text-danger'>$folder_name</span> (" . count($list) . " files) <a class='mx-2' href='Javascript: void(0);' onClick=\"var div_list = \$('#div_list_$k'); if(div_list.hasClass('hidden')) div_list.removeClass('hidden'); else  div_list.addClass('hidden');\"><i class='fas fa-angle-double-right'></i> Show/Hide</a></div>";

															$html_contents .= "<div id='div_list_$k' class='hidden'>";
																$html_contents .= "<div class='my-1 ml-5'><a class='mx-2' href='Javascript: void(0);' onClick=\"\$('.folder_files_$k').prop('checked', true);\"><i class='fas fa-check-circle'></i> Check All</a> - <a class='mx-2' href='Javascript: void(0);' onClick=\"\$('.folder_files_$k').prop('checked', false);\"><i class='far fa-times-circle'></i> Check None</a></div>";

															asort($list);
															foreach($list as $key=>$name){
																$html_contents .= '<div class="ml-5"><input class="server_files_selected_' . $ftype . ' folder_files_' . $k . '" type="checkbox" name="server_files_selected_' . $ftype . '[]" value="' . $key . '"> ' . $name . '</div>';
															}
															$html_contents .= "</div>";

														$html_contents .= "</div>";
													}

												}
												echo $html_contents;

											}
											echo '</div>';

										?>

								  	</div>

								  	<div class="modal-footer">
										<button type="submit" class="btn btn-primary">Submit</button>
										<button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
										<button type="reset" class="btn btn-link">Reset</button>
								  	</div>

								</div>
							</div>
						</div>
					</form>


				</div>
            </div>
		    <?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>
		</div>
	</div>
</body>
</html>