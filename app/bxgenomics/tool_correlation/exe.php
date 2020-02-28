<?php
include_once("config.php");

if (isset($_GET['action']) && $_GET['action'] == 'get_correlation') {

    header('Content-Type: application/json');
    $OUTPUT['type']  = 'Error';

    $species = $_SESSION['SPECIES_DEFAULT'];

    if ($_POST['comparison'] == 2){
    	$_POST['direction']	= 1;
    	$_POST['cutoff']	= 0;
    	$_POST['limit'] 	= 0;
    }

    $NUMBER_LIMIT = 1000000;


    $GENE_NAMES = category_text_to_idnames($_POST['Gene_List'], 'name', 'gene', $species);

    if (! is_array($GENE_NAMES) || count($GENE_NAMES) <= 0) {
        $OUTPUT['detail'] = '<h3 class="text-danger my-3">Error</h3><div class="my-3">Please enter at least one gene name to continue.</div>';
        echo json_encode($OUTPUT);
        exit();
    }

    $SINGLE_GENE = (count($GENE_NAMES) == 1) ? true : false;
    if($SINGLE_GENE){
        $SINGLE_GENE_ID = array_shift( array_keys($GENE_NAMES) );
    }


    $SAMPLE_NAMES = category_text_to_idnames($_POST['Sample_List'], 'name', 'sample', $species);

    if (!is_array($SAMPLE_NAMES) || count($SAMPLE_NAMES) < 3) {
        $OUTPUT['detail'] = '<h3 class="text-danger my-3">Error</h3><div class="my-3">Please enter <span class="text-danger">three or more sample names</span> to continue.</div>' ;
        echo json_encode($OUTPUT);
        exit();
    }
    if ($SINGLE_GENE && count($SAMPLE_NAMES) > 100) {
        $OUTPUT['detail'] = '<h3 class="text-danger my-3">Error</h3><div class="my-3">To reduce memory usage and improve the performance, you are only allowed to enter up to 100 samples.</div>';
        echo json_encode($OUTPUT);
        exit();
    }
    $sample_ids = array_keys($SAMPLE_NAMES);

    $sql = "SELECT DISTINCT `Platform_Type` FROM ?n WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND ?n IN (?a)";
    $platforms = $BXAF_MODULE_CONN -> get_col($sql, $BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES'], 'Name', $SAMPLE_NAMES);

    if(is_array($platforms) && count($platforms) > 1){
        $OUTPUT['detail'] = '<h3 class="text-danger my-3">Error</h3><div class="my-3">Your samples are from both Array and NGS. Please enter samples from one platform only.</div>';
        echo json_encode($OUTPUT);
        exit();
    }
    $platform = array_shift($platforms);

    if (! $SINGLE_GENE && $_POST['comparison'] == 1) {
        $OUTPUT['detail'] = '<h3 class="text-danger my-3">Error</h3><div class="my-3">Please <span class="text-danger">enter a single gene</span> to calculate its correlations against all available genes in the database.</div>';
        echo json_encode($OUTPUT);
        exit();
    }
    if ($SINGLE_GENE && $_POST['comparison'] == 2) {
        $OUTPUT['detail'] = '<h3 class="text-danger my-3">Error</h3><div class="my-3">Please <span class="text-danger">enter two or more genes</span> to calculate the correlations among these genes.</div>';
        echo json_encode($OUTPUT);
        exit();
    }


    $tabix_file    = ($platform == 'Array') ? 'GeneLevelExpression' : 'GeneFPKM';
    $tabix_genes   = $SINGLE_GENE ? array() : array_keys($GENE_NAMES);
    // $tabix_public = tabix_search_records_public(  $tabix_genes, $sample_ids, $tabix_file );
    // $private_data = tabix_search_records_private( $tabix_genes, $sample_ids, $tabix_file);
    // $tabix_data   = array_merge($tabix_public, $private_data);

    ini_set('memory_limit','8G');
    $tabix_data = tabix_search_bxgenomics($tabix_genes, $sample_ids, $tabix_file );


    if (!is_array($tabix_data) || count($tabix_data) <= 0) {
        $OUTPUT['detail'] = '<h3 class="text-danger my-3">Error</h3><div class="my-3">The search result does not contain any data. Please refine your options and try again.</div>';
        echo json_encode($OUTPUT);
        exit();
    }
    // foreach ($tabix_data as $i=>$row){
    //     if($row['Value'] == 0) unset($tabix_data[$i]);
    // }
    // $OUTPUT['detail'] = count($tabix_data) . "<pre>" . print_r(array_slice($tabix_data, 0, 5, true), true) . "</pre>";
    // echo json_encode($OUTPUT);
    // exit();

    $added_value = floatval($_POST['transform_value']);
    $tabix_gene_sample = array();
    foreach ($tabix_data as $i=>$row){
        $sample_id = $row['SampleIndex'];
        $gene_id   = $row['GeneIndex'];

        $value = floatval($row['Value']);
        if($row['Value'] == 0) continue;

        if (isset($_POST['transform']) && $_POST['transform'] == 1) $value = log($value + $added_value, 2);

        if (! array_key_exists($gene_id, $tabix_gene_sample)) {
            $tabix_gene_sample[$gene_id] = array($sample_id => $value);
        }
        else {
            $tabix_gene_sample[$gene_id][$sample_id] = $value;
        }
    }
    // $OUTPUT['detail'] = count($tabix_gene_sample) . "<pre>" . print_r(array_slice($tabix_gene_sample, 0, 10, true), true) . "</pre>";
    // echo json_encode($OUTPUT);
    // exit();

    if(count($tabix_gene_sample) > $NUMBER_LIMIT){
        $rand_keys = array_rand($tabix_gene_sample, $NUMBER_LIMIT);
        $tabix_gene_sample_new = array();
        foreach($rand_keys as $key) $tabix_gene_sample_new[$key] = $tabix_gene_sample[$key];
        if ($SINGLE_GENE) $tabix_gene_sample_new[$SINGLE_GENE_ID] = $tabix_gene_sample[$SINGLE_GENE_ID];
        $tabix_gene_sample = $tabix_gene_sample_new;
    }
    // $OUTPUT['detail'] = count($tabix_gene_sample) . "<pre>" . print_r(array_slice($tabix_gene_sample, 0, 5, true), true) . "</pre>";
    // echo json_encode($OUTPUT);
    // exit();


    $data_matrix  = array();
    $method = ($_POST['method'] == 'Spearman') ? 1 : 0;

    $row_number = 0;

    // If only one gene selected
    if ($SINGLE_GENE) {

        $index_1 = $SINGLE_GENE_ID;
        $data_1  = $tabix_gene_sample[$index_1];

        $all_corr = array();

        foreach($tabix_gene_sample as $index_2=>$data_2){

            if($index_1 == $index_2 || count($data_1) != count($data_2)) continue;

            $corr = getCorrelationCoefficient($data_1, $data_2, $method);
            if(! $corr) continue;

            if($_POST['direction'] == '2'){ // Positive
                if($corr < 0) continue;

            }
            if($_POST['direction'] == '3'){ // Negative
                if($corr > 0) continue;

            }

            if($_POST['cutoff'] > 0){
                if($_POST['direction'] == '2'){ // Positive
                    if($corr < $_POST['cutoff']) continue;
                }
                else if($_POST['direction'] == '3'){ // Negative
                    if($corr > -1 * $_POST['cutoff']) continue;
                }
                else { // Both
                    if(abs($corr) < $_POST['cutoff']) continue;
                }
            }

            $all_corr[$index_2] = $corr;
        }

        // Sort all r square first
        arsort($all_corr);


        $limit = intval($_POST['limit']);
        if($limit > 0){ // Top matched genes

            $limit = ($limit > count($all_corr)) ? count($all_corr) : $limit;

            if($_POST['direction'] == '2'){ // Positive
                $all_corr = array_slice($all_corr, 0, $limit, TRUE);
            }
            else if($_POST['direction'] == '3'){ // Negative
                $all_corr = array_slice($all_corr, count($all_corr) - $limit, $limit, TRUE);
            }
            else { // Both
                if( (2 * $limit) < count($all_corr) ){
                    $all_corr = array_slice($all_corr, 0, $limit, TRUE) + array_slice($all_corr, count($all_corr) - $limit, $limit, TRUE) ;
                }
            }
        }

        foreach ($all_corr as $index_2 => $corr) {

            $data_matrix[$index_1][$index_2] = array(
                'data_1'    => $data_1,
                'data_2'    => $tabix_gene_sample[$index_2],
                'pt_number' => count($data_1),
                'corr'      => sprintf("%.4f", $corr),
                'r_square'  => sprintf("%.4f", $corr * $corr)
            );
            $row_number++;
        }

    }

    // If multiple genes selected
    else {

        foreach($tabix_gene_sample as $index_1=>$data_1){

            foreach($tabix_gene_sample as $index_2=>$data_2){

                if($index_1 == $index_2 || array_key_exists($index_2, $data_matrix)) continue;

                $corr = getCorrelationCoefficient($data_1, $data_2, $method);
                if(! $corr) continue;

                $data_matrix[$index_1][$index_2] = array(
                    'data_1'    => $data_1,
                    'data_2'    => $data_2,
                    'pt_number' => count($data_1),
                    'corr'      => sprintf("%.4f", $corr),
                    'r_square'  => sprintf("%.4f", $corr * $corr)
                );

                $row_number++;
            }
        }

    }

    $GENE_NAMES = category_list_to_idnames(array_keys($tabix_gene_sample), 'id', 'gene', $species);



    $TIME = time();

    // Data Table
    $OUTPUT_TABLE = "";
	if (isset($data_matrix) && count($data_matrix) > 0) {

	    $OUTPUT_TABLE .= '<div class="w-100 my-5">
    	    <table class="table table-bordered table-striped" id="MyTable">
    	      <thead>
    	        <tr class="table-success">
    	          <th>Source Gene</th>
    	          <th>Matched Gene</th>
    	          <th>Correlation Coef</th>
    	          <th>R Square</th>
    	          <th># of Data Points</th>
                  <th>Actions</th>
    	        </tr>
    	      </thead>
    	      <tbody>';

        $n = 0;
        foreach ($data_matrix as $index_1 => $values) {
            foreach ($values as $index_2 => $value) {

                if($value['corr'] == 'NaN') continue;

                $OUTPUT_TABLE .= "<tr>";
                    $OUTPUT_TABLE .= "<td>" . '<a class="mx-2" target="_blank" title="Review Gene Details" href="../tool_search/view.php?type=gene&id=' . $index_1 . '"> ' . $GENE_NAMES[$index_1] . ' </a>' . "</td>";
                    $OUTPUT_TABLE .= "<td>" . '<a class="mx-2" target="_blank" title="Review Gene Details" href="../tool_search/view.php?type=gene&id=' . $index_2 . '"> ' . $GENE_NAMES[$index_2] . ' </a>' . "</td>";
                    $OUTPUT_TABLE .= "<td>" . $value['corr'] . "</td>";
                    $OUTPUT_TABLE .= "<td>" . sprintf("%.4f", $value['corr'] * $value['corr']) . "</td>";
                    $OUTPUT_TABLE .= "<td>" . $value['pt_number'] . "</td>";
                    $OUTPUT_TABLE .= "<td>";
                        $OUTPUT_TABLE .= '<a href="javascript:void(0);" title="Correlation Plot" class="btn_draw_regression_line" gene_1="' . $index_1 . '" gene_2="' . $index_2 . '" time="' . $TIME . '"> <i class="fas fa-chart-line"></i> Plot </a>';
                    $OUTPUT_TABLE .= "</td>";
                $OUTPUT_TABLE .= "</tr>";

                $n++;
                if($n >= $NUMBER_LIMIT){ break; break; }
            }
        }

        $OUTPUT_TABLE .= '
	      </tbody>
	    </table>
		</div>
		';
	}


    //------------------------------------------------------------------------------------
    // Output
    //------------------------------------------------------------------------------------
    $OUTPUT['type']           = 'Success';

    $OUTPUT['data']           = $data_matrix;

    $OUTPUT['row_number']     = $row_number;

    $OUTPUT['genes']          = $GENE_NAMES;
    $OUTPUT['samples']        = $SAMPLE_NAMES;

    $OUTPUT['time']           = $TIME;

    $OUTPUT['table']          = $OUTPUT_TABLE;


    $dir = $BXAF_CONFIG['USER_FILES']['TOOL_CACHE'] . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'];
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    file_put_contents("$dir/TOOL_CORR_$TIME", serialize($OUTPUT));

    echo json_encode($OUTPUT);

    exit();
}









if (isset($_GET['action']) && $_GET['action'] == 'generate_line_chart') {

    $gene_index_1 = intval($_POST['gene_1']);
    $gene_index_2 = intval($_POST['gene_2']);
    $time         = $_POST['time'];

    $filename = $BXAF_CONFIG['USER_FILES']['TOOL_CACHE'] . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'] . "/TOOL_CORR_$time";
    $saved_data = unserialize(file_get_contents($filename));

    $gene_1_name  = $saved_data['genes'][$gene_index_1];
    $gene_2_name  = $saved_data['genes'][$gene_index_2];

    $samples      = $saved_data['samples'];

    $data_detail  = $saved_data['data'][$gene_index_1][$gene_index_2];

    $data_1 = $data_detail['data_1'];
    $data_2 = $data_detail['data_2'];

    cleanTwoNumericArrays($data_1, $data_2);

    if($_POST['method'] == 'Spearman'){
        $data_1 = getRankArray($data_1);
        $data_2 = getRankArray($data_2);
    }

    $corr         = $data_detail['corr'];
    $regression   = getLinearRegression($data_1, $data_2);


    // Data
    $data_y = array(
        'smps' => array($gene_1_name, $gene_2_name),
        'vars' => array(),
        'data' => array()
    );

    foreach ($data_1 as $key => $value) {
        $data_y['vars'][] = $samples[$key];
        $data_y['data'][] = array($value, $data_2[$key]);
    }

    $data_settings = array(
        'backgroundType'             => 'window',
        'backgroundWindow'           => 'rgb(238,238,238)',
        'colors'                     => array("rgba(64,64,64,0.5)"),
        'decorationsBackgroundColor' => 'rgb(238,238,238)',
        'decorationsBoxColor'        => 'rgb(0,0,0)',
        'decorationsPosition'        => 'bottomRight',
        'graphType'                  => 'Scatter2D',
        'legendInside'               => true,
        'plotBox'                    => false,
        'showDecorations'            => false,
        'xAxis'                      => array('AJAP1'),
        'xAxisTickColor'             => 'rgb(255,255,255)',
        'yAxis'                      => array('CYP4A11'),
        'yAxisTickColor'             => 'rgb(255,255,255)'
    );


    // Additional Content
    $CONTENT = "
    <div class='my-3 w-100'>
        <span class=' mx-3'>Correlation: " . sprintf("%.4f", $corr) . " </span>
        <span class=' mx-3'>R_Square: " . sprintf("%.4f", $corr * $corr) . "</span>
        <span class=' mx-3'>Linear Regression: y = " . sprintf("%.4f", $regression['Slope']) . "x + " . sprintf("%.4f", $regression['Constant']) . " </span>
        <span class=' mx-3'># of Data Point: " . $data_detail['pt_number'] . "</span>
    </div>
    ";


    //------------------------------------------------------------------------------------
    // Output
    //------------------------------------------------------------------------------------
    $OUTPUT['title'] = $_POST['method'] . ' Correlation: ' . $gene_1_name . ' .vs. ' . $gene_2_name;
    $OUTPUT['plot_data'] = array(
        'y'        => $data_y,
        'settings' => $data_settings
    );
    $OUTPUT['content'] = preg_replace("/\r|\n/", "", $CONTENT);

    header('Content-Type: application/json');
    echo json_encode($OUTPUT);

    exit();
}

?>