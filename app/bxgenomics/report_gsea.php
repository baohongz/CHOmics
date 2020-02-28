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

					</div>

					<hr class="w-100" />

					<h1 class="w-100 my-4 text-center">BxGenomics - Summary of GSEA Analysis</h1>

					<h3 class="w-100 my-3">Introduction</h3>
					<p class="w-100">This page displays the top 10 lists from functional enrichment of differentiall expressed genes.</p>

<?php

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

    echo "<div class='w-100'>";
    echo '    <a class="mx-2" href="'. $GSEA_summary_csv_url .'" target="_blank">';
    echo '      <i class="fas fa-angle-double-right"></i> GSEA Top10 Genes Summary Data for All Comparisons (CSV format, open in Excel)';
    echo '    </a>';
    echo "</div>";

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
                $gsea_data_files[$comparison][$category]['HOVER_INFO'][] = '<b>' . $name . '</b><br />Number of Genes: ' . $data[ $header_flip['N_core'] ] . '<br />FDR.q.val: ' . $data[ $header_flip['FDR.q.val'] ];

                if(strlen($name) > 20) $name = substr($name, 0, 20) . '..';
                while(in_array($name, $gsea_data_files[$comparison][$category]['NAME'])) $name .= '.';
                $gsea_data_files[$comparison][$category]['NAME'][] = $name;

                $gsea_data_files[$comparison][$category]['N_core'][] = $data[ $header_flip['N_core'] ];
                $gsea_data_files[$comparison][$category]['FDR.q.val'][] = 'FDR:' . sprintf("%.2f", $data[ $header_flip['FDR.q.val'] ] );

                if($max_name_length < strlen($name)){
                    $max_name_length = strlen($name);
                }

            }
        }
        fclose($handle);
    }

    // echo "$GSEA_summary_csv_dir<pre>" . print_r($gsea_data_files, true) . "</pre>";




    $n_comparison = 0;
	foreach($gsea_data_files as $comparison=>$data){
        $n_comparison++;

        echo '<hr class="w-100 mt-5" />';

        echo "<div class='d-flex flex-row mt-3'>";
            echo "<h3 class='align-self-baseline'>$comparison</h3>";
            echo "<p class='align-self-baseline ml-5'><a href='" . $all_comparisons_detailed_report[$comparison] . "' target='_blank'><i class='fas fa-angle-double-right'></i> View Detailed Report</a></p>";
        echo '</div>';

        echo '<div class="container">';
            echo '<div class="row w-100">';

            $n_neg_pos = 0;
            foreach($data as $neg_pos => $values){
                $n_neg_pos++;

                echo '<div class="col-md-6">';
                    echo '<div id="myDiv_'.$n_comparison.'_'.$n_neg_pos.'" style=""></div>';
                echo '</div>';

                //-----------------------------------------------------------------------------------------------
                // Script for Plotly
                echo "\n\n<script>\n";
                echo '$(document).ready(function() {';

                    echo "\n  var xData = [" . implode(", ", $values['N_core']) . "];\n";
                    echo "  var yData = ['" . implode("', '", $values['NAME']) . "'];\n";
                    echo "  var annotationText = ['" . implode("', '", $values['FDR.q.val']) . "'];\n\n";

                    echo "  var data = [{\n";
                    echo "    type: 'bar',\n";
                    echo "    x: xData,\n";
                    echo "    y: yData,\n";
                    echo "    orientation: 'h',\n";
                    echo "    hoverinfo: 'text',\n";
                    echo "    text: ['" . implode("', '", $values['HOVER_INFO']) . "'],\n";
                    echo "    textposition: 'top',\n";
                    echo "  }];\n\n";

                    echo "  var layout = {\n";
                    echo "    margin: {";
                    echo "      l: " . min(intval($max_name_length) * 8, 500) . "";
                    echo "    },\n";
                    echo "    title: '$neg_pos',\n";
                    echo "    showlegend: false,\n";
                    echo "    xaxis: {\n";
                    echo "      title: 'Number of Genes',\n";
                    echo "      showticklabels: true,\n";
                    echo "    },";
                    echo "    hovermode: 'closest',\n";
                    echo "    annotations: []\n";
                    echo "  };\n\n";

                    echo "  for (var i = 0; i < " . count($values['NAME']) . "; i++) {\n";
                    echo "    var result = {\n";
                    // echo "      xref: 'x1',";
                    // echo "      yref: 'y1',";
                    echo "      x: xData[i] + Math.max.apply(null, xData) / 4,\n";
                    echo "      y: yData[i],\n";
                    echo "      text: annotationText[i],\n";
                    echo "      font: {\n";
                    echo "        family: 'Arial',\n";
                    echo "        size: 12,";
                    echo "        color: 'rgb(50, 171, 96)'\n";
                    echo "      },";
                    echo "      showarrow: false,\n";
                    echo "    };";
                    echo "    layout.annotations.push(result);\n";
                    echo "  }\n";

                    echo "  Plotly.newPlot('myDiv_".$n_comparison.'_'.$n_neg_pos . "', data, layout).then(function() {\n";
                    echo "    window.requestAnimationFrame(function() {\n";
                    // echo "      $('.loader').remove();\n";
                    echo "    });\n";
                    echo "  });\n\n";
                echo "});\n\n";

                echo "</script>\n\n";

            }

            echo '</div>';

        echo '</div>';

	}


?>



				</div>

            </div>

		    <?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>

		</div>

	</div>

</body>
</html>