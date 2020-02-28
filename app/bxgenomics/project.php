<?php

include_once(dirname(__FILE__) . "/config/config.php");



//-----------------------------------------------------------------------------------------
// Helper function to generate templates in project page
//-----------------------------------------------------------------------------------------
function generate_template_project_samples($project_id, $TYPE='Sample') {

	global $BXAF_CONFIG, $BXAF_MODULE_CONN;

	$PROJECT_DIR = $BXAF_CONFIG['USER_FILES']['TOOL_PROJECTS'] . $project_id;

	$TABLE     = ($TYPE == 'Sample') ? $BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES'] : $BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS'];
	if (!is_dir($PROJECT_DIR)) mkdir($PROJECT_DIR, 0755, true);

	$FILE_NAME = ($TYPE == 'Sample') ? 'samples_template.csv' : 'comparisons_template.csv';

	// Get Cols
	$sample_colnames_src = $BXAF_MODULE_CONN -> get_column_names($TABLE);
	$sample_colnames = array();
	foreach ($sample_colnames_src as $col) {
		if (substr($col, 0, 1) != '_' && !in_array($col, array('_Owner_ID', 'Permission', 'Time_Created', 'bxafStatus'))) {
			$sample_colnames[] = $col;
		}
	}
	$sql = "SELECT * FROM `{$TABLE}` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `_Projects_ID`={$project_id} ";
	$rs = $BXAF_MODULE_CONN -> get_all($sql);

	// Generate File
	$file = fopen($PROJECT_DIR . "/" . $FILE_NAME, "w");
	fputcsv($file, $sample_colnames);
	foreach ($rs as $sample) {
		$info = array();
		foreach ($sample_colnames as $col) {
			$info[$col] = $sample[$col];
		}
		fputcsv($file, $info);
	}
	fclose($file);

	return $BXAF_CONFIG['BXAF_URL'] . 'app/bxgenomics/download.php?f=' . bxaf_encrypt( str_replace($BXAF_CONFIG['BXAF_DIR'], '', $PROJECT_DIR . "/" . $FILE_NAME), $BXAF_CONFIG['BXAF_KEY'] );

}




$project_info = array();
if(isset($_GET['id']) && $_GET['id'] != ''){
	$sql = "SELECT * FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS']}` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `ID` = ?i";
	$project_info = $BXAF_MODULE_CONN -> get_row($sql, intval($_GET['id']));
}
else if(isset($_GET['name']) || $_GET['name'] != ''){
	$sql = "SELECT * FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS']}` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `Name` = ?s";
	$project_info = $BXAF_MODULE_CONN -> get_row($sql, $_GET['name']);
}
if (!is_array($project_info) || count($project_info) <= 1) {
	header("Location: index.php");
	exit();
}
$project_id = $project_info['ID'];

$sample_template_url     = generate_template_project_samples($project_id, 'Sample');
$comparison_template_url = generate_template_project_samples($project_id, 'Comparison');


//-----------------------------------------------------------------------------------------
// Existing Samples
//-----------------------------------------------------------------------------------------
$sql = "SELECT * FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES']}` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `_Projects_ID`={$project_id} ";
$sample_info = $BXAF_MODULE_CONN -> get_assoc('ID', $sql);
$number_samples = count($sample_info);

//-----------------------------------------------------------------------------------------
// Existing Comparisons
//-----------------------------------------------------------------------------------------
$sql = "SELECT * FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS']}` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `_Projects_ID`={$project_id} ";
$comparison_info = $BXAF_MODULE_CONN -> get_assoc('ID', $sql);
$number_comparisons = count($comparison_info);


$editable = false;
if($project_info['_Owner_ID'] > 0) $editable = true;


$dir = $BXAF_CONFIG['TABIX_IMPORT_DIR'] . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'] . "/{$project_id}/";
$url = $BXAF_CONFIG['TABIX_IMPORT_URL'] . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'] . "/{$project_id}/";

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

					<h1 class="w-100 my-3">
						<span style="font-size: 1.5rem;" class="text-danger">Project: </span>
						<span class="text-success"><?php echo $project_info['Name']; ?></span>
					</h1>

					<div class="w-100 my-3">
						<a class="mx-1" href="tool_search/index.php?type=project">
							<i class="fas fa-search"></i> Search All Projects
						</a>
<?php if($editable){ ?>
						<a class="mx-1" href="javascript: void(0);" data-toggle="modal" data-target="#modal_update_project">
							<i class="fas fa-caret-right"></i> Edit Project Details
						</a>
						<a class="mx-1 text-danger btn_delete_project" href="javascript: void(0);" rowid="<?php echo $project_id; ?>" action_type="delete_project" >
							<i class="fas fa-times"></i> Delete this Project
						</a>
						<?php

							if(file_exists($dir . "ngs_expression_data.txt") ){
								$u = "download.php?f=" . bxaf_encrypt(str_replace($BXAF_CONFIG['BXAF_DIR'], '', "{$dir}ngs_expression_data.txt"), $BXAF_CONFIG['BXAF_KEY']);
								echo '<a class="mx-1 text-danger btn_delete_project" href="javascript: void(0);" rowid="' . $project_id . '" action_type="delete_sample_data" ><i class="fas fa-times"></i> Clear Sample Data</a> <a class="mx-1 text-success" href="' . $u . '"><i class="fas fa-download"></i> Download Sample Data</a>';
							}
							else if(file_exists($dir . "array_expression_data.txt") ){
								$u = "download.php?f=" . bxaf_encrypt(str_replace($BXAF_CONFIG['BXAF_DIR'], '', "{$dir}array_expression_data.txt"), $BXAF_CONFIG['BXAF_KEY']);
								echo '<a class="mx-1 text-danger btn_delete_project" href="javascript: void(0);" rowid="' . $project_id . '" action_type="delete_sample_data" ><i class="fas fa-times"></i> Clear Sample Data</a> <a class="mx-1 text-success" href="' . $u . '"><i class="fas fa-download"></i> Download Sample Data</a>';
							}
							else{
								echo "<span class='text-muted ml-5'>(No sample data uploaded)</span>";
							}

							if( file_exists($dir . "comparison_data.txt") ){
								$u = "download.php?f=" . bxaf_encrypt(str_replace($BXAF_CONFIG['BXAF_DIR'], '', "{$dir}comparison_data.txt"), $BXAF_CONFIG['BXAF_KEY']);
								echo '<a class="mx-1 text-danger btn_delete_project" href="javascript: void(0);" rowid="' . $project_id . '" action_type="delete_comparison_data" ><i class="fas fa-times"></i> Clear Comparison Data</a> <a class="mx-1 text-success" href="' . $u . '"><i class="fas fa-download"></i> Download Comparison Data</a>';
							}
							else{
								echo "<span class='text-muted ml-5'>(No comparison data uploaded)</span>";
							}
						?>
<?php } //if($editable){ ?>
					</div>

					<div class='w-100 rounded border border-primary mx-0 mt-3 p-3'>

						<?php
							if($number_samples <= 0 && $number_comparisons <= 0) {
								echo '<div class="w-100 my-2">';
									echo 'No samples and comparisons found in this project.';
								echo '</div>';
							}

							if($number_samples > 0) {
								echo '<div class="w-100 my-2">';
									echo '<i class="fas fa-caret-right"></i> Based on gene expression results from the <strong>' . ( ($number_samples > 1) ? "$number_samples samples" : " 1 sample") . '</strong> in this project:';
								echo '</div>';

								echo '<div class="w-100 my-2">';
									echo '<a title="" class="mx-2" href="tool_gene_expression_plot/index.php?project_id=' . $project_id . '" target="_blank"><span class="badge badge-pill table-success text-danger">GE</span> Plot Gene Expression</a> ';
									echo '<a title="" class="mx-2" href="tool_heatmap/index.php?project_id=' . $project_id . '" target="_blank"><span class="badge badge-pill table-success text-danger">H</span> Generate Heatmap</a> ';
									echo '<a title="" class="mx-2" href="tool_correlation/index.php?project_id=' . $project_id . '" target="_blank"><span class="badge badge-pill table-success text-danger">CG</span> Find Correlated Genes</a> ';
									echo '<a title="" class="mx-2" href="tool_pca/index_genes_samples.php?project_id=' . $project_id . '" target="_blank"><span class="badge badge-pill table-success text-danger">PCA</span> PCA Analysis</a> ';
								echo '</div>';
							}


							if($number_comparisons > 0) {
								echo '<div class="w-100 my-2">';
									echo '<i class="fas fa-caret-right"></i> Using results from the <strong>' . ( ($number_comparisons > 1) ? "$number_comparisons comparisons" : " 1 comparison") . '</strong> in this project:';
								echo '</div>';

								echo '<div class="w-100 my-2">';
									echo '<a title="" class="mx-2" href="tool_pathway/changed_genes.php?project_id=' . $project_id . '" target="_blank"><span class="badge badge-pill table-success text-danger">C</span> Find Significantly Changed Genes</a> ';
									if($number_comparisons > 1) echo '<a title="" class="mx-2" href="tool_pathway_heatmap/index.php?project_id=' . $project_id . '" target="_blank"><span class="badge badge-pill table-success text-danger">PH</span> Pathway Heatmap</a> ';
									echo '<a title="" class="mx-2" href="tool_bubble_plot/multiple.php?project_id=' . $project_id . '" target="_blank"><span class="badge badge-pill table-success text-danger">B</span> Visualize Gene Changes in Bubble plot</a> ';
									if($number_comparisons > 1) echo '<a title="" class="mx-2" href="tool_meta_analysis/index.php?project_id=' . $project_id . '" target="_blank"><span class="badge badge-pill table-success text-danger">M</span> Meta Analysis</a> ';
								echo '</div>';

								echo '<div class="w-100 my-2">';
									echo '<i class="fas fa-caret-right"></i> Map Gene Changes to Pathways: ';
									echo '<a title="" class="mx-2" href="tool_pathway/index.php?project_id=' . $project_id . '" target="_blank"><span class="badge badge-pill table-success text-danger">W</span> WikiPathways</a> ';
									echo '<a title="" class="mx-2" href="tool_pathway/reactome.php?project_id=' . $project_id . '" target="_blank"><span class="badge badge-pill table-success text-danger">R</span> Reactome Pathways</a> ';
									echo '<a title="" class="mx-2" href="tool_pathway/kegg.php?project_id=' . $project_id . '" target="_blank"><span class="badge badge-pill table-success text-danger">K</span> KEGG Pathways</a> ';
								echo '</div>';

							}
						?>

						<?php
							if($project_info['_Analysis_ID'] > 0) {
								$analysis_id = intval($project_info['_Analysis_ID']);
							    $analysis_id_encrypted = $analysis_id . '_' . bxaf_encrypt($analysis_id, $BXAF_CONFIG['BXAF_KEY']);
								echo '<div class="w-100 my-3"><a title="View Analysis Report" style="font-size: 1rem;" class="ml-2 btn btn-danger" href="report_full_user.php?analysis=' . $analysis_id_encrypted . '" target="_blank">View Analysis Report</a></div>';
							}
						?>


					</div>

					<div class="row mt-5 w-100 mx-0">

						<h3 class="w-100 mb-3">
							<span class=""><i class="fas fa-list text-success" aria-hidden="true"></i> Project Details </span>

<?php if($editable){ ?>
							<a style="font-size: 1rem;" class=" ml-5" href="javascript: void(0);" data-toggle="modal" data-target="#modal_update_project">
								<i class="fas fa-caret-right"></i> Edit Project Details
							</a>
<?php } //if($editable){ ?>

						<a style="font-size: 1rem;" class=" ml-5" href="javascript: void(0);" onclick="if($('#project_additional_details').hasClass('hidden')) $('#project_additional_details').removeClass('hidden'); else $('#project_additional_details').addClass('hidden');  ">
							<i class="fas fa-caret-right"></i> Show/Hide All Details
						</a>

						</h3>


						<?php

							$project_cols = array(
								'Name'                => 'Name',
								'Disease'             => 'Disease',
								'PlatformName'        => 'PlatformName',
								'Description'         => 'Description',
							);

							echo '<div class="w-100"><table class="table table-bordered table-hover"><tbody>';
							foreach ($project_cols as $key => $value) {
								if($project_info[$key] != '' && $project_info[$key] != 'NA'){
									echo "
									<tr>
										<th style='width: 15rem;'>{$value}</th>
										<td>{$project_info[$key]}</td>
									</tr>";
								}
							}
							echo '</tbody></table></div>';


							$project_cols = array_diff($BXAF_CONFIG['TBL_BXGENOMICS_FIELDS']['Project'], array_keys($project_cols));

							echo '<div id="project_additional_details" class="w-100 hidden"><table class="table table-bordered table-hover"><tbody>';
							foreach ($project_cols as $key) {
								$value = str_replace('_', ' ', $key);
								if($project_info[$key] != '' && $project_info[$key] != 'NA'){
									echo "
									<tr>
										<th style='width: 15rem;'>{$value}</th>
										<td>{$project_info[$key]}</td>
									</tr>";
								}
							}
							echo '</tbody></table></div>';

						?>

					</div>









					<!----------------------------------------------------------------------------------->
					<!-- Existing Comparisons -->
					<!----------------------------------------------------------------------------------->
<?php if($number_comparisons > 0) { ?>
					<div class="row mt-5 w-100 mx-0">
						<h3 class="w-100 mb-3"><i class="fas fa-database text-success" aria-hidden="true"></i> Comparisons in Project: <?php echo $number_comparisons; ?></h3>


<?php if($editable){ ?>
						<div class="my-3">
							<a href="javascript:void(0);" class="mx-3" onclick="$('#form_batch_update_comparisons').slideToggle();">
								<i class="fas fa-angle-double-right"></i> Batch update comparison information
							</a>
						</div>

						<form id="form_batch_update_comparisons" style="display:none;" class="w-100">
							<div class="alert alert-info">
								<input name="rowid" value="<?php echo $project_id; ?>" hidden>
								<input type="file" name="file" required /><br />
								<button type="submit" class="btn btn-primary my-2 mr-2" id="btn_submit_batch_update_comparisons">
									<i class="fas fa-upload"></i> Upload
								</button>
								<a href="<?php echo $comparison_template_url; ?>" target="_blank">
									<i class="fas fa-download"></i> Download template
								</a>
							</div>
						</form>
<?php } //if($editable){ ?>


<?php
	$default_columns = $BXAF_CONFIG['USER_PREFERENCES']['table_column_comparison'];

	$platform = $project_info['PlatformName'];
	if(strpos($project_info['Platform'], 'GPL') === 0){
		$p = preg_replace("/^\[.*\]\s+/", "", $project_info['PlatformName']);
		$p = preg_replace("/\s*\[.*\]$/", "", $p);
		$p = preg_replace("/\s*\(.*\)$/", "", $p);
		$p = preg_replace("/\s*Array$/", "",  $p);
		$platform = '<a target="_blank" href="https://www.ncbi.nlm.nih.gov/geo/query/acc.cgi?acc=' . $project_info['Platform'] . '">' . $p . '</a>';
	}

	$project = '<a target="_blank" href="project.php?id=' . $project_info['ID'] . '">' . $project_info['Name'] . '</a>';

	echo "<input type='hidden' id='input_all_comparison_ids' value='" . implode(",", array_keys($comparison_info)). "' />";
	echo '<div class="w-100 mb-3">';
        echo '<a class="m-1 btn btn-sm btn-primary btn_comparison_actions" action_type="tool_save_lists/new_list.php?category=comparison&comparison_ids=" href="Javascript: void(0);"><i class="fas fa-caret-right mx-1"></i> Save Comparison List</a>';
        echo '<a class="m-1 btn btn-sm btn-primary btn_comparison_actions" action_type="tool_bubble_plot/multiple.php?comparison_ids=" href="Javascript: void(0);"><i class="fas fa-caret-right mx-1"></i> Bubble Plot</a>';
        echo '<a class="m-1 btn btn-sm btn-primary btn_comparison_actions" action_type="tool_pathway/changed_genes.php?comparison_ids=" href="Javascript: void(0);"><i class="fas fa-caret-right mx-1"></i> Significantly Changed Genes</a>';
        echo '<a class="m-1 btn btn-sm btn-primary btn_comparison_actions" action_type="tool_pathway_heatmap/index.php?comparison_ids=" href="Javascript: void(0);"><i class="fas fa-caret-right mx-1"></i> Pathway Heatmap</a>';
        echo '<a class="m-1 btn btn-sm btn-primary btn_comparison_actions" action_type="tool_meta_analysis/index.php?comparison_ids=" href="Javascript: void(0);"><i class="fas fa-caret-right mx-1"></i> Meta Analysis</a>';
        echo '<a class="m-1 btn btn-sm btn-primary btn_comparison_actions" action_type="tool_export/genes_comparisons.php?comparison_ids=" href="Javascript: void(0);"><i class="fas fa-caret-right mx-1"></i> Export Comparison Data</a>';

		echo '<a class="m-1 btn btn-sm btn-primary btn_comparison_actions" action_type="tool_pathway/index.php?comparison_ids=" href="Javascript: void(0);"><i class="fas fa-caret-right mx-1"></i> WikiPathways</a>';
		echo '<a class="m-1 btn btn-sm btn-primary btn_comparison_actions" action_type="tool_pathway/reactome.php?comparison_ids=" href="Javascript: void(0);"><i class="fas fa-caret-right mx-1"></i> Reactome Pathways</a>';
		echo '<a class="m-1 btn btn-sm btn-primary btn_comparison_actions" action_type="tool_pathway/kegg.php?comparison_ids=" href="Javascript: void(0);"><i class="fas fa-caret-right mx-1"></i> KEGG Pathways</a>';

    echo '</div>';

	echo '<div class="w-100">';
		echo '<table class="table table-bordered table-hover datatable">';
			echo '<thead><tr class="table-info">';
				echo "<th class='text-center'><input type='checkbox' class='bxaf_checkbox3 bxaf_checkbox_all3' /></th>";
				echo '<th>ID</th>';
				echo '<th>Actions</th>';
				foreach($default_columns as $col){
					echo '<th>' . str_replace('_', ' ', $col) . '</th>';
				}
			echo '</tr></thead>';
			echo '<tbody>';

			foreach ($comparison_info as $id => $comp) {

				echo '<tr>';
				echo "<td class='text-center'><input type='checkbox' class='bxaf_checkbox3 bxaf_checkbox_one3' rowid='" . $id . "' /></td>";
				echo '<td><a href="tool_search/view.php?type=comparison&amp;id=' . $id . '" title="View comparison details" target="_blank">' . $id . '</a></td>';
				echo '<td>';
					echo '<a href="tool_search/view.php?type=comparison&amp;id=' . $id . '" title="View comparison details" target="_blank" class=" mr-1"><i class="fas fa-list-ul"></i></a>

					<a href="tool_bubble_plot/multiple.php?comparison_id=' . $id . '" title="Bubble Plot" target="_blank" class=" mr-1"><span class="badge badge-pill table-success text-danger">B</span></a>
					<a href="tool_meta_analysis/index.php?comparison_id=' . $id . '" title="Meta Analysis" target="_blank" class=" mr-1"><span class="badge badge-pill table-success text-danger">M</span></a>
					<a href="tool_pathway_heatmap/index.php?comparison_id=' . $id . '" title="Pathway Heatmap" target="_blank" class=" mr-1"><span class="badge badge-pill table-success text-danger">PH</span></a>
					<a href="tool_pathway/changed_genes.php?comparison_id=' . $id . '" title="Significantly Changed Genes" target="_blank" class=" mr-1"><span class="badge badge-pill table-success text-danger">C</span></a>

					<a href="tool_volcano_plot/index.php?comparison_id=' . $id . '" title="Volcano Plot" target="_blank" class=" mr-1"><span class="badge badge-pill table-success text-danger">V</span></a>
					<a href="tool_pathway/index.php?comparison_id=' . $id . '" title="WikiPathways" target="_blank" class=" mr-1"><span class="badge badge-pill table-success text-danger">W</span></a>
					<a href="tool_pathway/reactome.php?comparison_id=' . $id . '" title="Reactome Pathways" target="_blank" class=" mr-1"><span class="badge badge-pill table-success text-danger">R</span></a>
					<a href="tool_pathway/kegg.php?comparison_id=' . $id . '" title="KEGG Pathways" target="_blank" class=" mr-1"><span class="badge badge-pill table-success text-danger">K</span></a>';

					if($editable){
						echo '
							<a title="Edit comparison information" href="edit_comparison.php?compid=' . $id . '" class="text-success mx-1" target="_blank">
								<i class="fas fa-edit fa-lg"></i>
							</a>
							<a title="Remove this comparison from project" href="javascript:void(0);" rowid="' . $id . '" class="text-danger btn_delete mx-1" type="comparison">
								<i class="fas fa-times fa-lg"></i>
							</a>';
					}
				echo '</td>';

				foreach($default_columns as $col){
					if($col == 'PlatformName' || $col == '_Platforms_ID'){
						echo '<td>' . $platform . '</td>';
					}
					else if($col == '_Projects_ID' || $col == 'Project_Name'){
						echo '<td>' . $project . '</td>';
					}
					else if($col == 'Name'){
						echo '<td><a href="tool_search/view.php?type=comparison&amp;id=' . $id . '" title="View comparison details" target="_blank">' . $comp[$col] . '</a></td>';
					}
					else if($col == 'Case_SampleIDs'){
						echo '<td><a href="Javascript: void(0);" onClick="$(this).next().toggle();">Show/Hide</a><div style="display: none;">' . str_replace(';', ' ', $comp[$col]) . '</div></td>';
					}
					else if($col == 'Control_SampleIDs'){
						echo '<td><a href="Javascript: void(0);" onClick="$(this).next().toggle();">Show/Hide</a><div style="display: none;">' . str_replace(';', ' ', $comp[$col]) . '</div></td>';
					}
					else {
						echo '<td>' . $comp[$col] . '</td>';
					}
				}
				echo '</tr>';
			}

			echo '</tbody>';
		echo '</table>';
	echo '</div>';

?>

					</div>
<?php } //if($number_comparisons > 0) { ?>









					<!----------------------------------------------------------------------------------->
					<!-- Existing Samples -->
					<!----------------------------------------------------------------------------------->
<?php if($number_samples > 0) { ?>
					<div class="row mt-5 w-100 mx-0">
						<h3 class="w-100 mb-3"><i class="fas fa-database text-success" aria-hidden="true"></i> Samples in Project: <?php echo $number_samples; ?></h3>

<?php if($editable){ ?>
						<div class="my-3">
							<a href="javascript:void(0);" class="mx-3" onclick="$('#form_batch_update_samples').slideToggle();">
								<i class="fas fa-angle-double-right"></i> Batch update samples information
							</a>
						</div>

						<form id="form_batch_update_samples" style="display:none;" class="w-100">
							<div class="alert alert-info">
								<input name="rowid" value="<?php echo $project_id; ?>" hidden>
								<input type="file" name="file" required /><br />
								<button type="submit" class="btn btn-primary my-2 mr-2" id="btn_submit_batch_update_samples">
									<i class="fas fa-upload"></i> Upload
								</button>
								<a href="<?php echo $sample_template_url; ?>" target="_blank">
									<i class="fas fa-download"></i> Download template
								</a>
							</div>
						</form>
<?php } //if($editable){ ?>





	<?php
		$default_columns = $BXAF_CONFIG['USER_PREFERENCES']['table_column_sample'];

		$platform = $project_info['PlatformName'];
		if(strpos($project_info['Platform'], 'GPL') === 0){
			$p = preg_replace("/^\[.*\]\s+/", "", $project_info['PlatformName']);
			$p = preg_replace("/\s*\[.*\]$/", "", $p);
			$p = preg_replace("/\s*\(.*\)$/", "", $p);
			$p = preg_replace("/\s*Array$/", "",  $p);
			$platform = '<a target="_blank" href="https://www.ncbi.nlm.nih.gov/geo/query/acc.cgi?acc=' . $project_info['Platform'] . '">' . $p . '</a>';
		}

		$project = '<a target="_blank" href="project.php?id=' . $project_info['ID'] . '">' . $project_info['Name'] . '</a>';

		echo "<input type='hidden' id='input_all_sample_ids' value='" . implode(",", array_keys($sample_info)). "' />";
		echo '<div class="w-100 mb-3">';
			echo '<a class="m-1 btn btn-sm btn-success btn_sample_actions" action_type="tool_save_lists/new_list.php?category=sample&sample_ids=" href="Javascript: void(0);"><i class="fas fa-caret-right mx-1"></i> Save Sample List</a>';
			echo '<a class="m-1 btn btn-sm btn-success btn_sample_actions" action_type="tool_gene_expression_plot/index.php?sample_ids=" href="Javascript: void(0);"><i class="fas fa-caret-right mx-1"></i> Gene Expression Plot</a>';
	        echo '<a class="m-1 btn btn-sm btn-success btn_sample_actions" action_type="tool_heatmap/index.php?sample_ids=" href="Javascript: void(0);"><i class="fas fa-caret-right mx-1"></i> Heatmap</a>';
	        echo '<a class="m-1 btn btn-sm btn-success btn_sample_actions" action_type="tool_correlation/index.php?sample_ids=" href="Javascript: void(0);"><i class="fas fa-caret-right mx-1"></i> Correlation Tool</a>';
	        echo '<a class="m-1 btn btn-sm btn-success btn_sample_actions" action_type="tool_pca/index_genes_samples.php?sample_ids=" href="Javascript: void(0);"><i class="fas fa-caret-right mx-1"></i> PCA Analysis</a>';
	        echo '<a class="m-1 btn btn-sm btn-success btn_sample_actions" action_type="tool_export/genes_samples.php?sample_ids=" href="Javascript: void(0);"><i class="fas fa-caret-right mx-1"></i> Export Expression Data</a>';
	    echo '</div>';

		echo '<div class="w-100">';
			echo '<table class="table table-bordered table-hover datatable">';
				echo '<thead><tr class="table-info">';
					echo "<th class='text-center'><input type='checkbox' class='bxaf_checkbox2 bxaf_checkbox_all2' /></th>";
					echo '<th>ID</th>';
					echo '<th>Actions</th>';
					foreach($default_columns as $col){
						echo '<th>' . str_replace('_', ' ', $col) . '</th>';
					}
				echo '</tr></thead>';
				echo '<tbody>';

				foreach ($sample_info as $id=>$sample) {

					echo '<tr>';
					echo "<td class='text-center'><input type='checkbox' class='bxaf_checkbox2 bxaf_checkbox_one2' rowid='" . $id . "' /></td>";
					echo '<td><a href="tool_search/view.php?type=sample&id=' . $id . '" title="View Detail" target="_blank">' . $id . '</a></td>';
					echo '<td>';
						echo '<a href="tool_search/view.php?type=sample&id=' . $id . '" title="View Detail" target="_blank" class=" mx-1"><i class="fas fa-list-ul"></i></a>

						<a title="Find Correlated Genes" href="tool_correlation/index.php?sample_id=' . $id . '" target="_blank" class=" mr-1"><span class="badge badge-pill table-success text-danger">CG</span></a>
						<a title="Plot Gene Expression" href="tool_gene_expression_plot/index.php?sample_id=' . $id . '" target="_blank" class=" mr-1"><span class="badge badge-pill table-success text-danger">GE</span></a>
						<a title="Generate Heatmap" href="tool_heatmap/index.php?sample_id=' . $id . '" target="_blank" class=" mr-1"><span class="badge badge-pill table-success text-danger">H</span></a>
						<a title="PCA Analysis" href="tool_pca/index_genes_samples.php?sample_id=' . $id . '" target="_blank" class=" mr-1"><span class="badge badge-pill table-success text-danger">PCA</span></a>';

						if($editable){
							echo '<a title="Edit sample information" href="edit_sample.php?sampleid=' . $id . '" class="text-success mx-1" target="_blank">
								<i class="fas fa-edit fa-lg"></i>
							</a>
							<a title="Remove this sample from project" href="javascript:void(0);" rowid="' . $id . '" class="text-danger btn_delete mx-1" type="sample">
								<i class="fas fa-times fa-lg"></i>
							</a>';
						}
					echo '</td>';

					foreach($default_columns as $col){
						if($col == 'PlatformName' || $col == '_Platforms_ID'){
							echo '<td>' . $platform . '</td>';
						}
						else if($col == '_Projects_ID' || $col == 'Project_Name'){
							echo '<td>' . $project . '</td>';
						}
						else {
							echo '<td>' . $sample[$col] . '</td>';
						}
					}
					echo '</tr>';
				}

				echo '</tbody>';
			echo '</table>';
		echo '</div>';

	?>

					</div>
<?php } // if($number_samples > 0) { ?>





					<input id="input_time" hidden>

				</div>
			</div>

	    <?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>

		</div>

	</div>

	<!---------------------------------------------------------------------------------------------------------->
	<!-- Modals -->
	<!---------------------------------------------------------------------------------------------------------->
	<div class="modal fade" tabindex="-1" role="dialog" id="modal_update_project">
		<div class="modal-dialog modal-lg" role="document">
			<div class="modal-content">
				<form id="form_update_project">
				<input name="rowid" value="<?php echo $project_id;?>" hidden>
				<div class="modal-header">
					<h3 class="modal-title">Update Project</h3>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body" style="overflow-y:scroll; height:70vh;">
					<table class="table table-no-border">
						<tbody>
							<?php

							$col_list = $BXAF_CONFIG['TBL_BXGENOMICS_FIELDS']['Project'];
							array_unshift($col_list, 'Name');
							array_unshift($col_list, 'Description');

							sort($col_list);

							foreach ($col_list as $key) {

								echo "
								<tr>
									<th style='width: 200px;'>{$key}:</th>
									<td>
										<input class='form-control' id='project_{$key}'
											name='project_{$key}'
											value='" . htmlentities($project_info[$key], ENT_QUOTES) . "'>
									</td>
								</tr>";
							}
							?>
						</tbody>
					</table>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
					<button type="submit" class="btn btn-primary">Submit</button>
				</div>
				</form>
			</div>
		</div>
	</div>




<script type="text/javascript">

	$(document).ready(function(){


        // // Check/Uncheck All
        $(document).on('change', '.bxaf_checkbox2', function() {
            if($(this).hasClass('bxaf_checkbox_all2')){
                $('.bxaf_checkbox_one2').prop ('checked', $(this).is(':checked') );
            }
            else if( $(this).hasClass('bxaf_checkbox_one2') ){
                var checked = true;
                $('.bxaf_checkbox_one2').each(function(index, element) {
                    if (! element.checked ) checked = false;
                });
                $('.bxaf_checkbox_all2').prop ('checked', checked);
            }
        });

        // Save session list
        $(document).on('click', '.btn_sample_actions', function() {

            var rowid = '';
            $('.bxaf_checkbox_one2').each(function(index, element) {
                if ( element.checked ) {
                    if(rowid == '') rowid = $(element).attr('rowid');
                    else rowid += ',' + $(element).attr('rowid');
                }
            });
            if (rowid == '')  rowid = $('#input_all_sample_ids').val();

            window.open($(this).attr('action_type') + rowid);

        });

		$(document).on('change', '.bxaf_checkbox3', function() {
            if($(this).hasClass('bxaf_checkbox_all3')){
                $('.bxaf_checkbox_one3').prop ('checked', $(this).is(':checked') );
            }
            else if( $(this).hasClass('bxaf_checkbox_one3') ){
                var checked = true;
                $('.bxaf_checkbox_one3').each(function(index, element) {
                    if (! element.checked ) checked = false;
                });
                $('.bxaf_checkbox_all3').prop ('checked', checked);
            }
        });

        $(document).on('click', '.btn_comparison_actions', function() {

            var rowid = '';
            $('.bxaf_checkbox_one3').each(function(index, element) {
                if ( element.checked ) {
                    if(rowid == '') rowid = $(element).attr('rowid');
                    else rowid += ',' + $(element).attr('rowid');
                }
            });
            if (rowid == '')  rowid = $('#input_all_comparison_ids').val();

            window.open($(this).attr('action_type') + rowid);

        });




		$('.datatable').DataTable({ dom: 'Blfrtip', buttons: ['colvis','copy','csv'], 'pageLength': 100, 'lengthMenu': [[10, 100, 500, 1000], [10, 100, 500, 1000]], "order": [[ 1, 'asc' ]], "columnDefs": [ { "targets": 0, "orderable": false } ] });

<?php if($editable){ ?>
		//---------------------------------------------------------------------------------
		// Update Project Information
		//---------------------------------------------------------------------------------
		var options = {
			url: 'project_exe.php?action=update_project',
			type: 'post',
			beforeSubmit: function(formData, jqForm, options) {
				return true;
			},
			success: function(res){
				if (res.substring(0, 5) == 'Error') {
					bootbox.alert(res);
				} else {
					location.reload(true);
				}
				return true;
			}
		};
		$('#form_update_project').ajaxForm(options);


		//---------------------------------------------------------------------------------
		// Batch Update Samples Information
		//---------------------------------------------------------------------------------
		var options = {
			url: 'project_exe.php?action=batch_update_info&type=Sample',
			type: 'post',
			beforeSubmit: function(formData, jqForm, options) {
				$('#btn_submit_batch_update_samples')
					.attr('disabled', '')
					.children(':first')
					.removeClass('fa-upload')
					.addClass('fa-spin fa-spinner');
				return true;
			},
			success: function(res){
				$('#btn_submit_batch_update_samples')
					.removeAttr('disabled')
					.children(':first')
					.addClass('fa-upload')
					.removeClass('fa-spin fa-spinner');
				if (typeof res == 'string' && res.substring(0, 5) == 'Error') {
					bootbox.alert(res);
				} else {
					location.reload(true);
				}
				return true;
			}
		};
		$('#form_batch_update_samples').ajaxForm(options);

		//---------------------------------------------------------------------------------
		// Batch Update Comparisons Information
		//---------------------------------------------------------------------------------
		var options_comparisons = {
			url: 'project_exe.php?action=batch_update_info&type=Comparison',
			type: 'post',
			beforeSubmit: function(formData, jqForm, options) {
				$('#btn_submit_batch_update_comparisons')
					.attr('disabled', '')
					.children(':first')
					.removeClass('fa-upload')
					.addClass('fa-spin fa-spinner');
				return true;
			},
			success: function(res){
				$('#btn_submit_batch_update_comparisons')
					.removeAttr('disabled')
					.children(':first')
					.addClass('fa-upload')
					.removeClass('fa-spin fa-spinner');
				if (typeof res == 'string' && res.substring(0, 5) == 'Error') {
					bootbox.alert(res);
				} else {
					location.reload(true);
				}
				return true;
			}
		};
		$('#form_batch_update_comparisons').ajaxForm(options_comparisons);



		$(document).on('click', '.btn_delete', function() {
			var curr = $(this);
			var type = curr.attr('type');
			var rowid = curr.attr('rowid');
			bootbox.confirm({
			    message: "Are you sure you want to delete " + type + " from the project?",
			    buttons: {
			        confirm: {
			          label: '<i class="fas fa-times"></i> Delete',
			          className: 'btn-danger btn-confirm-delete'
			        },
			        cancel: {
			          label: '<i class="fas fa-undo"></i> Cancel',
			          className: 'btn-secondary'
			        }
			    },
			    callback: function (result) {
	        		if (result) {
						$('.btn-confirm-delete').attr('disabled', '')
							.children(':first')
							.removeClass('fa-times')
							.addClass('fa-spin fa-spinner');
						$.ajax({
							type: 'post',
							url: 'exe_private_projects.php?action=delete_record',
							data: { type: type, rowid: rowid },
							success: function(res) {
								$('.btn-confirm-delete').removeAttr('disabled')
									.children(':first')
									.addClass('fa-times')
									.removeClass('fa-spin fa-spinner');
								location.reload(true);
								return;
							}
						});
					}
		    	}
			});
		});


		$(document).on('click', '.btn_delete_project', function() {
			var action_type = $(this).attr('action_type');
			var rowid = $(this).attr('rowid');

			var message = '';
			var location = '';
			if(action_type == 'delete_project'){ message = "<h1><i class='fas fa-exclamation-triangle'></i> Warning</h1><div class='my-2 text-danger'>Are you sure you want to delete this project? </div><div class='my-3'>This will also delete all related samples, comparisons, and expression data.</div>"; location = "index.php"; }
			else if(action_type == 'delete_sample_data'){ message = "<h1><i class='fas fa-exclamation-triangle'></i> Warning</h1><div class='my-3 text-danger'>Are you sure you want to delete all sample data of this project? </div><div class='my-3'>These records will not be affected: projects, samples, comparisons, and comparison data records.</div>"; location = "project.php?id=<?php echo $project_id; ?>"; }
			else if(action_type == 'delete_comparison_data'){ message = "<h1><i class='fas fa-exclamation-triangle'></i> Warning</h1><div class='my-2 text-danger'>Are you sure you want to delete all comparison data of this project?  </div><div class='my-3'>These records will not be affected: projects, samples, comparisons, and sample expression data records.</div>"; location = "project.php?id=<?php echo $project_id; ?>"; }

			bootbox.confirm({
			    message: message,
			    buttons: {
			        confirm: {
			          label: '<i class="fas fa-times"></i> Delete',
			          className: 'btn-danger btn-confirm-delete'
			        },
			        cancel: {
			          label: '<i class="fas fa-undo"></i> Cancel',
			          className: 'btn-secondary'
			        }
			    },
			    callback: function (result) {
	        		if (result) {
						$.ajax({
							type: 'post',
							url: 'exe_private_projects.php?action=delete_project',
							data: { 'rowid': rowid, 'action_type': action_type },
							success: function(res) {
								bootbox.alert(res, function(){
									window.location = location;
								});
							}
						});
					}
		    	}
			});
		});
<?php } //if($editable){ ?>

	});
</script>

</body>
</html>