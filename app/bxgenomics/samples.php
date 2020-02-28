<?php
include_once(__DIR__ . "/config/config.php");

$sql = "SELECT * FROM `".$BXAF_CONFIG['TBL_BXGENOMICS_SAMPLE']."` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} ";
if(isset($_GET['expid']) && intval($_GET['expid']) > 0) $sql .= " AND `Experiment_ID` = " . intval($_GET['expid']);
$sample_info = $BXAF_MODULE_CONN -> get_assoc('ID', $sql, $experiment_id);

if(! is_array($sample_info) || count($sample_info) <= 0){
	header("Location: experiments.php");
	exit();
}

$sql = "SELECT `ID`, `Name` FROM `".$BXAF_CONFIG['TBL_BXGENOMICS_EXPERIMENT']."` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} ";
$experiment_idnames = $BXAF_MODULE_CONN -> get_assoc('ID', $sql);


?><!DOCTYPE html>
<html lang="en">
<head>
	<?php include_once($BXAF_CONFIG['BXAF_PAGE_HEADER']); ?>

	<link   href='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/datatables/datatables.min.css.php' rel='stylesheet' type='text/css'>
	<script src='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/datatables/datatables.min.js.php'></script>

	<script type="text/javascript">
		$(document).ready(function(){

			$('.datatables').DataTable({ "pageLength": 10, "lengthMenu": [[10, 100, 500, 1000], [10, 100, 500, 1000]], dom: 'Blfrtip', buttons: ['colvis','copy','csv'] });

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


					<h3 class="my-2">My Samples</h3>

					<?php
					if(! is_array($sample_info) || count($sample_info) <= 0){
						echo "<div class='my-3 text-danger'>No samples has been created yet.</div>";
					}
					else {
					?>
					<div class="w-100 my-4">
						<table class="datatables table table-bordered table-hover mt-3">
							<thead>
								<tr class="table-info">
									<th>Sample Name</th>
									<th>Experiment</th>
									<th>Treatment</th>
									<th>Data Type</th>
									<th>Description</th>
									<th>Actions</th>
								</tr>
							</thead>
							<tbody>
							<?php
								foreach($sample_info as $sample_id=>$sample){
									$actions = '';
									if($_SESSION['BXAF_ADVANCED_USER']){
										$actions .= '<a href="sample.php?id=' . $sample_id . '" class="mr-2"><i class="fas fa-edit"></i> Edit</a>';
										$actions .= '<a href="javascript:void(0);" class="ml-2 text-danger btn_delete" type="sample" rowid="' . $sample_id . '"><i class="fas fa-times"></i> Delete</a>';
										$actions .= '<a href="new_sample.php?expid=' . $sample['Experiment_ID'] . '" class="ml-2"><i class="fas fa-plus-square"></i> Add Samples</a>';
									}

									echo '<tr>';
										echo '<td><a href="sample.php?id=' . $sample_id . '">' . $sample['Name'] . '</a></td>';
										echo '<td><a href="experiment.php?id=' . $sample['Experiment_ID'] . '">' . $experiment_idnames[ $sample['Experiment_ID'] ] . '</a></td>';
										echo '<td>' . $sample['Treatment_Name'] . '</td>';
										echo '<td>' . $sample['Data_Type'] . '</td>';
										echo '<td>' . $sample['Description'] . '</td>';
										echo '<td class="text-nowrap">' . $actions . '</td>';
									echo '</tr>';
								}
							?>
							</tbody>
						</table>
					</div>
					<?php } // if(count($sample_info) > 0){  ?>


				</div>
            </div>
		    <?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>
		</div>
	</div>
</body>
</html>