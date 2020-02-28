<?php
include_once("config.php");

if(isset($_SESSION['TOOL_CORR']) ) unset($_SESSION['TOOL_CORR']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<?php include_once($BXAF_CONFIG['BXAF_PAGE_HEADER']); ?>

	<script src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/jquery/jquery.form.min.js"></script>

	<link   href='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/datatables/datatables.min.css.php' rel='stylesheet' type='text/css'>
	<script src='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/datatables/datatables.min.js.php'></script>

	<link  href="../library/canvasxpress/canvasxpress-18.5/canvasXpress.css" rel="stylesheet">
	<script src="../library/canvasxpress/canvasxpress-18.5/canvasXpress.min.js.php"></script>

</head>
<body>

<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_MENU'])) include_once($BXAF_CONFIG['BXAF_PAGE_MENU']); ?>
<div id="bxaf_page_wrapper" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_WRAPPER']; ?>">
<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_LEFT'])) include_once($BXAF_CONFIG['BXAF_PAGE_LEFT']); ?>
<div id="bxaf_page_right" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT']; ?>">
<div id="bxaf_page_right_content" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT_CONTENT']; ?>">
<div class="container-fluid">

    <?php $help_key = 'Correlation Tool'; include_once( dirname(__DIR__) . "/help_content.php"); ?>


	<form class="w-100" id="form_correlation">

		<div class="row w-100">
            <div class="col-md-6">
              <?php include_once(dirname(__DIR__) . '/tool_save_lists/modal_gene.php'); ?>
			  <div class="text-muted my-3">Note: You must enter <span class="text-success">one or more gene names</span>.</div>
            </div>

            <div class="col-md-6">
              <?php include_once(dirname(__DIR__) . '/tool_save_lists/modal_sample.php'); ?>
			  <div class="text-muted my-3">Note: Please enter <span class='text-danger'>three or more sample names</span>.</div>
            </div>

		</div>

		<div class="w-100 my-3" id="div_advanced_options">

			<h4 class=''>Advanced Options</h4>
			<hr class="w-100" />


			<div class='my-3 font-weight-bold'>
				How do you like to compare the genes?
			</div>

			<div class='form-check'>
				<label class='form-check form-check-inline'>
					<input class='form-check-input comparison' type='radio' id='comparison_1' name='comparison' value='1' onClick="$('.adv_all_search_option').removeClass('hidden');" />
					<label class="form-check-label" for="">Calculate the correlations against all available genes in database</label>
				</label>

				<label class='form-check form-check-inline'>
 					<input class='form-check-input comparison' type='radio' id='comparison_2' name='comparison' value='2'  checked onClick="$('.adv_all_search_option').addClass('hidden');" />
					<label class="form-check-label" for="">Calculate the correlations among the entered genes only</label>
				</label>
			</div>


			<div class='form-inline ml-5 my-3 hidden adv_all_search_option mt-3'>

				<label for='method'><strong>Direction of Correlation:</strong></label>

				<select class='custom-select ml-3' id='direction' name='direction' />

					<?php
						$selected = 1;
						$values = array();
						$values[1] = 'Both';
						$values[2] = 'Positive';
						$values[3] = 'Negative';

						foreach($values as $tempKey => $tempValue){
							$checked = '';
							if ($selected == $tempKey){
								$checked = "selected='selected'";
							}
							echo "<option value='{$tempKey}' {$checked}>{$tempValue}</option>";
						}
					?>

				</select>

			</div>


			<div class='form-inline ml-5 my-3 hidden adv_all_search_option'>

				<label for='method'><strong>Cut-off of Correlation Coefficient:</strong></label>

				<select class='custom-select ml-3' id='cutoff' name='cutoff' />

					<?php

						$selected = '0.80';
						$values = array();

						$values['0.995'] 	= '0.995';
						$values['0.95'] 	= '0.95';
						$values['0.90'] 	= '0.90';
						$values['0.85'] 	= '0.85';
						$values['0.80'] 	= '0.80';
						$values['0.76'] 	= '0.76';
						$values['0.54'] 	= '0.54';
						$values['0'] 		= 'No Cut-off';

						foreach($values as $tempKey => $tempValue){
							$checked = '';

							if ($selected == $tempKey){
								$checked = "selected='selected'";
							}
							echo "<option value='{$tempKey}' {$checked}>{$tempValue}</option>";
						}
					?>

				</select>

			</div>


			<div class='form-inline ml-5 my-3 hidden adv_all_search_option'>

				<label for='method'><strong>Maximum Number of Top Matched Genes:</strong></label>

				<select class='custom-select ml-3' id='limit' name='limit' />

					<?php

						$selected = '100';
						$values = array();

						$values['10'] 		= '10';
						$values['20'] 		= '20';
						$values['50'] 		= '50';
						$values['100'] 		= '100';
						$values['200'] 		= '200';
						$values['500'] 		= '500';
						$values['1000'] 	= '1000';
						// $values['0'] 		= 'All';

						foreach($values as $tempKey => $tempValue){
							$checked = '';
							if ($selected == $tempKey){
								$checked = "selected='selected'";
							}
							echo "<option value='{$tempKey}' {$checked}>{$tempValue}</option>";
						}
					?>
				</select>

			</div>

			<div class='my-3 font-weight-bold'>
				Correlation Method:
			</div>

			<div class='form-check'>
				<div class="form-check form-check-inline">
					<input class='form-check-input' type='radio' id='method_0' name='method' value='Pearson' checked/>
					<label class="form-check-label" for="">Pearson Correlation</label>
				</div>
				<div class="form-check form-check-inline">
					<input class='form-check-input' type='radio' id='method_1' name='method' value='Spearman' />
					<label class="form-check-label" for="">Spearman Correlation (Pearson Correlation Coefficient Between Ranked Variables)</label>
				</div>
			</div>

			<div class='form-check mt-3 ml-3'>
				<label for='transform' class='form-check-label'>
					<input class='form-check-input' type='checkbox' onClick="if($(this).prop('checked')) $('.adv_log2_transformation_option').removeClass('hidden'); else  $('.adv_log2_transformation_option').addClass('hidden'); " id='transform' name='transform' value='1' />
					<strong>Enable Log<sub>2</sub> Transform</strong>
				</label>
			</div>

			<div class='form-inline ml-3 hidden adv_log2_transformation_option'>

				<label for='transform_value' class='col-form-label'>Value To Be Added For Log Transformation:</label>
				<input class='ml-3' type='text' id='transform_value' name='transform_value' value='0.5'/>

			</div>

		</div>


		<div class="w-100 m-3">
			<button type="submit" class="btn btn-primary" id="btn_submit">
				<i class="fas fa-upload"></i> Submit
			</button>

			<a class="ml-3" href="javascript:void(0);" onclick="if( $('#div_advanced_options').hasClass('hidden') ) $('#div_advanced_options').removeClass('hidden'); else $('#div_advanced_options').addClass('hidden')"><i class="fas fa-angle-double-right"></i> Show/Hide Advanced Options</a>
		</div>

		<div class="mt-3" id="div_debug"></div>

	</form>

	<div class="row w-100 mx-0" id="table_container"></div>


</div>
</div>
<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>
</div>
</div>




<!-------------------------------------------------------------------------------------------------->
<!-- Modal for Plot -->
<!-------------------------------------------------------------------------------------------------->
<div class="modal fade" id="modal_plot" tabindex="-1" role="dialog" aria-labelledby="">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="plot_title"></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="plot_body"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>




<script>

$(document).ready(function() {

  // Get Correlation
	var options = {
		url: 'exe.php?action=get_correlation',
		type: 'post',
		beforeSubmit: function(formData, jqForm, options) {
			$('#btn_submit').children(':first').removeClass('fa-upload').addClass('fa-spin fa-spinner');
			$('#btn_submit').attr('disabled', '');

			$('#table_container').html("");
			$('#plot_body').html("");

			return true;
		},
	    success: function(response){
			$('#btn_submit').children(':first').removeClass('fa-spin fa-spinner').addClass('fa-upload');
			$('#btn_submit').removeAttr('disabled');


			// console.log(response);

			if(response.type == 'Error'){
				bootbox.alert(response.detail);
				return;
			}
			else if(response.row_number == 0){
				bootbox.alert("No results found.");
				return;
			}

			$('#table_container').html(response.table);
			$('#MyTable').DataTable({ "pageLength": 100, "lengthMenu": [[10, 100, 500, 1000], [10, 100, 500, 1000]], order: [[ 2, 'desc' ]], dom: 'Blfrtip', buttons: ['colvis','copy','csv'] });

			// Check Number > 5000
			if (response.row_number > 5000) {
				var warning = '<div class="w-100 mb-3 alert alert-warning">';
				warning    += `${response.row_number } records found and only 5000 records are displayed in the results.`;
				warning    += '</div>';
				$('#table_container').prepend(warning);
			}

			return true;

		}
	};
	$('#form_correlation').ajaxForm(options);




	$(document).on('click', '.btn_draw_regression_line', function() {

		var gene_1 = $(this).attr('gene_1');
		var gene_2 = $(this).attr('gene_2');
		var time   = $(this).attr('time');
		var method = $('input[name=method]:checked').val();

		$('#plot_body').html("<canvas id='plotSection' width='690' height='690' xresponsive='true' aspectRatio='1:1'></canvas>");

		$.ajax({
			type: 'POST',
			url: 'exe.php?action=generate_line_chart',
			data: { 'gene_1': gene_1, 'gene_2': gene_2, 'time': time, 'method': method },
			success: function(res) {

				// $('#div_debug').html(res);

				var plotObj = new CanvasXpress(
					'plotSection',
					{ "y": res.plot_data.y },
					res.plot_data.settings
				);
				plotObj.addRegressionLine('red', false);

				$('#plot_title').html(res.title);
				$('#plot_body').append(res.content);

				$('#modal_plot').modal();
			}
		});
	});

});


</script>

</body>
</html>