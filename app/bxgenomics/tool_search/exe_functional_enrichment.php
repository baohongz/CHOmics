<?php

include_once('config.php');



if (isset($_GET['action']) && $_GET['action'] == 'show_chart_go') {

    $COMPARISON_INDEX = 0;

    // 1. Find Comparison Index
    if (isset($_GET['id']) && trim($_GET['id']) != '') {
        $COMPARISON_INDEX = intval(trim($_GET['id']));
    }
    else if (isset($_POST['id']) && trim($_POST['id']) != '') {
        $COMPARISON_INDEX = intval(trim($_POST['id']));
    }
    else if (isset($_GET['comparison_id']) && trim($_GET['comparison_id']) != '') {
        $COMPARISON_INDEX = intval(trim($_GET['comparison_id']));
    }
    else if (isset($_POST['comparison_id']) && trim($_POST['comparison_id']) != '') {
        $COMPARISON_INDEX = intval(trim($_POST['comparison_id']));
    }
    else if (isset($_GET['comparison_index']) && trim($_GET['comparison_index']) != '') {
        $COMPARISON_INDEX = intval(trim($_POST['comparison_index']));
    }
    else if (isset($_POST['comparison_index']) && trim($_POST['comparison_index']) != '') {
        $COMPARISON_INDEX = intval(trim($_POST['comparison_index']));
    }


    $regulation_direcitons = array('Up', 'Down');

    if($COMPARISON_INDEX > 0){

        $sql = "SELECT `Species` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS']}` WHERE `ID` = ?i";
        $species = $BXAF_MODULE_CONN -> get_one($sql, $COMPARISON_INDEX);

        $dir = $BXAF_CONFIG['GO_OUTPUT'][strtoupper( $species )] . 'comp_' . $COMPARISON_INDEX;
        $dir_pre_direction = $dir . "/comp_{$COMPARISON_INDEX}_GO_Analysis_";

        $comp_subdir_prefix = str_replace($BXAF_CONFIG['BXAF_DIR'], '', $dir_pre_direction);

        if (!is_dir($dir)) {
            // Need to run functional enrichment analysis
            echo '<div class="text-danger">Error: No comparison data found for comparison ' . $COMPARISON_INDEX . '.</div>';
            exit();
        }
    }
    else if($COMPARISON_INDEX == 0 && isset($_POST['analysis_id']) && trim($_POST['analysis_id']) != ''){

        $analysis_id = 0;
        $analysis_id_encrypted = '';
        if (isset($_POST['analysis_id']) && intval($_POST['analysis_id']) > 0) {
          $analysis_id = intval($_POST['analysis_id']);
          $analysis_id_encrypted = $analysis_id . '_' . bxaf_encrypt($analysis_id, $BXAF_CONFIG['BXAF_KEY']);
        }
        else if (isset($_POST['analysis']) && trim($_POST['analysis']) != '') {
          $analysis_id_encrypted = trim($_POST['analysis']);
          $analysis_id = intval( array_shift(explode('_', $analysis_id_encrypted)) );
        }

        $sql = "SELECT `Comparisons` FROM `" . $BXAF_CONFIG['BXGENOMICS_DB_TABLES']['TBL_BXGENOMICS_ANALYSIS'] . "` WHERE `ID` = $analysis_id";
        $Comparisons = $BXAF_MODULE_CONN -> get_one($sql);
        $all_comparisons = unserialize($Comparisons);
        if(! is_array($all_comparisons)) $all_comparisons = array();

        $current_comparison = '';
        if (isset($_GET['comp']) && trim($_GET['comp']) != '' && in_array($_GET['comp'], $all_comparisons)) {
            $current_comparison = $_GET['comp'];
        }
        else $current_comparison = current($all_comparisons);


        $dir = $BXAF_CONFIG['ANALYSIS_DIR'] . $analysis_id_encrypted . "/alignment/DEG/$current_comparison/Downstream";
        $dir_pre_direction = $dir . "/GO_Analysis_";

        $comp_subdir_prefix = str_replace($BXAF_CONFIG['BXAF_DIR'], '', $dir_pre_direction);

    }
    else if($COMPARISON_INDEX == 0 && isset($_POST['enrichment_id']) && trim($_POST['enrichment_id']) != ''){

        $regulation_direcitons = array('');

        $analysis_id = trim($_POST['enrichment_id']);
        if($analysis_id == 0){
            $dir = $BXAF_CONFIG['BXAF_APP_DIR'] . "bxgenomics/tool_functional_enrichment/example";
        }
        else {
            $dir = $BXAF_CONFIG['USER_FILES']['TOOL_FUNCTIONAL_ENRICHMENT'] . "analysis_results/{$analysis_id}";
        }

        $dir_pre_direction = $dir . "/results";

        $comp_subdir_prefix = str_replace($BXAF_CONFIG['BXAF_DIR'], '', $dir_pre_direction);

    }
    else {
        echo 'Error: No results found.';
        exit();
    }


    $CHART_ARRAY = array(
        'Biological Process'      => 'biological_process',
        'Cellular Component'      => 'cellular_component',
        'Molecular Function'      => 'molecular_function',
        'KEGG'                    => 'kegg',
        'Molecular Signature'     => 'msigdb',
        'Interpro Protein Domain' => 'interpro',
        'Wiki Pathway'            => 'wikipathways',
        'Reactome'                => 'reactome'
    );



    foreach ($regulation_direcitons as $direction) {

        echo '<div class="row w-100"><div class="col-md-3 pt-4"><ul class="nav nav-tab nav-stacked list-group">';
        echo '
          <li class="enrichment_tab_left list-group-item list-group-item-info text-center lead">
            ' . $direction . ' Regulated Genes
          </li>';

        foreach ($CHART_ARRAY as $chart_name => $chart_file_name) {
          echo '<li class="enrichment_tab_left enrichment_tab_left_' . $direction . ' list-group-item ' . ($chart_file_name == 'biological_process' ? 'list-group-item-warning' : '') . '"><a class="w-100" data-toggle="tab" href="#' . $chart_file_name . '_div_' . $direction . '" role="tab">' . $chart_name . '</a></li>';
        }

        echo '<li class="enrichment_tab_left list-group-item">
                <a class="text-danger" href="' . $BXAF_CONFIG['BXAF_APP_URL'] . 'bxgenomics/tool_search/report_enrichment.php?id=' . $COMPARISON_INDEX . '&comp_subdir=' . urlencode($comp_subdir_prefix . $direction . '/') . '">
                  <i class="fas fa-angle-double-right" aria-hidden="true"></i>
                  Enrichment Report
                </a>
              </li>';


        echo '</ul></div><div class="tab-content col-md-9 p-x-0" style="min-width:750px;">';


        // Each chart div
        foreach ($CHART_ARRAY as $chart_name => $chart_file_name) {

            $file = $dir_pre_direction . "{$direction}/" . $chart_file_name . ".txt";
            // echo $file; continue;

            if(! file_exists($file)) continue;

            $myfile = fopen($file, "r") or die("Unable to open file!");

            // Skip header row
            fgets($myfile);

            $CONTENT_ARRAY = array();
            $rows_limit = 10;
            while(!feof($myfile)) {
                $row_content = fgetcsv($myfile, 0, "\t");
                $name = $row_content[1];
                if(count($row_content) > 10) $CONTENT_ARRAY[ $name ] = $row_content;
                if(count($CONTENT_ARRAY) >= $rows_limit) break;
            }
            fclose($myfile);

            $rows_limit = count($CONTENT_ARRAY);


            // Prepare for drawing chart
            $CONTENT_LOGP_ARRAY = array();
            $CONTENT_NAME_ARRAY = array();
            $CONTENT_GENE_NUMBER_ARRAY = array();
            $CONTENT_HOVER_TEXT_ARRAY = array();
            $CONTENT_ANNOTATION_TEXT_ARRAY = array();
            $max_name_length = 0;

            foreach ($CONTENT_ARRAY as $key => $value) {
                $CONTENT_LOGP_ARRAY[] = $value[3];
                $CONTENT_NAME_ARRAY[] = $value[1];
                $max_name_length = max($max_name_length, strlen($value[1]));
                $CONTENT_GENE_NUMBER_ARRAY[] = count(explode(",", $value[10]));
                $CONTENT_HOVER_TEXT_ARRAY[] = '<b>' . $value[1] . '</b><br />Number of Genes: ' . count(explode(",", $value[10]));
                $CONTENT_ANNOTATION_TEXT_ARRAY[] = $value[3] == 0 ? "" : ( 'log(p):' . number_format($value[3], 2) );
            }

            $CONTENT_LOGP_ARRAY = array_reverse($CONTENT_LOGP_ARRAY);
            $CONTENT_NAME_ARRAY = array_reverse($CONTENT_NAME_ARRAY);
            $CONTENT_GENE_NUMBER_ARRAY = array_reverse($CONTENT_GENE_NUMBER_ARRAY);
            $CONTENT_HOVER_TEXT_ARRAY = array_reverse($CONTENT_HOVER_TEXT_ARRAY);
            $CONTENT_ANNOTATION_TEXT_ARRAY = array_reverse($CONTENT_ANNOTATION_TEXT_ARRAY);



          // Output
          echo '<div id="' . $chart_file_name . '_div_' . $direction . '" class="tab-pane fade' . ($chart_name == 'Biological Process' ? ' active show' : '') . '" aria-expanded="' . ($chart_name == 'Biological Process' ? 'true' : 'false') . '">';

          echo "<div class='mt-4'><a href='javascript:void(0);' id='download_SVG_{$chart_file_name}_{$direction}'><i class='fas fa-angle-double-right'></i> Download SVG File</a></div>" . '<div id="myDiv_' . $chart_file_name . '_' . $direction . '" style=" height: 420px; width: 1000px;"></div></div>';

          echo "
          <script>
            $(document).ready(function() {

                $(document).on('click', '.enrichment_tab_left_', function(){
                    $('.enrichment_tab_left_').removeClass('list-group-item-warning');
                    $(this).addClass('list-group-item-warning');
                });
                $(document).on('click', '.enrichment_tab_left_Up', function(){
                    $('.enrichment_tab_left_Up').removeClass('list-group-item-warning');
                    $(this).addClass('list-group-item-warning');
                });
                $(document).on('click', '.enrichment_tab_left_Down', function(){
                    $('.enrichment_tab_left_Down').removeClass('list-group-item-warning');
                    $(this).addClass('list-group-item-warning');
                });

              var xData = [" . implode(", ", $CONTENT_GENE_NUMBER_ARRAY) . "];
              var yData = ['" . implode("', '", $CONTENT_NAME_ARRAY) . "'];
              var annotationText = ['" . implode("', '", $CONTENT_ANNOTATION_TEXT_ARRAY) . "'];

              var data = [{
                type: 'bar',
                x: [" . implode(", ", $CONTENT_GENE_NUMBER_ARRAY) . "],
                y: ['" . implode("', '", $CONTENT_NAME_ARRAY) . "'],
                orientation: 'h'
              }];


              var layout = {
            		margin: {
            			l: 500
            		},
            		title: '{$chart_name}',
            		showlegend: false,
            		xaxis: {
                        title: 'Number of Genes',
                        showticklabels: true,
            		},
                    hovermode: 'closest',
                    annotations: []
            	};

              for (var i = 0; i < " . $rows_limit . "; i++) {
                var result = {
                  xref: 'x1',
                  yref: 'y1',
                  x: xData[i] + Math.max.apply(null, xData) / 6,
                  y: yData[i],
                  text: annotationText[i],
                  font: {
                    family: 'Arial',
                    size: 12,
                    color: 'rgb(50, 171, 96)'
                  },
                  showarrow: false,
                };
                layout.annotations.push(result);
              }
              ";

        echo "
            Plotly.newPlot('myDiv_" . $chart_file_name . "_" . $direction . "', data, layout).then(function(gd) {
                window.requestAnimationFrame(function() {
                  window.requestAnimationFrame(function() {
                    $('.loader').remove();
                  });
                });
        ";

        echo '
                $(document).on("click", "#download_SVG_' . $chart_file_name . '_' . $direction . '", function(){
                    Plotly.downloadImage(gd, {
                            filename: "' . $chart_file_name . '_' . $direction . '-Regulated",
                            format: "svg",
                            height: layout.height,
                            width: layout.width
                    })
                    .then(function(filename){

                    });
                });
            });
        ';

        echo "var graphDiv" . $chart_file_name . $direction . " = document.getElementById('myDiv_" . $chart_file_name . "_" . $direction . "');

              clickEvent = function(data){
                var pathway = data.points[0].y;

                $.ajax({
                  type: 'POST',
                  url: '" . $BXAF_CONFIG['BXAF_APP_URL'] . "bxgenomics/tool_search/exe_functional_enrichment.php?action=get_gene_list',
                  data: {comparison: '$COMPARISON_INDEX', enrichment_id: '{$_POST['enrichment_id']}', pathway: pathway, chart_name: '$chart_file_name', direction: '$direction'},

                  success: function(response) {
                    if(response != ''){ \n\n";

                    if ($chart_file_name == 'wikipathways') {
                        echo "bootbox.alert(\"<h4 class='mb-3'>\" + pathway + \":</h4><ul>";
                            if($COMPARISON_INDEX > 0) echo "<li><a target='_blank' href='" . $BXAF_CONFIG['BXAF_APP_URL'] . "bxgenomics/tool_volcano_plot/index.php?comparison_id={$COMPARISON_INDEX}&gene_time=\" + response + \"'> Volcano Plot</a> </li>";
                            if($COMPARISON_INDEX > 0) echo "<li><a target='_blank' href='" . $BXAF_CONFIG['BXAF_APP_URL'] . "bxgenomics/tool_pathway/index.php?pathway=\" + encodeURIComponent(pathway) + \"&comparison_id={$COMPARISON_INDEX}'> WikiPathway</a> </li>";
                            echo "<li><a target='_blank' href='" . $BXAF_CONFIG['BXAF_APP_URL'] . "bxgenomics/tool_save_lists/new_list.php?Category=Gene&type=Gene&time=\" + response + \"'> Save Gene List</a> </li>";
                        echo "</ul>\");";
                    }
                    else if ($chart_file_name == 'reactome') {
                        echo "bootbox.alert(\"<h4 class='mb-3'>\" + pathway + \":</h4><ul>";
                            if($COMPARISON_INDEX > 0) echo "<li><a target='_blank' href='" . $BXAF_CONFIG['BXAF_APP_URL'] . "bxgenomics/tool_volcano_plot/index.php?comparison_id={$COMPARISON_INDEX}&gene_time=\" + response + \"'> Volcano Plot</a> </li>";
                            if($COMPARISON_INDEX > 0) echo "<li><a target='_blank' href='" . $BXAF_CONFIG['BXAF_APP_URL'] . "bxgenomics/tool_pathway/reactome.php?pathway=\" + encodeURIComponent(pathway) + \"&comparison_id={$COMPARISON_INDEX}'> Reactome Pathway</a> </li>";
                            echo "<li><a target='_blank' href='" . $BXAF_CONFIG['BXAF_APP_URL'] . "bxgenomics/tool_save_lists/new_list.php?Category=Gene&type=Gene&time=\" + response + \"'> Save Gene List</a> </li>";
                        echo "</ul>\");";
                    }
                    else if ($chart_file_name == 'kegg') {
                        echo "bootbox.alert(\"<h4 class='mb-3'>\" + pathway + \":</h4><ul>";
                            if($COMPARISON_INDEX > 0) echo "<li><a target='_blank' href='" . $BXAF_CONFIG['BXAF_APP_URL'] . "bxgenomics/tool_volcano_plot/index.php?comparison_id={$COMPARISON_INDEX}&gene_time=\" + response + \"'> Volcano Plot</a> </li>";
                            if($COMPARISON_INDEX > 0) echo "<li><a target='_blank' href='" . $BXAF_CONFIG['BXAF_APP_URL'] . "bxgenomics/tool_pathway/kegg.php?pathway=\" + encodeURIComponent(pathway) + \"&comparison_id={$COMPARISON_INDEX}'> KEGG Pathway</a> </li>";
                            echo "<li><a target='_blank' href='" . $BXAF_CONFIG['BXAF_APP_URL'] . "bxgenomics/tool_save_lists/new_list.php?Category=Gene&type=Gene&time=\" + response + \"'> Save Gene List</a> </li>";
                        echo "</ul>\");";
                    }
                    else {
                        echo "bootbox.alert(\"<h4 class='mb-3'>\" + pathway + \":</h4><ul>";
                            if($COMPARISON_INDEX > 0) echo "<li><a target='_blank' href='" . $BXAF_CONFIG['BXAF_APP_URL'] . "bxgenomics/tool_volcano_plot/index.php?comparison_id={$COMPARISON_INDEX}&gene_time=\" + response + \"'> Volcano Plot</a> </li>";
                            echo "<li><a target='_blank' href='" . $BXAF_CONFIG['BXAF_APP_URL'] . "bxgenomics/tool_save_lists/new_list.php?Category=Gene&type=Gene&time=\" + response + \"'> Save Gene List</a> </li>";
                        echo "</ul>\");";
                    }

                    echo "
                    }
                  }
                });
              }
              graphDiv" . $chart_file_name . $direction . ".on('plotly_click', clickEvent);

            });
          </script>";

        }
        echo '</div></div><hr />';
    }


    exit();
}




// ----------------------------------------------------------------------------------
// Go To Volcano Plot
if (isset($_GET['action']) && $_GET['action'] == 'get_gene_list') {

    $COMPARISON_INDEX    = intval($_POST['comparison']);

    $term_name = $_POST['pathway'];
    $direction = $_POST['direction'];
    $file_name = $_POST['chart_name'] . '.txt';
    $species = $_SESSION['SPECIES_DEFAULT'];

    if($COMPARISON_INDEX > 0){

        $sql = "SELECT `Species` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS']}` WHERE `ID` = ?i";
        $species = $BXAF_MODULE_CONN -> get_one($sql, $COMPARISON_INDEX);

        $dir = $BXAF_CONFIG['GO_OUTPUT'][strtoupper($species)] . 'comp_' . $COMPARISON_INDEX . '/comp_' . $COMPARISON_INDEX . '_GO_Analysis_' . $direction;
    }
    else if($COMPARISON_INDEX == 0 && isset($_POST['analysis_id']) && intval($_POST['analysis_id']) != ''){

        $analysis_id = 0;
        $analysis_id_encrypted = '';
        if (isset($_POST['analysis_id']) && intval($_POST['analysis_id']) > 0) {
          $analysis_id = intval($_POST['analysis_id']);
          $analysis_id_encrypted = $analysis_id . '_' . bxaf_encrypt($analysis_id, $BXAF_CONFIG['BXAF_KEY']);
        }
        else if (isset($_POST['analysis']) && trim($_POST['analysis']) != '') {
          $analysis_id_encrypted = trim($_POST['analysis']);
          $analysis_id = intval( array_shift(explode('_', $analysis_id_encrypted)) );
        }

        $sql = "SELECT * FROM `" . $BXAF_CONFIG['BXGENOMICS_DB_TABLES']['TBL_BXGENOMICS_ANALYSIS'] . "` WHERE `ID` = $analysis_id";
        $analysis_info = $BXAF_MODULE_CONN -> get_row($sql);

        $species = array_shift(explode(' ', $analysis_info['Species']));

        $all_comparisons = unserialize($analysis_info['Comparisons']);

        $current_comparison = '';
        if (isset($_GET['comp']) && trim($_GET['comp']) != '') {
            $current_comparison = $_GET['comp'];
        }
        if(! in_array($current_comparison, $all_comparisons)) $current_comparison = current($all_comparisons);

        $dir = $BXAF_CONFIG['ANALYSIS_DIR'] . $analysis_id_encrypted . "/alignment/DEG/$current_comparison/Downstream/GO_Analysis_" . $direction;

    }
    else if($COMPARISON_INDEX == 0 && isset($_POST['enrichment_id']) && trim($_POST['enrichment_id']) != ''){

        $regulation_direcitons = array('');

        $analysis_id = trim($_POST['enrichment_id']);
        if($analysis_id == 0){
            $dir = $BXAF_CONFIG['BXAF_APP_DIR'] . "bxgenomics/tool_functional_enrichment/example";
        }
        else {
            $dir = $BXAF_CONFIG['USER_FILES']['TOOL_FUNCTIONAL_ENRICHMENT'] . "analysis_results/{$analysis_id}";
        }

        $species = file_get_contents("$dir/species.txt");

        $dir = "$dir/results";
    }
    else {
        exit();
    }


    if (!file_exists($dir . '/' . $file_name)) {
        exit();
    }



    $myfile = fopen($dir . '/' . $file_name, "r") or die("Unable to open file!");

    $head = fgetcsv($myfile, 0, "\t");
    $head_flip = array_flip($head);
    $genes_list = array();
    while(!feof($myfile)) {
        $row_content = fgetcsv($myfile, 0, "\t");
        if ($row_content[ $head_flip['Term'] ] == $term_name) {
            $genes_list = $row_content[ $head_flip['Gene Symbols'] ];
            break;
        }
    }
    fclose($myfile);

    $genes_id_list = category_text_to_idnames($genes_list, 'name', 'gene', $species);

    if (! is_array($genes_id_list) || count($genes_id_list) <= 0) {
        exit();
    }

    $uniqueID = md5(microtime(true));
    $_SESSION['SAVED_LIST'][$uniqueID] = array_keys($genes_id_list);

    echo $uniqueID;

    exit();
}


?>