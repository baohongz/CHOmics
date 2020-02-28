<?php
include_once("config.php");

$comparison_id = '';
$COMPARISON_NAME_DEFAULT = '';
$comparison_names = array();

if (isset($_GET['comparison_name']) && trim($_GET['comparison_name']) != ''){
    $sql = "SELECT `ID` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS']}` WHERE `Species` = '" . $_SESSION['SPECIES_DEFAULT'] . "' AND " . $BXAF_CONFIG['QUERY_DEFAULT_FILTER'] . " AND `Name` = ?s";
    $comparison_id = $BXAF_MODULE_CONN -> get_one($sql, trim($_GET['comparison_name']) );
    if($comparison_id != '') $COMPARISON_NAME_DEFAULT = trim($_GET['comparison_name']);
}
else if (isset($_GET['id']) && trim($_GET['id']) != '' || isset($_GET['comparison_id']) && trim($_GET['comparison_id']) != '') {

    if (isset($_GET['id']) && trim($_GET['id']) != '') $comparison_id = intval($_GET['id']);
    else $comparison_id = intval($_GET['comparison_id']);

    $sql = "SELECT `Name` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS']}` WHERE `Species` = '" . $_SESSION['SPECIES_DEFAULT'] . "' AND " . $BXAF_CONFIG['QUERY_DEFAULT_FILTER'] . " AND `ID`= ?i";
    $COMPARISON_NAME_DEFAULT = $BXAF_MODULE_CONN -> get_one($sql, $comparison_id);
    if($COMPARISON_NAME_DEFAULT == '') $comparison_id = '';
}
else if (isset($_GET['project_id']) && intval($_GET['project_id']) >= 0) {
    $sql = "SELECT `ID`, `Name` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS']}` WHERE `Species` = '" . $_SESSION['SPECIES_DEFAULT'] . "' AND " . $BXAF_CONFIG['QUERY_DEFAULT_FILTER'] . " AND `_Projects_ID` = ?i";
    $comparison_names = $BXAF_MODULE_CONN -> get_assoc('ID', $sql, intval($_GET['project_id']) );

    if(is_array($comparison_names) && count($comparison_names) > 0){
        $comparison_id = key($comparison_names);
        $COMPARISON_NAME_DEFAULT = $comparison_names[$comparison_id];
        unset($comparison_names[$comparison_id]);
    }
}


$custom_gene_list = false;
if (isset($_GET['geneset']) || isset($_GET['geneset_id']) || isset($_GET['gene_name']) || isset($_GET['gene_names']) || isset($_GET['gene_id']) || isset($_GET['gene_ids']) || isset($_GET['gene_time']) || isset($_GET['gene_list']) ) {
    $custom_gene_list = true;
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include_once($BXAF_CONFIG['BXAF_PAGE_HEADER']); ?>

    <script src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/jquery/jquery.form.min.js"></script>

    <link   href='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/datatables/datatables.min.css.php' rel='stylesheet' type='text/css'>
	<script src='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/datatables/datatables.min.js.php'></script>

    <script src="../library/highcharts.js.php"></script>
    <script src="../library/exporting-5.0.0.js"></script>

</head>
<body>

<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_MENU'])) include_once($BXAF_CONFIG['BXAF_PAGE_MENU']); ?>
<div id="bxaf_page_wrapper" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_WRAPPER']; ?>">
<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_LEFT'])) include_once($BXAF_CONFIG['BXAF_PAGE_LEFT']); ?>
<div id="bxaf_page_right" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT']; ?>">
<div id="bxaf_page_right_content" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT_CONTENT']; ?>">
<div class="container-fluid">

    <?php $help_key = 'Volcano Plot'; include_once( dirname(__DIR__) . "/help_content.php"); ?>

    <?php if($comparison_id != '') echo '<div class="w-100 my-3"><a href="../tool_search/view.php?type=comparison&id=' . $comparison_id . '" class=""><i class="fas fa-undo"></i> Back to Comparison Details</a></div>'; ?>


    <div class="w-100 my-3">

        <form id="form_valcano_chart" method="post">

            <input name="chart_number" value="<?php echo count($comparison_names) + 1; ?>" id="chart_number" hidden>

            <div class="w-100 mx-0" id="chart_setting_all_container">

                <div class="chart_setting_single_container w-100">

                    <div class="row">
                      <div class="col-md-2 mt-2 text-md-right text-muted">
                        Comparison Name:
                      </div>
                      <div class="col-md-10">

                         <div class="input-group mb-3" style="max-width:30em;">

                          <input id="comparison_id_0" name="comparison_id[]" name="comparison_id[]" class="form-control input_comparison_id" value="<?php echo $COMPARISON_NAME_DEFAULT; ?>" required>

                          <div class="input-group-append">
                            <button class="btn_search_comparison btn btn-success" inhouse="false" type="button" index="0">
                              <i class="fas fa-search"></i> Comparisons
                            </button>
                          </div>
                        </div>

                        <span class="text-muted">Please enter the comparison id, e.g., GSE43696.GPL6480.test2</span>
                      </div>
                    </div>

                    <div class="row mt-3">
                      <div class="col-md-2 mt-2 text-md-right text-muted">
                        Y-axis Statistics:
                      </div>
                      <div class="col-md-10 form-inline">

                        <input class="mr-2" type="radio" name="volcano_y_statistics_0" value="P-Value">
                        <label class="mr-2">P-value</label>
                        <input class="mr-2" type="radio" name="volcano_y_statistics_0" value="FDR" checked>
                        <label class="mr-2">FDR</label>

                        <label class="ml-4"> Cutoff: </label>
                        <select class="form-control volcano_statistic_cutoff custom-select mx-2" name="volcano_statistic_cutoff[]" style="width:8em;">
                          <option value="0.05">0.05</option>
                          <option value="0.01">0.01</option>
                          <option value="0.001">0.001</option>
                          <option value="enter_value">Enter Value</option>
                        </select>
                        <input class="form-control" name="volcano_statistic_custom_cutoff[]" placeholder="Custom Cutoff" style="width:10.3em;" hidden>

                      </div>
                    </div>

                    <div class="row mt-2">
                      <div class="col-md-2 mt-2 text-md-right p-t-sm text-muted">
                        Fold Change Cutoff:
                      </div>
                      <div class="col-md-10 form-inline">
                        <select class="form-control volcano_fc_cutoff custom-select" name="volcano_fc_cutoff[]" style="width:8.6em;">
                          <option value="2">2</option>
                          <option value="4">4</option>
                          <option value="8">8</option>
                          <option value="enter_value">Enter Value</option>
                        </select>
                        <input class="form-control ml-2" name="volcano_fc_custom_cutoff[]" placeholder="Custom Cutoff" style="width:10.3em;" hidden>
                      </div>
                    </div>

                    <div class="row mt-2">
                      <div class="col-md-2 mt-2 text-md-right p-t-sm text-muted">
                        Chart Name
                      </div>
                      <div class="col-md-10">
                        <input class="form-control" name="chart_name[]" value="Volcano Chart" style="width:20em;" required>
                      </div>
                    </div>

                    <hr />

                </div>

            </div>


            <div class="row">
              <div class="col-md-2 text-md-right text-muted">
                Show Gene Symbol：
              </div>
              <div class="col-md-10">
                <label>
                  <input type="radio" class="volcano_show_gene" name="volcano_show_gene" id="volcano_show_gene_auto" value="auto" <?php if(! $custom_gene_list) echo 'checked'; ?>>
                  Auto (based on cutoff)
                </label>
                &nbsp;&nbsp;
                <label>
                  <input type="radio" class="volcano_show_gene" name="volcano_show_gene" id="volcano_show_gene_customize" value="customize" <?php if($custom_gene_list) echo 'checked'; ?>>
                  Customize
                </label>

                <div class="row mt-1 <?php if(! $custom_gene_list) echo 'hidden'; ?>" id="div_gene_list">

                    <?php include_once(dirname(__DIR__) . '/tool_save_lists/modal_gene.php'); ?>

                </div>

              </div>
            </div>


            <div class="row mt-3">
                <div class="col-md-2">&nbsp;</div>
                <div class="col-md-10 form-group form-inline">

                    <button id="btn_submit" class="btn btn-primary"><i class="fas fa-upload"></i> Submit</button>

                    <label class="text-muted mx-2">Chart Width (px): </label>
                    <input class="form-control" name="volcano_chart_width" value="1000" style="width:5em;">

                    <label class="mx-2 text-muted">Chart Height (px): </label>
                    <input class="form-control" name="volcano_chart_height" value="800" style="width:5em;">

                    <a class="mx-5" href="javascript:void(0);" id="btn_add_chart"><i class="fas fa-angle-double-right"></i> Add A New Chart</a>

                </div>
            </div>


        </form>

    </div>


    <div class="w-100 my-3" id="div_results"></div>

    <div class="w-100 my-3" id="table_div"></div>

    <div id="debug" class="w-100"></div>


</div>
</div>
<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>
</div>
</div>






<div class="modal" id="modal_select_comparison" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Search Comparison</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body">
        <?php
          echo '
          <table class="table table-bordered table-striped table-hover w-100 datatables" id="table_search_comparison">
            <thead>
            <tr class="table-info">
              <th>Name</th>
              <th>Disease State</th>
              <th>Actions</th>
            </tr>
            </thead>
            <tbody>';

            $sql = "SELECT `ID`, `Name`, `Case_DiseaseState` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS']}` WHERE `Species` = '" . $_SESSION['SPECIES_DEFAULT'] . "' AND " . $BXAF_CONFIG['QUERY_DEFAULT_FILTER'] . " ORDER BY `Name`";
            $comparisons = $BXAF_MODULE_CONN -> get_all($sql);

            foreach ($comparisons as $comparison) {
              echo '
              <tr>
                <td class="text-nowrap"><a target="_blank" href="../tool_search/view.php?type=comparison&id=' . $comparison['ID'] . '">' . $comparison['Name'] . '</a></td>
                <td>' . $comparison['Case_DiseaseState'] . '</td>
                <td><a href="javascript:void(0);" class="btn_select_search_comparison ml-2" content="' . $comparison['Name'] . '"><i class="fas fa-check"></i> Select</a></td>
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





<script>

$(document).ready(function() {

    <?php
        if ($COMPARISON_NAME_DEFAULT != '') {
        	echo "setTimeout(function(){ $('#btn_submit').trigger('click'); },3000);";
        }
    ?>


    // Select Comparison
	var index_select = 0;
  	$(document).on('click', '.btn_search_comparison', function() {
        index_select = $(this).attr('index');

        $('#table_search_comparison').DataTable();
  		$('#modal_select_comparison').modal('show');
  	});

  	$(document).on('click', '.btn_select_search_comparison', function() {
  		var comparison_name = $(this).attr('content');
        $('.input_comparison_id').each(function(index, element) {
			if (index == index_select) {
				$(element).val(comparison_name);
			}
		});
  		$('#modal_select_comparison').modal('hide');
  	});




  // Change Select
	$(document).on('change', '.volcano_fc_cutoff, .volcano_statistic_cutoff', function() {
		if ($(this).val() == 'enter_value') {
			$(this).next().removeAttr('hidden');
		} else {
			$(this).next().attr('hidden', '');
		}
	});



	// Change Gene Symbol Option
	$(document).on('change', '.volcano_show_gene', function() {
		if ($('#volcano_show_gene_auto').is(':checked')) {
			$('#div_gene_list').slideUp();
		} else if ($('#volcano_show_gene_customize').is(':checked')) {
			$('#div_gene_list').slideDown();
		}
	});


    // Change # Genes Displayed
    $(document).on('change', '#volcano_gene_number_all', function() {
        if ($(this).is(":checked")) {
            bootbox.alert('<h4 class="red">Warning:</h4> It may take over 1 minute to display all genes.');
        }
    });


	// Add New Chart
	$(document).on('click', '#btn_add_chart', function() {

		var current_index = $('.input_comparison_id').length;
        var comparison_name = '';
		$.ajax({
			type: 'POST',
			url: 'exe.php?action=add_new_chart',
			data: {'current_index': current_index, 'comparison_name': comparison_name},
			success: function(responseText) {
				$('#chart_setting_all_container').append(responseText);
				$('#chart_number').val(parseInt(current_index) + 1);
			}
		});
	});

<?php
    // Multipe initial comparisons
    if(is_array($comparison_names) && count($comparison_names) > 0){
        foreach($comparison_names as $comparison_id=>$comparison_name){
            echo "
                    $.ajax({
            			type: 'POST',
            			url: 'exe.php?action=add_new_chart',
            			data: {'current_index': $('.input_comparison_id').length, 'comparison_name': '$comparison_name'},
            			success: function(responseText) {
            				$('#chart_setting_all_container').append(responseText);
            				$('#chart_number').val(parseInt(current_index) + 1);
            			}
            		});
            ";

        }
    }
?>


	// Generate Chart
	var options = {
		url: 'exe.php?action=volcano_generate_chart',
 		type: 'post',
        beforeSubmit: function(formData, jqForm, options) {
			if ($('#comparison_id_0').val() == '') {
				bootbox.alert('Please select a comparison.');
                $('#comparison_id_0').focus();
				return false;
			}
			$('#btn_submit').children(':first').removeClass('fa-upload').addClass('fa-spin fa-spinner');
			$('#btn_submit').attr('disabled', '');

            $('#div_results').html("<i class='fas fa-spin fa-spinner'></i>");
			return true;
		},
        success: function(responseText, statusText){
			$('#btn_submit').children(':first').removeClass('fa-spin fa-spinner').addClass('fa-upload');
			$('#btn_submit').removeAttr('disabled');
			if(responseText.substring(0, 5) == 'Error'){
				bootbox.alert(responseText);
			} else {
				$('#div_results').html(responseText);

                $("#volcano_table").DataTable({"pageLength": 100, "lengthMenu": [[10, 100, 500, 1000], [10, 100, 500, 1000]], dom: 'Blfrtip', buttons: ['colvis','copy','csv'] });

			}

			return true;
		}
    };
	$('#form_valcano_chart').ajaxForm(options);

});


</script>

</body>
</html>