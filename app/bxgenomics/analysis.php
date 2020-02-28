<?php

include_once(__DIR__ . "/config/config.php");

if(! $_SESSION['BXAF_ADVANCED_USER']){
	header("Location: index.php");
	exit();
}


$analysis_id = intval($_GET['id']);
$sql = "SELECT * FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_ANALYSIS'] . "` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `ID`= ?i";
$analysis_info = $BXAF_MODULE_CONN -> get_row($sql, $analysis_id);

if( ! is_array($analysis_info) || count($analysis_info) <= 0){
	header("Location: index.php");
	exit();
}





$previous_data_ids = explode(",", $analysis_info['Data']);

$experiment_id = $analysis_info['Experiment_ID'];

$sql = "SELECT * FROM `".$BXAF_CONFIG['TBL_BXGENOMICS_EXPERIMENT']."` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `ID` = ?i";
$experiment_info = $BXAF_MODULE_CONN -> get_row($sql, $experiment_id);

$sql = "SELECT * FROM `".$BXAF_CONFIG['TBL_BXGENOMICS_SAMPLE']."` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `Experiment_ID` = ?i";
$experiment_samples = $BXAF_MODULE_CONN -> get_assoc('ID', $sql, $experiment_id);

$experiment_sample_idnames = array();
foreach($experiment_samples as $sample_id=>$sample){
	$experiment_sample_idnames[ $sample_id ] = $sample['Name'];
}
asort($experiment_sample_idnames);

$sql = "SELECT * FROM `".$BXAF_CONFIG['TBL_BXGENOMICS_DATA']."` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `Sample_ID` IN (?a)";
$data_info = $BXAF_MODULE_CONN -> get_assoc('ID', $sql, array_keys($experiment_samples));
$experiment_datafiles = array();
foreach($data_info as $id=>$info){
	$experiment_datafiles[ $info['Sample_ID'] ][ $id ] = $info;
}

$sample_types = array(
	'PE' => 'fastq, Paired-end (PE)',
	'SE' => 'fastq, Single-end (SE)',
	'bam' => 'bam, sorted and indexed (.sorted.bam)',
	'gene_counts' => 'Gene counts (.txt)',
);



$all_available_steps = array();
if( $analysis_info['Data_Type'] == 'gene_counts') $all_available_steps = array(3);
else if( $analysis_info['Data_Type'] == 'bam') $all_available_steps = array(1,2,3);
else $all_available_steps = array_keys( $BXAF_CONFIG['RNA_SEQ_WORKFLOW'] );




$imported_project_id = 0;
$sql = "SELECT `ID` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS']}` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `_Analysis_ID` = ?i";
$imported_project_id = $BXAF_MODULE_CONN -> get_one($sql, $analysis_id);




$analysis_id_encrypted = $analysis_id . '_' . bxaf_encrypt($analysis_id, $BXAF_CONFIG['BXAF_KEY']);

$analysis_dir = $BXAF_CONFIG['ANALYSIS_DIR'] . $analysis_id_encrypted . "/";
$analysis_url = $BXAF_CONFIG['ANALYSIS_URL'] . $analysis_id_encrypted . "/";

// Last finished step
$analysis_last_step_finished = -1;
foreach($BXAF_CONFIG['RNA_SEQ_WORKFLOW'] as $i=>$name){
	if (file_exists($analysis_dir . $BXAF_CONFIG['RNA_SEQ_WORKFLOW_CHECK_FINISHED'][$i] ) ){
		$analysis_last_step_finished = $i;
	}
}


$sql = "SELECT * FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_PROCESS'] . "` WHERE `Analysis_ID`= ?i AND `Start_Time` NOT LIKE '0000%' AND `End_Time` LIKE '0000%'";
$running_process_info = $BXAF_MODULE_CONN->get_row($sql, $analysis_id);

$sql= "SELECT `ID`, `Start_Time`, `End_Time`, `Pipeline_Index`, `bxafStatus` FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_PROCESS'] . "` WHERE `Analysis_ID`= ?i ORDER BY `ID`";
$process_info = $BXAF_MODULE_CONN->get_assoc('Pipeline_Index', $sql, $analysis_id);


// Initialization
$class_analysis  = new SingleAnalysis($analysis_id);
$analysis_status = array_pop( array_keys($class_analysis -> showAnalysisStatus() ) );

$analysis_status_all = array();
$analysis_status_desc = array();
foreach($BXAF_CONFIG['RNA_SEQ_WORKFLOW'] as $i=>$name){
	$s = $class_analysis -> showAnalysisStepStatus($i);
	$analysis_status_all[$i] = key( $s );
	$analysis_status_desc[$i] = "<a href='" . $analysis_url . $BXAF_CONFIG['RNA_SEQ_WORKFLOW_LOG'][$i] . "' class='mx-2' title='View Execution Log'><i class='fas fa-file-alt'></i> </a> " . current( $s );
}

// echo "$sql<pre>" . print_r($analysis_status_all, true) . "</pre>";


?><!DOCTYPE html>
<html lang="en">
<head>
	<?php include_once($BXAF_CONFIG['BXAF_PAGE_HEADER']); ?>
	<script src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/jquery/jquery.form.min.js"></script>

	<link   href='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/datatables/datatables.min.css.php' rel='stylesheet' type='text/css'>
	<script src='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/datatables/datatables.min.js.php'></script>

</head>
<body>
	<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_MENU'])) include_once($BXAF_CONFIG['BXAF_PAGE_MENU']); ?>
	<div id="bxaf_page_wrapper" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_WRAPPER']; ?>">
		<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_LEFT'])) include_once($BXAF_CONFIG['BXAF_PAGE_LEFT']); ?>
		<div id="bxaf_page_right" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT']; ?>">
			<div id="bxaf_page_right_content" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT_CONTENT']; ?>">
				<div class="container-fluid">




					<div class="d-flex flex-row mt-3">

						<?php
							if ($analysis_info['bxafStatus'] == 4) {
								echo '<img src="images/finished.png" id="finished_img" style="position: absolute; opacity: 0.5 !important; max-width: 18rem; left: 25%; z-index: -1;" hidden>';
							}

							echo '<p class="align-self-baseline mr-2">Analysis: </p>';

							echo '<h3 class="align-self-baseline">' . $analysis_info['Name'] . '</h3>';

							if ($analysis_status != 'Ongoing' && $analysis_status != 'Pending') {
								echo '<p class="align-self-baseline ml-3"> <a href="javascript:void(0);" class="delete_btn text-danger" type="analysis" rowid="' . $analysis_id . '"> <i class="fas fa-times"></i> Delete Analysis</a> </p>';
							}
						?>

					</div>




					<div class="w-100 my-3">
						<h3 class="">
							Analysis Details
							<?php
								if ($analysis_status != 'Ongoing' && $analysis_status != 'Pending') {
									echo '<span class="ml-3" style="font-size: 1rem;"><a href="javascript:void(0);" class="edit_analysis_info text-success"> <i class="fas fa-edit"></i> Edit Analysis Details</a></span>';
								}
							?>
						</h3>

						<div class="w-100">
							<span class="font-weight-bold">Experiment: </span> <span class="mx-2"><a href="experiment.php?id=<?php echo $experiment_id; ?>"><?php echo $experiment_info['Name']; ?></a></span>
						</div>
						<div class="w-100">
							<span class="font-weight-bold">Time Created: </span> <span class="mx-2"><?php echo $analysis_info['Time_Added']; ?></span>
						</div>
						<div class="w-100">
							<span class="font-weight-bold">Name: </span> <span class="mx-2"><?php echo $analysis_info['Name']; ?></span>
						</div>
						<div class="w-100">
							<span class="font-weight-bold">Description: </span> <span class="mx-2"><?php echo $analysis_info['Description'] == '' ? "(Not set)" : $analysis_info['Description']; ?></span>
						</div>

					</div>




					<div class="w-100 my-5">

						<h3 class="">Analysis Samples <span class="ml-3" style="font-size: 1rem;">(Data Type: <?php echo $analysis_info['Data_Type']; ?>)</span></h3>

						<span class="text-danger lead"><?php echo count(explode(",", $analysis_info['Samples'])); ?></span> samples are used.

						<a href="javascript:void(0);" class="mx-2" onclick="$('#show_all_samples_div').toggle();"><i class="fas fa-hand-point-right"></i> Show All Samples</a>

						<?php if($analysis_status != 'Ongoing' && $analysis_status != 'Pending' && $analysis_info['bxafStatus'] != 4){ ?>
							<a href="javascript:void(0);" class="mr-2" onclick="$('#myModal_select_sample').modal();"><i class="fas fa-tasks"></i> Select Samples and Files</a>
							<a href="new_sample.php?expid=<?php echo $experiment_id; ?>" target="_blank"><i class="fas fa-plus-circle"></i> Create Sample</a>
						<?php } ?>

						<div class="p-3 hidden" id="show_all_samples_div">
							<table class="datatables table table-sm table-bordered table-hover mt-3">
								<thead>
									<tr class="table-info">
										<th>Sample Name</th>
										<th>Treatment</th>
										<th>Data Type</th>
										<th>Data Files</th>
									</tr>
								</thead>
								<tbody>
								<?php
									foreach($experiment_sample_idnames as $sample_id=>$sample_name){
										$sample = $experiment_samples[$sample_id];
										$data_files = array();
										foreach($experiment_datafiles[$sample_id] as $data_id=>$data_info){
											if(in_array($data_id, $previous_data_ids)) $data_files[ $data_info['Name'] ] = '<div>' . $data_info['Name'] . '</div>';
										}
										ksort($data_files);
										if(count($data_files) <= 0) continue;

										echo '<tr>';
											echo '<td><a href="sample.php?id=' . $sample_id . '">' . $sample['Name'] . '</a></td>';
											echo '<td>' . $sample['Treatment_Name'] . '</td>';
											echo '<td>' . $sample['Data_Type'] . '</td>';
											echo '<td>' . implode("", $data_files ) . '</td>';

										echo '</tr>';
									}
								?>
								</tbody>
							</table>

						</div>

					</div>


					<div class="w-100 my-5">

						<h3 class="">Analysis Steps and Progress</h3>

						<div class="w-100 my-3" id="div_action_buttons">
						<?php

							// If not running and pending (queued to run)
							if($analysis_status != 'Ongoing' && $analysis_status != 'Pending'){
								echo '<button type="button" class="btn btn-info m-2 duplicate_analysis"><i class="fas fa-copy"></i> Duplicate Analysis</button>';
							}

							// If finished, but not finalized
							if($analysis_status == 'Finished' && $analysis_info['bxafStatus'] != 4){
								echo '<button type="button" class="btn btn-warning m-2 mark_as_finished"><i class="fas fa-calendar-check"></i> Finalize Analysis</button>';
							}

							// If finished or finalized
							if ($analysis_status == 'Finished' || $analysis_info['bxafStatus'] == 4) {
								echo '<a href="report_full.php?analysis=' . $analysis_id_encrypted . '" class="btn btn-success m-2" target="_blank"><i class="fas fa-file-alt"></i> Review Full Report</a>';
							}

							// If imported as project already
							if($imported_project_id > 0){
								echo '<a class="btn btn-primary m-2" target="_blank" href="project.php?id=' . $imported_project_id . '" title="View Imported Project"> <i class="fas fa-list"></i> View Imported Project </a>';
							}

							// If finalized already, but not imported yet
							if($imported_project_id <= 0 && $analysis_info['bxafStatus'] == 4){
								echo '<a href="Javascript: void(0);" title="Import information into Projects" class="btn_save_analysis btn btn-primary m-2" rowid="' . $analysis_id . '"> <i class="fas fa-cloud-upload-alt"></i> Import as Project</a>';
							}

						?>
						</div>



						<?php if ($analysis_info['bxafStatus'] != 4) echo '<div class="text-muted my-3">Tip: Please select one or multiple analysis steps to get started:</div>'; ?>

						<div>

							<form id="form_start_analysis" role="form">

								<input id="sample_list_saved" name="sample_list_saved_for_DEG" type="hidden">

								<table class="table table-bordered table-sm table-hover">
									<thead>
										<tr class="table-info">
											<th class='text-center'><input class="select_step_all" type="checkbox"></th>
											<th class='text-danger'>Run Complete Analysis</th>
											<th>Status</th>
											<th>Step Files</th>
										</tr>
									</thead>
									<tbody>
										<?php
											foreach($all_available_steps as $step){

												$step_name = $BXAF_CONFIG['RNA_SEQ_WORKFLOW'][$step];

												echo "<tr>";
													echo "<td class='text-center'><input class='select_step' type='checkbox' name='steps[]' value='$step'></td>";
													echo "<td><strong>Step " . ($step + 1) . "</strong>: $step_name ";
														if($step > 0 && $analysis_last_step_finished >= $step) echo "<a href='javascript: void(0);' class='analysis_step_detail mx-2' recordid='$step' title='View Parameters'> <i class='fas fa-sliders-h'></i> </a>";
													echo "</td>";
													echo "<td>" . $analysis_status_desc[$step] . "</td>";
													echo "<td>" . $class_analysis -> showAnalysisStepFiles($step) . "</td>";
												echo "</tr>";
											}
										?>
									</tbody>
								</table>

								<?php

								// If the analysis is running, save the running process ID.
								if ($analysis_status == 'Ongoing' || $analysis_status == 'Pending'){
									echo '<input type="hidden" id="runningProcessID" value="' . $running_process_info['ID'] . '">';
									echo '<strong>Running Process</strong>: ' . $running_process_info['Notes'];
									echo '&nbsp;(<span id="runningProcessTime" class="text-success">Loading</span> )';
								}
								else {
									echo '<input type="hidden" id="runningProcessID" value="0">';
								}
								?>


								<div class="w-100 hidden" id="select_step_comment_general"></div>


								<div class="w-100 my-3">
								<?php
									// If the analysis is running and the analysis has been marked as finished.
									if ($analysis_status != 'Ongoing' && $analysis_status != 'Pending' && $analysis_info['bxafStatus'] != 4){
										echo '<button type="submit" class="btn btn-primary m-2 hidden btn_analysis" id="start_analysis_btn" ><i class="fas fa-chart-area"></i> Submit Analysis Job</button> <span id="busy_icon_for_all" class="m-2 hidden"><i class="fas fa-spinner fa-pulse"></i></span>';
									}

									if ($analysis_status == 'Ongoing' || $analysis_status == 'Pending') {
										echo '<a href="javascript: void(0);" class="btn btn-danger btn_analysis" id="terminate_process"><i class="fas fa-ban"></i> Terminate Process</a>';
									}
								?>
								</div>


								<!-- Select Comparison Samples-->
<!--
								<div class="mt-2 hidden" id="select_step_comment_DEG">
									<p class="text-danger w-100">Please select treatment samples for your DEG analysis:</p>

									<table class="table table-sm table-bordered table-hover mt-3">
										<thead>
											<tr class="table-info">
												<th class="text-center"><input type="checkbox" class="bxaf_checkbox bxaf_checkbox_all" checked></th>
												<th>Sample Name</th>
												<th>Treatment</th>
												<th>Data Type</th>
												<th>Data Files</th>
											</tr>
										</thead>
										<tbody>

										<?php
											foreach($experiment_sample_idnames as $sample_id=>$sample_name){
												$sample = $experiment_samples[$sample_id];
												$data_files = array();
												foreach($experiment_datafiles[$sample_id] as $data_id=>$data_info){
													if(in_array($data_id, $previous_data_ids)) $data_files[ $data_info['Name'] ] = '<div>' . $data_info['Name'] . '</div>';
												}
												ksort($data_files);
												if(count($data_files) <= 0) continue;

												echo '<tr>';
													echo '<td class="text-center"><input type="checkbox" class="bxaf_checkbox bxaf_checkbox_one select_comparison_sample" recordid="'.$sample_id.'" id="select_comparison_sample_'.$sample_id.'" name="select_comparison_sample_'.$sample_id.'" checked></td>';
													echo '<td><a href="sample.php?id=' . $sample_id . '">' . $sample['Name'] . '</a></td>';
													echo '<td>' . $sample['Treatment_Name'] . '</td>';
													echo '<td>' . $sample['Data_Type'] . '</td>';
													echo '<td>' . implode("", $data_files ) . '</td>';

												echo '</tr>';
											}
										?>
										</tbody>
									</table>

									<a href="javascript: void(0);" class="btn btn-primary my-3" id="confirm_comparison_sample">
										<i class="fas fa-check-circle"></i> Confirm Samples
									</a>

								</div>
 -->
							</form>

						</div>
					</div>





<!-- Delete Record -->
<div class="modal fade" id="myModal_delete">
	<div class="modal-dialog" role="document">
	  <div class="modal-content">
	    <div class="modal-header">
			<h4 class="modal-title"><i class="fas fa-exclamation-triangle text-warning"></i> Delete Analysis</h4>
			<button type="button" class="close" data-dismiss="modal">
			    <span aria-hidden="true">&times;</span>
			    <span class="sr-only">Close</span>
			</button>
	    </div>

	  	<div class="modal-body">
			<div class="lead p-3 text-danger">Are you sure you want to delete this analysis?</div>
			<div class="px-3 text-muted">After deletion, it can not be recovered.</div>
			<input id="delete_div_type" hidden> <input id="delete_div_rowid" hidden>
	  	</div>

	  	<div class="modal-footer">
				<button type="button" class="btn btn-danger" id="confirm_delete">Delete</button>
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
	  	</div>
		</div>
	</div>
</div>






<!-- Select Sample Modal -->
<form id="form_select_sample" role="form">
<div class="modal fade" id="myModal_select_sample" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">

		    <div class="modal-header">
  				<h4 class="modal-title" id="myModalLabel">Select Samples and Files</h4>
  				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
  				    <span aria-hidden="true">&times;</span>
  				    <span class="sr-only">Close</span>
  				</button>
		    </div>

		  	<div class="modal-body p-3">

				<div class="form-inline my-3">
					<label class="font-weight-bold">Data Type: </label>
					<select name="Data_Type" id="Data_Type" class="custom-select mx-2">
						<option value=""> (Select all data files with a type) </option>
						<?php
							foreach($sample_types as $k=>$v){
								echo '<option value="'. $k .'">'. $v .'</option>';
							}
						?>
					</select>
				</div>

				<div class="w-100 my-2 p-2" style="display: block; height: 350px; overflow-y: auto;">
					<table class="datatables table table-sm table-bordered table-hover mt-3">
						<thead>
							<tr class="table-info">
								<th>Sample Name</th>
								<th>Treatment</th>
								<th>Data Type</th>
								<th>Data Files</th>
							</tr>
						</thead>
						<tbody>
						<?php

							foreach($experiment_sample_idnames as $sample_id=>$sample_name){

								$sample = $experiment_samples[$sample_id];

								$data_files = array();
								foreach($experiment_datafiles[$sample_id] as $data_id=>$data_info){

									$classes = array();
									$classes[] = 'data_checkbox_one';
									$classes[] = 'class_' . $sample['Data_Type'];
									$classes[] = 'class_read_number_' . $data_info['Read_Number'];

									$data_files[ $data_info['Name'] ] = '<input type="checkbox" sampleid="' . $sample_id . '" class="data_checkbox_all ' . implode(" ", $classes) . '" name="data_ids[]" value="'. $data_id .'" ' . (in_array($data_id, $previous_data_ids) ? " checked " : "") . '> <label>' . $data_info['Name'] . '</label>';
								}
								ksort($data_files);

								echo '<tr>';
									echo '<td><a href="sample.php?id=' . $sample_id . '">' . $sample['Name'] . '</a></td>';
									echo '<td>' . $sample['Treatment_Name'] . '</td>';
									echo '<td>' . $sample['Data_Type'] . '</td>';
									echo '<td>' . implode("<BR>", $data_files ) . '</td>';

								echo '</tr>';
							}
						?>
						</tbody>
					</table>
				</div>

		  	</div>

		  	<div class="modal-footer">
  				<?php
						// Confirm if the analysis has been started
  					// if($analysis_last_step_finished >= 0){
  					// 	echo '<button type="button" id="select_sample_trigger_confirm_btn" class="btn btn-primary">Save Selected Samples</button>';
					// 	echo '<button type="submit" id="select_sample_submit_btn" class="btn btn-primary" hidden>Save Selected Samples</button>';
  					// }
					// else {
					// 	echo '<button type="submit" id="select_sample_submit_btn" class="btn btn-primary">Save Selected Samples</button>';
					// }
  				?>
				<button type="submit" id="select_sample_submit_btn" class="btn btn-primary">Save Selected Samples</button>
  				<button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
  				<button type="reset" class="btn btn-link">Reset</button>
				<input name="analysis_id" value="<?php echo $analysis_id; ?>" hidden>
		  	</div>

		</div>
	</div>
</div>
</form>





<!-- Analysis Step Detail Modal -->
<div class="modal fade" id="myModal_step_detail" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
	    <div class="modal-header">
  			<h4 class="modal-title">Step Details</h4>
  			<button type="button" class="close" data-dismiss="modal" aria-label="Close">
  			    <span aria-hidden="true">&times;</span>
  			    <span class="sr-only">Close</span>
  			</button>
	    </div>
	  	<div class="modal-body pl-3" id="myModal_step_detail_content"></div>
	  	<div class="modal-footer">
			     <button type="button" class="btn btn-primary" data-dismiss="modal">OK</button>
	  	</div>
		</div>
	</div>
</div>




<!-- Edit Analysis Info Modal -->
<form id="form_edit_analysis_info" enctype="multipart/form-data" role="form">
<div class="modal fade" id="myModal_edit_analysis_info" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
		    <div class="modal-header">
          		<h4 class="modal-title">Edit Analysis Infomation</h4>
  				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
  				    <span aria-hidden="true">&times;</span>
  				    <span class="sr-only">Close</span>
  				</button>
		    </div>
		  	<div class="modal-body">
  				<input name="analysis_id" value="<?php echo $analysis_id; ?>" hidden>
  				<div class="row mt-2">
  					<div class="col-md-3">
  						<span class="text-nowrap"><span class="text-danger">*</span> <strong>Name:</strong></span>
  					</div>
  					<div class="col-md-9">
  						<input name="analysis_name" id="analysis_name" class="form-control" value="<?php echo $analysis_info['Name']; ?>" required>
  					</div>
  				</div>

				<input type="hidden" name="analysis_species" id="analysis_species" value="<?php echo $analysis_info['Species']; ?>">

  				<div class="row mt-2">
  					<div class="col-md-3">
  						<span class="text-nowrap"><strong>Description:</strong></span>
  					</div>
  					<div class="col-md-9">
  						<textarea name="analysis_description" id="analysis_description" class="form-control"> <?php echo $analysis_info['Description']; ?></textarea>
  					</div>
  				</div>
		  	</div>

		  	<div class="modal-footer">
  				<button type="submit" class="btn btn-primary">Save</button>
  				<button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
  				<button type="reset" class="btn btn-link">Reset</button>
		  	</div>

		</div>
	</div>
</div>
</form>





<script type="text/javascript">

$(document).ready(function(){

	<?php if($analysis_status == 'Ongoing' || $analysis_status == 'Pending'){ ?>
		setInterval(function(){
			var currentRunningProcess = $('#runningProcessID').val();
			$.ajax({
				method: 'POST',
				url: 'bxgenomics_exe_analysis.php?action=analysis_process_refresh',
				data: {currentRunningProcess: currentRunningProcess, analysisID: <?php echo $analysis_info['ID']?>},
				success: function(responseText){
					//alert(responseText);
					if(responseText == 'refresh'){
						location.reload(true);
					}
					else {
						$('#runningProcessTime').html(responseText);
					}
				}
			});
		}, 1000);
	<?php } ?>



	<?php if ($analysis_info['bxafStatus'] == 4){ ?>
		$('#finished_img').removeAttr('hidden');
	<?php } ?>



	$(document).on('click', '.btn_save_analysis', function() {

		var rowid = $(this).attr('rowid');
		bootbox.confirm(
			'<h2><i class="fas fa-exclamation-triangle text-warning"></i> Warning</h2><div class="lead p-3 text-danger">Are you sure you want to import this analysis as a new project?</div><div class="p-3 text-muted">All related experiment, sample, comparison, and expression data will be imported.</div>',
			function(result){
				if(result) window.location = "tool_import/import_analysis.php?id=" + rowid;
			}
		);

	});



	/**
	 * Parameters & Comments for analysis steps
	 */
	var alignment_parameter = "<h3 class='w-100 p-2 table-info'>Alignment Parameters</h3>";
	alignment_parameter += "<table class='table table-borderless'>";
	alignment_parameter += '<tr><td class="text-right align-middle">Phred score: </td><td><select style="width: 20rem;" id="select_phred" name="select_phred" class="custom-select form-control-sm"><option value="3">+33 (Default)</option><option value="6">+64</option></select></td></tr>';
	alignment_parameter += "</table> ";

	var gene_counts_parameter = "<h3 class='w-100 p-2 table-info'>Gene Counts Parameters</h3>";
	gene_counts_parameter += "<table class='table table-borderless'>";
	gene_counts_parameter += '<tr><td class="text-right align-middle" style="width: 20rem;">Strand:</td><td class=""><select style="width: 20rem;" name="analysis_strand" class="custom-select form-control-sm"><option value="0">Not stranded</option><option value="1">Same strand as mRNA</option><option value="2">Opposite strand as mRNA</option></select></td></tr>';
	gene_counts_parameter += "</table> ";

	var deg_parameter = "<h3 class='w-100 p-2 table-info'>DEG Parameters</h3>";
	deg_parameter += "<table class='table table-borderless'> ";
	deg_parameter += "<tr> <td class='text-right align-middle' style='width: 20rem;'>Use TMM Normalization:</td> <td> <select style='width: 10rem;' class='custom-select form-control-sm' name='TMM'>  <option value='T'>True</option>  <option value='F'>False</option> </select> </td> </tr> <tr> <td class='text-right'>Minimum number of Genes in a set:</td> <td><input style='width: 10rem;' class='form-control form-control-sm' name='minimum_gene_number' type='number' value='15'></td> </tr>";
	deg_parameter += "<tr> <td class='text-right align-middle'>Maximum number of Genes in a set:</td> <td><input style='width: 10rem;' class='form-control form-control-sm' type='number' value='1000' name='maximum_gene_number'></td> </tr> <tr> <td class='text-right align-middle'>Select Comparison:</td> <td><div id='comparison_div'></div></td> </tr> ";
	deg_parameter += "<tr> <td class='text-right'>&nbsp;</td> <td><a href='javascript: void(0);' id='add_comparison_btn'><i class='fas fa-angle-double-right'></i> Add Comparison</a></td> </tr> ";
	deg_parameter += "</table> ";



	$(document).on('change', '#Data_Type', function(){
		$('.data_checkbox_all').prop('checked', false);
		$('.data_checkbox_all').parent().parent().addClass('hidden');

		var value = 'class_' + $(this).val();
		$('.' + value ).parent().parent().removeClass('hidden');
		$('.' + value ).prop('checked', true);
	});

	var options_select_sample = {
		url: 'bxgenomics_exe.php?action=select_analysis_sample',
		type: 'post',
		beforeSubmit: function(formData, jqForm, options) {

			return true;
		},
		success: function(responseText, statusText){
			$('#myModal_select_sample').modal('hide');

			if(responseText != ''){
				bootbox.alert('<h2><i class="fas fa-check-square text-danger"></i> Error</h2><div class="lead p-3">' + responseText + '</div>');
			}
			else {
				bootbox.alert('<h2><i class="fas fa-check-square text-success"></i> Message</h2><div class="lead p-3">The analysis has been updated.</div>',
					function(){ location.reload(true); }
				);
			}
			return true;
		}
	};
	$('#form_select_sample').ajaxForm(options_select_sample);



	// $(document).on('click', '#select_sample_trigger_confirm_btn', function(){
	//
	// 	bootbox.confirm('<h2><i class="fas fa-exclamation-triangle text-warning"></i> Warning</h2><div class="lead p-3 text-danger">Are you sure you want to change sample selection?</div><div class="p-3 text-muted">All existing analysis results will be removed if you proceed. You can also duplicate this analysis and then make changes.</div>',
	// 	function(result){
	// 		if(result){
	// 			$('#myModal_select_sample').modal('hide');
	// 			$('#select_sample_submit_btn').trigger('click');
	// 		}
	// 	});
	//
	// });



	/**----------------------------------------------------
	 * Start Analysis
	 * When the analysis is not running & not pending
	 */

	<?php
	if ($analysis_status != 'Ongoing' && $analysis_status != 'Pending' && $analysis_info['bxafStatus'] != 4){
	?>

		$(document).on('change', '.select_step_all', function(){

			var checked = $(this).prop('checked');

			$('.select_step').prop('checked', checked);


			// if(! $('#select_step_comment_DEG').hasClass('hidden')) $('#select_step_comment_DEG').addClass('hidden');
			$('#select_step_comment_general').html('');
			if(! $('#select_step_comment_general').hasClass('hidden')) $('#select_step_comment_general').addClass('hidden');
			if(! $('#terminate_process').hasClass('hidden') ) $('#terminate_process').addClass('hidden');
			if(! $('#start_analysis_btn').hasClass('hidden') ) $('#start_analysis_btn').addClass('hidden');

			if( checked ){

				if( $('#busy_icon_for_all').hasClass('hidden') ) $('#busy_icon_for_all').removeClass('hidden');
				$.ajax({
					method: 'POST',
					url: 'bxgenomics_exe_analysis.php?action=DEG_select_comparison&sub=new',
					data: { analysis_id: <?php echo $analysis_id; ?> },
					success: function(responseText){
						if(! $('#busy_icon_for_all').hasClass('hidden') ) $('#busy_icon_for_all').addClass('hidden');

						if( $('#select_step_comment_general').hasClass('hidden') ) $('#select_step_comment_general').removeClass('hidden');

						$('#select_step_comment_general').prepend(deg_parameter);
						$('#comparison_div').html(responseText); // Load existing comparisons

						<?php if( $analysis_info['Data_Type'] != 'gene_counts') echo "\n$('#select_step_comment_general').prepend(gene_counts_parameter); \n$('#select_step_comment_general').prepend(alignment_parameter);\n"; ?>

						// if($('#select_step_comment_DEG').hasClass('hidden')) $('#select_step_comment_DEG').removeClass('hidden');
						// if(! $('#start_analysis_btn').hasClass('hidden')) $('#start_analysis_btn').addClass('hidden');

						if( $('#start_analysis_btn').hasClass('hidden')) $('#start_analysis_btn').removeClass('hidden');
					}
				});

			}
			else {

				// if(! $('#select_step_comment_DEG').hasClass('hidden')) $('#select_step_comment_DEG').addClass('hidden');

				$('#select_step_comment_general').html('');
				if(! $('#select_step_comment_general').hasClass('hidden')) $('#select_step_comment_general').addClass('hidden');

				if(! $('#terminate_process').hasClass('hidden') ) $('#terminate_process').addClass('hidden');
				if(! $('#start_analysis_btn').hasClass('hidden') ) $('#start_analysis_btn').addClass('hidden');

			}

		});


		$(document).on('change', '.select_step', function(){

			// if(! $('#select_step_comment_DEG').hasClass('hidden')) $('#select_step_comment_DEG').addClass('hidden');

			$('#select_step_comment_general').html('');
			if(! $('#select_step_comment_general').hasClass('hidden')) $('#select_step_comment_general').addClass('hidden');

			if(! $('#terminate_process').hasClass('hidden') ) $('#terminate_process').addClass('hidden');
			if(! $('#start_analysis_btn').hasClass('hidden') ) $('#start_analysis_btn').addClass('hidden');


			var all_checked = true;
			var one_checked = false;

			$('.select_step').each(function(){

				var checked = $(this).prop('checked');
				if(! checked ) all_checked = false;

				var step = $(this).val();

				if( (step == 0 || step == 1 || step == 2) && checked ){
					one_checked = true;
				}

				// fastQC
				if(step == 0 && checked){
					if( $('#start_analysis_btn').hasClass('hidden') ) $('#start_analysis_btn').removeClass('hidden');
				}

				// Alignment
				if(step == 1 && checked){
					if( $('#select_step_comment_general').hasClass('hidden') ) $('#select_step_comment_general').removeClass('hidden');
					$('#select_step_comment_general').append(alignment_parameter);

					if( $('#start_analysis_btn').hasClass('hidden') ) $('#start_analysis_btn').removeClass('hidden');
				}

				// Gene Counts and QC
				if(step == 2 && checked){
					if( $('#select_step_comment_general').hasClass('hidden') ) $('#select_step_comment_general').removeClass('hidden');
					$('#select_step_comment_general').append(gene_counts_parameter);

					if( $('#start_analysis_btn').hasClass('hidden') ) $('#start_analysis_btn').removeClass('hidden');
				}

				// DEG, GSEA and GO Analysis
				if(step == 3 && checked){

					// if( $('#select_step_comment_DEG').hasClass('hidden') ) $('#select_step_comment_DEG').removeClass('hidden');

					if( $('#select_step_comment_general').hasClass('hidden') ) $('#select_step_comment_general').removeClass('hidden');
					$('#select_step_comment_general').append(deg_parameter);

					$.ajax({
						method: 'POST',
						url: 'bxgenomics_exe_analysis.php?action=DEG_select_comparison&sub=new',
						data: { analysis_id: <?php echo $analysis_id; ?> },
						success: function(responseText){
							$('#comparison_div').html(responseText); // Load existing comparisons
						}
					});

					// if(! $('#start_analysis_btn').hasClass('hidden') ) $('#start_analysis_btn').addClass('hidden');

					if( $('#start_analysis_btn').hasClass('hidden') ) $('#start_analysis_btn').removeClass('hidden');
				}

			});

			$('.select_step_all').prop('checked', all_checked);

		});




		$(document).on('click', '#add_comparison_btn', function(){

			if($('#select_step_DEG').is(':checked')){
				var sample_list = $('#sample_list_saved').val();
				$('#busy_icon_for_all').removeClass('hidden');
				$.ajax({
					method: 'POST',
					url: 'bxgenomics_exe_analysis.php?action=DEG_select_comparison&sub=add&type=selected_sample',
					data: {analysis_id: <?php echo $analysis_id; ?>, sample_list: sample_list},
					success: function(responseText){
						$('#busy_icon_for_all').addClass('hidden');
						$('#comparison_div').append(responseText);
					}
				});
			}
			else {
				$('#busy_icon_for_all').removeClass('hidden');
				var sample_list = $('#sample_list_saved').val();
				$.ajax({
					method: 'POST',
					url: 'bxgenomics_exe_analysis.php?action=DEG_select_comparison&sub=add',
					data: {analysis_id: <?php echo $analysis_id; ?> },
					success: function(responseText){
						$('#busy_icon_for_all').addClass('hidden');
						$('#comparison_div').append(responseText);
					}
				});
			}
		});




		// $(document).on('click', '#confirm_comparison_sample', function(){
		//
		// 	// Get the list for all selected samples.
		// 	var sample_list = [];
		// 	$('.select_comparison_sample').each(function(index, element){
		// 		var recordid = $(element).attr('recordid');
		// 		if($(element).is(':checked')){
		// 			sample_list.push(recordid);
		// 		}
		// 	});
		// 	$('#sample_list_saved').val(sample_list);
		//
		// 	// $('#select_step_comment_DEG').addClass('hidden');
		// 	$('#select_step_comment_general').html('');
		//
		// 	$('#busy_icon_for_all').removeClass('hidden');
		// 	$.ajax({
		// 		method: 'POST',
		// 		url: 'bxgenomics_exe_analysis.php?action=DEG_select_comparison&sub=new&type=selected_sample',
		// 		data: { 'analysis_id': <?php echo $analysis_id; ?>, 'sample_list': sample_list },
		// 		success: function(responseText){
		// 			$('#busy_icon_for_all').addClass('hidden');
		//
		// 			$('#select_step_comment_general').html(deg_parameter);
		// 			$('#comparison_div').append(responseText);
		// 			$('#select_step_comment_general').removeClass('hidden');
		//
		// 			$('#start_analysis_btn').removeClass('hidden');
		//
		// 			// $('#start_analysis_btn').click();
		// 		}
		// 	});
		//
		// });


		var options_start_analysis = {
			url: 'bxgenomics_exe_analysis.php?action=start_analysis',
			type: 'post',
			data: {analysis_id: <?php echo $analysis_id; ?>},
			beforeSubmit: function(formData, jqForm, options) {
				$('#busy_icon_for_all').removeClass('hidden');
				$('#start_analysis_btn').attr('disabled', '');
				return true;
			},
			success: function(responseText, statusText){
				$('#busy_icon_for_all').addClass('hidden');
				$('#start_analysis_btn').removeAttr('disabled');

				if(responseText != ''){
					bootbox.alert('<h2><i class="fas fa-exclamation-triangle text-warning"></i> Warning</h2><div class="lead p-3">' + responseText + '</div>');
				}
				else {
					bootbox.alert(
						'<h2><i class="fas fa-check-square text-success"></i> Message</h2><div class="lead p-3">The analysis has been saved. It will start in the background soon.</div>',
						function(){
							location.reload(true);
						}
					);
				}

				return true;
			}
		};
		$('#form_start_analysis').ajaxForm(options_start_analysis);


	<?php } ?>





	/**----------------------------------------------------
	 * Terminate Process
	 */
	<?php if (is_array($running_process_info) && count($running_process_info) > 0){ ?>
	$(document).on('click', '#terminate_process', function(){

		bootbox.confirm(
			'<h2><i class="fas fa-exclamation-triangle text-warning"></i> Warning</h2><div class="lead p-3 text-danger">Are you sure you want to terminate this analysis process?</div><div class="p-3 text-muted">After doing so, all intermediate files will be removed.</div>',
			function(result){
				if(result){

					$('#busy_icon_for_all').removeClass('hidden');

					$.ajax({
						method: 'POST',
						url: 'bxgenomics_exe_analysis.php?action=terminate_process',
						data: {analysis_id: <?php echo $analysis_id; ?>, process_id: <?php echo $running_process_info['ID']; ?>, process_processid: <?php echo $running_process_info['Process_ID']; ?>},
						success: function(responseText){

							bootbox.alert('<h2><i class="fas fa-check-square text-success"></i> Message</h2><div class="lead p-3">The analysis process has been terminated.</div>', function(){ location.reload(true); });

						}
					});

				}
			}
		);

	});

	<?php } // if ($analysis_status != 'Ongoing' && $analysis_status != 'Pending' && $analysis_info['bxafStatus'] != 4){  ?>




	/**----------------------------------------------------
	 * Mark As Finished
	 */


	$(document).on('click', '#mark_as_finished', function(){

		bootbox.confirm(
			'<h2><i class="fas fa-exclamation-triangle text-warning"></i> Warning</h2><div class="lead p-3 text-danger">Are you sure you want to mark this analysis as finished?</div><div class="p-3 text-muted">After doing so, in the future you can only review the reports.</div>',
			function(result){
				if(result){

					$('#busy_icon_for_all').removeClass('hidden');

					$.ajax({
						method: 'POST',
						url: 'bxgenomics_exe_analysis.php?action=mark_as_finished',
						data: {analysis_id: <?php echo $analysis_id; ?>},
						success: function(responseText){

							bootbox.alert('<h2><i class="fas fa-check-square text-success"></i> Message</h2><div class="lead p-3">The analysis has been marked as finished.</div>', function(){ location.reload(true); });

						}
					});

				}
			}
		);

	});




	/**----------------------------------------------------
	 * Duplicate Analysis
	 */


	$(document).on('click', '.duplicate_analysis', function(){

		bootbox.confirm(
			'<h2><i class="fas fa-exclamation-triangle text-warning"></i> Warning</h2><div class="lead p-3 text-danger">Are you sure you want to duplicate the analysis?</div>',
			function(result){
				if(result){

					$('#busy_icon_for_all').removeClass('hidden');

					$.ajax({
						method: 'POST',
						url: 'bxgenomics_exe_analysis.php?action=duplicate_analysis',
						data: {analysis_id: <?php echo $analysis_id; ?> },
						success: function(responseText){

							bootbox.alert('<h2><i class="fas fa-check-square text-success"></i> Message</h2><div class="lead p-3">The analysis has been duplicated.</div>', function(){ window.location = 'analysis.php?id=' + responseText; });

						}
					});

				}
			}
		);


	});





	/**----------------------------------------------------
	 * Get Step Detail
	 */


	$(document).on('click', '.analysis_step_detail', function(){
		var step = $(this).attr('recordid');
		$.ajax({
			method: 'POST',
			url: 'bxgenomics_exe_analysis.php?action=get_step_detail',
			data: {step: step, analysis_id: <?php echo $analysis_id; ?> },
			success: function(responseText){

				$('#myModal_step_detail_content').html(responseText);
				$('#myModal_step_detail').modal();

			}
		});

	});





	/**----------------------------------------------------
	 * Edit Analysis Info
	 */

	$(document).on('click', '.edit_analysis_info', function(){
		$('#myModal_edit_analysis_info').modal();
	});
	var options_edit_analysis_info = {
		url: 'bxgenomics_exe.php?action=edit_analysis_info',
		type: 'post',
		beforeSubmit: function(formData, jqForm, options) {
			$('#busy_icon_for_all').removeClass('hidden');
			return true;
		},
		success: function(responseText, statusText){
			$('#myModal_edit_analysis_info').modal('hide');

			bootbox.alert('<h2><i class="fas fa-check-square text-success"></i> Message</h2><div class="lead p-3">The analysis information has been updated.</div>', function(){ location.reload(true); });

			return true;
		}
	};
	$('#form_edit_analysis_info').ajaxForm(options_edit_analysis_info);





	/**----------------------------------------------------
	 * Delete Experiment & Sample & Analysis
	 */

	$(document).on('click', '.delete_btn', function(){
		$('#myModal_delete').modal();

		var type = $(this).attr('type');
		var rowid = $(this).attr('rowid');
		$('#delete_div_type').val(type);
		$('#delete_div_rowid').val(rowid);
	});

	$(document).on('click', '#confirm_delete', function(){
		$('#busy_icon_for_all').removeClass('hidden');

		$('#myModal_delete').modal('hide');
		var type = $('#delete_div_type').val();
		var rowid = $('#delete_div_rowid').val();

		$.ajax({
			method: 'POST',
			url: 'bxgenomics_exe.php?action=delete_record',
			data: {type: type, rowid: rowid},
			success: function(responseText){

				bootbox.alert('<h2><i class="fas fa-check-square text-success"></i> Message</h2><div class="lead p-3">The analysis has been deleted.</div>', function(){ window.location = 'experiment.php?id=<?php echo $experiment_id; ?>'; });
			}
		});
	});


});
</script>





					<div id="div_debug"></div>

				</div>
            </div>
		    <?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>
		</div>
	</div>
</body>
</html>