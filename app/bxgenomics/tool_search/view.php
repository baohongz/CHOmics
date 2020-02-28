<?php
include_once('config.php');

$PAGE_TYPE = 'Comparison';
if(isset($_GET['type']) && in_array(ucfirst(strtolower($_GET['type'])), $PAGE_TYPE_ALL) ){
	$PAGE_TYPE = ucfirst(strtolower($_GET['type']));
}

$TABLE = $TABLE_ALL[$PAGE_TYPE];
$TABLE_FIELD_NAME = $TABLE_FIELD_NAMES[$PAGE_TYPE];

if(!isset($_GET['id']) && isset($_GET['name']) ) {
	$sql = "SELECT `ID` FROM ?n WHERE ?n = ?s";
	$_GET['id'] = $BXAF_MODULE_CONN -> get_one($sql, $TABLE, $TABLE_FIELD_NAME, $_GET['name']);
}

if (!isset($_GET['id']) || trim($_GET['id']) == '') {
	header("Location: index.php");
	exit();
}

$current_id = intval($_GET['id']);

if ($PAGE_TYPE == 'Project'){
	header("Location: ../project.php?id=$current_id");
	exit();
}

$record_info = $BXAF_MODULE_CONN -> get_row("SELECT * FROM ?n WHERE `ID` = ?i", $TABLE, $current_id);

$editable = false;
if($record_info['_Owner_ID'] > 0) $editable = true;

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<?php include_once($BXAF_CONFIG['BXAF_PAGE_HEADER']); ?>
	<script src="../library/plotly.min.js"></script>
</head>
<body>

<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_MENU'])) include_once($BXAF_CONFIG['BXAF_PAGE_MENU']); ?>
	<div id="bxaf_page_wrapper" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_WRAPPER']; ?>">
		<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_LEFT'])) include_once($BXAF_CONFIG['BXAF_PAGE_LEFT']); ?>
		<div id="bxaf_page_right" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT']; ?>">
			<div id="bxaf_page_right_content" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT_CONTENT']; ?>">

    		<div class="container-fluid pt-3">
				<h1><span style="font-size: 1.5rem;" class="text-danger"><?php echo $PAGE_TYPE; ?>:</span> <span><?php echo $record_info[$TABLE_FIELD_NAME]; ?></span></h1>

	    		<div class="my-3">
					<a href="index.php?type=<?php echo strtolower($PAGE_TYPE); ?>"> <i class="fas fa-angle-double-right"></i> Search All <?php echo $PAGE_TYPE; ?>s </a>
					<?php
						if($PAGE_TYPE == 'Comparison'){
							echo '<a href="comparison_table.php?name=' . $record_info[$TABLE_FIELD_NAME] . '" class="ml-3"><i class="fas fa-angle-double-right"></i> View Comparison Genes</a>';
							if($editable) echo '<a href="../edit_comparison.php?compid=' . $current_id . '" class="ml-3 text-danger"><i class="fas fa-angle-double-right"></i> Edit Comparison Details</a>';
						}
					?>
	    		</div>

				<hr />

				<!-- Action Buttons -->
				<?php

				if ($PAGE_TYPE == 'Comparison') {

					$project_name = $BXAF_MODULE_CONN -> get_one("SELECT `Name` FROM ?n WHERE `ID` = ?i", $BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS'], $record_info['_Projects_ID']);

					echo "<div class='w-100'><table class='table table-bordered'>";
						echo "<tr class='table-info'>";
							echo "<th>ID</th><th>Name</th><th>Project</th><th>Category</th><th>DiseaseState</th><th>Tissue</th><th>Contrast</th><th>Case Samples</th><th>Control Samples</th>";
						echo "</tr>";
						echo "<tr>";
							echo "<td>" . $current_id . "</td>";
							echo "<td>" . $record_info[$TABLE_FIELD_NAME] . "</td>";
							echo "<td><a href='../project.php?id=" . $record_info['_Projects_ID'] . "' target='_blank'>" . $project_name . "</a></td>";
							echo "<td>" . $record_info['ComparisonCategory'] . "</td>";
							echo "<td>" . $record_info['Case_DiseaseState'] . "</td>";
							echo "<td>" . $record_info['Case_Tissue'] . "</td>";
							echo "<td>" . $record_info['ComparisonContrast'] . "</td>";
							echo '<td><a href="Javascript: void(0);" onClick="$(this).next().toggle();">Show/Hide</a><div style="display: none;">' . str_replace(';', ' ', $record_info['Case_SampleIDs']) . '</div></td>';
							echo '<td><a href="Javascript: void(0);" onClick="$(this).next().toggle();">Show/Hide</a><div style="display: none;">' . str_replace(';', ' ', $record_info['Control_SampleIDs']) . '</div></td>';

						echo "</tr>";
					echo "</table></div>";


					echo "<div class='w-100' style='line-height: 2.5rem;'>";
						echo '<a href="#info_table_div" class="mx-2 btn btn-sm btn-primary"><i class="fas fa-arrow-circle-down"></i> View Details</a>';

						echo '
							<a title="" class="ml-2 btn btn-sm btn-success" href="../tool_search/comparison_table.php?id=' . $current_id . '" target="_blank">Genes</a>
							<a title="" class="ml-2 btn btn-sm btn-success" href="../tool_pathway/changed_genes.php?comparison_id=' . $current_id . '" target="_blank">Changed Genes</a>
							<a title="" class="ml-2 btn btn-sm btn-success" href="../tool_pathway/index.php?comparison_id=' . $current_id . '" target="_blank">WikiPathways</a>
							<a title="" class="ml-2 btn btn-sm btn-success" href="../tool_pathway/reactome.php?comparison_id=' . $current_id . '" target="_blank">Reactome Pathways</a>
							<a title="" class="ml-2 btn btn-sm btn-success" href="../tool_pathway/kegg.php?comparison_id=' . $current_id . '" target="_blank">KEGG Pathways</a>
							<a title="" class="ml-2 btn btn-sm btn-success" href="../tool_pathway_heatmap/index.php?comparison_id=' . $current_id . '" target="_blank">Pathway Heatmap</a>
							<a title="" class="ml-2 btn btn-sm btn-success" href="../tool_bubble_plot/multiple.php?comparison_id=' . $current_id . '" target="_blank">Bubble Plot</a>
							<a title="" class="ml-2 btn btn-sm btn-success" href="../tool_meta_analysis/index.php?comparison_id=' . $current_id . '" target="_blank">Meta Analysis</a>
							<a title="" class="ml-2 btn btn-sm btn-success" href="../tool_volcano_plot/index.php?comparison_id=' . $current_id . '" target="_blank">Volcano Chart</a>';

					echo "</div>";


					echo '
						<div class="w-100 my-3" id="div_enrichment"></div>

						<div id="div_page_plot" class="hidden my-5">
							<div class="my-3" id="chart_up_div"   style="min-width:600px; max-width:1200px; height:600px;"></div>
							<div class="my-3" id="chart_down_div" style="min-width:600px; max-width:1200px; height:600px;"></div>
						</div>
						<hr />
						<br />';
				}


				if ($PAGE_TYPE == 'Sample') {

					$project_name = $BXAF_MODULE_CONN -> get_one("SELECT `Name` FROM ?n WHERE `ID` = ?i", $BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS'], $record_info['_Projects_ID']);

					$comparison_info = $BXAF_MODULE_CONN -> get_all("SELECT `ID`, `Name` FROM ?n WHERE `_Projects_ID` = ?i AND `Case_SampleIDs` LIKE '%" . addslashes($record_info['Name']) . "%' OR `Control_SampleIDs` LIKE '%" . addslashes($record_info['Name']) . "%'", $BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS'], $record_info['_Projects_ID'] );

					if($project_name != '') echo "<div class='w-100 my-3'>Found in project: <a href='../project.php?id=" . $record_info['_Projects_ID'] . "' target='_blank'>" . $project_name . "</a></div>";

					if(is_array($comparison_info) && count($comparison_info) > 0){
						echo "<div class='w-100 my-3'>Found in comparisons: ";
						foreach($comparison_info as $comparison) echo "<a class='mx-2' href='view.php?type=comparison&id=" . $comparison['ID'] . "' target='_blank'>" . $comparison['Name'] . "</a>";
						echo "</div>";
					}

					echo "<div class='w-100 my-3'>";
						echo '
							<a title="" class="mr-2 btn btn-primary" href="../tool_correlation/index.php?sample_id=' . $current_id . '" target="_blank">Gene Expression Correlation</a>
							<a title="" class="mr-2 btn btn-primary" href="../tool_gene_expression_plot/index.php?sample_id=' . $current_id . '" target="_blank">Gene Expression Plot</a>
							<a title="" class="mr-2 btn btn-primary" href="../tool_heatmap/index.php?sample_id=' . $current_id . '" target="_blank">Gene Expression Heatmap</a>
							<a title="" class="mr-2 btn btn-primary" href="../tool_pca/index_genes_samples.php?sample_id=' . $current_id . '" target="_blank">PCA Analysis</a>';

					echo "</div>";

				}

				if ($PAGE_TYPE == 'Gene') {
					echo '
					<div class="w-100 my-3">
						<a href="../tool_gene_expression_plot/index.php?gene_name=' . $record_info[$TABLE_FIELD_NAME] . '" class="mx-1"><span class="badge badge-pill table-success text-danger">G</span> Gene Expression Plot</a>
						<a href="../tool_bubble_plot/index.php?gene_name=' . $record_info[$TABLE_FIELD_NAME] . '" class="mx-1"><span class="badge badge-pill table-success text-danger">B</span> Gene Bubble Plot</a>
					</div>';
				}

				if ($PAGE_TYPE == 'Project') {
					// header("Location: ../project.php?id=$current_id");
					// exit();
				}
				?>



				<!-- Table of Columns -->
				<div class="w-100 mt-5" id="info_table_div">
					<div class="w-100 bg-info p-2"><h3><?php echo $PAGE_TYPE; ?> Details</h3></div>
					<table class="table table-striped table-bordered">
						<?php
							$i = 0;
							foreach ($record_info as $key => $value) {
								if(in_array($key, array('Permission', '_Owner_ID', 'bxafStatus', 'Time_Created'))) continue;
								if ($i % 2 == 0) echo '<tr>';
								echo '<td class="text-right"><strong>' . str_replace('_', ' ', $key) . '</strong></td>
									<td style="max-width:500px;">' . str_replace('|', ' ', str_replace(';', ';<br />', $value)) . '</td>';
								if ($i % 2 == 1) echo '</tr>';
								$i++;
							}
						?>
					</table>
				</div>

    		</div>

      </div>
      <?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>
    </div>
  </div>





<script>

$(document).ready(function() {

	<?php if ($PAGE_TYPE == 'Comparison') { ?>
	  	$.ajax({
	  		type: 'POST',
	  		url: 'exe_functional_enrichment.php?action=show_chart_go',
	  		data: {comparison_index: '<?php echo $current_id; ?>'},
	  		success: function(responseText) {
		        $('#div_enrichment').html(responseText);
	  		}
	  	});

	    // GSEA
	    $.ajax({
	  		type: 'POST',
	  		url: 'exe.php?action=show_chart_page',
	  		data: {comparison_index: '<?php echo $current_id; ?>'},
	  		success: function(response) {

				if(response.error != ''){
					// bootbox.alert(response.error);
					return;
				}

				$('#div_page_plot').removeClass('hidden');

		        var data_up = response.up.data;
		        var layout_up = response.up.layout;
		        var setting_up = response.up.setting;

		        var data_down = response.down.data;
		        var layout_down = response.down.layout;
		        var setting_down = response.down.setting;

				Plotly.newPlot('chart_up_div', data_up, layout_up, setting_up).then(function(gd1) {
	                window.requestAnimationFrame(function() {
	                  window.requestAnimationFrame(function() {
	                    $('.loader').remove();
	                  });
	                });
					$(document).on("click", "#download_SVG_chart_up_div", function(){
	                    Plotly.downloadImage(gd1, {
	                            filename: "chart_up_div",
	                            format: "svg",
	                            height: layout_up.height,
	                            width: layout_up.width
	                    })
	                    .then(function(filename){

	                    });
	                });
	            });

		        Plotly.newPlot('chart_down_div', data_down, layout_down, setting_down).then(function(gd2) {
	                window.requestAnimationFrame(function() {
	                  window.requestAnimationFrame(function() {
	                    $('.loader').remove();
	                  });
	                });
					$(document).on("click", "#download_SVG_chart_down_div", function(){
	                    Plotly.downloadImage(gd2, {
	                            filename: "chart_down_div",
	                            format: "svg",
	                            height: layout_down.height,
	                            width: layout_down.width
	                    })
	                    .then(function(filename){

	                    });
	                });
	            });

				var page_report_up    = '<a href="report_page.php?id=<?php echo $current_id; ?>&direction=up" class="btn btn-info">PAGE Report for Up-Regulated Genes</a><BR><a href="javascript:void(0);" id="download_SVG_chart_up_div"><i class="fas fa-angle-double-right"></i> Download SVG File</a>';
				var page_report_down  = '<a href="report_page.php?id=<?php echo $current_id; ?>&direction=down" class="btn btn-info mt-5">PAGE Report for Down-Regulated Genes</a><BR><a href="javascript:void(0);" id="download_SVG_chart_down_div"><i class="fas fa-angle-double-right"></i> Download SVG File</a>';


				$('#chart_up_div').prepend(page_report_up);
				$('#chart_down_div').prepend(page_report_down);

				return true;
	  		}
	  	});

	<?php } ?>

});


</script>


</body>
</html>