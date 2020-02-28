<?php

//To disable login requirement
//$BXAF_CONFIG_CUSTOM['PAGE_LOGIN_REQUIRED'] = false;

include_once(dirname(__FILE__) . "/config/config.php");

bxaf_sync_tables();


if(! isset($_SESSION['View_NGS_in_TPM']) || $_SESSION['View_NGS_in_TPM'] == ''){

    $sql = "SELECT `Detail` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_USERPREFERENCE']}` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `Category` = 'View_NGS_in_TPM' ";
    $v = $BXAF_MODULE_CONN -> get_one($sql);

    if($v == 'TPM') $_SESSION['View_NGS_in_TPM'] = 'TPM';
    else $_SESSION['View_NGS_in_TPM'] = 'FPKM';
}


if(! isset($_SESSION['Dashboard']) || ! is_array($_SESSION['Dashboard']) || count($_SESSION['Dashboard']) < 10){

    $_SESSION['Dashboard'] = array();

    $sql = "SELECT `Detail` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_USERPREFERENCE']}` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `Category` = 'Dashboard Options' ";
    $dashboard_options = $BXAF_MODULE_CONN -> get_one($sql);
    if($dashboard_options != ''){
        $_SESSION['Dashboard'] = unserialize($dashboard_options);
    }

    if(! isset($_SESSION['Dashboard']) || ! is_array($_SESSION['Dashboard']) || count($_SESSION['Dashboard']) < 10){
        $_SESSION['Dashboard']['ComparisonCategory_Show_Top_15'] = 0;
        $_SESSION['Dashboard']['Case_Tissue_Show_Top_15'] = 0;
        $_SESSION['Dashboard']['Case_DiseaseState_Show_Top_15'] = 0;
        $_SESSION['Dashboard']['Case_Treatment_Show_Top_15'] = 0;
        $_SESSION['Dashboard']['PlatformName_Show_Top_15'] = 0;

        $_SESSION['Dashboard']['Case_Tissue_Hide_Unknown'] = 0;
        $_SESSION['Dashboard']['Case_Tissue_Hide_Others'] = 0;
        $_SESSION['Dashboard']['Case_DiseaseState_Hide_Unknown'] = 0;
        $_SESSION['Dashboard']['Case_DiseaseState_Hide_Normal_Control'] = 0;
        $_SESSION['Dashboard']['Case_DiseaseState_Hide_Others'] = 0;
        $_SESSION['Dashboard']['Case_Treatment_Hide_Unknown'] = 0;
        $_SESSION['Dashboard']['Case_Treatment_Hide_Others'] = 0;
        $_SESSION['Dashboard']['PlatformName_Hide_Generic'] = 0;
    }
}



$filter = " {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `Species` = '{$_SESSION['SPECIES_DEFAULT']}' ";

// Force to update Record count
$_SESSION['RECORD_COUNTS'] = array();
$sql = "SELECT COUNT(*) FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS']}` WHERE  $filter ";
$_SESSION['RECORD_COUNTS']['Comparison'] = $BXAF_MODULE_CONN -> get_one($sql);

$sql = "SELECT COUNT(*) FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS']}` WHERE  $filter ";
$_SESSION['RECORD_COUNTS']['Project'] = $BXAF_MODULE_CONN -> get_one($sql);

$sql = "SELECT COUNT(*) FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES']}` WHERE  $filter ";
$_SESSION['RECORD_COUNTS']['Sample'] = $BXAF_MODULE_CONN -> get_one($sql);

$sql = "SELECT COUNT(*) FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_GENES']}` WHERE  $filter ";
$_SESSION['RECORD_COUNTS']['Gene'] = $BXAF_MODULE_CONN -> get_one($sql);


if(isset($_GET['comparison_type']) && $_GET['comparison_type'] == 'private'){
    $filter = " (`bxafStatus` < 5 AND `_Owner_ID`=" . intval( $BXAF_CONFIG['BXAF_USER_CONTACT_ID'] ) . " AND `Species` = '{$_SESSION['SPECIES_DEFAULT']}') ";
}
else if(isset($_GET['comparison_type']) && $_GET['comparison_type'] == 'public'){
    $filter = " (`bxafStatus` < 5 AND (`_Owner_ID` IS NULL OR `_Owner_ID`=0 OR `_Owner_ID`='') AND `Species` = '{$_SESSION['SPECIES_DEFAULT']}') ";
}






if (isset($_GET['action']) && $_GET['action'] == 'refresh_table') {
    // echo "<pre>" . print_r($_POST, true) . "</pre>";

    $sql = "SELECT `ID`, `Name`, `ComparisonCategory`, `Case_Tissue`, `Case_DiseaseState`, `Case_Treatment`, `PlatformName` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS']}` WHERE `ID` IN (?a)";
    $comparison_selected_info = $BXAF_MODULE_CONN -> get_all($sql, $_POST['comparisons']);

    // If no data file
    if(! is_array($comparison_selected_info) || count($comparison_selected_info) <= 0){
        exit();
    }

    foreach ($comparison_selected_info as $i=>$row){
        foreach($row as $k=>$v){
            if($v == '' || $v == 'NA' || $v == 'none'){ $row[$k] = 'Others'; }
        }

        $row['PlatformName'] = preg_replace("/^\[.*\]\s+/", "", $row['PlatformName']);
        $row['PlatformName'] = preg_replace("/\s*\[.*\]$/", "", $row['PlatformName']);
        $row['PlatformName'] = preg_replace("/\s*\(.*\)$/", "", $row['PlatformName']);
        $row['PlatformName'] = preg_replace("/\s*Array$/", "", $row['PlatformName']);

        $comparison_selected_info[$i] = $row;
    }


    echo "<input type='hidden' id='input_all_ids' value='" . implode(",", $_POST['comparisons']). "' />";

    echo '<div class="w-100 mb-3">';
        echo '<a class="m-1 btn btn-sm btn-primary btn_comparison_actions" action_type="tool_save_lists/new_list.php?comparison_ids=" href="Javascript: void(0);"><i class="fas fa-caret-right mx-1"></i> Save Comparison List</a>';
        echo '<a class="m-1 btn btn-sm btn-primary btn_comparison_actions" action_type="tool_bubble_plot/multiple.php?comparison_ids=" href="Javascript: void(0);"><i class="fas fa-caret-right mx-1"></i> Bubble Plot</a>';
        echo '<a class="m-1 btn btn-sm btn-primary btn_comparison_actions" action_type="tool_pathway/changed_genes.php?comparison_ids=" href="Javascript: void(0);"><i class="fas fa-caret-right mx-1"></i> Significantly Changed Genes</a>';
        echo '<a class="m-1 btn btn-sm btn-primary btn_comparison_actions" action_type="tool_pathway_heatmap/index.php?comparison_ids=" href="Javascript: void(0);"><i class="fas fa-caret-right mx-1"></i> Pathway Heatmap</a>';
        echo '<a class="m-1 btn btn-sm btn-primary btn_comparison_actions" action_type="tool_meta_analysis/index.php?comparison_ids=" href="Javascript: void(0);"><i class="fas fa-caret-right mx-1"></i> Meta Analysis</a>';
        echo '<a class="m-1 btn btn-sm btn-primary btn_comparison_actions" action_type="tool_export/genes_comparisons.php?comparison_ids=" href="Javascript: void(0);"><i class="fas fa-caret-right mx-1"></i> Export Comparison Data</a>';

		echo '<a class="m-1 btn btn-sm btn-primary btn_comparison_actions" action_type="tool_pathway/index.php?comparison_ids=" href="Javascript: void(0);"><i class="fas fa-caret-right mx-1"></i> WikiPathways</a>';
		echo '<a class="m-1 btn btn-sm btn-primary btn_comparison_actions" action_type="tool_pathway/reactome.php?comparison_ids=" href="Javascript: void(0);"><i class="fas fa-caret-right mx-1"></i> Reactome Pathways</a>';
		echo '<a class="m-1 btn btn-sm btn-primary btn_comparison_actions" action_type="tool_pathway/kegg.php?comparison_ids=" href="Javascript: void(0);"><i class="fas fa-caret-right mx-1"></i> KEGG Pathways</a>';

    echo '</div>';

    echo '<div class="w-100 mb-3">';
        echo '<a class="m-1 btn btn-sm btn-success btn_comparison_actions" action_type="tool_gene_expression_plot/index.php?comparison_ids=" href="Javascript: void(0);"><i class="fas fa-caret-right mx-1"></i> Gene Expression Plot</a>';
        echo '<a class="m-1 btn btn-sm btn-success btn_comparison_actions" action_type="tool_heatmap/index.php?comparison_ids=" href="Javascript: void(0);"><i class="fas fa-caret-right mx-1"></i> Heatmap</a>';
        echo '<a class="m-1 btn btn-sm btn-success btn_comparison_actions" action_type="tool_correlation/index.php?comparison_ids=" href="Javascript: void(0);"><i class="fas fa-caret-right mx-1"></i> Correlation Tool</a>';
        echo '<a class="m-1 btn btn-sm btn-success btn_comparison_actions" action_type="tool_pca/index_genes_samples.php?comparison_ids=" href="Javascript: void(0);"><i class="fas fa-caret-right mx-1"></i> PCA Analysis</a>';
        echo '<a class="m-1 btn btn-sm btn-success btn_comparison_actions" action_type="tool_export/genes_samples.php?comparison_ids=" href="Javascript: void(0);"><i class="fas fa-caret-right mx-1"></i> Export Expression Data</a>';
    echo '</div>';

    echo '<div class="w-100"><table class="datatables table table-bordered table-hover mt-3">
        <thead>
            <tr class="table-info">
                ' . "<th class='text-center'><input type='checkbox' class='bxaf_checkbox bxaf_checkbox_all' /></th>" . '
                <th>Name</th>
                <th>ComparisonCategory</th>
                <th>Case_Tissue</th>
                <th>Case_DiseaseState</th>
                <th>Case_Treatment</th>
                <th>PlatformName</th>
            </tr>
        </thead>
        <tbody>';

        foreach($comparison_selected_info as $comparison){
            echo '<tr>';
                echo "<td class='text-center'><input type='checkbox' class='bxaf_checkbox bxaf_checkbox_one' rowid='" . $comparison['ID'] . "' /></td>";
                echo '<td><a href="tool_search/view.php?type=comparison&id=' . $comparison['ID'] . '">' . $comparison['Name'] . '</a></td>';
                echo '<td>' . $comparison['ComparisonCategory'] . '</td>';
                echo '<td>' . $comparison['Case_Tissue'] . '</td>';
                echo '<td>' . $comparison['Case_DiseaseState'] . '</td>';
                echo '<td>' . $comparison['Case_Treatment'] . '</td>';
                echo '<td>' . $comparison['PlatformName'] . '</td>';
            echo '</tr>';
        }
    echo '</tbody></table></div>';

    exit();
}



$filter_comparison = $filter;
if($_SESSION['Dashboard']['Case_Tissue_Hide_Unknown']) $filter_comparison .= " AND `Case_Tissue` != 'Unknown Tissue' ";
if($_SESSION['Dashboard']['Case_Tissue_Hide_Others']) $filter_comparison .= " AND `Case_Tissue` != '' AND `Case_Tissue` != 'NA' AND `Case_Tissue` != 'none' ";
if($_SESSION['Dashboard']['Case_DiseaseState_Hide_Unknown']) $filter_comparison .= " AND `Case_DiseaseState` != 'Unknown Disease' ";
if($_SESSION['Dashboard']['Case_DiseaseState_Hide_Normal_Control']) $filter_comparison .= " AND `Case_DiseaseState` != 'normal control' ";
if($_SESSION['Dashboard']['Case_DiseaseState_Hide_Others']) $filter_comparison .= " AND `Case_DiseaseState` != '' AND `Case_DiseaseState` != 'NA' AND `Case_DiseaseState` != 'none' ";
if($_SESSION['Dashboard']['Case_Treatment_Hide_Unknown']) $filter_comparison .= " AND `Case_Treatment` != 'Unknown Treatment' ";
if($_SESSION['Dashboard']['Case_Treatment_Hide_Others']) $filter_comparison .= " AND `Case_Treatment` != '' AND `Case_Treatment` != 'NA' AND `Case_Treatment` != 'none' ";
if($_SESSION['Dashboard']['PlatformName_Hide_Generic']) $filter_comparison .= " AND `_Platforms_ID` > 100 ";

$sql = "SELECT `ID`, `ComparisonCategory`, `Case_Tissue`, `Case_DiseaseState`, `Case_Treatment`, `PlatformName` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS']}` WHERE  $filter_comparison ";
$comparison_info = $BXAF_MODULE_CONN -> get_all($sql);

foreach ($comparison_info as $i=>$row){
    foreach($row as $k=>$v){
        if($v == '' || $v == 'NA' || $v == 'none'){ $row[$k] = 'Others'; }
    }
    $row['PlatformName'] = preg_replace("/^\[.*\]\s+/", "", $row['PlatformName']);
    $row['PlatformName'] = preg_replace("/\s*\[.*\]$/", "", $row['PlatformName']);
    $row['PlatformName'] = preg_replace("/\s*\(.*\)$/", "", $row['PlatformName']);
    $row['PlatformName'] = preg_replace("/\s*Array$/", "", $row['PlatformName']);

    $comparison_info[$i] = $row;
}

$top_flds = array('ComparisonCategory', 'Case_Tissue', 'Case_DiseaseState', 'Case_Treatment', 'PlatformName');
$limit = 15;
foreach($top_flds as $fld){
    if($_SESSION['Dashboard'][$fld . '_Show_Top_' . $limit]){
        $counts = array();
        foreach ($comparison_info as $i=>$row){
            foreach($row as $k=>$v){
                if($k == $fld) $counts[$v] += 1;
            }
        }
        arsort($counts);
        $counts = array_slice($counts, 0, $limit);

        foreach ($comparison_info as $i=>$row){
            foreach($row as $k=>$v){
                if($k == $fld && ! array_key_exists($v, $counts)){
                    unset($comparison_info[$i]);
                    break;
                }
            }
        }
    }
}





$dir = __DIR__ . "/files/user_files/{$BXAF_CONFIG['BXAF_USER_CONTACT_ID']}/";
if (! file_exists($dir))  mkdir($dir, 0775, true);

$csv_file = "files/user_files/{$BXAF_CONFIG['BXAF_USER_CONTACT_ID']}/index_comparisons.csv";
$fp = fopen(__DIR__ . "/$csv_file", 'w');
fputcsv($fp, array('ComparisonIndex', 'ComparisonCategory', 'Case_Tissue', 'Case_DiseaseState', 'Case_Treatment', 'PlatformName') );
foreach ($comparison_info as $i=>$row){
    fputcsv($fp, $row);
}
fclose($fp);





// Record count
$RECORD_COUNTS = array();
$RECORD_COUNTS['Comparison'] = count($comparison_info);

$sql = "SELECT COUNT(*) FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS']}` WHERE  $filter ";
$RECORD_COUNTS['Project'] = $BXAF_MODULE_CONN -> get_one($sql);

$sql = "SELECT COUNT(*) FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES']}` WHERE  $filter ";
$RECORD_COUNTS['Sample'] = $BXAF_MODULE_CONN -> get_one($sql);

$sql = "SELECT COUNT(*) FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_GENES']}` WHERE  $filter ";
$RECORD_COUNTS['Gene'] = $BXAF_MODULE_CONN -> get_one($sql);




?><!DOCTYPE html>
<html lang="en">
<head>
	<?php include_once($BXAF_CONFIG['BXAF_PAGE_HEADER']); ?>
	<script src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/jquery/jquery.form.min.js"></script>

    <link   href='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/datatables/datatables.min.css.php' rel='stylesheet' type='text/css'>
	<script src='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/datatables/datatables.min.js.php'></script>


    <link type="text/css" rel="stylesheet" href="./library/canvasxpress/canvasxpress-20.1/canvasXpress.css.php"/>
    <script type="text/javascript" src="./library/canvasxpress/canvasxpress-20.1/canvasXpress.js.php"></script>

    <script type="text/javascript" src="./library/d3/d3-3.5.17.min.js"></script>

    <script type="text/javascript" src="./library/crossfilter/crossfilter-1.3.12.min.js"></script>

    <link type="text/css" rel="stylesheet" href="./library/dc/dc-2.2.1.min.css">
    <script type="text/javascript" src="./library/dc/dc-2.2.1.min.js"></script>


	<style>
	.card{
		min-width: 25rem;
	}
    .card_wide{
        min-width: 40rem;
    }
	</style>

	<script type="text/javascript">
		$(document).ready(function(){

			// Create New Experiment
			$(document).on('click', '.new_experiment_btn', function(){
				$('#myModal_new_experiment').modal();
			});

			var options_new_experiment = {
				url: 'bxgenomics_exe.php?action=new_experiment',
				type: 'post',
				beforeSubmit: function(formData, jqForm, options) {
                    if($('#Experiment_Name').val() == ''){
						bootbox.alert('<h2><i class="fas fa-exclamation-triangle text-warning"></i> Warning</h2><div class="lead p-3">Experiment name is required.</div>');
						return false;
					}
					return true;
				},
				success: function(responseText, statusText){
                    if(responseText != ''){
						bootbox.alert('<h2><i class="fas fa-exclamation-triangle text-warning"></i> Warning</h2><div class="lead p-3">' + responseText + '</div>');
						return false;
					}
					else {
						bootbox.alert('<h2><i class="fas fa-check-square text-success"></i> Message</h2><div class="lead p-3">New experiment has been created.</div>', function(){ location.reload(true); });
					}
				}
			};
			$('#form_new_experiment').ajaxForm(options_new_experiment);


			// New Project
			$(document).on('click', '.new_project_btn', function(){
				$('#myModal_new_project').modal();
			});

			var options_new_project = {
				url: 'bxgenomics_exe.php?action=new_project',
				type: 'post',
				beforeSubmit: function(formData, jqForm, options) {
					if($('#project_name').val() == ''){
		                bootbox.alert('<h2><i class="fas fa-exclamation-triangle text-warning"></i> Warning</h2><div class="lead p-3">Please enter a valid project name.</div>');
		                return false;
		            }
					return true;
				},
				success: function(response){
					$('#myModal_new_project').modal('hide');
					var type = response.type;
					if (type == 'Error') {
						bootbox.alert(`${response.detail}`);
					} else {
						window.location = 'project.php?id=' + response.id;
					}
				}
			};
			$('#form_new_project').ajaxForm(options_new_project);



            $(document).on('click', '.div_section_toggle', function(){
                var target = $(this).attr('target_id');
                if($('#' + target).hasClass('hidden')){
                    $(this).html('<i class="fas fa-minus-square"></i> Hide');
                    $('#' + target).removeClass('hidden');
                }
                else{
                    $(this).html('<i class="fas fa-plus-square"></i> Show');
                    $('#' + target).addClass('hidden');
                }

        	});


            // Save session list
            $(document).on('click', '.btn_comparison_actions', function() {

                var rowid = '';
                $('.bxaf_checkbox_one').each(function(index, element) {
                    if ( element.checked ) {
                        if(rowid == '') rowid = $(element).attr('rowid');
                        else rowid += ',' + $(element).attr('rowid');
                    }
                });
                if (rowid == '')  rowid = $('#input_all_ids').val();

                window.open($(this).attr('action_type') + rowid);
                // window.location = $(this).attr('action_type') + rowid;

            });


            // // Check/Uncheck All
            $(document).on('change', '.bxaf_checkbox', function() {
                if($(this).hasClass('bxaf_checkbox_all')){
                    $('.bxaf_checkbox_one').prop ('checked', $(this).is(':checked') );
                }
                else if( $(this).hasClass('bxaf_checkbox_one') ){
                    var checked = true;
                    $('.bxaf_checkbox_one').each(function(index, element) {
                        if (! element.checked ) checked = false;
                    });
                    $('.bxaf_checkbox_all').prop ('checked', checked);
                }
            });

            // Set NGS view data type
            $(document).on('change', '#View_NGS_in_TPM', function() {
                $.ajax({
    				method: 'GET',
    				url: 'bxgenomics_exe.php?action=save_View_NGS_in_TPM&value=' + $(this).prop('checked'),
    				success: function(responseText){
    					// alert(responseText);
    					// window.location = "index.php";
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


				<!-- Main contents here -->
				<div class="container-fluid">

                    <?php if($_SESSION['BXAF_ADVANCED_USER']){ ?>

                        <div class="d-flex mt-3 p-2 table-info">
                            <h3 class="align-self-center"><i class='fas fa-chart-line'></i> My Experiments and Analyses</h3>
                            <a class="ml-auto align-self-center div_section_toggle" href="javascript:void(0);" target_id="div_my_experiments_and_analysis"><i class="fas fa-minus-square"></i> Hide</a>
                        </div>

    					<div id="div_my_experiments_and_analysis" class="w-100 my-3">

    						<div class='w-100 my-3'>
                                <span class='text-danger font-weight-bold'>Private Folder:</span> <?php echo $BXAF_CONFIG['BXGENOMICS_SERVER_FILES_PRIVATE']; ?>
    						</div>

                            <div class='w-100 my-3 form-inline'>
    							<a href="javascript:void(0);" class="new_experiment_btn btn btn-outline-primary">
    								<i class="fas fa-hand-point-right"></i> Create New Experiment
    							</a>

                                <div class="custom-control custom-switch ml-5">
                                    <input type="checkbox" class="custom-control-input" id="View_NGS_in_TPM" value='TPM' <?php echo $_SESSION['View_NGS_in_TPM'] == 'TPM' ? "checked" : ""; ?>>
                                    <label class="custom-control-label" for="View_NGS_in_TPM">View NGS data in TPM (otherwise, in FPKM)</label>
                                </div>

    						</div>


    						<?php
                                $sql = "SELECT * FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_EXPERIMENT'] . "` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} ORDER BY `Name`";
                                $experiment_info = $BXAF_MODULE_CONN -> get_assoc('ID', $sql);

                                $sql = "SELECT * FROM `".$BXAF_CONFIG['TBL_BXGENOMICS_SAMPLE']."` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} ";
                                $sample_info = $BXAF_MODULE_CONN -> get_assoc('ID', $sql);

                                $experiment_samples = array();
                                foreach($sample_info as $id=>$info){
                                    $experiment_samples[ $info['Experiment_ID'] ][ $id ] = $info;
                                }

                                $sql = "SELECT * FROM `".$BXAF_CONFIG['TBL_BXGENOMICS_ANALYSIS']."` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} ";
                                $analysis_info = $BXAF_MODULE_CONN -> get_assoc('ID', $sql);

                                $experiment_analyses = array();
                                foreach($analysis_info as $id=>$info){
                                    $experiment_analyses[ $info['Experiment_ID'] ][ $id ] = $info;
                                }

                                if(! is_array($experiment_info) || count($experiment_info) <= 0){
                        			echo "<h5 class='text-danger m-3'>No experiments found.</h5>";
                        		}

                                else if(is_array($experiment_info) && count($experiment_info) > 0){

                                    echo '<div class="card-deck">';

                                    foreach($experiment_info as $experiment_id=>$experiment){

                                        echo '<div class="card border-info m-2">';

                                            echo '<div class="card-header bg-info d-flex">';
                                				echo '<div class="mr-auto"><a class="text-white" href="experiment.php?id='. $experiment_id .'" class="experiment_title_span"> '. $experiment['Name'] .'</a></div>';
                                                echo '<a href="new_sample.php?expid='. $experiment_id .'" class="mx-1 text-white" title="Add New Samples"> <i class="fas fa-plus"></i> </a>';
                                				if (is_array($experiment_samples[ $experiment_id ] ) && count($experiment_samples[ $experiment_id ]) > 0 ){
                                					echo '<a href="new_analysis.php?id='. $experiment_id .'" class="mx-1 text-white" title="Add New Analysis" target="_blank"><i class="fas fa-chart-line"></i></a>';
                                				}
                                			echo '</div>';

                                			// Card Body
                                			echo '<div class="card-body">';

                                                echo '<div class="text-success">'. substr($experiment['Time_Created'], 0, 10) .'</div>';

                                                // Samples
                                    			echo '<div class="font-weight-bold">Samples (' . count($experiment_samples[ $experiment_id ]) . ')</div>';
                                				if (count($experiment_samples[ $experiment_id ]) <= 0){
                                					echo '<div class="text-muted">No samples found. <a href="new_sample.php?expid=' . $experiment_id . '" class=""><i class="fas fa-plus"></i> Add Now</a></div>';
                                				}
                                                else {
                                					echo '<div class="mb-0 ml-3"><a href="javascript: void(0);" onClick="$(this).parent().next().toggle(\'slow\');"><i class="fas fa-caret-right"></i> Show/Hide Samples</a></div>';
                                                    echo '<div class="my-2" style="height:7rem; overflow-y:auto; display: none;">';
                                                        echo '<ul class="mb-0">';
                                    					foreach($experiment_samples[ $experiment_id ] as $sample_id => $sample){
                                    						echo '<li><a href="sample.php?id='. $sample_id .'" class="">' . $sample['Name'] . '</a> (<span class="">' . $sample['Treatment_Name'] . ' / ' . $sample['Data_Type'] . '</span>)</li>';
                                    					}
                                    					echo '</ul>';
                                                    echo '</div>';
                                				}

                                				// Analysis
                                                echo '<div class="font-weight-bold">Analyses (' . count($experiment_analyses[ $experiment_id ]) . ')</div>';
                                                if (! is_array($experiment_analyses[ $experiment_id ] ) || count($experiment_analyses[ $experiment_id ]) < 1){ // If no samples
                                					echo '<div class="text-muted">No analyses found. <a href="new_analysis.php?id='. $experiment_id .'" class=""><i class="fas fa-chart-line"></i> Start Analysis</a></div>';
                                				}
                                                else {
                                					echo '<div class="mb-0 ml-3"><a href="javascript: void(0);" onClick="$(this).parent().next().toggle(\'slow\');"><i class="fas fa-caret-right"></i> Show/Hide Analyses</a></div>';
                                                    echo '<div class="my-2" style="height:7rem; overflow-y:auto; display: block;">';
                                                        echo '<ul class="mb-0">';
                                    					foreach($experiment_analyses[ $experiment_id ] as $analysis_id=>$analysis){
                                                            $class_analysis = new SingleAnalysis($analysis_id);
                        									$status = $class_analysis -> showAnalysisStatus();
                                    						echo '<li><a href="analysis.php?id='. $analysis_id .'" class="">' . $analysis['Name'] . '</a> (<span class="">'. current( $status ) .'</span>)</li>';
                                    					}
                                    					echo '</ul>';
                                                    echo '</div>';
                                				}
                                            echo '</div>';
                                        echo '</div>';
                                    }

                                    echo '</div>';
                                }

    						?>
    					</div>

                    <?php } // if($_SESSION['BXAF_ADVANCED_USER']){ ?>




					<div class="d-flex mt-5 p-2 table-info">
						<h3 class="align-self-center"><i class='fas fa-lock'></i> My Private Projects</h3>
                        <a class="ml-auto align-self-center div_section_toggle" href="javascript:void(0);" target_id="div_my_private_projects"><i class="fas fa-minus-square"></i> Hide</a>
					</div>


					<div id="div_my_private_projects" class="w-100 my-3">

						<div class='w-100 my-3'>
							<a href="javascript:void(0);" class="new_project_btn btn btn-outline-primary">
								<i class="fas fa-hand-point-right"></i> Create New Project
							</a>
						</div>

						<?php
							$sql = "SELECT `ID`, `Name`, `_Analysis_ID`, `Time_Created` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS']}` WHERE  (`bxafStatus` < 5 AND `_Owner_ID`=" . intval( $BXAF_CONFIG['BXAF_USER_CONTACT_ID'] ) . " AND `Species` = '{$_SESSION['SPECIES_DEFAULT']}') ORDER BY `Name`";
							$private_projects = $BXAF_MODULE_CONN -> get_assoc('ID', $sql);

							if (! is_array($private_projects) || count($private_projects) <= 0) {
								echo '<h5 class="mt-3 text-danger">No projects found.</h5>';
							}
							else {

								// Existing Samples
								$sql = "SELECT `ID`, `Name`, `_Projects_ID` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES']}` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `_Projects_ID` IN (?a)";
								$SAMPLES = $BXAF_MODULE_CONN -> get_assoc('ID', $sql, array_keys($private_projects) );

								$project_samples = array();
								foreach ($SAMPLES as $sample_id => $sample) {
									$project_samples[ $sample['_Projects_ID'] ][] = $sample_id;
								}

								// Existing Comparisons
								$sql = "SELECT `ID`, `Name`, `_Projects_ID` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS']}` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `_Projects_ID` IN (?a)";
								$COMPARISONS = $BXAF_MODULE_CONN -> get_assoc('ID', $sql, array_keys($private_projects) );
								$project_comparisons = array();
 								foreach ($COMPARISONS as $comparison_id => $comparison) {
 									$project_comparisons[ $comparison['_Projects_ID'] ][] = $comparison_id;
 								}


                                echo '<div class="card-deck">';
                                foreach ($private_projects as $project_id => $project) {

                                    echo '<div class="card my-3">';

                                        echo '<div class="card-header text-nowrap font-weight-bold " style="overflow-x: hidden;">';
                                            echo '<a class="lead" href="project.php?id=' . $project_id . '" title="View project details" target="">' . $project['Name'] . '</a>';
                                        echo '</div>';

                                        echo '<div class="card-body">';

                                            echo '<div class="w-100 mb-3">';
                                                echo 'Created on ' . substr($project['Time_Created'], 0, 10 );
                                            echo '</div>';

                                            $n_samples = count($project_samples[$project_id]);
                							if($n_samples > 0) {
                								echo '<div class="w-100">';
                									echo '<i class="fas fa-caret-right"></i> ' . ( ($n_samples > 1) ? "There are <span class='text-danger'>$n_samples samples</span>" : "There is <span class='text-danger'>1 sample</span>") . ' in this project.';
                								echo '</div>';

                							}
                                            else {
                                                echo '<div class="w-100">';
                									echo '<i class="fas fa-caret-right"></i> No samples found in this project.';
                								echo '</div>';
                                            }

                                            $n_comparisons = count($project_comparisons[$project_id]);
                							if($n_comparisons > 0) {
                								echo '<div class="w-100 my-3">';
                									echo '<i class="fas fa-caret-right"></i> ' . ( ($n_comparisons > 1) ? "There are <span class='text-danger'>$n_comparisons comparisons</span>" : "There is <span class='text-danger'>1 comparison</span>") . ' in this project.';
                								echo '</div>';

                							}
                                            else {
                                                echo '<div class="w-100">';
                									echo '<i class="fas fa-caret-right"></i> No comparisons found in this project.';
                								echo '</div>';
                                            }


                                        echo '</div>';

                                        echo '<div class="card-footer">';
                                            $analysis_id = intval($project['_Analysis_ID']);
                                            if($analysis_id > 0) {
                                                $analysis_id_encrypted = $analysis_id . '_' . bxaf_encrypt($analysis_id, $BXAF_CONFIG['BXAF_KEY']);
                                                echo 'Analysis: <a href="report_full_user.php?analysis=' . $analysis_id_encrypted . '" title="View Analysis Report" target="" class="mx-1"><span class="badge badge-pill table-success text-danger">View Report</span></a>';
                                            }
                                            else {
                                                echo 'No analysis performed online.';
                                            }
                                        echo '</div>';


                                    echo '</div>';
                                }

                                echo '</div>';

							}

						?>

					</div>



<?php if($RECORD_COUNTS['Comparison'] > 0){ ?>

    				<div class="d-flex mt-5 p-2 table-info">
    					<h3 class="align-self-center">
                            <i class='fas fa-copyright'></i>
                            <?php if(isset($_GET['comparison_type']) && $_GET['comparison_type'] == 'private') echo 'Private'; else if(isset($_GET['comparison_type']) && $_GET['comparison_type'] == 'public') echo 'Public'; else echo 'All'; ?> Comparisons
                        </h3>
                        <a class="ml-2 align-self-center btn btn-sm btn-success" href="tool_search/index.php?type=comparison"><i class="fas fa-search"></i> Search</a>
                        <a class="ml-auto align-self-center div_section_toggle" href="javascript:void(0);" target_id="div_all_comparisons"><i class="fas fa-minus-square"></i> Hide</a>
    				</div>

    				<div id="div_all_comparisons" class="w-100 my-3">

                        <div class='w-100 my-3 ml-3'>
                            Database Records:
                            <a href='tool_search/index.php?type=Project' target='_blank'>Projects (<?php echo $RECORD_COUNTS['Project']; ?>)</a> -
                            <a href='tool_search/index.php?type=Comparisons' target='_blank'>Comparisons (<?php echo $RECORD_COUNTS['Comparison']; ?>)</a> -
                            <a href='tool_search/index.php?type=Samples' target='_blank'>Samples (<?php echo $RECORD_COUNTS['Sample']; ?>)</a>
                        </div>

    	                <div class="p-3" id='statisticsSection'>

                            <div class='rounded border border-primary mb-3 p-3'>

                                <div class="w-100 my-3" id='selectedCountSection'></div>

                                <div class='w-100 mt-1'>
                                    <a href="Javascript: void(0);" id="dashboard_display_options"><i class="fas fa-angle-double-right"></i> Display Options</a>
                                </div>

                                <div class='w-100 mt-3'>
                                    <label class="font-weight-bold mr-1" for="">Sort By: </label>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input sortMethod" type="radio" name="sortMethod" id="sortByNumber" value="name" checked>
                                        <label class="form-check-label" for="">Counts</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input sortMethod" type="radio" name="sortMethod" id="sortByName" value="name">
                                        <label class="form-check-label" for="">Alphabets</label>
                                    </div>
                                </div>

                                <div class='w-100 mt-1'>
                                    <label class="font-weight-bold mr-1" for="">Comparison Type: </label>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input select_comparison_type" type="radio" name="select_comparison_type" id="select_comparison_type_private" value="private" <?php if($_GET['comparison_type'] == 'private') echo 'checked'; ?>>
                                        <label class="form-check-label" for="">Private Only</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input select_comparison_type" type="radio" name="select_comparison_type" id="select_comparison_type_public" value="public" <?php if($_GET['comparison_type'] == 'public') echo 'checked'; ?>>
                                        <label class="form-check-label" for="">Public Only</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input select_comparison_type" type="radio" name="select_comparison_type" id="select_comparison_type_both" value=""  <?php if(! isset($_GET['comparison_type']) || $_GET['comparison_type'] == '') echo 'checked'; ?>>
                                        <label class="form-check-label" for="">Both Private and Public</label>
                                    </div>

                                </div>

                            </div>

                            <div class='card-deck'>
                                <div class='card card_wide'>
                                    <div class='card-header'><span class='card-title'><strong>Comparison Category</strong></span><span class='selectedCountIndividualSection startHidden' style='padding-left:10px;'>(Selected: <span class='selectedCount'></span> out of <?php echo $RECORD_COUNTS['Comparison']; ?>)</span>
                                        <a id='resetTrigger_ComparisonCategory' href='javascript:void(0);' class='float-right'><i class='fa-fw fas fa-sync-alt' aria-hidden='true'></i> Reset</a>
                                    </div>
                                    <div class='card-block p-3'><div id='chartSection_ComparisonCategory'><i class='fa-fw fas fa-spinner fa-spin' aria-hidden='true'></i></div></div>
                                </div>
                                <div class='card card_wide'>
                                    <div class='card-header'><span class='card-title'><strong>Cell Tissue</strong></span><span class='selectedCountIndividualSection startHidden' style='padding-left:10px;'>(Selected: <span class='selectedCount'></span> out of <?php echo $RECORD_COUNTS['Comparison']; ?>)</span><a id='resetTrigger_Case_Tissue' href='javascript:void(0);' class='float-right'><i class='fa-fw fas fa-sync-alt' aria-hidden='true'></i> Reset</a>
                                    </div>
                                    <div class='card-block'>
                                        <div id='chartSection_Case_Tissue'><i class='fa-fw fas fa-spinner fa-spin' aria-hidden='true'></i></div></div>
                                </div>
                                <div class='card card_wide'>
                                    <div class='card-header'><span class='card-title'><strong>Disease State</strong></span><span class='selectedCountIndividualSection startHidden' style='padding-left:10px;'>(Selected: <span class='selectedCount'></span> out of <?php echo $RECORD_COUNTS['Comparison']; ?>)</span>
                                        <a id='resetTrigger_Case_DiseaseState' href='javascript:void(0);' class='float-right'><i class='fa-fw fas fa-sync-alt' aria-hidden='true'></i> Reset</a>
                                    </div>
                                    <div class='card-block'><div id='chartSection_Case_DiseaseState'><i class='fa-fw fas fa-spinner fa-spin' aria-hidden='true'></i></div></div>
                                </div>
                                <div class='card card_wide'>
                                    <div class='card-header'><span class='card-title'><strong>Treatment</strong></span><span class='selectedCountIndividualSection startHidden' style='padding-left:10px;'>(Selected: <span class='selectedCount'></span> out of <?php echo $RECORD_COUNTS['Comparison']; ?>)</span><a id='resetTrigger_Case_Treatment' href='javascript:void(0);' class='float-right'><i class='fa-fw fas fa-sync-alt' aria-hidden='true'></i> Reset</a>
                                    </div>
                                    <div class='card-block'>
                                        <div id='chartSection_Case_Treatment'><i class='fa-fw fas fa-spinner fa-spin' aria-hidden='true'></i></div>
                                    </div>
                                </div>
                                <div class='card card_wide'>
                                    <div class='card-header'><span class='card-title'><strong>Platform Name</strong></span><span class='selectedCountIndividualSection startHidden' style='padding-left:10px;'>(Selected: <span class='selectedCount'></span> out of <?php echo $RECORD_COUNTS['Comparison']; ?>)</span><a id='resetTrigger_PlatformName' href='javascript:void(0);' class='float-right'><i class='fa-fw fas fa-sync-alt' aria-hidden='true'></i> Reset</a>
                                    </div>
                                    <div class='card-block p-3'><div id='chartSection_PlatformName'><i class='fa-fw fas fa-spinner fa-spin' aria-hidden='true'></i></div></div>
                                </div>

                            </div>

    	                </div>

    				</div>





					<div class="d-flex mt-5 p-2 table-info">
						<h3 class="align-self-center"><i class='fas fa-list'></i> List of Comparisons</h3>
                        <a class="ml-auto align-self-center div_section_toggle" href="javascript:void(0);" target_id="dashboardSection"><i class="fas fa-minus-square"></i> Hide</a>
					</div>

                    <div id='dashboardSection'  class='w-100 my-3'>

                        <div id='tableSection' class='w-100'></div>

                        <div id='tableSectionBusy' class="hidden"><i class='fa-fw fas fa-spinner fa-spin'></i></div>
                    </div>





                    <!-- Dashboard Options Modal -->
                    <form id="form_dashboard_options" role="form">
        				<div class="modal fade" id="myModal_dashboard_options">
        					<div class="modal-dialog modal-lg" role="document">
        						<div class="modal-content">

        						    <div class="modal-header">
        								<h4 class="modal-title">Dashboard Options</h4>
        								<button type="button" class="close" data-dismiss="modal">
        								    <span aria-hidden="true">&times;</span>
        								    <span class="sr-only">Close</span>
        								</button>
        						    </div>

        						  	<div class="modal-body">
                                        <!-- $top_flds = array('ComparisonCategory', 'Case_Tissue', 'Case_DiseaseState', 'Case_Treatment', '_Platforms_ID'); -->

                                        <div class="my-3">
                                          <label class="form-check-label font-weight-bold" for="">Comparison Category: </label>
                                        </div>
                                        <div class="w-100 my-3 ml-3">
                                            <div class="form-check form-check-inline">
                                              <input class="form-check-input" type="checkbox" name="Dashboard[ComparisonCategory_Show_Top_15]" value="1"  <?php if($_SESSION['Dashboard']['ComparisonCategory_Show_Top_15']) echo "checked"; ?>>
                                              <label class="form-check-label" for="">Show Top 15 Only</label>
                                            </div>
                                        </div>

                                        <div class="my-3">
                                          <label class="form-check-label font-weight-bold" for="">Cell Tissue: </label>
                                        </div>
                                        <div class="w-100 my-3 ml-3">
                                            <div class="form-check form-check-inline">
                                              <input class="form-check-input" type="checkbox" name="Dashboard[Case_Tissue_Hide_Unknown]" value="1" <?php if($_SESSION['Dashboard']['Case_Tissue_Hide_Unknown']) echo "checked"; ?> >
                                              <label class="form-check-label" for="">Hide "Unknown Tissue"</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                              <input class="form-check-input" type="checkbox" name="Dashboard[Case_Tissue_Hide_Others]" value="1"  <?php if($_SESSION['Dashboard']['Case_Tissue_Hide_Others']) echo "checked"; ?>>
                                              <label class="form-check-label" for="">Hide "Others" Type</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                              <input class="form-check-input" type="checkbox" name="Dashboard[Case_Tissue_Show_Top_15]" value="1"  <?php if($_SESSION['Dashboard']['Case_Tissue_Show_Top_15']) echo "checked"; ?>>
                                              <label class="form-check-label" for="">Show Top 15 Only</label>
                                            </div>
                                        </div>

                                        <div class="my-3">
                                          <label class="form-check-label font-weight-bold" for="">Disease State: </label>
                                        </div>
                                        <div class="w-100 my-3 ml-3">
                                            <div class="form-check form-check-inline">
                                              <input class="form-check-input" type="checkbox" name="Dashboard[Case_DiseaseState_Hide_Unknown]" value="1"  <?php if($_SESSION['Dashboard']['Case_DiseaseState_Hide_Unknown']) echo "checked"; ?>>
                                              <label class="form-check-label" for="">Hide "Unknown Disease"</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                              <input class="form-check-input" type="checkbox" name="Dashboard[Case_DiseaseState_Hide_Normal_Control]" value="1"  <?php if($_SESSION['Dashboard']['Case_DiseaseState_Hide_Normal_Control']) echo "checked"; ?>>
                                              <label class="form-check-label" for="">Hide "normal control"</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                              <input class="form-check-input" type="checkbox" name="Dashboard[Case_DiseaseState_Hide_Others]" value="1"  <?php if($_SESSION['Dashboard']['Case_DiseaseState_Hide_Others']) echo "checked"; ?>>
                                              <label class="form-check-label" for="">Hide "Others" Type</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                              <input class="form-check-input" type="checkbox" name="Dashboard[Case_DiseaseState_Show_Top_15]" value="1"  <?php if($_SESSION['Dashboard']['Case_DiseaseState_Show_Top_15']) echo "checked"; ?>>
                                              <label class="form-check-label" for="">Show Top 15 Only</label>
                                            </div>
                                        </div>

                                        <div class="my-3">
                                          <label class="form-check-label font-weight-bold" for="">Treatment: </label>
                                        </div>
                                        <div class="w-100 my-3 ml-3">
                                            <div class="form-check form-check-inline">
                                              <input class="form-check-input" type="checkbox" name="Dashboard[Case_Treatment_Hide_Unknown]" value="1"  <?php if($_SESSION['Dashboard']['Case_Treatment_Hide_Unknown']) echo "checked"; ?>>
                                              <label class="form-check-label" for="">Hide "Unknown Treatment"</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                              <input class="form-check-input" type="checkbox" name="Dashboard[Case_Treatment_Hide_Others]" value="1"  <?php if($_SESSION['Dashboard']['Case_Treatment_Hide_Others']) echo "checked"; ?>>
                                              <label class="form-check-label" for="">Hide "Others" Type</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                              <input class="form-check-input" type="checkbox" name="Dashboard[Case_Treatment_Show_Top_15]" value="1"  <?php if($_SESSION['Dashboard']['Case_Treatment_Show_Top_15']) echo "checked"; ?>>
                                              <label class="form-check-label" for="">Show Top 15 Only</label>
                                            </div>
                                        </div>

                                        <div class="my-3">
                                          <label class="form-check-label font-weight-bold" for="">Platform Name: </label>
                                        </div>

                                        <div class="w-100 my-3 ml-3">
                                            <div class="form-check form-check-inline">
                                              <input class="form-check-input" type="checkbox" name="Dashboard[PlatformName_Hide_Generic]" value="1"  <?php if($_SESSION['Dashboard']['PlatformName_Hide_Generic']) echo "checked"; ?>>
                                              <label class="form-check-label" for="">Hide "Generic" Types</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                              <input class="form-check-input" type="checkbox" name="Dashboard[PlatformName_Show_Top_15]" value="1"  <?php if($_SESSION['Dashboard']['PlatformName_Show_Top_15']) echo "checked"; ?>>
                                              <label class="form-check-label" for="">Show Top 15 Only</label>
                                            </div>
                                        </div>

        						  	</div>

        						  	<div class="modal-footer">
                                        <button type="submit" class="btn btn-primary">Save</button>
        								<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        						  	</div>

        						</div>
        					</div>
        				</div>
                    </form>


<?php } // if($RECORD_COUNTS['Comparison'] > 0){ ?>


                </div>


                <div id='div_debug' class='w-100 my-5'></div>


				<!-- Sign In as Advanced User Modal -->
				<form id="form_sign_in_as_advanced_user" role="form">
					<div class="modal fade" id="myModal_sign_in_as_advanced_user">
						<div class="modal-dialog" role="document">
							<div class="modal-content">

							<div class="modal-header">
								<h4 class="modal-title">Sign In As Advanced User</h4>
								<button type="button" class="close" data-dismiss="modal">
									<span aria-hidden="true">&times;</span>
									<span class="sr-only">Close</span>
								</button>
							</div>

							<div class="modal-body">
								<div class="px-3">
									<div class="font-weight-bold">Admin Password:</div>
									<input name="admin_password" id="admin_password" class="form-control" placeholder="" required>
								</div>
							</div>

							<div class="modal-footer">
								<button type="submit" class="btn btn-primary">Sign In</button>
								<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
								<button type="reset" class="btn btn-secondary">Reset</button>
							</div>

							</div>
						</div>
					</div>
				</form>






				<!-- New Experiment Modal -->
                <form id="form_new_experiment" enctype="multipart/form-data" role="form">
                	<div class="modal fade" id="myModal_new_experiment">
                		<div class="modal-dialog" role="document">
                			<div class="modal-content">

                    			<div class="modal-header">
                    				<h4 class="modal-title" id="myModalLabel">Create New Experiment</h4>
                    				<button type="button" class="close" data-dismiss="modal">
                    					<span aria-hidden="true">&times;</span>
                    					<span class="sr-only">Close</span>
                    				</button>
                    			</div>

                                <div class="modal-body">
                    				<div class="px-3">
                    					<div class="font-weight-bold my-1">Name: (required)</div>
                    					<input name="Name" id="Experiment_Name" class="form-control" placeholder="Experiment Name" required>

                    					<div class="mt-3">Description:</div>
                    					<textarea name="Description" id="Description" placeholder="Experiment Description" class="form-control"></textarea>
                    				</div>
                    			</div>

                    			<div class="modal-footer">
                    				<button type="submit" class="btn btn-primary">Save</button>
                    				<button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                    				<button type="reset" class="btn btn-link">Reset</button>
                    			</div>

                			</div>
                		</div>
                	</div>
                </form>





				<!-- New Project Modal -->
				<form id="form_new_project" role="form">
					<div class="modal fade" id="myModal_new_project">
						<div class="modal-dialog" role="document">
							<div class="modal-content">

    						    <div class="modal-header">
    								<h4 class="modal-title">Create New Project</h4>
    								<button type="button" class="close" data-dismiss="modal">
    								    <span aria-hidden="true">&times;</span>
    								    <span class="sr-only">Close</span>
    								</button>
    						    </div>

    						  	<div class="modal-body">
    								<div class="px-3">
    									<div class="font-weight-bold">Name: (required)</div>
    									<input name="project_name" id="project_name" class="form-control" placeholder="Project Name" required>

    									<div class="pt-3">Description:</div>
    									<textarea name="project_description" id="project_description" placeholder="Project Description" class="form-control"></textarea>
    								</div>
    						  	</div>

    						  	<div class="modal-footer">
    								<button type="submit" class="btn btn-primary">SAVE</button>
    								<button type="button" class="btn btn-secondary" data-dismiss="modal">CANCEL</button>
    								<button type="reset" class="btn btn-secondary">RESET</button>
    						  	</div>

							</div>
						</div>
					</div>
				</form>




<?php if($RECORD_COUNTS['Comparison'] > 0){ ?>

    <style>

        .statisticCell{
        	font-family: Arial,Helvetica,sans-serif;
        	color:#FFF;
        	padding:20px;
        	text-align:center;
        	font-size:20px;
        	border-collapse:collapse;
        	border:1px solid #FFF;
        	width: 160px !important;
        	height: 150px  !important;
        	padding-top:40px;
        	vertical-align:middle;
        }

        .statisticCell a{
        	color:#FFF;
        }

        .card-block{
        	height:450px;
        	max-height:450px;
        	overflow-y:auto;

        }

        .dc-chart g.row text {
            fill: #000;
        }


        #chartSection_Case_Treatment .x.axis text {
            text-anchor: end !important;
            transform: rotate(-35deg);
        	font-size:11px;
        }

    </style>


    <script type="text/javascript">
    $(document).ready(function(){

        $(document).on('click', '#dashboard_display_options', function(){
            $('#myModal_dashboard_options').modal();
    	});

        var options_dashboard_options = {
    		url: 'bxgenomics_exe.php?action=save_dashboard_options',
    		type: 'post',
    		beforeSubmit: function(formData, jqForm, options) {
    			return true;
    		},
    		success: function(response){
                // $('#div_debug').append(response);
                $('#myModal_dashboard_options').modal('hide');
                location.reload(true);
    		}
    	};
    	$('#form_dashboard_options').ajaxForm(options_dashboard_options);


    	var chartObj_ComparisonCategory = dc.pieChart('#chartSection_ComparisonCategory');
    			$(document).on('click', '#resetTrigger_ComparisonCategory', function(){
    				chartObj_ComparisonCategory.filterAll();
    				dc.redrawAll()
    			});

    	var chartObj_Case_Tissue = dc.rowChart('#chartSection_Case_Tissue');
    			$(document).on('click', '#resetTrigger_Case_Tissue', function(){
    				chartObj_Case_Tissue.filterAll();
    				dc.redrawAll()
    			});

    	var chartObj_Case_DiseaseState = dc.rowChart('#chartSection_Case_DiseaseState');
    			$(document).on('click', '#resetTrigger_Case_DiseaseState', function(){
    				chartObj_Case_DiseaseState.filterAll();
    				dc.redrawAll()
    			});

    	var chartObj_Case_Treatment = dc.barChart('#chartSection_Case_Treatment');

            chartObj_Case_Treatment.yAxis().tickFormat(d3.format('d'));

    			$(document).on('click', '#resetTrigger_Case_Treatment', function(){
    				chartObj_Case_Treatment.filterAll();
    				dc.redrawAll()
    			});

    	var chartObj_PlatformName = dc.pieChart('#chartSection_PlatformName');
    			$(document).on('click', '#resetTrigger_PlatformName', function(){
    				chartObj_PlatformName.filterAll();
    				dc.redrawAll()
    			});




        function reloadTable(obj){

            var count = parseInt(obj.length);
        	count = count.toLocaleString();
        	$('.selectedCount').html(count);

        	$('.selectedCountIndividualSection').show();

        	$('#tableSection').empty();
        	$('#tableSectionBusy').removeClass('hidden');

        	var selected_comparison_indexes = [];

        	for (var tempKey in obj) {
        		if (!obj.hasOwnProperty(tempKey)) continue;
        		selected_comparison_indexes.push( obj[tempKey].ComparisonIndex );
        	}

            $.ajax({
                type: 'POST',
                url: '<?php echo $_SERVER['PHP_SELF']; ?>?action=refresh_table',
                data: {'comparisons': selected_comparison_indexes },
                success: function(responseText){

                    $('#tableSectionBusy').addClass('hidden');
                    $('#tableSection').html(responseText);

                    $('.datatables').DataTable({ "pageLength": 10, "lengthMenu": [[10, 100, 500, 1000], [10, 100, 500, 1000]], dom: 'Blfrtip', buttons: ['colvis','copy','csv'], "order": [[ 1, 'asc' ]], "columnDefs": [ { "targets": 0, "orderable": false } ] });

                }
            });
        }


    	d3.csv('<?php echo $csv_file; ?>', function (data) {
            var totalWidth 	= 990;

            var processedData 	= crossfilter(data);
            var all 			= processedData.groupAll();
    		var rowBarHeight	= 18;
    		var chartHeight		= 390;


            function getValues_ComparisonCategory(sourceGroup){
    			return {
    				all:function(){
    					return sourceGroup.all();
    				}
    			};
    		}

    		function getValues_Case_Tissue(sourceGroup){
    			return {
    				all:function(){
    					return sourceGroup.all();
    				}
    			};
    		}

    		function getValues_Case_DiseaseState(sourceGroup){
    			return {
    				all:function(){
    					return sourceGroup.all();
    				}
    			};
    		}

    		function getValues_Case_Treatment(sourceGroup){
    			return {
    				all:function(){
    					return sourceGroup.all();
    				}
    			};
    		}

    		function getValues_PlatformName(sourceGroup){
    			return {
    				all:function(){
    					return sourceGroup.all();
    				}
    			};
    		}


    		var Dimension_ComparisonCategory = processedData.dimension(function (d) {
    			return d['ComparisonCategory'];
    		});


    		var Group_ComparisonCategory	= getValues_ComparisonCategory(Dimension_ComparisonCategory.group().reduceCount());


    		chartObj_ComparisonCategory.width(totalWidth / 1.1)
    				.height(parseInt(chartHeight * 0.8))
    				.slicesCap(40)
    				.innerRadius(40)
    				.dimension(Dimension_ComparisonCategory)
    				.group(Group_ComparisonCategory)
    				.renderLabel(true)
    				.ordinalColors(d3.scale.category10().range())
    				.transitionDuration(500)
    				.drawPaths(true)
    				.title(function (d) {
    					return d.key + ': ' + d.value;
    				})
    				.label(function (d) {
    					return d.value;
    				})
    				.on('filtered', function (){
    					reloadTable(Dimension_ComparisonCategory.top(Infinity));
    				})
    				.legend(dc.legend());




    		var Dimension_Case_Tissue = processedData.dimension(function (d) {
    			return d['Case_Tissue'];
    		});

    		var Group_Case_Tissue	= getValues_Case_Tissue(Dimension_Case_Tissue.group().reduceCount());




    		var Case_Tissue_Height = parseInt((Dimension_Case_Tissue.group().size() + 2)*(rowBarHeight + 5));
    		if ((Case_Tissue_Height < chartHeight) || (isNaN(Case_Tissue_Height))){
    			Case_Tissue_Height = chartHeight;
    		}

    		chartObj_Case_Tissue.width(totalWidth / 2.1)
    			.height(Case_Tissue_Height)
    			.fixedBarHeight(rowBarHeight)
    			.margins({top: 20, left: 10, right: 10, bottom: 20})
    			.dimension(Dimension_Case_Tissue)
    			.ordinalColors(d3.scale.category10().range())
    			.renderLabel(true)
    			.ordering(function (d) {

    				if ($('#sortByName').prop('checked')){
    					return d.key;
    				} else {
    					return -d.value;
    				}
    			})
    			.group(Group_Case_Tissue)
    			.elasticX(true)
    			.on('filtered', function (){
    				reloadTable(Dimension_Case_Tissue.top(Infinity));
    			})
    			.label(function (d) {
    				return d.key + "\n" + ' (' + d.value + ')';
    			})
    			.xAxis().tickFormat(d3.format("d"));




    				var Dimension_Case_DiseaseState = processedData.dimension(function (d) {
    			return d['Case_DiseaseState'];
    		});

    		var Group_Case_DiseaseState	= getValues_Case_DiseaseState(Dimension_Case_DiseaseState.group().reduceCount());



    		var Case_DiseaseState_Height = parseInt((Dimension_Case_DiseaseState.group().size() + 2)*(rowBarHeight + 5));

    		if ((Case_DiseaseState_Height < chartHeight) || (isNaN(Case_DiseaseState_Height))){
    			Case_DiseaseState_Height = chartHeight;
    		}

    		chartObj_Case_DiseaseState.width(totalWidth / 2.1)
    			.height(Case_DiseaseState_Height)
    			.fixedBarHeight(rowBarHeight)
    			.margins({top: 20, left: 10, right: 10, bottom: 20})
    			.dimension(Dimension_Case_DiseaseState)
    			.ordinalColors(d3.scale.category10().range())
    			.renderLabel(true)
    			.ordering(function (d) {

    				if ($('#sortByName').prop('checked')){
    					return d.key;
    				} else {
    					return -d.value;
    				}
    			})
    			.group(Group_Case_DiseaseState)
    			.elasticX(true)
    			.on('filtered', function (){
    				reloadTable(Dimension_Case_DiseaseState.top(Infinity));
    			})
    			.label(function (d) {
    				return d.key + "\n" + ' (' + d.value + ')';
    			})
    			.xAxis().tickFormat(d3.format("d"));




    		var Dimension_Case_Treatment = processedData.dimension(function (d) {
    			return d['Case_Treatment'];
    		});

    		var Group_Case_Treatment	= getValues_Case_Treatment(Dimension_Case_Treatment.group().reduceCount());



    		chartObj_Case_Treatment.height(parseInt(chartHeight))
    			.width(totalWidth / 1.5)
    			.margins({top: 40, left: 50, right: 10, bottom: 150})
    			.x(d3.scale.ordinal())
    			.xUnits(dc.units.ordinal)
    			.yAxisLabel('')
    			.brushOn(true)
    			.elasticY(true)
    			.dimension(Dimension_Case_Treatment)
    			.barPadding(0.1)
    			.outerPadding(0.05)
    			.ordering(function (d) {
    				if ($('#sortByName').prop('checked')){
    					return d.key;
    				} else {
    					return -d.value;
    				}
    			})
    			.title(function (d) {
    				return d.key + ': ' + d.value;
    			})
    			.group(Group_Case_Treatment)
    			.on('renderlet', function (chart){

    				//Check if labels exist
    				var gLabels = chart.select(".labels");
    				if (gLabels.empty()){
    					gLabels = chart.select(".chart-body").append('g').classed('labels', true);
    				}

    				var gLabelsData = gLabels.selectAll("text").data(chart.selectAll(".bar")[0]);

    				gLabelsData.exit().remove(); //Remove unused elements

    				gLabelsData.enter().append("text") //Add new elements

    				gLabelsData
    				.attr('text-anchor', 'middle')
    				.attr('fill',  function(d){
    					if (+d.getAttribute('y') > 0){
    						return 'black';
    					} else {
    						return 'white';
    					}
    				})
    				.text(function(d){
    					return d3.select(d).data()[0].data.value;
    				})
    				.attr('x', function(d){
    					return +d.getAttribute('x') + (d.getAttribute('width')/2);
    				})
    				.attr('y', function(d){
    					if (+d.getAttribute('y') > 0){
    						return +d.getAttribute('y') - 5;
    					} else {
    						return +d.getAttribute('y') + 15;
    					}
    				})
    				.attr('style', function(d){
    					//if (+d.getAttribute('height') < 18) return "display:none";
    				});

    			})
    			.on('filtered', function (){
    				reloadTable(Dimension_Case_Treatment.top(Infinity));
    			});


    		var Dimension_PlatformName = processedData.dimension(function (d) {
    			return d['PlatformName'];
    		});

    		var Group_PlatformName	= getValues_PlatformName(Dimension_PlatformName.group().reduceCount());


    		chartObj_PlatformName.width(totalWidth / 1.1)
    				.height(parseInt(chartHeight * 0.8))
    				.slicesCap(40)
    				.innerRadius(40)
    				.dimension(Dimension_PlatformName)
    				.group(Group_PlatformName)
    				.renderLabel(true)
    				.ordinalColors(d3.scale.category10().range())
    				.transitionDuration(500)
    				.drawPaths(true)
    				.title(function (d) {
    					return d.key + ': ' + d.value;
    				})
    				.label(function (d) {
    					return d.value;
    				})
    				.on('filtered', function (){
    					reloadTable(Dimension_PlatformName.top(Infinity));
    				})
    				.legend(dc.legend());



            var recordCount = dc.dataCount('#selectedCountSection')
                .dimension(processedData)
                .group(all)
                .html({
                    // some: "<div class='mb-3 text-danger'>Tip: Please click on the graphs to apply filters.</div><div class='my-3'><strong>%filter-count</strong> out of <strong><?php echo $RECORD_COUNTS['Comparison']; ?></strong> comparisons have been selected. </div> <div class='my-3'><a class='btn btn-sm btn-primary' href='javascript:dc.filterAll(); dc.renderAll();'><i class='fa-fw fas fa-sync-alt' aria-hidden='true'></i> Reset all filters</a> &nbsp; <a class='btn btn-sm btn-outline-success' href='<?php echo $_SERVER['PHP_SELF']; ?>?type=selected' target='_blank'><i class='fa-fw fas fa-chart-pie' aria-hidden='true'></i> Create a new dashboard based on selected comparisons</a></div>",
                    some: "<div class='mb-3 text-danger'>Tip: Please click on the graphs to apply filters.</div><div class='my-3'><strong>%filter-count</strong> out of <strong><?php echo $RECORD_COUNTS['Comparison']; ?></strong> comparisons have been selected. <a class='btn btn-sm btn-primary' href='javascript:dc.filterAll(); dc.renderAll();'><i class='fa-fw fas fa-sync-alt' aria-hidden='true'></i> Reset all filters</a> </div>",
                    all: "<div class='mb-3 text-danger'>Tip: Please click on the graphs to apply filters.</div><div class='my-3'>Showing all comparisons (<strong><?php echo $RECORD_COUNTS['Comparison']; ?></strong>).</div>"
                });


            dc.renderAll();
    		reloadTable(Dimension_PlatformName.top(Infinity));

    		$('.card-block .fa-spinner').hide();

        });


    	$(document).on('change', '.sortMethod', function(){
    		dc.renderAll();
    	});

        $(document).on('change', '.select_comparison_type', function(){
            window.location = "<?php echo $_SERVER['PHP_SELF']; ?>?comparison_type=" + $(this).val();
    	});

    });


    </script>

<?php } // if($RECORD_COUNTS['Comparison'] > 0){ ?>






            </div>
		    <?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>
		</div>
	</div>
</body>
</html>