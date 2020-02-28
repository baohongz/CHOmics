<?php
include_once(__DIR__ . "/config/config.php");

$sql = "SELECT * FROM `".$BXAF_CONFIG['TBL_BXGENOMICS_EXPERIMENT']."` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} ";
$experiment_info = $BXAF_MODULE_CONN -> get_assoc('ID', $sql);

$sql = "SELECT `Experiment_ID`, COUNT(`ID`) FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_SAMPLE'] . "` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} GROUP BY `Experiment_ID` ";
$sample_counts = $BXAF_MODULE_CONN -> get_assoc('Experiment_ID', $sql);

$sql = "SELECT `Experiment_ID`, COUNT(`ID`) FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_ANALYSIS'] . "` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} GROUP BY `Experiment_ID` ";
$analysis_counts = $BXAF_MODULE_CONN -> get_assoc('Experiment_ID', $sql);

?><!DOCTYPE html>
<html lang="en">
<head>
	<?php include_once($BXAF_CONFIG['BXAF_PAGE_HEADER']); ?>

	<script src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/jquery/jquery.form.min.js"></script>

	<link   href='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/datatables/datatables.min.css.php' rel='stylesheet' type='text/css'>
	<script src='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/datatables/datatables.min.js.php'></script>

	<script type="text/javascript">
		$(document).ready(function(){
			$('.datatables').DataTable({"pageLength": 10, "lengthMenu": [[10, 100, 500, 1000], [10, 100, 500, 1000]], dom: 'Blfrtip', buttons: ['colvis','copy','csv'] });


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


			$(document).on('click', '.new_experiment_btn', function(){
				$('#myModal_new_experiment').modal();
			});

			var options_new_experiment = {
				url: 'bxgenomics_exe.php?action=new_experiment',
				type: 'post',
				beforeSubmit: function(formData, jqForm, options) {
					if($('#Experiment_Name').val() == ''){
						bootbox.alert('<h2><i class="fas fa-exclamation-triangle text-warning"></i> Warning</h2><div class="lead p-3">Experiment name is required.</div>');
						return false;
					}
					return true;
				},
				success: function(responseText, statusText){
					if(responseText != ''){
						bootbox.alert('<h2><i class="fas fa-exclamation-triangle text-warning"></i> Warning</h2><div class="lead p-3">' + responseText + '</div>');
						return false;
					}
					else {
						bootbox.alert('<h2><i class="fas fa-check-square text-success"></i> Message</h2><div class="lead p-3">New experiment has been created.</div>', function(){ location.reload(true); });
					}
				}
			};
			$('#form_new_experiment').ajaxForm(options_new_experiment);
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



					<div class="d-flex flex-row mt-3">
						<h3 class="align-self-baseline">My Experiments</h3>
						<?php if($_SESSION['BXAF_ADVANCED_USER']){ ?>
							<p class="align-self-baseline ml-3">
								<a href="javascript:void(0);" class="new_experiment_btn btn_link_small_success">
									<i class="fas fa-plus-square"></i> Create New Experiment
								</a>
							</p>
						<?php } // if($_SESSION['BXAF_ADVANCED_USER']){ ?>
					</div>


				<?php
				if(! is_array($experiment_info) || count($experiment_info) <= 0){
					echo "<div class='my-3 text-danger'>No experiment has been created yet.</div>";
				}
				else {
				?>
				<div class="w-100 my-4">
					<table class="datatables table table-bordered table-hover mt-3">
						<thead>
							<tr class="table-info">
								<th>Experiment Name</th>
								<th>Date Created</th>
								<th>Samples</th>
								<th>Analysis</th>
								<th>Description</th>
								<th>Actions</th>
							</tr>
						</thead>
						<tbody>
						<?php
							foreach($experiment_info as $experiment_id=>$experiment){
								$actions = '';
								if($_SESSION['BXAF_ADVANCED_USER']){
									$actions .= '<a href="experiment.php?id=' . $experiment_id . '" class="mr-2"><i class="fas fa-edit"></i> Edit</a>';
									$actions .= '<a href="javascript:void(0);" class="ml-2 text-danger btn_delete" type="experiment" rowid="' . $experiment_id . '"><i class="fas fa-times"></i> Delete</a>';
									if($sample_counts[$experiment_id] > 0) $actions .= '<BR><a class="text-success" href="new_analysis.php?id=' . $experiment_id . '"><i class="fas fa-plus-square"></i> Start New Analysis</a>';
								}

								echo '<tr>';
									echo '<td><a href="experiment.php?id=' . $experiment_id . '">' . $experiment['Name'] . '</a></td>';
									echo '<td>' . substr($experiment['Time_Created'], 0, 10) . '</td>';
									echo '<td><a href="samples.php?expid=' . $experiment_id . '">' . $sample_counts[$experiment_id] . '</a></td>';
									echo '<td><a href="analysis_all.php?expid=' . $experiment_id . '">' . $analysis_counts[$experiment_id] . '</a></td>';
									echo '<td>' . $experiment['Description'] . '</td>';
									echo '<td class="text-nowrap">' . $actions . '</td>';
								echo '</tr>';
							}
						?>
						</tbody>
					</table>
				</div>
				<?php } // if(count($experiment_info) > 0){  ?>






<!-- New Experiment Modal -->
<form id="form_new_experiment" enctype="multipart/form-data" role="form">
	<div class="modal fade" id="myModal_new_experiment">
		<div class="modal-dialog" role="document">
			<div class="modal-content">

				<div class="modal-header">
					<h4 class="modal-title" id="myModalLabel">Create New Experiment</h4>
					<button type="button" class="close" data-dismiss="modal">
						<span aria-hidden="true">&times;</span>
						<span class="sr-only">Close</span>
					</button>
				</div>

				<div class="modal-body">
					<div class="px-3">
						<div class="font-weight-bold my-1">Name: (required)</div>
						<input name="Name" id="Experiment_Name" class="form-control" placeholder="Experiment Name" required>

						<div class="mt-3">Description:</div>
						<textarea name="Description" id="Description" placeholder="Experiment Description" class="form-control"></textarea>
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



				</div>
            </div>
		    <?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>
		</div>
	</div>
</body>
</html>