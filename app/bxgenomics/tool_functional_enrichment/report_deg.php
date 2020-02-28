<?php

include_once(__DIR__ . "/config.php");


$analysis = 0;
if (isset($_GET['analysis']) && trim($_GET['analysis']) != '')  $analysis = trim($_GET['analysis']);


$input_ids_url = "";
if($analysis == 0){
	$input_ids_url = "example/input_ids.txt";
}
else {
	$input_ids_url = $BXAF_CONFIG['USER_FILES_URL']['TOOL_FUNCTIONAL_ENRICHMENT'] . "analysis_results/{$analysis}/input_ids.txt";
}

?><!DOCTYPE html>
<html lang="en">
<head>
	<?php include_once($BXAF_CONFIG['BXAF_PAGE_HEADER']); ?>

	<script src="../library/plotly.min.js"></script>

	<script type="text/javascript">
		$(document).ready(function(){

            $.ajax({
    	  		type: 'POST',
    	  		url: '../tool_search/exe_functional_enrichment.php?action=show_chart_go',
    	  		data: {comparison_index: '0', enrichment_id: '<?php echo $analysis; ?>' },
    	  		success: function(responseText) {
    		        $('#div_enrichment').html(responseText);
    	  		}
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

					<div class="d-flex flex-row mt-3">
                        <h1 class="align-self-baseline">Gene Ontology Enrichment Summary Plots</h1>
                        <p class="align-self-baseline ml-5 lead"><a href="index.php" class=""><i class="fas fa-chart-bar"></i> Start New Analysis</a></p>
                        <p class="align-self-baseline ml-5 lead"><a href="<?php echo $input_ids_url; ?>" target="_blank" class=""><i class="fas fa-chart-bar"></i> Input IDs</a></p>
					</div>

					<hr class="w-100" />

					<h3 class="w-100 my-3">Introduction</h3>
					<p class="w-100 mb-5">This page displays the top 10 lists from functional enrichment of differentiall expressed genes. Click "Detailed Report" link to view full list from all categories.</p>

                    <div class="w-100 my-3" id="div_enrichment"></div>

				</div>


            </div>
		    <?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>
		</div>
	</div>
</body>
</html>