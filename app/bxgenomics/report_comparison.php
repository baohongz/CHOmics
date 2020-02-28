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



$all_comparisons_detailed_report = array();
foreach($all_comparisons as $comparison){
    $detail_report_subdir = "alignment/DEG/$comparison/Downstream/GSEA_$comparison/";

    $subdir = '';
    if(file_exists($analysis_dir . $detail_report_subdir)){

        $d = dir($analysis_dir . $detail_report_subdir);
        while (false !== ($entry = $d->read())) {
            if(strpos($entry, $comparison) === 0 ) {
                $subdir = $entry;
                break;
            }
        }
        $d->close();
    }
    if($subdir != ''){
        $all_comparisons_detailed_report[$comparison] = $analysis_url . $detail_report_subdir . $subdir . "/";
    }
}


$GSEA_summary_csv_dir = $analysis_dir . "alignment/DEG/GSEA_Summary/GSEA_summary.csv";
$GSEA_summary_csv_url = $analysis_url . "alignment/DEG/GSEA_Summary/GSEA_summary.csv";

$gsea_data_files = array();
$max_name_length = 0;
if (($handle = fopen($GSEA_summary_csv_dir, "r")) !== FALSE) {
    $header = fgetcsv($handle, 1000, ",");
    $header_flip = array_flip($header);

    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        if(is_array($data) && count($data) == count($header) ){

            $comparison = str_replace("GSEA_", '', $data[ $header_flip['Comparison'] ]);
            $category = $data[ $header_flip['Category'] ];

            $name = $data[ $header_flip['NAME'] ];
            $gsea_data_files[$comparison][$category]['HOVER_INFO'][] = '<b>' . $name . '</b><br />Number of Genes: ' . $data[ $header_flip['N_core'] ];

            if(strlen($name) > 20) $name = substr($name, 0, 20) . '..';
            while(in_array($name, $gsea_data_files[$comparison][$category]['NAME'])) $name .= '.';
            $gsea_data_files[$comparison][$category]['NAME'][] = $name;
            $gsea_data_files[$comparison][$category]['N_core'][] = $data[ $header_flip['N_core'] ];
            $gsea_data_files[$comparison][$category]['FDR.q.val'][] = 'FDR:' . sprintf("%.2f", $data[ $header_flip['FDR.q.val'] ]);

            if($max_name_length < strlen($name)){
                $max_name_length = strlen($name);
            }
        }
    }
    fclose($handle);
}

// echo "$GSEA_summary_csv_dir<pre>" . print_r($gsea_data_files, true) . "</pre>";


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

            $('.scroll').on('click', function(event) {
                if (this.hash !== "") {
                    event.preventDefault();
                    var hash = this.hash;
                    $('html, body').animate({
                        scrollTop: $(hash).offset().top
                    }, 800, function() {
                        window.location.hash = hash;
                    });
                }
            });

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

<?php

	echo "<div class='w-100 my-3'>Comparisions: ";
	foreach($all_comparisons as $comparison){
		echo "<a href='report_comparison.php?analysis=$analysis_id_encrypted&comp=$comparison' class='mx-2 btn " . ($current_comparison == $comparison ? "btn-success" : "btn-secondary") . "'>$comparison</a>";
	}
	echo "</div>";

    echo "<h1 class='w-100 my-5 text-center'>Differentially Expressed Genes for <span class='text-danger'>" . $current_comparison . "</span></h1>";

?>



        			<!-- PART 1 -->
        			<div class="row my-3" id="part1">

        				<h3>1. Summary of Differentially Expressed Genes (DEGs)</h3>

        				<p class="w-100 my-1">For comparison <?php echo trim($summary[0][1]);?>, the number of differentially expressed genes (DEGs) are shown below. Up-regulated genes are high in the first condition (<?php echo trim($conditions[0]);?>) while down-regulated genes are high in the second condition (<?php echo trim($conditions[1]);?>)</p>

        				<?php
                            $library_array = array();
                            $file_dir = $analysis_dir . "alignment/DEG/" . $current_comparison . "/DEG_Analysis/";
                            if(file_exists($file_dir . $current_comparison . "_DEG_Summary.csv")){
                                $file = fopen($file_dir . $current_comparison . "_DEG_Summary.csv", "r");
            					while(! feof($file))  $library_array[] = fgetcsv($file);
            					fclose($file);
                            }

                            if(is_array($library_array) && count($library_array) > 0){
        				?>

            				<div class="w-100" style="border: 1px solid #999; font-size: 0.8rem; overflow-x: auto;">
            					<table class="table table-bordered table-striped table-hover my-0">
            						<thead>
            							<tr class="table-active">
            								<th>Cutoff</th>
            								<th>Standard (two fold and FDR 0.05)</th>
            								<th>Low Stringency (two fold and p-value 0.01)</th>
            							</tr>
            						</thead>
            						<tbody>
            							<?php if (trim($summary[6][1])=='P-Value') { ?>
            								<tr>
            									<td># of Up-regulated genes</td>
            									<td><i><?php echo $library_array[1][1];?></i></td>
            									<td><b><?php echo $library_array[1][2];?></b></td>
            								</tr>
            								<tr>
            									<td># of Down-regulated genes</td>
            									<td><i><?php echo $library_array[2][1];?></i></td>
            									<td><b><?php echo $library_array[2][2];?></b></td>
            								</tr>
            								<tr>
            									<td>Total number of changed genes</td>
            									<td><i><?php echo $library_array[3][1];?></i></td>
            									<td><b><?php echo $library_array[3][2];?>*</b></td>
            								</tr>
            							<?php } else { ?>
            								<tr>
            									<td># of Up-regulated genes</td>
            									<td><b><?php echo $library_array[1][1];?></b></td>
            									<td><i><?php echo $library_array[1][2];?></i></td>
            								</tr>
            								<tr>
            									<td># of Down-regulated genes</td>
            									<td><b><?php echo $library_array[2][1];?></b></td>
            									<td><i><?php echo $library_array[2][2];?></i></td>
            								</tr>
            								<tr>
            									<td>Total number of changed genes</td>
            									<td><b><?php echo $library_array[3][1];?>*</b></td>
            									<td><i><?php echo $library_array[3][2];?></i></td>
            								</tr>
            							<?php } ?>
            						</tbody>
            					</table>
            				</div>

            				<p class="w-100 my-1">*These genes are reported in the table for differentially expressed genes (DEGs) and shown in the DEG heat maps.</p>

                        <?php } // if(is_array($library_array) && count($library_array) > 0){ ?>


        				<div class="row my-3">
        					<div class="col">
        						<p class="w-100 my-3 font-weight-bold">Summary heat map showing all the differentially expressed genes (DEGs)</p>

        						<p class="w-100 my-1">Here log gene expression levels were scaled and used to cluster genes and samples. Each row is a gene, each column is a sample. Gene names are not shown in this plot. Click image to download PDF version.</p>

        						<?php
                                    $file_dir = $analysis_dir . "alignment/DEG/" . $current_comparison . "/DEG_Analysis/";
                                    $file_url = $analysis_url . "alignment/DEG/" . $current_comparison . "/DEG_Analysis/";

                                    if(file_exists($file_dir . $current_comparison . "_DEG_details_low.pdf")){
                                        echo "<p class='w-100 my-4 text-muted'><a href='" . $file_url . $current_comparison . "_DEG_sum_low.pdf' target='_blank'><i class='fas fa-angle-double-right'></i> View detailed heat map showing names for all DEGs</a> (If there are more than 1000 DEGs, only the top 1000 genes are shown)</p>";
                                    }
                                    else if(file_exists($file_dir . $current_comparison . "_DEG_details.pdf")){
                                        echo "<p class='w-100 my-4 text-muted'><a href='" . $file_url . $current_comparison . "_DEG_details.pdf' target='_blank'><i class='fas fa-angle-double-right'></i> View detailed heat map showing names for all DEGs</a> (If there are more than 1000 DEGs, only the top 1000 genes are shown)</p>";
                                    }

                                    echo "<p class='w-100 my-2'>Sometimes, you may want to view gene-only clustering while leaving the sample order unchanged. You can view such heat maps here: ";

                                    if(file_exists($file_dir . $current_comparison . "_DEG_sum_low_g.pdf")){
                                        echo "<a href='" . $file_url . $current_comparison . "_DEG_sum_low_g.pdf' target='_blank'>Summary</a>";
                                    }
                                    else if(file_exists($file_dir . $current_comparison . "_DEG_sum_g.pdf")){
                                        echo "<a href='" . $file_url . $current_comparison . "_DEG_sum_g.pdf' target='_blank'>Summary</a>";
                                    }

                                    echo " or ";

                                    if(file_exists($file_dir . $current_comparison . "_DEG_details_low_g.pdf")){
                                        echo "<a href='" . $file_url . $current_comparison . "_DEG_details_low_g.pdf' target='_blank'>Detailed Plots</a>";
                                    }
                                    else if(file_exists($file_dir . $current_comparison . "_DEG_details_g.pdf")){
                                        echo "<a href='" . $file_url . $current_comparison . "_DEG_details_g.pdf' target='_blank'>Detailed Plots</a>";
                                    }

                                    echo "</p>";
                                ?>

        					</div>
        					<div class="col">
        						<?php
                                    if(file_exists($file_dir . $current_comparison . "_DEG_sum_low.png")){
                                        echo "<a href='" . $file_url . $current_comparison . "_DEG_sum_low.pdf' target='_blank'><img class='img-fluid' src='" . $file_url . $current_comparison . "_DEG_sum_low.png' /></a>";
                                    }
                                    else if(file_exists($file_dir . $current_comparison . "_DEG_sum.png")){
                                        echo "<a href='" . $file_url . $current_comparison . "_DEG_sum.pdf' target='_blank'><img class='img-fluid' src='" . $file_url . $current_comparison . "_DEG_sum.png' /></a>";
                                    }
                                ?>

        					</div>
        				</div>

        			</div>



                    <?php
                        $n_comparison = 0;
                        $comparison = $current_comparison;
                        $data = $gsea_data_files[$comparison];

                        echo '<hr class="w-100 mt-5" />';

                        echo "<div class='d-flex flex-row mt-3'>";
                            echo "<h3 class='align-self-baseline'>GSEA Analysis Results for $comparison</h3>";
                            echo "<p class='align-self-baseline ml-5'><a href='" . $all_comparisons_detailed_report[$comparison] . "' target='_blank'><i class='fas fa-angle-double-right'></i> View Detailed Report</a> </p>";
                        echo '</div>';

                        echo "<div class='w-100 mt-3'>";
                            echo "<p class=''>Below are the top 10 functional categories from GSEA analysis for up (positive) and down(negative) regulated genes. Click <a href='" . $all_comparisons_detailed_report[$comparison] . "' target='_blank'>View Detailed Report</a> link to view all functional categories. If you are new to GSEA analysis, <a href='http://www.broadinstitute.org/gsea/doc/GSEAUserGuideTEXT.htm' target='_blank'>here is a guide on how to interpret GSEA results</a>.</p>";
                        echo '</div>';

                        echo '<div class="container">';
                            echo '<div class="row w-100">';

                            $n_neg_pos = 0;
                            foreach($data as $neg_pos => $values){
                                $n_neg_pos++;

                                echo '<div class="col-md-6">';
                                    echo '<div id="myDiv_'.$n_comparison.'_'.$n_neg_pos.'"';
                                    echo '  style="height: 420px;">';
                                    echo '</div>';
                                echo '</div>';

                                //-----------------------------------------------------------------------------------------------
                                // Script for Plotly
                                echo '<script>';
                                echo '$(document).ready(function() {';

                                    echo "  var xData = [" . implode(", ", $values['N_core']) . "];";
                                    echo "  var yData = ['" . implode("', '", $values['NAME']) . "'];";
                                    echo "  var annotationText = ['" . implode("', '", $values['FDR.q.val']) . "'];";

                                    echo "  var data = [{";
                                    echo "    type: 'bar',";
                                    echo "    x: xData,";
                                    echo "    y: yData,";
                                    echo "    orientation: 'h',";
                                    echo "    hoverinfo: 'text',";
                                    echo "    text: ['" . implode("', '", $values['HOVER_INFO']) . "'],";
                                    echo "    textposition: 'top',";
                                    echo "  }];";

                                    echo "  var layout = {";
                                    echo "    margin: {";
                                    echo "      l: " . min(intval($max_name_length) * 8, 500) . "";
                                    echo "    },";
                                    echo "    title: '$neg_pos',";
                                    echo "    showlegend: false,";
                                    echo "    xaxis: {";
                                    echo "      title: 'Number of Genes',";
                                    echo "      showticklabels: true,";
                                    echo "    },";
                                    echo "    hovermode: 'closest',";
                                    echo "    annotations: []";
                                    echo "  };";

                                    echo "  for (var i = 0; i < " . count($values['NAME']) . "; i++) {";
                                    echo "    var result = {";
                                    echo "      xref: 'x1',";
                                    echo "      yref: 'y1',";
                                    echo "      x: xData[i] + Math.max.apply(null, xData) / 4,";
                                    echo "      y: yData[i],";
                                    echo "      text: annotationText[i],";
                                    echo "      font: {";
                                    echo "        family: 'Arial',";
                                    echo "        size: 12,";
                                    echo "        color: 'rgb(50, 171, 96)'";
                                    echo "      },";
                                    echo "      showarrow: false,";
                                    echo "    };";
                                    echo "    layout.annotations.push(result);";
                                    echo "  }";


                                    echo "  Plotly.newPlot('myDiv_".$n_comparison.'_'.$n_neg_pos . "', data, layout).then(function() {";
                                    echo "    window.requestAnimationFrame(function() {";
                                    echo "      $('.loader').remove();";
                                    echo "    });";
                                    echo "  });";
                                echo "});";

                                echo "</script>";

                            }

                            echo '</div>';

                        echo '</div>';






                        echo '<hr class="w-100 mt-5" />';

                        echo "<div class='d-flex flex-row mt-3'>";
                            echo "<h3 class='align-self-baseline'>Functional Enrichment of GO Analysis</h3>";
                            echo "<p class='align-self-baseline ml-5'><a href='report_deg.php?analysis=$analysis_id_encrypted&comp=$current_comparison' target='_blank'><i class='fas fa-angle-double-right'></i> View All Functional Enrichment Results on Seperate Page</a> </p>";
                        echo '</div>';

                        echo "<div class='w-100 mt-3'>";
                            echo "<p class=''>Below are the top 10 lists from functional enrichment of differentially expressed genes. The top 10 lists from 6 categories are shown: Biological Process, Cellular Component , Molecular Function, KEGG Pathway, Molecular Signature, Interpro Protein Domain. Click <a href='report_deg.php?analysis=$analysis_id_encrypted&comp=$current_comparison' target='_blank'>this</a> link to view all functional categories.</p>";
                        echo '</div>';




                    	$report_dir = $analysis_dir . "alignment/DEG/$current_comparison/Downstream/GO_Analysis_";
                    	$report_url = $analysis_url . "alignment/DEG/$current_comparison/Downstream/GO_Analysis_";


                    	$file_list   = array(
                    		'biological_process.txt',
                    		'cellular_component.txt',
                    		'molecular_function.txt',
                    		'kegg.txt',
                    		'msigdb.txt',
                    		'interpro.txt',
                    		'wikipathways.txt',
                    		'reactome.txt'
                    	);

                    	$chart_name_list = array(
                    		'Biological Process',
                    		'Cellular Component',
                    		'Molecular Function',
                    		'KEGG',
                    		'Molecular Signature',
                    		'Interpro Protein Domain',
                    		'Wiki Pathway',
                    		'Reactome',
                    	);

                    	$report_file = 'geneOntology.html';



                    	$directions = array('Up', 'Down');

                    	foreach($directions as $direction){


                            echo '<div class="row w-100 my-5">';

                                // Analysis LeftBar
                                echo '<div class="col-md-3 p-t-2">';

                                    echo "<div class='card w-100'>";
                                        echo "<div class='card-block lead list-group-item-success'>$direction Regulated Genes</div>";

                                	    echo '<ul class="list-group list-group-flush">';

                                	    for ($i = 0; $i < count($file_list); $i++) {

                                	      $chart_name      = $chart_name_list[$i];
                                	      $chart_file_name = $file_list[$i];

                                	      echo '<li style="padding:2px 5px;" class="enrichment_tab_left list-group-item';

                                	      if ($chart_name == 'Biological Process') {

                                	      }

                                	      echo '" aria-expanded="';
                                	      if ($chart_name == 'Biological Process') {
                                	        echo 'true';
                                	      } else {
                                	        echo 'false';
                                	      }
                                	      echo '">';

                                	      echo '  <a data-toggle="tab" class="tab_link" target="' . $chart_file_name . '_div_' . $direction . '" href="#' . $chart_file_name . '_div_' . $direction . '" direction="' . $direction . '"><i class="fas fa-chart-bar"></i> ' . $chart_name . '  </a>';

                                	      echo '</li>';
                                	    }

                                	    // Analysis Report
                                	    if ($report_url != '') {
                                	      echo '  <li class="enrichment_tab_left list-group-item">';
                                	      echo '    <a class="text-danger" href="report_enrichment.php?analysis=' . $analysis_id_encrypted . '&comp=' . $current_comparison . '&direction=' . $direction . '" target="_blank">';
                                	      echo '      <i class="fas fa-angle-double-right"></i>';
                                	      echo '      Detailed Report';
                                	      echo '    </a>';
                                	      echo '  </li>';
                                	    }
                                	    echo '  </ul>';

                            	    echo '</div>';

                                echo '</div>';


                    	    //-----------------------------------------------------------------------------------------------
                    	    // Analysis Chart Container
                    	    echo '<div class="tab-content col-md-9 p-x-0" style="min-width:750px;">';

                    	    for ($i = 0; $i < count($file_list); $i++) {
                    	      $chart_name      = $chart_name_list[$i];
                    	      $chart_file_name = $file_list[$i];
                    	      echo '<div id="'.$chart_file_name.'_div_'.$direction.'" class="tab-pane container_chart_' . $direction . ' fade';
                    	      if ($chart_name == 'Biological Process') {
                    	        echo ' active show';
                    	      }
                    	      echo '">';



                    	      $myfile = fopen("{$report_dir}{$direction}/{$chart_file_name}", "r") or die("Unable to open file!");

                    	      $CONTENT_ARRAY = array();
                    	      while(!feof($myfile)) {
                    	          $row_content = explode("\t", fgets($myfile));
                    	          $CONTENT_ARRAY[] = $row_content;
                    	      }
                    	      fclose($myfile);

                    	      // Leave only 10 records
                    	      foreach ($CONTENT_ARRAY as $key => $value) {
                    	        $upper_bound = 11;
                    	        if ($chart_name == 'KEGG') { $upper_bound = 22;}
                    	        if ($key == 0 || $key >= $upper_bound) {
                    	          unset($CONTENT_ARRAY[$key]);
                    	        }
                    	      }

                    	      // Prepare for drawing chart
                    	      $CONTENT_LOGP_ARRAY = array();
                    	      $CONTENT_NAME_ARRAY = array();
                    	      $CONTENT_GENE_NUMBER_ARRAY = array();
                    	      $CONTENT_HOVER_TEXT_ARRAY = array();
                    	      $CONTENT_ANNOTATION_TEXT_ARRAY = array();
                    	      $max_name_length = 0;
                    	      foreach ($CONTENT_ARRAY as $key => $value) {
                    	        $CONTENT_LOGP_ARRAY[] = $value[3];

                                $name = str_replace("'", "", $value[1]);
                                if(strlen($name) > 30) $name = substr($name, 0, 30) . '..';
                                while(in_array($name, $CONTENT_NAME_ARRAY)) $name .= '.';
                    	        $CONTENT_NAME_ARRAY[] = $name;
                    	        $max_name_length = max($max_name_length, strlen($name));
                    	        $CONTENT_GENE_NUMBER_ARRAY[] = count(explode(",", $value[10]));
                    	        $CONTENT_HOVER_TEXT_ARRAY[] = '<b>' . str_replace("'", "", $value[1]) . '</b><br />Number of Genes: ' . count(explode(",", $value[10]));
                    	        $CONTENT_ANNOTATION_TEXT_ARRAY[] = 'log(p):' . number_format($value[3], 2);
                    	      }
                    	      $CONTENT_LOGP_ARRAY = array_reverse($CONTENT_LOGP_ARRAY);
                    	      $CONTENT_NAME_ARRAY = array_reverse($CONTENT_NAME_ARRAY);
                    	      $CONTENT_GENE_NUMBER_ARRAY = array_reverse($CONTENT_GENE_NUMBER_ARRAY);
                    	      $CONTENT_HOVER_TEXT_ARRAY = array_reverse($CONTENT_HOVER_TEXT_ARRAY);
                    	      $CONTENT_ANNOTATION_TEXT_ARRAY = array_reverse($CONTENT_ANNOTATION_TEXT_ARRAY);


                    	      // Output
                    	      echo '<div id="myDiv_'.$chart_file_name.'_'.$direction.'"';
                    	      echo '  style="height: 420px;">';
                    	      echo '</div>';
                    	      echo '';
                    	      echo '';
                    	      echo '</div>';


                    	      //-----------------------------------------------------------------------------------------------
                    	      // Script for Plotly
                    	      echo '<script>';
                    	      echo '$(document).ready(function() {';
                    	      echo "  var xData = [" . implode(", ", $CONTENT_GENE_NUMBER_ARRAY) . "];";
                    	      echo "  var yData = ['" . implode("', '", $CONTENT_NAME_ARRAY) . "'];";
                    	      echo "  var annotationText = ['" . implode("', '", $CONTENT_ANNOTATION_TEXT_ARRAY) . "'];";
                    	      echo "  var data = [{";
                    	      echo "    type: 'bar',";
                    	      echo "    x: xData,";
                    	      echo "    y: yData,";
                    	      echo "    orientation: 'h',";
                    	      echo "    hoverinfo: 'text',";
                    	      echo "    text: ['" . implode("', '", $CONTENT_HOVER_TEXT_ARRAY) . "'],";
                    	      echo "    textposition: 'top',";
                    	      echo "  }];";
                    	      echo "  var layout = {";
                    	      echo "    margin: {";
                    	      echo "      l: " . min(intval($max_name_length) * 8, 500) . "";
                    	      echo "    },";
                    	      echo "    title: '{$chart_name}',";
                    	      echo "    showlegend: false,";
                    	      echo "    xaxis: {";
                    	      echo "      title: 'Number of Genes',";
                    	      echo "      showticklabels: true,";
                    	      echo "    },";
                    	      echo "    hovermode: 'closest',";
                    	      echo "    annotations: []";
                    	      echo "  };";

                    	      echo "  for (var i = 0; i < ";
                    	        if ($chart_name == 'KEGG') {
                    	          echo "21";
                    	        } else {
                    	          echo "10";
                    	        }

                    	      echo "    ; i++) {";
                    	      echo "    var result = {";
                    	      echo "      xref: 'x1',";
                    	      echo "      yref: 'y1',";
                    	      echo "      x: xData[i] + Math.max.apply(null, xData) / 6,";
                    	      echo "      y: yData[i],";
                    	      echo "      text: annotationText[i],";
                    	      echo "      font: {";
                    	      echo "        family: 'Arial',";
                    	      echo "        size: 12,";
                    	      echo "        color: 'rgb(50, 171, 96)'";
                    	      echo "      },";
                    	      echo "      showarrow: false,";
                    	      echo "    };";
                    	      echo "    layout.annotations.push(result);";
                    	      echo "  }";
                    	      echo "  Plotly.newPlot('myDiv_" . $chart_file_name . "_" . $direction . "', data, layout).then(function() {";
                    	      echo "    window.requestAnimationFrame(function() {";
                    	      echo "      $('.loader').remove();";
                    	      echo "    });";
                    	      echo "  });";
                    	      echo "});";

                    	      echo "</script>";
                    	    }
                    		echo '</div>';
                    		echo '</div>';


                    	}


                    ?>




        			<!-- PART 2 -->
        			<div class="row my-5" id="part2">

        				<h3>2. DEG Data in Table Format</h3>

        				<ul class="w-100 my-1">
        					<li>
                                <?php
                                    echo "<a href='" . $analysis_url . "alignment/DEG/$current_comparison/Overview/$current_comparison" . "_alldata.csv' target='_blank'><i class='fas fa-download'></i> Table listing all genes for comparison $current_comparison</a> (CSV format)";
                                ?>
        					</li>
        					<li>
                                <?php
                                if(file_exists($file_dir . $current_comparison . "_DEG.csv")){
                                    echo "<a href='" . $file_url . $current_comparison . "_DEG.csv' target='_blank'><i class='fas fa-download'></i> Table listing DEGs for comparison $current_comparison</a> (CSV format)";
                                }
                                else if(file_exists($file_dir . $current_comparison . "_DEG_low.csv")){
                                    echo "<a href='" . $file_url . $current_comparison . "_DEG_low.csv' target='_blank'><i class='fas fa-download'></i> Table listing DEGs for comparison $current_comparison</a> (CSV format)";
                                }

                                ?>
        					</li>
        				</ul>

        				<p class="w-100 my-1">These tables show the Ensembl gene IDs, RPKM (reads per kilobase per million) values for the genes followed by statistics for differential expression, and gene annotations. We report RPKM in the table as a convenient value for
        	estimated gene expression level. In the actual statistical analysis, raw counts of genes are used by limma package to compute significance of differential expression.</p>
        				<ul class="w-100 my-1">
        					<li>To compute the RPKM value, we first get raw read counts for each gene, and convert raw counts to counts per million (cpm) to normalize for sequencing depth; next we divide cpm by gene length in kb to normalize for transcript length. If the data is paired-end, we treat the two paired-reads as one fragment, then use the same calculation method to get FPKM value.</li>
        					<li>
        						The statistic values include:
        						<ul>
        							<li>Average Expression (AveExpr, which is average log2 of CPM). You can use this to rank genes by average expression level.</li>
        							<li>Log 2 Fold Change (logFC). You can use logFC>=1 to filter for two fold up-regulation, or logFC <=-1 to filter for two fold down-regulation.</li>
        							<li>P.Value. Unadjusted p-value for differential expression. You can use p-value&lt;0.01 as a low stringency cutoff, although FDR should be used in most cases.</li>
        							<li>False Discovery Rate (FDR, shown as adj.P.val in table). FDR is computed by adjusting the p-value for multiple testing. It is preferred over p-value to select for differentially expressed genes. Typically FDR&lt;0.05 is recommended, but you can use a smaller (more stringent) or larger (less stringent) value. </li>
        						</ul>
        					</li>
        					<li>The gene annotations include gene symbol (Associated.Gene.Name), Description, Gene.Biotype (useful to tell if a gene is protein-coding, lincRNA, pseudogene, etc), and EntrezGene.ID.</li>
        				</ul>
        				<p class="w-100 my-1">The DEG table is a subset of the all gene table. You can create your own filters with the all gene table to come up with your own list of DEGs. For example, a smaller logFC may be needed if your samples are mixed cell population and the changes occur only in a subset of cells.</p>

        			</div>




        			<!-- PART 3 -->
        			<div class="row my-3" id="part3">

        				<h3>3. Grouping of Samples</h3>


        				<p class="w-100 my-1">Although the comparison is between <?php echo trim($conditions[0]); ?> and <?php echo trim($conditions[1]); ?> conditions, it is useful to review all the samples to make sure replicates within each condition are similar to each other, and the major variability in the experiment is between the conditions, rather than between replicate samples of the same condition. </p>

        				<div class="row">
        					<div class="col-md-6">
        						<p class="w-100 my-1">We created multidimensional (MDS) plot to view sample relationships. Ideally, good separation should be seen between samples from the two conditions.</p>
        					</div>
        					<div class="col-md-6">
                                <?php
                                    if(file_exists($analysis_dir . "alignment/DEG/$current_comparison/Overview/MDS_plot.png")){
                                        echo "<a href='" . $analysis_url . "alignment/DEG/$current_comparison/Overview/MDS_plot.pdf' target='_blank'><img class='img-fluid' src='" . $analysis_url . "alignment/DEG/$current_comparison/Overview/MDS_plot.png' /></a>";
                                    }
                                ?>
        					</div>
        				</div>

        				<div class="row my-3">
        					<div class="col-md-6">
        						<p class="w-100 my-1">We also selected genes that have variable expression across samples regardless of the conditions, and made a heatmap. These variable genes were chosen based on standard deviation (SD) of expression values larger than 30% of the mean expression values (Mean). Again, ideally the samples should be clustered according to the conditions. You can also get a rough estimate of what types of changes occur in the dataset.</p>
        						<p class="w-100 my-1">In the heatmap next to gene cluster tree (left side of main heatmap block), the color bar is based on statistic cutoff for <?php echo $comparison; ?> comparison for each gene. Color yellow means no significantly changed, color orange means passing low stringency cutoff (p-value based), color red means passing standard cutoff (FDR based).</p>
        					</div>
        					<div class="col-md-6">
                                <?php
                                    if(file_exists($analysis_dir . "alignment/DEG/$current_comparison/Overview/" . $current_comparison . "_Overall_Heatmap.png")){
                                        echo "<a href='" . $analysis_url . "alignment/DEG/$current_comparison/Overview/" . $current_comparison . "_Overall_Heatmap.pdf' target='_blank'><img class='img-fluid' src='" . $analysis_url . "alignment/DEG/$current_comparison/Overview/" . $current_comparison . "_Overall_Heatmap.png' /></a>";
                                    }
                                ?>
        					</div>
        				</div>
        				<br />

        			</div>




        			<!-- PART 4 -->
        			<div class="row" id="part4">
        				<br /><br />
        				<h3>4. Top Genes</h3>
        				<hr>

        				<p class="w-100 my-1">Sometimes it is useful to view the top genes. Here we selected either the top 100 or top 50 genes based on logFC (FDR and p-value cutoff also applied if there are enough number of DEGs), and created heatmaps.</p>
        				<br />
        				<div class="row">
                            <?php
                                $file_dir = $analysis_dir . "alignment/DEG/$current_comparison/Top100Genes/";
                                $file_url = $analysis_url . "alignment/DEG/$current_comparison/Top100Genes/";
                            ?>

        					<div class="col-md-5">
        						<p class="w-100 my-1">The graph shows top 50 genes. You can also get additional graphs in PDF format from the links below. </p>

        						<ul class="w-100 my-1">
        							<li>
        								Top 100 genes: <a href="<?php echo $file_url . $current_comparison . "_Top100.pdf"; ?>" target="_blank">Genes and Samples Clustered</a>,
        								<a href="<?php echo $file_url . $current_comparison . "_Top100_g.pdf"; ?>" target="_blank">Gene Clustered Only</a>,
        								<a href="<?php echo $file_url . $current_comparison . "_Top100.csv"; ?>" target="_blank">Data Table (CSV format)</a>
        							</li>
        							<li>
        								Top 50 genes: <a href="<?php echo $file_url . $current_comparison . "_Top50.pdf"; ?>" target="_blank">Genes and Samples Clustered</a>,
        								<a href="<?php echo $file_url . $current_comparison . "_Top50_g.pdf"; ?>" target="_blank">Gene Clustered Only</a>,
        								<a href="<?php echo $file_url . $current_comparison . "_Top50.csv"; ?>" target="_blank">Data Table (CSV format)</a>
        							</li>
        						</ul>

        						<p class="w-100 my-1">In the top100/top50 gene heatmaps, the color bar is also based on statistic cutoff for <?php echo $comparison; ?> comparison for each gene. Color yellow means no significantly changed, color orange means passing low stringency cutoff (p-value based), color red means passing standard cutoff (FDR based).</p>
        					</div>
        					<div class="col-md-7">
                                <?php
                                    if(file_exists($file_dir . $current_comparison . "_Top50.png")){
                                        echo "<a href='" . $file_url . $current_comparison . "_Top50.pdf' target='_blank'><img class='img-fluid' src='" . $file_url . $current_comparison . "_Top50.png' /></a>";
                                    }
                                ?>
        					</div>
        				</div>

        			</div>



				</div>

            </div>

		    <?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>

		</div>

	</div>

</body>
</html>