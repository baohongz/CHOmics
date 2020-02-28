<?php

include_once(__DIR__ . "/config/config.php");

if(!isset($_GET['id']) || intval($_GET['id']) <= 0){
	header("Location: index.php");
}

$sample_id = intval($_GET['id']);

$sql = "SELECT * FROM `".$BXAF_CONFIG['TBL_BXGENOMICS_SAMPLE']."` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `ID` = ?i";
$sample_info = $BXAF_MODULE_CONN -> get_row($sql, $sample_id);

if( ! is_array($sample_info) || count($sample_info) <= 0){
	header("Location: samples.php");
	exit();
}

$experiment_id = $sample_info['Experiment_ID'];
$sql = "SELECT `Name` FROM `".$BXAF_CONFIG['TBL_BXGENOMICS_EXPERIMENT']."` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `ID` = ?i";
$experiment_name = $BXAF_MODULE_CONN -> get_one($sql, $experiment_id);

$sql = "SELECT `ID`, `Name` FROM `".$BXAF_CONFIG['TBL_BXGENOMICS_SAMPLE']."` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `Experiment_ID` = ?i";
$sample_idnames = $BXAF_MODULE_CONN -> get_assoc('ID', $sql, $experiment_id);

$sql = "SELECT * FROM `".$BXAF_CONFIG['TBL_BXGENOMICS_DATA']."` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `Sample_ID` = ?i ORDER BY `Name`";
$sample_datafiles = $BXAF_MODULE_CONN -> get_assoc('ID', $sql, $sample_id);



?><!DOCTYPE html>
<html lang="en">
<head>
	<?php include_once($BXAF_CONFIG['BXAF_PAGE_HEADER']); ?>

	<script src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/jquery/jquery.form.min.js"></script>

	<link   href='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/datatables/datatables.min.css.php' rel='stylesheet' type='text/css'>
	<script src='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/datatables/datatables.min.js.php'></script>

	<!-- Drag and Drop -->
	<script src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/dropzone/dropzone.js"></script>
	<link rel="stylesheet" href="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/dropzone/dropzone.css">

	<script>


		// JavaScript Document
		$(document).ready(function(){

			var table = $('.datatables').DataTable({
				'pageLength': 10,
				'lengthMenu': [[10, 25, 100, 500], [10, 25, 100, 500]]
				// ,"order": [[ 1, 'asc' ]],
				// ,"columnDefs": [ { "targets": 0, "orderable": false } ]
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


			// Edit Sample Information
			$(document).on('click', '.edit_sample_info', function(){
				$('#myModal_edit_sample_info').modal();
			});

			var options_edit_sample_info = {
				url: 'bxgenomics_exe.php?action=edit_sample_info',
				type: 'post',
				beforeSubmit: function(formData, jqForm, options) {
					if($('#Name_Sample').val() == '' || $('#Treatment_Name').val() == '' || $('#Description').val() == '' || $('#Data_Type').val() == ''){
						bootbox.alert('<h2><i class="fas fa-exclamation-triangle text-warning"></i> Warning</h2><div class="lead p-3">Please enter values for all fields.</div>');
						return false;
					}
					return true;
				},
				success: function(responseText, statusText){
					$('#myModal_edit_sample_info').modal('hide');

					if(responseText != ''){
						bootbox.alert('<h2><i class="fas fa-exclamation-triangle text-warning"></i> Warning</h2><div class="lead p-3">' + responseText + '</div>');
						return false;
					}
					else {
						bootbox.alert('<h2><i class="fas fa-check-square text-success"></i> Message</h2><div class="lead p-3">The sample information has been updated.</div>', function(){ location.reload(true); });
					}
					return true;
				}
			};
			$('#form_edit_sample_info').ajaxForm(options_edit_sample_info);




			// Edit Data File Information
			$(document).on('click', '.edit_data_info', function(){

				var rowid = $(this).attr('rowid');

				$.ajax({
					type: 'GET',
					url: 'bxgenomics_exe.php?action=get_data_info&experiment_id=<?php echo $experiment_id; ?>&sample_id=<?php echo $sample_id; ?>&data_id=' + rowid + '',
					success: function(responseText){
						$('#data_content').html(responseText);
						$('#myModal_edit_data_info').modal();
					}
				});

			});

			var options_edit_data_info = {
				url: 'bxgenomics_exe.php?action=edit_data_info',
				type: 'post',
				beforeSubmit: function(formData, jqForm, options) {
					if($('#Name_Data').val() == '' || $('#Read_Number').val() == '' || $('#Phred_Score').val() == ''){
						bootbox.alert('<h2><i class="fas fa-exclamation-triangle text-warning"></i> Warning</h2><div class="lead p-3">Please enter values for all fields.</div>');
						return false;
					}
					return true;
				},
				success: function(responseText, statusText){
					$('#myModal_edit_data_info').modal('hide');

					if(responseText != ''){
						bootbox.alert('<h2><i class="fas fa-exclamation-triangle text-warning"></i> Warning</h2><div class="lead p-3">' + responseText + '</div>');
						return false;
					}
					else {
						bootbox.alert('<h2><i class="fas fa-check-square text-success"></i> Message</h2><div class="lead p-3">The data file information has been updated.</div>', function(){ location.reload(true); });
					}
					return true;
				}
			};
			$('#form_edit_data_info').ajaxForm(options_edit_data_info);


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


					<div class="w-100 d-flex flex-row mt-2">
						<p class="align-self-baseline mr-2">Sample: </p>
						<h3 class="align-self-baseline"><?php echo $sample_info['Name']; ?></h3>
						<?php if($_SESSION['BXAF_ADVANCED_USER']){ ?>
							<p class="align-self-baseline ml-3"><a href="javascript:void(0);" class="text-danger btn_delete" type="sample" rowid="<?php echo $sample_id; ?>"><i class="fas fa-times"></i> Delete Sample</a></p>
						<?php } // if($_SESSION['BXAF_ADVANCED_USER']){ ?>
					</div>

					<div class="w-100 d-flex flex-row mt-2">
						<p class="align-self-baseline mr-2">Experiment: </p>
						<h3 class="align-self-baseline"><?php echo $experiment_name; ?></h3>
						<?php if($_SESSION['BXAF_ADVANCED_USER']){ ?>
							<p class="align-self-baseline ml-3"><a href='experiment.php?id=<?php echo $experiment_id; ?>' class='ml-1'><i class="fas fa-angle-double-right"></i> Review</a></p>
							<p class="align-self-baseline ml-3"><a href='new_analysis.php?id=<?php echo $experiment_id; ?>' class='ml-1'><i class="fas fa-plus"></i> Start New Analysis</a></p>
						<?php } // if($_SESSION['BXAF_ADVANCED_USER']){ ?>

					</div>

					<div class="w-100 my-2">
						<label class="">Experiment Samples: </label>
					<?php
						foreach($sample_idnames as $sid=>$sname){
							echo '<a class="mx-2 my-1 btn btn-sm ' . ($sid == $sample_id ? "btn-success" : "btn-outline-primary") . '" href="sample.php?id=' . $sid . '">' . $sname . '</a> ';
						}
					?>
					</div>

					<div class="w-100 mt-5">
						<h3>Sample Details  <?php if($_SESSION['BXAF_ADVANCED_USER']) echo '<a style="font-size: 1rem;" href="javascript:void(0);" class="ml-2 edit_sample_info"><i class="fas fa-edit"></i> Edit</a>'; ?></h3>

						<div class='my-1 ml-3'><span class='font-weight-bold'>Treatment: </span> <span class=''><?php echo $sample_info['Treatment_Name']; ?></span>  </div>
						<div class='my-1 ml-3'><span class='font-weight-bold'>Data Type: </span> <span class=''><?php echo $sample_info['Data_Type']; ?></span>  </div>
						<div class='my-1 ml-3'><span class='font-weight-bold'>Description: </span> <span class=''><?php echo $sample_info['Description']; ?></span>  </div>

						<h3 class="mt-5">Sample Data Files  <?php if($_SESSION['BXAF_ADVANCED_USER']) echo '<a style="font-size: 1rem;" href="experiment.php?id=' . $experiment_id . '" class="ml-2"><i class="fas fa-file"></i> Manage experiment data files</a> <a style="font-size: 1rem;" href="javascript:void(0);" class="ml-2 edit_data_info" rowid="0"><i class="fas fa-plus-square"></i> Create New Data File Record</a>'; ?></h3>

						<?php if(count($sample_datafiles) > 0){  ?>
						<div class="w-100 my-4">
							<table class="datatables table table-bordered table-hover mt-3">
						        <thead>
						            <tr class="table-info">
						                <th>File Name</th>
						                <th>Size</th>
						                <th>Read Number</th>
						                <th>Phred Score</th>
										<th>Actions</th>
						            </tr>
						        </thead>
						        <tbody>
								<?php
							        foreach($sample_datafiles as $data_id=>$data){
							            echo '<tr>';
							                echo '<td>' . $data['Name'] . '</td>';
							                echo '<td>' . format_size($data['Size']) . '</td>';
							                echo '<td>' . $data['Read_Number'] . '</td>';
							                echo '<td>' . $data['Phred_Score'] . '</td>';
							                echo '<td class="text-nowrap"><a href="javascript:void(0);" class="text-danger btn_delete" type="data" rowid="' . $data_id .'"><i class="fas fa-times"></i> Delete File</a> <a href="javascript:void(0);" class="ml-2 edit_data_info" rowid="' . $data_id . '"><i class="fas fa-edit"></i> Edit File</a></td>';
							            echo '</tr>';
							        }
								?>
							    </tbody>
							</table>
						</div>
						<?php } // if(count($sample_datafiles) > 0){  ?>

					</div>


					<div class="w-100">
						<div id='debug'></div>
					</div>





<!-- Edit Sample Info Modal -->
<form id="form_edit_sample_info" role="form">

	<input name="sample_id" value="<?php echo $sample_id; ?>" hidden>
	<input name="experiment_id" value="<?php echo $experiment_id; ?>" hidden>

	<div class="modal fade" id="myModal_edit_sample_info">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
			    <div class="modal-header">
		          <h4 class="modal-title">Edit Sample Information</h4>
		          <button type="button" class="close pull-right" data-dismiss="modal" aria-label="Close">
		            <span aria-hidden="true">&times;</span>
		          </button>
			    </div>

			  	<div class="modal-body">

					<div class="row mt-2">
						<div class="col-md-3 text-right"><strong>Name: </strong></div>
						<div class="col-md-9">
							<input id="Name_Sample" name="Name" value="<?php echo $sample_info['Name']; ?>" class="form-control" required>
						</div>
					</div>
					<div class="row mt-2">
						<div class="col-md-3 text-right"><strong>Treatment: </strong></div>
						<div class="col-md-9">
							<input id="Treatment_Name" name="Treatment_Name" value="<?php echo $sample_info['Treatment_Name'] ?>" class="form-control" required>
						</div>
					</div>
					<div class="row mt-2">
						<div class="col-md-3 text-right"><strong>Description: </strong></div>
						<div class="col-md-9">
							<input id="Description" name="Description" value="<?php echo $sample_info['Description']; ?>" class="form-control">
						</div>
					</div>
					<div class="row mt-2">
						<div class="col-md-3 text-right"><strong>Data Type: </strong></div>
						<div class="col-md-9">
							<select id="Data_Type" name="Data_Type" class="custom-select">
								<?php
									$sample_types = array(
										'PE' => 'fastq, Paired-end (PE)',
										'SE' => 'fastq, Single-end (SE)',
										'bam' => 'bam, sorted and indexed (.sorted.bam)',
										'gene_counts' => 'Gene counts (.txt)',
									);
									foreach($sample_types as $k=>$v){
										echo '<option value="'. $k .'" ' . ($sample_info['Data_Type'] == $k ? "selected" : "") . '>'. $v .'</option>';
									}
								?>
							</select>
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



<!-- Edit Data Info Modal -->
<form id="form_edit_data_info" role="form">
	<div class="modal fade" id="myModal_edit_data_info">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
			    <div class="modal-header">
		          <h4 class="modal-title" id="edit_data_modal_title">Add/Edit Data File Information</h4>
		          <button type="button" class="close pull-right" data-dismiss="modal" aria-label="Close">
		            <span aria-hidden="true">&times;</span>
		          </button>
			    </div>

			  	<div class="modal-body" id="data_content"></div>

			  	<div class="modal-footer">
					<button type="submit" class="btn btn-primary">Save</button>
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