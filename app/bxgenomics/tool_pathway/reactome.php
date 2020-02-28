<?php
include_once(__DIR__ . "/config.php");

if(! function_exists('get_stat_scale_color2')){
    function get_stat_scale_color2($value, $type='Log2FoldChange') {
      if ($type == 'Log2FoldChange') {
        if ($value >= 1) {
          return '#FF0000';
        } else if ($value > 0) {
          return '#FF8989';
        } else if ($value == 0) {
          return '#E5E5E5';
        } else if ($value > -1) {
          return '#7070FB';
        } else {
          return '#0000FF';
        }
      }
      else if ($type == 'AdjustedPValue') {
        if ($value > 0.05) {
          return '#9CA4B3';
        } else if ($value <= 0.01) {
          return '#015402';
        } else {
          return '#5AC72C';
        }
      }
      else if ($type == 'PValue') {
        if ($value >= 0.01) {
          return '#9CA4B3';
        } else {
          return '#5AC72C';
        }
      }
      return '';
    }
}


// Upload File
if (isset($_GET['action']) && $_GET['action'] == 'upload_file') {

    header('Content-Type: application/json');
    $OUTPUT = array();
    $OUTPUT['type'] = 'Error';
    $OUTPUT['detail'] = '';


    $pathway_selected = '';
    $pathway_selected_name = '';
    $pathway_genes = array();

    if(isset($_POST['pathway']) && $_POST['pathway'] != ''){
        $pathway_selected = $_POST['pathway'];

        $pathway_gmt = __DIR__ . "/reactome/ReactomePathways.gmt";
        if (($handle = fopen($pathway_gmt, "r")) !== FALSE) {
            while (($row = fgetcsv($handle, 0, "\t")) !== FALSE) {
                if(! is_array($row) || count($row) <= 2) continue;

                $pathway_name = array_shift($row);
                $pathway = array_shift($row);

                if($pathway == $pathway_selected){
                    $pathway_selected_name = $pathway_name;
                    $pathway_genes = $row;
                    break;
                }
            }
            fclose($handle);
        }
    }

    $pathway_geneindex_genename = array();
    $pathway_name_geneindex = array();
    if (count($pathway_genes) > 0){

        $sql = "SELECT `GeneIndex`, `GeneName`  FROM `TBL_BXGENOMICS_GENES_INDEX` WHERE `Species` = '" . $_SESSION['SPECIES_DEFAULT'] . "' AND `Name` IN (?a)";
        $pathway_geneindex_genename = $BXAF_MODULE_CONN -> get_assoc('GeneIndex', $sql, $pathway_genes );

        $sql = "SELECT `Name`, `GeneIndex`  FROM `TBL_BXGENOMICS_GENES_INDEX` WHERE `Species` = '" . $_SESSION['SPECIES_DEFAULT'] . "' AND `GeneIndex` IN (?a)";
        $pathway_name_geneindex = $BXAF_MODULE_CONN -> get_assoc('Name', $sql, array_keys($pathway_geneindex_genename));

    }

    $all_geneindex_genenames = array();
    $sql = "SELECT `GeneIndex`, `GeneName`  FROM `TBL_BXGENOMICS_GENES_INDEX` WHERE `Species` = '" . $_SESSION['SPECIES_DEFAULT'] . "'";
    $all_geneindex_genenames = $BXAF_MODULE_CONN -> get_assoc('GeneIndex', $sql);
    $all_geneindex_genenames_flip = array_flip($all_geneindex_genenames);

    $time = time();

    $CACHE_DIR = $BXAF_CONFIG['CURRENT_SYSTEM_CACHE'] . $time;
    $CACHE_URL = $BXAF_CONFIG['CURRENT_SYSTEM_CACHE_URL'] . $time;

    if(! file_exists($CACHE_DIR)) mkdir($CACHE_DIR, 0755, true);
    if(file_exists("$CACHE_DIR/uploaded.csv")) unlink("$CACHE_DIR/uploaded.csv");

    $file_name = 'comparison.csv';
    if (isset($_FILES["file"]["error"]) && $_FILES["file"]["error"] == UPLOAD_ERR_OK) {
        if (is_uploaded_file($_FILES['file']['tmp_name'])) {
            $file_name = $_FILES['file']['name'];
            move_uploaded_file($_FILES['file']['tmp_name'], "$CACHE_DIR/uploaded.csv");
        }
    }
    else if(isset($_POST['Comparison_List']) && trim($_POST['Comparison_List']) != ''){


        $comparison_indexnames = category_text_to_idnames($_POST['Comparison_List'], 'name', 'comparison', $_SESSION['SPECIES_DEFAULT']);

        $list = preg_split("/[\s,]+/", $_POST['Comparison_List'], NULL, PREG_SPLIT_NO_EMPTY);
        if ( ! is_array($comparison_indexnames) || count($comparison_indexnames) <= 0 ){
			$OUTPUT['detail'] = "<div class='lead text-danger'>Error: No valid comparison is found. Please enter at least one valid comparison ID.</div>";
            echo json_encode($OUTPUT);
			exit();
		}
		else {
			foreach($list as $c){
				if(! in_array($c, $comparison_indexnames)){
					$OUTPUT['detail'] = "<div class='lead text-danger'>Error: Comparison '$c' is not found.</div>";
                    echo json_encode($OUTPUT);
					exit();
				}
			}
		}


        $comparison_indexes = array_keys($comparison_indexnames);
        $comparison_names   = array_values($comparison_indexnames);

        // $tabix_public  = tabix_search_records_public(   array(), $comparison_indexes, 'ComparisonData' );
        // $tabix_private = tabix_search_records_private( array(), $comparison_indexes, 'ComparisonData');
        // $tabix_results = array_merge($tabix_public, $tabix_private);

        ini_set('memory_limit','8G');
		$tabix_results = tabix_search_bxgenomics(  array(), $comparison_indexes, 'ComparisonData');


        $data_types = array('Log2FoldChange', 'PValue', 'AdjustedPValue');


        $handle_uploaded = fopen("$CACHE_DIR/uploaded.csv", "w");
        $header = array();
        $header[] = 'GeneName';
        foreach($comparison_names as $name) {
            foreach($data_types as $type) $header[] = $name . '_' . $type;
        }
        fputcsv($handle_uploaded, $header);

        $data_uploaded = array();
        foreach($tabix_results as $row){

            $g_index = $row['GeneIndex'];
            $g_name  = $all_geneindex_genenames[ $g_index ];
            if(! preg_match("/^[\w\-]+$/", $g_name) ) continue;

            $c_index = $row['ComparisonIndex'];
            $c_name  = $comparison_indexnames[$c_index];

            if(! isset($data_uploaded[$g_index])) $data_uploaded[$g_index] = array();

            $data_uploaded[$g_index]['GeneName'] = $g_name;
            foreach($data_types as $type) $data_uploaded[$g_index][$c_name . '_' . $type] = sprintf("%.4f", $row[ $type ] );

        }

        foreach($data_uploaded as $g_index => $row){

            if(count($row) != count($header)) continue;

            $row_values = array();
            foreach($header as $col){
                $row_values[] = $row[$col];
            }
            fputcsv($handle_uploaded, $row_values);
        }
        fclose($handle_uploaded);
    }


    $OUTPUT['pathway_name'] = $pathway_selected_name;
    $OUTPUT['pathway'] = $pathway_selected;
    $OUTPUT['time'] = $time;

    $OUTPUT['token'] = '';
    $OUTPUT['results'] = array();
    $OUTPUT['table'] = '';

    if(file_exists("$CACHE_DIR/uploaded.csv")){

        // Filter data
        $range_min = intval($_POST['range_min']);
        $range_max = intval($_POST['range_max']);

        $csv_contents = '';
        $csv_filtered = "$CACHE_DIR/filtered.csv";
        if(file_exists($csv_filtered)) unlink($csv_filtered);

        $n = 0;
        if (count($pathway_name_geneindex) > 0 && count($pathway_geneindex_genename) > 0 && ($handle = fopen("$CACHE_DIR/uploaded.csv", "r")) !== FALSE) {

            $handle_filtered = fopen($csv_filtered, "w");

            $header = fgetcsv($handle);
            $header[0] = 'GeneName';
            fputcsv($handle_filtered, $header);

            $csv_contents .= "<table id='table_filtered_results' class='table table-bordered table-hover'><thead><tr class='table-success'>";
            foreach($header as $k=>$v){
                if($k==0) {
                    $csv_contents .= "<th>Gene Name</th><th>Description</th>";
                }
                else $csv_contents .= "<th>$v</th>";
            }
            $csv_contents .= "</tr></thead><tbody>";

            while (($row = fgetcsv($handle)) !== FALSE) {
                if(! is_array($row) || count($row) <= 1) continue;

                $key = array_shift($row);
                if(! array_key_exists($key, $pathway_name_geneindex)){
                    continue;
                }

                $n++;

                $row_filtered = array();
                $row_filtered1 = array();
                if(array_key_exists($key, $pathway_name_geneindex)){
                    $index = $pathway_name_geneindex[$key];
                    $name = $all_geneindex_genenames[$index];
                    $row_filtered[] = $name;
                    $row_filtered1[] = $name;
                }
                else {
                    $row_filtered[] = $key;
                    $row_filtered1[] = $key;
                }

                foreach($row as $k=>$v){
                    if($_POST['data_option'] == 'range' && $v < $range_min) $row_filtered[] = $range_min;
                    else if($_POST['data_option'] == 'range' && $v > $range_max) $row_filtered[] = $range_max;
                    else $row_filtered[] = sprintf("%.4f", $v);

                    $row_filtered1[] = sprintf("%.4f", $v);
                }

                if(count($row_filtered) > 0){
                    fputcsv($handle_filtered, $row_filtered);

                    $csv_contents .= "<tr>";
                    foreach($row_filtered1 as $k=>$v){
                        if($k == 0){
                            $sql = "SELECT `Description`  FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_GENES']}` WHERE `Species` = '" . $_SESSION['SPECIES_DEFAULT'] . "' AND `GeneName` = ?s";
                            $desc = $BXAF_MODULE_CONN -> get_one($sql, $v);

                            $csv_contents .= "<td><a href='../tool_search/view.php?type=gene&id=" . $all_geneindex_genenames_flip[$v] . "' target='_blank'>" . $v . "</a></td><td>" . $desc . "</td>";
                        }
                        else {
                            if($v == 'NA') $csv_contents .= "<td class='text-muted'>NA</td>";
                            else $csv_contents .= "<td style='color: " . get_stat_scale_color2($v, 'Log2FoldChange') . ";'>" . sprintf("%.4f", $v) . "</td>";
                        }
                    }
                    $csv_contents .= "</tr>";

                }

            }
            fclose($handle);

            $csv_contents .= "</tbody></table>";

            fclose($handle_filtered);
        }

        $csv_contents .= "<div class='mx-3'><a href='$CACHE_URL/uploaded.csv'>Download Complete Data (CSV format)</a></div>";

        $csv_to_upload = "$CACHE_DIR/uploaded.csv";
        if(file_exists($csv_filtered) && (! isset($_POST['checkbox_no_filter']) || $_POST['checkbox_no_filter'] != 1) ){
            $csv_to_upload = $csv_filtered;
        }

        $target_url = 'https://reactome.org/AnalysisService/identifiers/form';

        $postFields = array(
            'pathway' => $pathway_selected,
            'file' => new cURLFile($csv_to_upload, "text/plain", $file_name)
        );


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $target_url);
        curl_setopt($ch, CURLOPT_POST,1);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);

        $json=curl_exec ($ch);
        curl_close ($ch);

        $result_array = json_decode($json, true);

        $token = $result_array['summary']['token'];

        if($token != ''){
            $found_pathway = array();
            foreach($result_array['pathways'] as $k => $v){
                if($v['stId'] == $pathway_selected){
                    $found_pathway[$k] = $v;
                    break;
                }
            }
            $result_array['pathways'] = $found_pathway;

            $OUTPUT['token'] = $token;
            $OUTPUT['results'] = $result_array;
        }

        $OUTPUT['table'] = $csv_contents;

    }


    $OUTPUT['type'] = 'Success';

    echo json_encode($OUTPUT);

    exit();
}


$pathway_file = '';
if($_SESSION['SPECIES_DEFAULT'] == 'Human') $pathway_file = __DIR__ . "/reactome/species/Homo_sapiens.txt";
else if($_SESSION['SPECIES_DEFAULT'] == 'Mouse') $pathway_file = __DIR__ . "/reactome/species/Mus_musculus.txt";
else {
    die("The species '" . $_SESSION['SPECIES_DEFAULT'] . "' is not supported yet.");
}
$BXAF_CONFIG['PATHWAY_LIST'] = unserialize( file_get_contents($pathway_file) );

if (isset($_GET['pathway']) && $_GET['pathway'] != ''){
	$similar_pathways = find_similar_pathways($_GET['pathway'], 'reactome');
}

?><!DOCTYPE html>
<html lang="en">
<head>
    <?php include_once($BXAF_CONFIG['BXAF_PAGE_HEADER']); ?>

    <script src='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/jquery/jquery.form.min.js'></script>

    <link   href='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/datatables/datatables.min.css.php' rel='stylesheet' type='text/css'>
	<script src='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/datatables/datatables.min.js.php'></script>

    <script type="text/javascript" language="javascript" src="https://reactome.org/FireworksJs/fireworks/fireworks.nocache.js"></script>
    <script type="text/javascript" language="javascript" src="http://www.reactome.org/DiagramJs/diagram/diagram.nocache.js"></script>


    <script>

        $(document).ready(function() {

            $(document).on('click', '#btn_select_pathway_show_modal', function() {
                $('#table_select_pathway').DataTable();
            	$('#modal_select_pathway').modal('show');
            });

            $(document).on('click', '.btn_select_search_pathway', function() {

    			<?php
    			if ((sizeof($similar_pathways) > 1) && (is_array($similar_pathways))){
    				echo "$('#modal_similar_pathways').modal('hide');";
    			}
    			?>

                var content = $(this).attr('content');
                var displayed_name = $(this).attr('displayed_name');

                $('#text_pathway_name').text(displayed_name);
                if( $('#text_pathway_name').hasClass('text-muted') )  $('#text_pathway_name').removeClass('text-muted')
                if(! $('#text_pathway_name').hasClass('text-danger') )  $('#text_pathway_name').addClass('text-danger')

                $('#input_pathway').val(content);

                $('#modal_select_pathway').modal('hide');

                if( $('#Comparison_List').val() != '' && $('#input_pathway').val() != ''){
                    $('#form_main').submit();
                }

            });


    		<?php if ((sizeof($similar_pathways) > 1) && (is_array($similar_pathways))){ ?>
    		$(document).on('click', '.btn_select_similar_pathway', function() {

    			$('#modal_similar_pathways').modal('hide');

                var content = $(this).attr('content');
                var displayed_name = $(this).attr('displayed_name');

                $('#text_pathway_name').text(displayed_name);
                if( $('#text_pathway_name').hasClass('text-muted') )  $('#text_pathway_name').removeClass('text-muted')
                if(! $('#text_pathway_name').hasClass('text-danger') )  $('#text_pathway_name').addClass('text-danger')

                $('#input_pathway').val(content);

                if( $('#Comparison_List').val() != '' && $('#input_pathway').val() != ''){
                    $('#form_main').submit();
                }

            });
    		<?php } ?>


            <?php
    			//$_GET['pathway'] is exact match
    			if ((sizeof($similar_pathways) == 1) && (is_array($similar_pathways))){

    				$key 	= array_keys($similar_pathways);
    				$key 	= $key[0];

    				$value 	= array_values($similar_pathways);
    				$value	= $value[0];

    				echo "var content = '$key';
                        var displayed_name = '$value';

                        $('#text_pathway_name').text(displayed_name);
                        if( $('#text_pathway_name').hasClass('text-muted') )  $('#text_pathway_name').removeClass('text-muted')
                        if(! $('#text_pathway_name').hasClass('text-danger') )  $('#text_pathway_name').addClass('text-danger')

                        $('#input_pathway').val(content); ";
    			}

    			if ((sizeof($similar_pathways) > 1) && (is_array($similar_pathways))){
    				echo "$('#modal_similar_pathways').modal('show');";
    			}
            ?>

            // File Upload
            var options = {
                url: '<?php echo $_SERVER['PHP_SELF']; ?>?action=upload_file',
                type: 'post',
                beforeSubmit: function(formData, jqForm, options) {

                    $('#btn_submit').attr('disabled', '').children(':first').removeClass('fa-upload').addClass('fa-spin fa-spinner');

                    return true;
                },
                success: function(response){

                    $('#btn_submit').removeAttr('disabled').children(':first').addClass('fa-upload').removeClass('fa-spin fa-spinner');

                    var type = response.type;
                    if (type == 'Error') {
                        bootbox.alert(response.detail);
                    }

                    else {

                        $('#form_main').addClass('hidden');
                        $('#div_options_restart').removeClass('hidden');

                        if(response.table != ''){
                            $('#div_table').html(response.table);
                            $('#table_filtered_results').DataTable({ "pageLength": 100, "lengthMenu": [[10, 100, 500, 1000], [10, 100, 500, 1000]], dom: 'Blfrtip', buttons: ['colvis','copy','csv'] });
                        }

                        var w = window.innerWidth - 50;
                        var h = window.innerHeight - 20;


                        if(response.pathway != ''){

                            var diagram = Reactome.Diagram.create({
                                "placeHolder" : "diagramHolder",
                                "width" : window.innerWidth - 20,
                                "height" : 1200
                            });

                            //Initialising it to the "Hemostasis" pathway
                            diagram.loadDiagram(response.pathway);

                            //Adding different listeners
                            diagram.onDiagramLoaded(function (loaded) {
                                // console.info("Loaded ", loaded);
                                if(response.pathway_name != '') diagram.flagItems(response.pathway_name);
                                if(response.token != '') diagram.setAnalysisToken(response.token, "");
                            });

                            diagram.onObjectHovered(function (hovered){
                                // console.info("Hovered ", hovered);
                            });

                            diagram.onObjectSelected(function (selected){
                                // console.info("Selected ", selected);
                            });
                        }
                        else {
                            var fireworks = Reactome.Fireworks.create({
                                "placeHolder" : "fireworksHolder",
                                "width" : window.innerWidth - 20,
                                "height" : 1200
                            });

                            $('#div_options_overview').removeClass('hidden');

                            //Adding different listeners

                            fireworks.onFireworksLoaded(function (loaded) {
                                // console.info("Loaded ", loaded);
                            });

                            fireworks.onNodeHovered(function (hovered){
                                // console.info("Hovered ", hovered);
                                if(response.token != '') fireworks.setAnalysisToken(response.token, "");
                            });

                            fireworks.onNodeSelected(function (selected){
                                console.info("Selected ", selected);

                                if(selected !== 'null' && selected.hasOwnProperty('stId')){

                                    // if(response.token != '') window.location = "reactome.php?pathway=" + selected.stId;
                                    // else window.location = "reactome.php?pathway=" + selected.stId + '&token=' + response.token;

                                    $('#div_fireworks').addClass('hidden');
                                    $('#div_options_restart').removeClass('hidden');

                                    var diagram = Reactome.Diagram.create({
                                        "placeHolder" : "diagramHolder",
                                        "width" : window.innerWidth - 20,
                                        "height" : 1200
                                    });

                                    //Initialising it to the "Hemostasis" pathway
                                    diagram.loadDiagram(selected.stId);

                                    //Adding different listeners
                                    diagram.onDiagramLoaded(function (loaded) {
                                        // console.info("Loaded ", loaded);
                                        if(response.pathway_name != '') diagram.flagItems(response.pathway_name);
                                        if(response.token != '') diagram.setAnalysisToken(response.token, "");
                                    });

                                    diagram.onObjectHovered(function (hovered){
                                        // console.info("Hovered ", hovered);
                                    });

                                    diagram.onObjectSelected(function (selected){
                                        // console.info("Selected ", selected);
                                    });

                                }
                            });
                        }

                        return true;
                    }

                }
            };
            $('#form_main').ajaxForm(options);


            if( $('#Comparison_List').val() != '' && $('#input_pathway').val() != ''){
                $('#form_main').submit();
            }

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

    <?php $help_key = 'Reactome Pathway View'; include_once( dirname(__DIR__) . "/help_content.php"); ?>

    <div class="my-3 w-100">

        <form class="my-2" id="form_main" method="post" enctype="multipart/form-data">

            <div class="mt-3 w-100">
                <div class="form-group">
                    <input class="hidden" name="pathway" id="input_pathway" value="">
                    <span class="text-muted" id="text_pathway_name">(No Pathway Selected yet, will show Pathway Overview)</span>
                </div>
                <div class="form-group">
                    <button class="btn btn-outline-success btn-sm" type="button" id="btn_select_pathway_show_modal">
                      <i class="fas fa-angle-double-right"></i> Select Pathway
                    </button>
                </div>
            </div>


            <div class="form-group" id="div_select_comparisons">
                <?php include_once(dirname(__DIR__) . '/tool_save_lists/modal_comparison.php'); ?>
            </div>


            <div class="form-group form-inline">

                <span class="mx-2">Or, Upload your comparison file: </span>

                <input id="input_upload_file" type="file" class="mx-2" name="file" onclick="$('#Comparison_List').val('');" >

                <span id="form_upload_file_busy" class="mx-2 hidden text-danger"><i class="fas fa-spinner fa-spin"></i> Uploading ... </span>

            </div>

            <div class="form-group">
                <label class="font-weight-bold">Data Range Options: </label>

                <div class="form-row align-items-center">
                    <div class="col-auto my-1">
                        <div class="form-check form-check-inline">
                          <input class="form-check-input" type="radio" name="data_option" value="" checked>
                          <label class="form-check-label">No Change</label>
                        </div>
                        <div class="form-check form-check-inline">
                          <input class="form-check-input" type="radio" name="data_option" value="range">
                          <label class="form-check-label">Limit values within range: </label>
                        </div>
                        <div class="form-check form-check-inline">
                          <input class="form-control" style="width: 4rem;" type="number" name="range_min" id="range_min" value="-1">
                          <label class="mx-2"> - </label>
                          <input class="form-control" style="width: 4rem;" type="number" name="range_max" id="range_max" value="1">
                        </div>
                    </div>

                    <div class="col-auto my-1">
                        <select class="custom-select" onchange="if($(this).val() != '') { $('#range_min').val( (-1) * $(this).val() ); $('#range_max').val( $(this).val() ); $(this).val(''); } ">
                            <option value="" selected>Select a Preset Range</option>
                            <option value="1">-1 to 1</option>
                            <option value="2">-2 to 2</option>
                            <option value="3">-3 to 3</option>
                        </select>
                    </div>
                </div>

            </div>

            <div class="form-group">
                <button id="btn_submit" class="btn btn-primary" type="submit"><i class="fas fa-upload"></i> View Pathway</button>

                <input class="mx-1" type="checkbox" name="checkbox_no_filter" id="checkbox_no_filter" value="1">Upload all gene data to Reactome

                <a class="mx-2" href="files/demo_logfc.csv">
                    <i class="fas fa-angle-double-right"></i> Demo Data1
                </a>
                <a class="mx-2" href="files/demo_pvalue.csv">
                    <i class="fas fa-angle-double-right"></i> Demo Data2
                </a>
                <a class="mx-2" href="files/demo_adjpval.csv">
                    <i class="fas fa-angle-double-right"></i> Demo Data3
                </a>

            </div>

        </form>

        <div class="w-100 my-3">
            <div id="div_options" class="w-100 my-3">
                <a id="div_options_overview" class="hidden mr-5" href="Javascript: void(0);" onclick="if( $('#div_fireworks').hasClass('hidden') ) $('#div_fireworks').removeClass('hidden'); else $('#div_fireworks').addClass('hidden'); "><i class='fas fa-caret-right'></i> Show/Hide Overview</a>
                <a id="div_options_restart" class="hidden" href="<?php echo $_SERVER['PHP_SELF']; ?>"><i class='fas fa-caret-right'></i> Restart</a>
            </div>
            <div id="div_fireworks" class="w-100 my-3"><div id="fireworksHolder"></div></div>
            <div id="div_diagram" class="w-100 my-3"><div id="diagramHolder"></div></div>
            <div id="div_table" class="w-100 my-3"></div>
        </div>

    </div>

    <div id="div_debug" class="w-100 my-3"></div>

</div>
</div>
<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>
</div>
</div>



<?php if ((sizeof($similar_pathways) > 1) && (is_array($similar_pathways))){ ?>
<!----------------------------------------------------------------------------------------------------->
<!-- Modal to Similar Pathway -->
<!----------------------------------------------------------------------------------------------------->
<div class="modal" id="modal_similar_pathways" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Please select a pathway</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body">
      	<p>The exact pathway is not found. Please try selecting a similar pathway below:</p>

      	<ul>
        <?php

			foreach($similar_pathways as $key => $value){
				echo "<li><a href='javascript:void(0);' class='btn_select_similar_pathway' content='{$key}' displayed_name='{$value}'>{$value}</a></li>";
			}

          //echo general_printr($similar_pathways);
        ?>
        </ul>
        <p>If the pathway is not in the list, the likely cause is that some old reactome pathways are not available online anymore.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<?php } ?>


<!-------------------------------------------------------------------------------------------------------->
<!-- Modal to Select Pathway -->
<!-------------------------------------------------------------------------------------------------------->
<div class="modal" id="modal_select_pathway" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title">Select Pathway</h4>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			</div>
			<div class="modal-body">
				<?php
					echo '
					<table class="table table-bordered" id="table_select_pathway">
						<thead>
						<tr>
                            <th>Pathway Code</th>
							<th>Pathway Name</th>
							<th>Action</th>
						</tr>
						</thead>
						<tbody>';

                        foreach ($BXAF_CONFIG['PATHWAY_LIST'] as $key => $value) {
							echo '
							<tr>
                                <td>' . $key . '</td>
								<td>' . $value . '</td>
								<td><a href="javascript:void(0);" class="btn_select_search_pathway" content="' . $key . '" displayed_name="' . $value . '"><i class="fas fa-angle-double-right"></i> Select</a></td>
							</tr>';
						}

					echo '
						</tbody>
					</table>
					';
				?>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
			</div>
		</div>
	</div>
</div>


</body>
</html>