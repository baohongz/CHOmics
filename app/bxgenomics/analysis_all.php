<?php

include_once(__DIR__ . "/config/config.php");

$sql = "SELECT * FROM `".$BXAF_CONFIG['TBL_BXGENOMICS_ANALYSIS']."` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} ";
if(isset($_GET['expid']) && intval($_GET['expid']) > 0) $sql .= " AND `Experiment_ID` = " . intval($_GET['expid']);
$all_analyses = $BXAF_MODULE_CONN -> get_assoc('ID', $sql, $experiment_id);

if(! is_array($all_analyses) || count($all_analyses) <= 0){
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

			$('.datatables').DataTable({ "pageLength": 100, "lengthMenu": [[10, 100, 500, 1000], [10, 100, 500, 1000]], "dom": 'Blfrtip', "buttons": ['colvis','copy','csv'] });

			$(document).on('click', '.btn_save_analysis', function() {

				var rowid = $(this).attr('rowid');
				bootbox.confirm(
					'<h2><i class="fas fa-exclamation-triangle text-warning"></i> Warning</h2><div class="lead p-3 text-danger">Are you sure you want to import this analysis as a new project?</div><div class="p-3 text-muted">All related experiment, sample, comparison, and expression data will be imported.</div>',
					function(result){
						if(result) window.location = "tool_import/import_analysis.php?id=" + rowid;
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

					<h3 class="w-100 my-3">My Analyses</h3>

					<div class="w-100 my-4">
						<table class="datatables table table-bordered table-hover mt-3">
					        <thead>
					            <tr class="table-info">
									<th>Analysis Name</th>
					                <th>Experiment</th>
					                <th>Samples</th>
					                <th>Status</th>
					                <th>Actions</th>
					            </tr>
					        </thead>
					        <tbody>
							<?php

								$sql = "SELECT `_Analysis_ID`, `ID` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS']}` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} ";
								$imported_analyses = $BXAF_MODULE_CONN -> get_assoc('_Analysis_ID', $sql);

						        foreach($all_analyses as $analysis_id=>$analysis){

									$class_analysis = new SingleAnalysis($analysis_id);
									$status = $class_analysis -> showAnalysisStatus();

									$actions = array();
									if(array_key_exists($analysis_id, $imported_analyses)){
										$actions[] = '<a target="_blank" href="project.php?id=' . $imported_analyses[ $analysis_id ] . '" title="View Imported Project" class=""> <i class="fas fa-list"></i> View Imported Project </a>';
									}
									else if( key($status) == 'Finished' ){
										$actions[] = '<a href="Javascript: void(0);" title="Import information into Projects" class="btn_save_analysis mx-1" rowid="' . $analysis_id . '"> <i class="fas fa-cloud-upload-alt"></i> Import as Project</a>';
									}

						            echo '<tr>';
						                echo '<td><a href="analysis.php?id=' . $analysis_id . '">' . $analysis['Name'] . '</a></td>';
										echo '<td><a href="experiment.php?id=' . $analysis['Experiment_ID'] . '">' . $experiment_idnames[ $analysis['Experiment_ID'] ] . '</a></td>';
						                echo '<td>' . count( explode(",", $analysis['Samples']) ) . '</td>';
						                echo '<td>' . current( $status ) . '</td>';
						                echo '<td>' . implode("<BR>", $actions ) . '</td>';
						            echo '</tr>';
						        }
							?>
						    </tbody>
						</table>
					</div>

				</div>


            </div>
		    <?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>
		</div>
	</div>

</body>
</html>