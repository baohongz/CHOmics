<?php

//To disable login requirement
//$BXAF_CONFIG_CUSTOM['PAGE_LOGIN_REQUIRED'] = false;

include_once(dirname(__FILE__) . "/config/config.php");


// e.g. http://yz.bxaf.com:8002/bxgenomics_v2.2/app/bxgenomics/report_qc.php?analysis=6_Cd8PPQJZa2EDV--Y4tlgdtSmdwrzaGClkYT9XAFFecQ

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

$sql = "SELECT * FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_ANALYSIS'] . "` WHERE `ID` = $analysis_id";
$analysis_info = $BXAF_MODULE_CONN -> get_row($sql);

$sql = "SELECT * FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_EXPERIMENT'] . "` WHERE `ID` = " . $analysis_info['Experiment_ID'];
$experiment_info = $BXAF_MODULE_CONN -> get_row($sql);

$log_file = $analysis_dir . "step_1.log";
$alignment_log = '';
$subread_version = 'v1.5.3';
if(file_exists($log_file)){
    $alignment_log_array = explode("\n", file_get_contents($log_file));
    $start = false;
    foreach($alignment_log_array as $i=>$row){
        if(preg_match("/^\tv/", $row)) $subread_version = trim($row);
        else if($row == '//============================= subjunc setting ==============================\\\\'){
            $start = true;
            $alignment_log .= "<hr class='mt-3'><h3 class='text-danger'>Subjunc Settings</h3>";
            continue;
        }
        else if($row == '//================================= Summary ==================================\\\\'){
            $start = true;
            $alignment_log .= "<h5 class='text-success'>Summary:</h5>";
            continue;
        }
        else if($row == '\\\\===================== http://subread.sourceforge.net/ ======================//'){
            $start = false;
            $alignment_log .= "";
            continue;
        }
        if($start) $alignment_log .= str_replace('||', '', $row) . "\n";
    }
}
// echo "$n_rows<pre>" . print_r($alignment_log, true) . "</pre>";

?><!DOCTYPE html>
<html lang="en">
<head>
	<?php include_once($BXAF_CONFIG['BXAF_PAGE_HEADER']); ?>

	<link   href='css/report.css' rel='stylesheet' type='text/css'>

	<script type="text/javascript">
		$(document).ready(function(){

            $('.scroll').on('click', function(event) {
                if (this.hash !== "") {
                    event.preventDefault();
                    var hash = this.hash;
                    $('html, body').animate({
                        scrollTop: $(hash).offset().top - 50
                    }, 800, function() {
                        window.location.hash = hash;
                    });
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

					<h1 class="w-100">BxGenomics - Sequence Alignment Logs</h1>

					<div class="w-100 my-5" id="part1">

<?php
    echo "<div class='w-100 my-3'>Subread: $subread_version (<a href='http://subread.sourceforge.net/' target='_blank'>http://subread.sourceforge.net/</a>)</div>";
    if($alignment_log != '') echo "<pre>" . print_r($alignment_log, true) . "</pre>";
?>

					</div>




				</div>



            </div>

		    <?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>

		</div>

	</div>

</body>
</html>