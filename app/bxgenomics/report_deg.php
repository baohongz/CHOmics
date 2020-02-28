<?php

//To disable login requirement
//$BXAF_CONFIG_CUSTOM['PAGE_LOGIN_REQUIRED'] = false;

include_once(dirname(__FILE__) . "/config/config.php");


// e.g. http://yz.bxaf.com:8002/bxgenomics_v2.2/app/bxgenomics/report_gsea.php?analysis=6_Cd8PPQJZa2EDV--Y4tlgdtSmdwrzaGClkYT9XAFFecQ&comp=Control.vs.Drug.A

$analysis_id = 0;
$analysis_id_encrypted = '';
if (isset($_GET['analysis_id']) && intval($_GET['analysis_id']) > 0) {
  $analysis_id = intval($_GET['analysis_id']);
  $analysis_id_encrypted = $analysis_id . '_' . bxaf_encrypt($analysis_id, $BXAF_CONFIG['BXAF_KEY']);
}
else if (isset($_GET['analysis']) && trim($_GET['analysis']) != '') {
  $analysis_id_encrypted = trim($_GET['analysis']);
  $analysis_id = intval(array_shift(explode('_', $analysis_id_encrypted)));
}

$analysis_dir = $BXAF_CONFIG['ANALYSIS_DIR'] . $analysis_id_encrypted . "/";
$analysis_url = $BXAF_CONFIG['ANALYSIS_URL'] . $analysis_id_encrypted . "/";


if($analysis_id <= 0 || ! file_exists($analysis_dir) || ! is_dir($analysis_dir) || ! is_readable($analysis_dir)){
	header("Location: analysis_all.php");
}

$sql = "SELECT * FROM `" . $BXAF_CONFIG['BXGENOMICS_DB_TABLES']['TBL_BXGENOMICS_ANALYSIS'] . "` WHERE `ID` = $analysis_id";
$analysis_info = $BXAF_MODULE_CONN -> get_row($sql);
$all_comparisons = unserialize($analysis_info['Comparisons']);

$sql = "SELECT * FROM `" . $BXAF_CONFIG['BXGENOMICS_DB_TABLES']['TBL_BXGENOMICS_EXPERIMENT'] . "` WHERE `ID` = " . $analysis_info['Experiment_ID'];
$experiment_info = $BXAF_MODULE_CONN -> get_row($sql);


$current_comparison = '';
if (isset($_GET['comp']) && trim($_GET['comp']) != '') {
  $current_comparison = $_GET['comp'];
}
if(! in_array($current_comparison, $all_comparisons)) $current_comparison = current($all_comparisons);

?><!DOCTYPE html>
<html lang="en">
<head>
	<?php include_once($BXAF_CONFIG['BXAF_PAGE_HEADER']); ?>

	<link   href='css/report.css' rel='stylesheet' type='text/css'>

	<script src="library/plotly.min.js"></script>

  	<style>
  	.card{
  		width: 25rem;
  	}
  	</style>

	<script type="text/javascript">

		$(document).ready(function(){

		    $(document).on('click', '.tab_link', function() {
		      var target = $(this).attr('target');
		      var direction = $(this).attr('direction');
		      $('.container_chart_' + direction).each(function(index, element) {
		        if ($(element).attr('id') != target) {
		          $(element).removeClass('active show');
		        } else {
		          $(element).addClass('active show');
		        }
		      });
		    });


            $.ajax({
    	  		type: 'POST',
    	  		url: 'tool_search/exe_functional_enrichment.php?action=show_chart_go&comp=<?php echo $_GET['comp']; ?>',
    	  		data: {comparison_index: '0', analysis_id: '<?php echo $analysis_id; ?>' },
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



				<div class="container">

					<div class="d-flex flex-row mt-3">

						<p class="align-self-baseline">Experiment: </p>
						<p class="align-self-baseline ml-2 lead"><a href="experiment.php?id=<?php echo $experiment_info['ID']; ?>" class=""><?php echo $experiment_info['Name']; ?></a></p>

						<p class="align-self-baseline ml-5">Analysis: </p>
						<p class="align-self-baseline ml-2 lead"><a href="analysis.php?id=<?php echo $analysis_id; ?>" class=""><?php echo $analysis_info['Name']; ?></a></p>

                        <p class="align-self-baseline ml-5 lead"><a href="report_full.php?analysis=<?php echo $analysis_id_encrypted; ?>" class=""><i class="fas fa-flag"></i> Full Report</a></p>

					</div>

					<hr class="w-100" />

					<h1 class="w-100 my-4 text-center">Gene Ontology Enrichment Analysis Results</h1>

					<h3 class="w-100 my-3">Introduction</h3>
					<p class="w-100 mb-5">This page displays the top 10 lists from functional enrichment of differentiall expressed genes. Click "Detailed Report" link to view full list from all categories.</p>

<?php

	echo "<div class='w-100 my-3'>Comparisions: ";
	foreach($all_comparisons as $comparison){
		echo "<a href='report_deg.php?analysis=$analysis_id_encrypted&comp=$comparison' class='m-2 btn " . ($current_comparison == $comparison ? "btn-success" : "btn-secondary") . "'>$comparison</a>";
	}
	echo "</div>";

?>

                    <div class="w-100 my-3" id="div_enrichment"></div>

				</div>



            </div>

		    <?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>

		</div>

	</div>

</body>
</html>