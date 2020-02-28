<?php
include_once('config.php');


?><!DOCTYPE html>
<html lang="en">
<head>
    <?php include_once($BXAF_CONFIG['BXAF_PAGE_HEADER']); ?>
    <script src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/jquery/jquery.form.min.js"></script>

    <link   href='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/datatables/datatables.min.css.php' rel='stylesheet' type='text/css'>
	<script src='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/datatables/datatables.min.js.php'></script>

</head>
<body>

<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_MENU'])) include_once($BXAF_CONFIG['BXAF_PAGE_MENU']); ?>
<div id="bxaf_page_wrapper" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_WRAPPER']; ?>">
<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_LEFT'])) include_once($BXAF_CONFIG['BXAF_PAGE_LEFT']); ?>
<div id="bxaf_page_right" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT']; ?>">
<div id="bxaf_page_right_content" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT_CONTENT']; ?>">
<div class="container-fluid">

    <?php $help_key = 'Meta Analysis'; include_once( dirname(__DIR__) . "/help_content.php"); ?>

    <div class="w-100">
        <a href="my_results.php">
            <i class="fas fa-angle-double-right"></i> Saved Meta Analysis Results
        </a>
    </div>

    <div class="w-100">
        <form class="w-100" id="form_main">
            <div class="row my-4">
                <div class="col-md-6">
    				<?php include_once(dirname(__DIR__) . '/tool_save_lists/modal_gene.php'); ?>
                </div>

                <div class="col-md-6">
    				<?php include_once(dirname(__DIR__) . '/tool_save_lists/modal_comparison.php'); ?>
                </div>
            </div>

            <?php
                $pre_checked = array('GeneName', 'EntrezID', 'Description');
                $type = 'Gene';
                echo '<div class="w-100 my-1">';
                    echo '
                    <p class="w-100 mb-1">
                        <label class="font-weight-bold">' . $type . ' Attributes:</label>
                        <span class="table-success mx-2 p-2">( <span id="number_attributes_selected">' . count($pre_checked) . '</span> selected )</span>
                        <a href="javascript:void(0);" onclick="if($(\'#div_attributes\').hasClass(\'hidden\')) $(\'#div_attributes\').removeClass(\'hidden\'); else $(\'#div_attributes\').addClass(\'hidden\'); "> <i class="fas fa-angle-double-right"></i> Show Attributes </a>
                    </p>';

                    echo '<div id="div_attributes" class="w-100 hidden">';
                        foreach ($BXAF_CONFIG['TBL_BXGENOMICS_FIELDS']['Gene'] as $colname) {
                            $caption = str_replace('_', ' ', $colname);
                            echo '<div class="form-check form-check-inline">
                                <input class="form-check-input checkbox_check_individual" type="checkbox" category="' . $type . '" value="' . $colname . '" name="attributes_' . $type . '[]"' . (in_array($colname, $pre_checked) ? " checked " : "") . '>';
                                echo '<label class="form-check-label">' . $caption . '</label>';
                            echo '</div>';
                        }

						echo "<a href='Javascript: void(0);' class='ml-3' onClick=\"$('.checkbox_check_individual').prop('checked', true ); $('#number_attributes_selected').html('all'); \" ><i class='fas fa-check'></i> Check All</a>";
						echo "<a href='Javascript: void(0);' class='ml-3' onClick=\"$('.checkbox_check_individual').prop('checked', false ); $('#number_attributes_selected').html('0'); \" ><i class='fas fa-times'></i> Check None</a>";


                    echo '</div>';
                echo '</div>';

            ?>



            <!------------------------------------------------------------------------------->
            <!-- Advanced Settings -->
            <!------------------------------------------------------------------------------->
            <div class="w-100 my-3">
                <strong>Advanced Settings: </strong>&nbsp;&nbsp;
                <a
                  href="javascript:void(0);"
                  onclick="$('#div_advanced_settings').slideToggle(200)">
                  <i class="fas fa-angle-double-right"></i>
                  Toggle
                </a>
                <div class="alert alert-info w-100 hidden" id="div_advanced_settings">
                  <table class="table table-sm table-noborder mb-0">
                    <tr>
                      <th style="width: 300px;">Missing data allowed for P-value: </th>
                      <td>
                        <input
                          type="number" min="0" max="1"
                          step="0.1" value="0.3"
                          class="form-control"
                          id="miss_tol" name="miss_tol"
                          style="width:100px;">
                      </td>
                    </tr>
                    <tr>
                      <th>Log2FC cutoff <span class="text-muted">(1 is 2 fold)</span>: </th>
                      <td>
                        <input
                          type="number" min="0"
                          value="1" step="0.1"
                          class="form-control"
                          id="logFC_cutoff" name="logFC_cutoff"
                          style="width:100px;">
                      </td>
                    </tr>
                    <tr>
                      <th>Statistical type for changed gene: </th>
                      <td>
                        <select
                          class="custom-select"
                          id="sig_type" name="sig_type"
                          style="width:100px;">
                          <option value="FDR">FDR</option>
                          <option value="P-value">P-value</option>
                        </select>
                      </td>
                    </tr>
                    <tr>
                      <th>Statistical cutoff: </th>
                      <td>
                        <input
                          type="number" min="0" max="1"
                          step="0.01" value="0.05"
                          class="form-control"
                          id="sig_cutoff" name="sig_cutoff"
                          style="width:100px;">
                      </td>
                    </tr>
                  </table>

                </div>
            </div>


            <div class="w-100 my-3">
                <button class="btn btn-primary mt-3" id="btn_submit"> <i class="fas fa-upload"></i> Submit </button>
            </div>

        </form>

    </div>

    <div id="div_results"></div>

</div>
</div>
<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>
</div>
</div>



<script>

$(document).ready(function() {

    // Update number selected
	$(document).on('change', '.checkbox_check_individual', function() {
		var number = 0;
		$('.checkbox_check_individual').each(function(i, e) {
			if ($(e).is(':checked')) number++;
		});
		$('#number_attributes_selected').html(number);
	});



	var options = {
		url: 'exe.php?action=submit_data',
 		type: 'post',
	    beforeSubmit: function(formData, jqForm, options) {

			$('#div_results').html('');

			$('#btn_submit')
				.attr('disabled', '')
				.children(':first')
				.removeClass('fa-upload')
				.addClass('fa-spin fa-spinner');

			return true;
		},
    	success: function(response){

			$('#btn_submit')
				.removeAttr('disabled')
				.children(':first')
				.addClass('fa-upload')
				.removeClass('fa-spin fa-spinner');

			// $('#div_results').html(response);

            var type = response.type;
            if (type == 'Error') {
                $('#div_results').html(response.detail);
            } else {
                window.location = 'meta_result.php?time=' + response.time + '<?php echo $_SERVER['QUERY_STRING'] == '' ? ("&" . $_SERVER['QUERY_STRING']) : ""; ?>'
            }

			return true;
		}
	};
	$('#form_main').ajaxForm(options);


});

</script>


</body>
</html>