<?php

//To disable login requirement
//$BXAF_CONFIG_CUSTOM['PAGE_LOGIN_REQUIRED'] = false;

include_once(dirname(__FILE__) . "/config/config.php");


// e.g. http://yz.bxaf.com:8002/bxgenomics_v2.2/app/bxgenomics/report_fastqc.php?analysis=6_Cd8PPQJZa2EDV--Y4tlgdtSmdwrzaGClkYT9XAFFecQ

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




$QC_Statistics_All = array();
$QC_Summary_All = array();
$key_name = '';

$fastQC_files = bxaf_list_files_only($analysis_dir . 'fastQC/');

foreach($fastQC_files as $f){
    $f_name = array_pop( explode('/', trim($f) ) );
    if(preg_match("/_fastqc\.html$/", $f_name)){

        $key_name = str_replace("_fastqc.html", "", $f_name);

        $QC_Statistics_All[$key_name] = array();
        $QC_Statistics_All[$key_name]['url'] = $analysis_url . 'fastQC/' . $f_name;
        $QC_Statistics_All[$key_name]['dir'] = str_replace(".html", "", trim($f) );
    }
}


$fastqc_data_txt_key_columns = array('Filename', 'File type', 'Encoding', 'Total Sequences', 'Sequences flagged as poor quality', 'Sequence length', '%GC', '#Total Deduplicated Percentage');

foreach($QC_Statistics_All as $key_name => $row){

    if(file_exists( $row['dir'] . '/fastqc_data.txt' )){

        $handle = @fopen($row['dir'] . '/fastqc_data.txt', "r");
        if ($handle) {

            foreach($fastqc_data_txt_key_columns as $k){
                $QC_Statistics_All[$key_name][ $k ] = '';
            }

            while (($buffer = fgetcsv($handle, 0, "\t")) !== false) {
                if(! is_array($buffer) || count($buffer) <= 0) continue;
                // if($buffer[0] == '>>END_MODULE') break;
                // else
                if(in_array($buffer[0], $fastqc_data_txt_key_columns) ) $QC_Statistics_All[$key_name][ $buffer[0] ] = trim( $buffer[1] );
            }

            fclose($handle);
        }
    }

    if(file_exists( $row['dir'] . '/summary.txt' )){

        $handle = @fopen($row['dir'] . '/summary.txt', "r");
        if ($handle) {
            while (($cols = fgetcsv($handle, 0, "\t")) !== false) {
                if(count($cols) == 3){
                    $QC_Summary_All[ trim($cols[2]) ][ trim($cols[1]) ] = trim($cols[0]);
                }
            }
            fclose($handle);
        }

    }
}

// echo "<pre>" . print_r($QC_Statistics_All, true) . "</pre>";
// echo "<pre>" . print_r($QC_Summary_All, true) . "</pre>";


$QC_Statistics_header = array(
    'Filename',
    // 'File type',
    // 'Encoding',
    'Total Sequences',
    'Sequences flagged as poor quality',
    'Sequence length',
    '%GC',
    '#Total Deduplicated Percentage',
);
$QC_Summary_header = array(
    'Basic Statistics' => 'http://www.bioinformatics.babraham.ac.uk/projects/fastqc/Help/3%20Analysis%20Modules/1%20Basic%20Statistics.html',
    'Per base sequence quality' => 'http://www.bioinformatics.babraham.ac.uk/projects/fastqc/Help/3%20Analysis%20Modules/2%20Per%20Base%20Sequence%20Quality.html',
    'Per tile sequence quality' => 'http://www.bioinformatics.babraham.ac.uk/projects/fastqc/Help/3%20Analysis%20Modules/12%20Per%20Tile%20Sequence%20Quality.html',
    'Per sequence quality scores' => 'http://www.bioinformatics.babraham.ac.uk/projects/fastqc/Help/3%20Analysis%20Modules/3%20Per%20Sequence%20Quality%20Scores.html',
    'Per base sequence content' => 'http://www.bioinformatics.babraham.ac.uk/projects/fastqc/Help/3%20Analysis%20Modules/4%20Per%20Base%20Sequence%20Content.html',
    'Per sequence GC content' => 'http://www.bioinformatics.babraham.ac.uk/projects/fastqc/Help/3%20Analysis%20Modules/5%20Per%20Sequence%20GC%20Content.html',
    'Per base N content' => 'http://www.bioinformatics.babraham.ac.uk/projects/fastqc/Help/3%20Analysis%20Modules/6%20Per%20Base%20N%20Content.html',
    'Sequence Length Distribution' => 'http://www.bioinformatics.babraham.ac.uk/projects/fastqc/Help/3%20Analysis%20Modules/7%20Sequence%20Length%20Distribution.html',
    'Sequence Duplication Levels' => 'http://www.bioinformatics.babraham.ac.uk/projects/fastqc/Help/3%20Analysis%20Modules/8%20Duplicate%20Sequences.html',
    'Overrepresented sequences' => 'http://www.bioinformatics.babraham.ac.uk/projects/fastqc/Help/3%20Analysis%20Modules/9%20Overrepresented%20Sequences.html',
    'Adapter Content' => 'http://www.bioinformatics.babraham.ac.uk/projects/fastqc/Help/3%20Analysis%20Modules/10%20Adapter%20Content.html',
    // 'Kmer Content' => 'http://www.bioinformatics.babraham.ac.uk/projects/fastqc/Help/3%20Analysis%20Modules/11%20Kmer%20Content.html',
);


?><!DOCTYPE html>
<html lang="en">
<head>
	<?php include_once($BXAF_CONFIG['BXAF_PAGE_HEADER']); ?>

	<link   href='css/report.css' rel='stylesheet' type='text/css'>

	<script type="text/javascript">
		$(document).ready(function(){


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

					<h1 class="w-100 text-center">BxGenomics - Raw Sequencing Data QC</h1>

					<div class="row my-5">
						<div class="mb-2 text-muted">
							<p>The <a href="http://www.bioinformatics.babraham.ac.uk/projects/fastqc/" target="_blank">fastQC</a> program is used to verify raw data quality of the Illumina reads.</p>
							<p>The table below shows a summary of basic statistics. Click the file name to open the detailed report for each individual sample.</p>
							<p>If the sequencing run is paired-end (PE), you may have two files per sample with R1 and R2 in the file names respectively.</p>
						</div>

						<div class="w-100">
<?php
echo '<table class="table table-bordered table-striped table-hover w-100">';
echo '	<thead>';
echo '		<tr class="table-info">';
foreach($QC_Statistics_header as $name){
    echo "			<th>$name</th>";
}
echo '		</tr>';
echo '	</thead>';
echo '	<tbody>';
foreach($QC_Statistics_All as $key=>$val){
    echo '		<tr class="">';
    foreach($QC_Statistics_header as $name){
        if($name == 'Filename') echo "			<td><a href='" . $val['url'] . "' target='_blank'>" . $val[$name] . "</a></td>";
        else if($name == '#Total Deduplicated Percentage') echo "			<td>" . sprintf("%4.1f", $val[$name]) . "%</td>";
        else echo "			<td>" . $val[$name] . "</td>";
    }
    echo '		</tr>';
}
echo '	</tbody>';
echo '</table>';

?>

						</div>

						<p class="mt-3 w-100">The table below show pass/fail for several QC metrics. Click the file name to open individual reports. You can view fastQC documentation to get more information about the QC metrics.</p>

						<p class="mt-3 w-100 font-italic text-muted">Please note that for RNA-Seq data, it is normal to observe a few failed metrics, which usually will not affect subsequent data analysis. First, per base sequence content (and Kmer content) will often fail fastQC due to non-random base content at the first ~12 bases. This is because the random primers used during reverse transcription step are actually not totally random in terms of base content. Second, the sequence duplication levels of RNA-Seq data are usually high because many transcripts are highly expressed.</p>


						<div class="w-100">
<?php
echo '<table class="table table-bordered table-striped table-hover w-100">';
echo '	<thead>';
echo '		<tr class="table-info">';
echo "			<th>File Name</th>";
foreach($QC_Summary_header as $name=>$url){
    echo "			<th><a href='" . $url . "' target='_blank'>" . $name . "</a></th>";
}
echo '		</tr>';
echo '	</thead>';
echo '	<tbody>';
foreach($QC_Summary_All as $filename=>$val){
    echo '		<tr class="">';

    $key_name = str_replace(".fq.gz", "", str_replace(".fastq.gz", "", $filename));
    $url = $analysis_url . 'fastQC/' . $key_name . '_fastqc.html';
    echo "			<td><a href='" . $url . "' target='_blank'>" . $filename . "</a></td>";
    foreach($QC_Summary_header as $name=>$url){
        $v = $val[$name];
        // if($name == 'Total Deduplicated Percentage') $v = sprintf("%.1f", $v );
        echo '<td class="';
            if($v == 'FAIL') echo 'text-danger'; else if($v=='FAIL') echo 'text-warning'; else if($v=='PASS') echo 'text-success';
            echo '">';
            echo $v;
        echo '</td>';
    }
    echo '		</tr>';
}
echo '	</tbody>';
echo '</table>';

?>

						</div>
					</div>

				</div>



            </div>

		    <?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>

		</div>

	</div>

</body>
</html>