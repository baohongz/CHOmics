<?php

include_once('config.php');


if (isset($_GET['action']) && $_GET['action'] == 'generate_heatmap') {

    $species = $_SESSION['SPECIES_DEFAULT'];

    $otherOptions = array();
    $otherOptions['transform'] = isset($_POST["options_enable_log2"]) ? true : false;
    $otherOptions['transform_value'] = abs(floatval($_POST["options_log_value"]));

    $otherOptions['zscore'] = isset($_POST["options_enable_z_score"]) ? true : false;
    $otherOptions['upper_limit_enable'] = isset($_POST["options_enable_upper"]) ? true : false;
    $otherOptions['upper_limit_value'] = floatval($_POST["options_upper_value"]);
    $otherOptions['lower_limit_enable'] = isset($_POST["options_enable_lower"]) ? true : false;
    $otherOptions['lower_limit_value'] = floatval($_POST["options_lower_value"]);

    $otherOptions['options_cluster_genes'] = isset($_POST["options_cluster_genes"]) ? 'true' : 'false';
    $otherOptions['options_cluster_samples'] = isset($_POST["options_cluster_samples"]) ? 'true' : 'false';

    $otherOptions['options_overlay_samples'] = isset($_POST["options_overlay_samples"]) ? true : false;
    $otherOptions['options_display_genes'] = isset($_POST["options_display_genes"]) ? 'true' : 'false';
    $otherOptions['options_display_samples'] = isset($_POST["options_display_samples"]) ? 'true' : 'false';



    // Get Attributes
    $ATTRIBUTES = array();
    foreach ($BXAF_CONFIG['TOOL_EXPORT_COLNAMES_ALL']['Sample'] as $attr) {
        if (isset($_POST["attributes_Sample_{$attr}"])) {
            $ATTRIBUTES[] = $attr;
        }
    }


    $GENE_NAMES = category_text_to_idnames($_POST['Gene_List'], 'name', 'gene', $species);

    if (! is_array($GENE_NAMES) || count($GENE_NAMES) <= 0) {
        echo '<h3 class="text-danger m-3">Error: No genes found, please revise.<h3>';
        exit();
    }
    // if (is_array($GENE_NAMES) && count($GENE_NAMES) > 100) {
    //     echo '<h3 class="text-danger my-3">Error</h3><div class="my-3">To reduce memory usage and improve the performance, you are only allowed to enter up to 100 genes.</div>';
    //     exit();
    // }

    $GENE_INDEXES = array_flip($GENE_NAMES);

    $SAMPLE_NAMES = category_text_to_idnames($_POST['Sample_List'], 'name', 'sample', $species);

    if (!is_array($SAMPLE_NAMES) || count($SAMPLE_NAMES) <= 0) {
        echo '<h3 class="text-danger my-3">Error</h3><div class="my-3">Please enter at least one sample name to continue.</div>' ;
        exit();
    }

    if (count($GENE_NAMES) * count($SAMPLE_NAMES) > 2000000) {
        echo '<h3 class="text-danger my-3">Error</h3><div class="my-3">To reduce memory usage and improve the performance, you are only allowed to enter up to genes * samples < 2000000.</div>';
        exit();
    }

    $SAMPLE_INDEXES = array_flip($SAMPLE_NAMES);

    $sql = "SELECT * FROM ?n WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND ?n IN (?a)";
    $sample_info = $BXAF_MODULE_CONN -> get_assoc('ID', $sql, $BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES'], 'Name', $SAMPLE_NAMES);

    $sql = "SELECT DISTINCT `Platform_Type` FROM ?n WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND ?n IN (?a)";
    $platforms = $BXAF_MODULE_CONN -> get_col($sql, $BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES'], 'Name', $SAMPLE_NAMES);

    if(is_array($platforms) && count($platforms) > 1){
        echo '<h3 class="text-danger my-3">Error</h3><div class="my-3">Your samples are from both Array and NGS. Please enter samples from one platform only.</div>';
        exit();
    }
    $platform = array_shift($platforms);

    $data_column = ($platform == 'Array') ? 'Value' : 'FPKM';

    ini_set('memory_limit','8G');
    $tabix_data = tabix_search_bxgenomics(array_values($GENE_INDEXES), array_keys($SAMPLE_NAMES), ($platform == 'Array') ? 'GeneLevelExpression' : 'GeneFPKM' );


    if(! is_array($tabix_data) || count($tabix_data) <= 0){
        echo '<h3 class="text-danger m-3">Error: No gene expression data retrieved. Please enter different genes and samples.<h3>';
        exit();
    }

    $geneExpressionValueIndex = array();
    $geneExpressionValueIndex_raw = array();
    $geneExpressionValues = array();
    foreach($tabix_data as $tempValue){
        $gene_index 	= $tempValue['GeneIndex'];
        $sample_index   = $tempValue['SampleIndex'];
        $value		    = $tempValue['Value'];

        $geneExpressionValueIndex_raw[$gene_index][$sample_index] = $value;

        if ($value == ''){
            $geneExpressionValueIndex[$gene_index][$sample_index] = '"NA"';
        }
        else {
            if ($otherOptions['transform']){
                $value = log(floatval($value) + $otherOptions['transform_value'], 2);
            }

            $geneExpressionValueIndex[$gene_index][$sample_index] = $value;

            $geneExpressionValues[$gene_index]['Numeric'][$sample_index] = $value;
            $geneExpressionValues[$gene_index]['Numeric_Count'] = count( $geneExpressionValues[$gene_index]['Numeric'] );
        }
    }
    // Remove genes with no values
    foreach ($GENE_INDEXES as $gene_name=>$gene_index) {
        if(array_sum( $geneExpressionValueIndex_raw[$gene_index] ) <= 0){
            unset($GENE_INDEXES[$gene_name]);
            unset($geneExpressionValueIndex[$gene_index]);
            unset($geneExpressionValues[$gene_index]);
        }
    }

    $found_gene_index = array();
    $found_sample_index = array();
    foreach($geneExpressionValues as $currentGeneIndex => $tempValue1){
        foreach($tempValue1['Numeric'] as $currentSampleIndex => $v){
            $found_gene_index[$currentGeneIndex] = 1;
            $found_sample_index[$currentSampleIndex] = 1;
        }
    }
    foreach($SAMPLE_INDEXES as $sample_name=>$sample_index){
        if(! array_key_exists($sample_index, $found_sample_index)){
            unset($SAMPLE_INDEXES[$sample_name]);
            unset($SAMPLE_NAMES[$sample_index]);
        }
    }


    $raw_data = array();
    $i = 0;
    $raw_data[$i] = array();
    $raw_data[$i][] = 'Samples';
    foreach($SAMPLE_INDEXES as $sample_name=>$sample_index){
        $raw_data[$i][] = $sample_name;
    }
    foreach ($ATTRIBUTES as $attr) {
        $i++;
        $raw_data[$i] = array();
        $raw_data[$i][] = $attr;
        foreach($SAMPLE_INDEXES as $sample_name=>$sample_index){
            $raw_data[$i][] = $sample_info[$sample_index][$attr];
        }
    }
    foreach ($GENE_INDEXES as $gene_name=>$gene_index) {
        $i++;
        $raw_data[$i] = array();
        $raw_data[$i][] = $gene_name;
        foreach($SAMPLE_INDEXES as $sample_name=>$sample_index){
            $raw_data[$i][] = $geneExpressionValueIndex_raw[$gene_index][$sample_index];
        }
    }


    if ($otherOptions['zscore']){
		foreach($geneExpressionValues as $currentGeneIndex => $tempValue1){

			if ($tempValue1['Numeric_Count'] > 0){

				$mean 	= calculateMean($tempValue1['Numeric']);
				$stdev 	= calculateStdev($tempValue1['Numeric']);

				foreach($geneExpressionValueIndex[$currentGeneIndex] as $currentSampleIndex => $tempValue2){
					if ($tempValue2 != '"NA"'){
						$geneExpressionValueIndex[$currentGeneIndex][$currentSampleIndex] = calculateZScore($tempValue2, $mean, $stdev);
					}
				}
			}
		}
	}


	if ($otherOptions['upper_limit_enable'] || $otherOptions['lower_limit_enable']){

		foreach($geneExpressionValues as $currentGeneIndex => $tempValue1){

			if ($tempValue1['Numeric_Count'] > 0){

				foreach($geneExpressionValueIndex[$currentGeneIndex] as $currentSampleIndex => $tempValue2){

					if ($tempValue2 != '"NA"'){

						if ($otherOptions['upper_limit_enable']){
							if ($tempValue2 > $otherOptions['upper_limit_value']){
								$geneExpressionValueIndex[$currentGeneIndex][$currentSampleIndex] = $otherOptions['upper_limit_value'];
							}
						}

						if ($otherOptions['lower_limit_enable']){
							if ($tempValue2 < $otherOptions['lower_limit_value']){
								$geneExpressionValueIndex[$currentGeneIndex][$currentSampleIndex] = $otherOptions['lower_limit_value'];
							}
						}

					}
				}
			}

		}
	}


    $heatmap_data = array();
    foreach($raw_data as $i=>$row){

        if($row[0] == 'Samples') $heatmap_data[$i] = $row;
        else if(in_array($row[0], $ATTRIBUTES)){
            foreach($row as $j=>$c){
                if($c == '') $heatmap_data[$i][$j] = 'No Info';
                else $heatmap_data[$i][$j] = $c;
            }
        }
        else if( array_key_exists($row[0], $GENE_INDEXES) ){

            $currentGeneIndex = $GENE_INDEXES[ $row[0] ];

            foreach($row as $j=>$c){
                if($j == 0){
                    $heatmap_data[$i][$j] = $c;
                }
                else {
                    $currentSampleIndex = $SAMPLE_INDEXES[ $raw_data[0][$j] ];
                    $heatmap_data[$i][$j] = $geneExpressionValueIndex[$currentGeneIndex][$currentSampleIndex];
                }

            }
        }
    }






    $data_file_unique = microtime(true);
    $data_file_dir = $BXAF_CONFIG['CURRENT_SYSTEM_CACHE_DIR'] . $data_file_unique;
    $data_file_url = $BXAF_CONFIG['CURRENT_SYSTEM_CACHE_URL'] . $data_file_unique;
    if (!is_dir($data_file_dir)) mkdir($data_file_dir, 0755, true);

    $raw_data_file = $data_file_dir . '/raw_data.csv';
    $fp = fopen($raw_data_file, 'w');
    foreach($raw_data as $i=>$row){
        fputcsv($fp, $row);
    }
    fclose($fp);

    $heatmap_data_file = $data_file_dir . '/heatmap_data.csv';
    $fp = fopen($heatmap_data_file, 'w');
    foreach($heatmap_data as $i=>$row){
        fputcsv($fp, $row);
    }
    fclose($fp);



    $output_contents = '';
    $output_contents .= '<h3 class="m-3">Summary of Data</h3>';
    $output_contents .= '<div>';
        $output_contents .= '<ul>';
            $output_contents .= '<li><strong><span class="text-success">' . (count($GENE_INDEXES) > 1 ? (count($GENE_INDEXES) . ' genes') : ' 1 gene') . ' found: </span></strong> ' . implode(", ", array_keys($GENE_INDEXES)) . '</li>';
            $output_contents .= '<li><strong><span class="text-success">' . (count($SAMPLE_INDEXES) > 1 ? (count($SAMPLE_INDEXES) . ' samples') : ' 1 sample') . ' found:</span> </strong> ' . implode(", ", array_keys($SAMPLE_INDEXES)) . '</li>';

            foreach($platforms as $id=>$s){
                $output_contents .= '<li><strong>Platform Name: </strong> (' . $platform_info[$id]['Type'] . ') ' . $platform_info[$id]['Name'] . ' (<span class="text-success">' . (count($s) > 1 ? (count($s) . ' samples') : ' 1 sample') . '</span>)</li>';
            }
        $output_contents .= '</ul>';
    $output_contents .= '</div>';


    $output_contents .= "<div class='my-5'><h5>Download: <a href='{$data_file_url}/raw_data.csv' target='_blank'><i class='fas fa-download'></i> Raw Data File</a> <a href='{$data_file_url}/heatmap_data.csv' target='_blank'><i class='fas fa-download'></i> Heatmap Data File</a> <a href='index.php?key=$data_file_unique'><i class='fas fa-bookmark'></i> Bookmark URL</a> </h5></div>";

    $output_contents .= "<canvas class='plot_container my-3' id='plotSection' width='900' height='900' xresponsive='false' aspectRatio='1:1'></canvas>";
    $output_contents .= '<script type="text/javascript">';

        $output_contents .= '$(document).ready(function() {';

            $output_contents .= 'var plotObj = new CanvasXpress("plotSection", ';

// data
                $output_contents .= '{';

                    $output_contents .= '"x": {';

                        $x_contents = array();
                        foreach ($ATTRIBUTES as $i=>$k) {
                            $vals = array();
                            foreach($SAMPLE_INDEXES as $name=>$index){
                                $v = $sample_info[$index][$k];
                                if($v == '') $v = 'No info';
                                $vals[] = "'" . addslashes($v) . "'";
                            }
                            $x_contents[] = "'$k': [" . implode(",", $vals) . "]";
                        }
                        $output_contents .= implode(",\n", $x_contents);

                    $output_contents .= '},';
                    $output_contents .= "\n\n";

                    $output_contents .= '"y": {';

                        $vars = array();
                        foreach ($GENE_INDEXES   as $name=>$index) $vars[] = "'$name'";
                        $output_contents .= '"vars":[' . implode(",", $vars) . '],';

                        $smps = array();
                        foreach ($SAMPLE_INDEXES as $name=>$index) $smps[] = "'$name'";
                        $output_contents .= '"smps":[' . implode(",", $smps) . '],';

                        $data = array();
                        foreach ($GENE_INDEXES as $gene_name=>$gene_index){
                            $vs = array();
                            foreach ($SAMPLE_INDEXES as $sample_name=>$sample_index){
                                $vs[] = $geneExpressionValueIndex[$gene_index][$sample_index];
                            }
                            $data[] = "[" . implode(",", $vs) . "]";
                        }
                        $output_contents .= '"data":[' . implode(",\n", $data) . ']';

                    $output_contents .= '}';

                $output_contents .= '},';
                $output_contents .= "\n\n";


// layout $otherOptions['zscore']
                $output_contents .= '{';
                    $output_contents .= '
                        "colorSpectrum"             : ["blue", "white", "red"],
                        "colorSpectrumZeroValue"    : 0,
                        "graphType"                 : "Heatmap",
                        "heatmapIndicatorHeight"    : 50,
                        "heatmapIndicatorHistogram" : false,
                        "heatmapIndicatorPosition"  : "topRight",
                        "heatmapIndicatorWidth"     : 60,
                        "heatmapSmpSeparateBy"      : "Treatment",
                        "samplesClustered"          : ' . $otherOptions["options_cluster_samples"] . ',
                        "variablesClustered"        : ' . $otherOptions["options_cluster_genes"] . ',
                        "title"                     : "Gene Expression Levels",
                        "subtitle"                  : "' . ($otherOptions['zscore'] ? "Z-Score from " : "") . ($otherOptions['transform'] ? ('log2(' . $data_column . ' + ' . $otherOptions['transform_value'] . ')') : $data_column) . '",
                        "legendBox"                 : false,
                        "showLegend"                : false,
                        "showShadow"                : false,
                        "axisTitleScaleFontFactor"  : 0.5,
                        "axisTickFontSize"          : 10,
                        "axisTickScaleFontFactor"   : 0.5,
                        "citation"                  : "",
                        "citationScaleFontFactor"   : 0.7,
                        "xAxisTitle"                : "",
                        "titleFontSize"             : 25,
                        "smpLabelScaleFontFactor"   : 0.85,
                        "titleScaleFontFactor"      : 0.7,
                        "subtitleScaleFontFactor"   : 0.7,
                        "legendScaleFontFactor"     : 0.6,
                        "nodeScaleFontFactor"       : 0.7,
                        "sampleSeparationFactor"    : 0.7,
                        "variableSeparationFactor"  : 0.7,
                        "widthFactor"               : 0.7,
                        "fontAttributeSize"         : 0.2,
                        "varLabelScaleFontFactor"   : 0.85,
                        "showSampleNames"           : ' . $otherOptions["options_display_samples"] . ',
                        "showVariableNames"         : ' . $otherOptions["options_display_genes"] . ',
                        "marginTop"                 : 25,
                        "marginLeft"                : 25,
                        "marginRight"               : 25,
                        "marginBottom"              : 100,
                        "printType"                 : "window"
                    ';
                $output_contents .= '}';
                $output_contents .= "\n\n";

            $output_contents .= ');';
            $output_contents .= "\n\n";


// Overlays

            if($otherOptions["options_overlay_samples"]){
                foreach ($ATTRIBUTES as $i=>$k) {
                    $output_contents .= 'plotObj.showSampleOverlays("' . $k . '");';
                }
            }

            $output_contents .= 'plotObj.sizes = plotObj.sizes.map(function(x) {';
            $output_contents .= '   return Number(x * 0.5).toFixed(1);';
            $output_contents .= '});';
            $output_contents .= "\n\n";

            $output_contents .= 'CanvasXpress.stack["plotSection"]["config"]["sizes"] = plotObj.sizes.map(function(x) {';
            $output_contents .= '   return Number(x * 0.5).toFixed(1);';
            $output_contents .= '});';

            $output_contents .= "\n\n";

        $output_contents .= '});';
    $output_contents .= '</script>';

    $history_data_file = $data_file_dir . '/history.txt';
    $history_contents  = array('key'=>$data_file_unique, '_POST'=>$_POST, 'OUTPUT'=>$output_contents);
    file_put_contents($history_data_file, serialize($history_contents));

    echo $output_contents;

    exit();
}

?>