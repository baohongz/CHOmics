<?php
include_once("config.php");

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<?php include_once($BXAF_CONFIG['BXAF_PAGE_HEADER']); ?>

	<script src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/jquery/jquery.form.min.js"></script>

	<link  href="../library/canvasxpress/canvasxpress-18.1/canvasXpress.css" rel="stylesheet">
	<script src="../library/canvasxpress/canvasxpress-18.1/canvasXpress.min.js.php"></script>

	<link   href='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/datatables/datatables.min.css' rel='stylesheet' type='text/css'>
	<script src='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/datatables/datatables.min.js.php'></script>

</head>
<body>

<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_MENU'])) include_once($BXAF_CONFIG['BXAF_PAGE_MENU']); ?>
<div id="bxaf_page_wrapper" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_WRAPPER']; ?>">
<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_LEFT'])) include_once($BXAF_CONFIG['BXAF_PAGE_LEFT']); ?>
<div id="bxaf_page_right" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT']; ?>">
<div id="bxaf_page_right_content" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT_CONTENT']; ?>">
<div class="container-fluid">

    <?php $help_key = 'Gene Expression Plot'; include_once( dirname(__DIR__) . "/help_content.php"); ?>

	<div class="w-100">
        <form class="w-100" id="form_main">

			<div class="row w-100">
            	<div class="col-md-6">
					<?php include_once(dirname(__DIR__) . '/tool_save_lists/modal_gene.php'); ?>
					<div class="text-muted my-3">Note: Leave empty to view expression of <span class="text-success">all genes from selected samples</span>.</div>
				</div>
                <div class="col-md-6">
					<?php include_once(dirname(__DIR__) . '/tool_save_lists/modal_sample.php'); ?>
					<div class="text-muted my-3">Note: Leave empty to view expression of <span class="text-success">selected genes from all samples</span>.</div>
				</div>
			</div>


			<div class="w-100">

				<div class="w-100 my-3">
					<div class="form-check form-check-inline">
						<label class="form-check-label font-weight-bold" for="">Platform Type:</label>
					</div>
					<div class="form-check form-check-inline">
						<input class="form-check-input reset_chart" type="radio" name="platform_type" value="NGS" id="platform_type_rna_seq">
						<label class="form-check-label" for="platform_type_rna_seq">RNA-Seq Only, ignore Microarray Samples</label>
					</div>
					<div class="form-check form-check-inline">
						<input class="form-check-input reset_chart" type="radio" name="platform_type" value="Array" id="platform_type_array">
						<label class="form-check-label" for="platform_type_array">Microarray Only, ignore RNA-Seq Samples</label>
					</div>
					<div class="form-check form-check-inline">
						<input class="form-check-input reset_chart" type="radio" name="platform_type" value="" id="platform_type_auto" checked>
						<label class="form-check-label" for="platform_type_array">Automatic based on the entered samples. <span class="text-danger">If no samples entered, will use RNA-Seq only.</span></label>
					</div>
				</div>

				<div class="w-100 my-3">
					<div class="form-check form-check-inline">
						<label class="form-check-label font-weight-bold" for="">Data Type:</label>
					</div>
					<div class="form-check form-check-inline">
						<input class="form-check-input reset_chart" type="radio" name="data_type" value="public" id="data_type_public">
						<label class="form-check-label" for="data_type_public">Public only</label>
					</div>
					<div class="form-check form-check-inline">
						<input class="form-check-input reset_chart" type="radio" name="data_type" value="private" id="data_type_private">
						<label class="form-check-label" for="data_type_private">Private only</label>
					</div>
					<div class="form-check form-check-inline">
						<input class="form-check-input reset_chart" type="radio" name="data_type" value="all" id="data_type_auto" checked>
						<label class="form-check-label" for="data_type_all">Automatic based on the entered samples. <span class="text-danger">If no samples entered, will use both public and private samples.</span></label>
					</div>
				</div>

				<div class="w-100">
					<?php

    					$type = 'Sample';
						$pre_checked = array('DiseaseState', 'Tissue', 'Treatment');

						$common_list = array('DiseaseState', 'Tissue', 'Treatment', 'SamplingTime', 'SampleSource', 'Gender', 'Ethnicity');
						$list = $BXAF_CONFIG['TOOL_EXPORT_COLNAMES_ALL']['Sample'];
						sort($list);

						if (isset($_GET['project_id']) && intval($_GET['project_id']) >= 0) {
						    $sql = "SELECT `" . implode("`,`", $list) . "` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES']}` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `_Projects_ID` = ?i";
						    $sample_info = $BXAF_MODULE_CONN -> get_all($sql, intval($_GET['project_id']) );
							$list_count = array();
							foreach($sample_info as $row){ foreach($row as $k=>$v){ if($v != '' && $v != 'NA') $list_count[$k] += 1; } }
							$pre_checked = array_keys($list_count);
						}

					?>

					<div class="w-100 my-3">
						<label class="font-weight-bold"><?php echo $type; ?> Attributes:</label>

						<span class="table-success mx-2 p-2">( <span id="span_number_attributes"><?php echo count($pre_checked); ?></span> selected )</span>

						<a href="javascript:void(0);" onclick="if($('#div_attributes').hasClass('hidden')) $('#div_attributes').removeClass('hidden'); else $('#div_attributes').addClass('hidden'); "> <i class="fas fa-angle-double-right"></i> Show Attributes </a>

						<a class="ml-5 btn btn-outline-success" href="javascript:void(0);" id="btn_show_data_filter"> <i class="fas fa-plus"></i> Filter Data by Sample Attributes </a>

						<a class="ml-1 hidden reset_chart" href="javascript:void(0);" id="btn_clear_data_filter" onclick="$('#div_data_filter').html(''); $('#div_data_filter').addClass('hidden');"> <i class="fas fa-angle-double-right"></i> Clear Data Filter </a>
					</div>

    				<?php

						echo '<div id="div_attributes" class="w-100 hidden my-3">';

							foreach ($common_list as $colname) {
								$caption = str_replace("_", " ", $colname);

								echo '<div class="form-check form-check-inline">
									<input class="form-check-input checkbox_check_individual reset_chart" type="checkbox" category="' . $type . '" value="' . $colname . '" name="attributes_' . $type . '[]" ' . (in_array($colname, $pre_checked) ? "checked " : "") . '>';
									echo '<label class="form-check-label">' . $caption . '</label>';
								echo '</div>';
							}

							$colname = '_Platforms_ID';
							$caption = 'Platform Name';
							echo '<div class="form-check form-check-inline">
								<input class="form-check-input checkbox_check_individual reset_chart" type="checkbox" category="' . $type . '" value="' . $colname . '" name="attributes_' . $type . '[]" ' . (in_array($colname, $pre_checked) ? "checked " : "") . '>';
								echo '<label class="form-check-label">' . $caption . '</label>';
							echo '</div>';

							echo "<div class='w-100 my-1'></div>";

							foreach ($list as $colname) {
								$caption = str_replace("_", " ", $colname);
								if(in_array($colname, $common_list)) continue;

								echo '<div class="form-check form-check-inline">
									<input class="form-check-input checkbox_check_individual reset_chart" type="checkbox" category="' . $type . '" value="' . $colname . '" name="attributes_' . $type . '[]" ' . (in_array($colname, $pre_checked) ? "checked " : "") . '>';
									echo '<label class="form-check-label">' . $caption . '</label>';
								echo '</div>';
							}

							echo "<div class='w-100 my-1'></div>";

    						echo "<a href='Javascript: void(0);' class='mr-3' onClick=\"$('.checkbox_check_individual').prop('checked', true ); $('#span_number_attributes').html('all'); \" ><i class='fas fa-check'></i> Check All</a>";
    						echo "<a href='Javascript: void(0);' class='mr-3' onClick=\"$('.checkbox_check_individual').prop('checked', false ); $('#span_number_attributes').html('0'); \" ><i class='fas fa-times'></i> Check None</a>";


						echo '</div>';

    				?>

				</div>

				<div class="my-3 hidden" id="div_data_filter"></div>

			</div>

			<div class="w-100 my-3">

    			<button type="submit" class="btn btn-primary" id="btn_submit">
    				<i class="fas fa-chart-pie"></i> Plot
    			</button>

				<a class="ml-3" href="<?php echo $_SERVER['PHP_SELF']; ?>"> <i class="fas fa-sync"></i> Reset All </a>
			</div>

		</form>

    </div>

    <div class="my-3 p-3" id="div_results"></div>
	<div class="my-3" id="div_debug"></div>

</div>
</div>
<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>
</div>
</div>



<div class="modal" id="modal_data_filter">
	<div class="modal-dialog modal-lg" role="document">


			<div class="modal-content w-100">

			  <div class="modal-header">
				<h3 class="modal-title" id="modal_data_filter_title">Data Filters</h3>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
				  <span aria-hidden="true">&times;</span>
				</button>
			  </div>

			  <div class="modal-body w-100" id="modal_data_filter_body"></div>

			  <div class="modal-footer">
				<input type="hidden" value="" id="modal_data_filter_field">
				<button type="button" class="btn btn-primary" data-dismiss="modal" id="modal_data_filter_save">Save</button>
			  </div>

			</div>


	</div>
</div>



<script>

$(document).ready(function() {

	$(document).on('change', '.reset_chart', function() {
        $('#div_data_filter').html('');
		$('#div_results').html('');
		$('#div_debug').html('');
	});


	$(document).on('change', '.checkbox_check_individual', function() {
		// Update number selected
		var number = 0;
		$('.checkbox_check_individual').each(function(i, e) {
			if ($(e).is(':checked')) number++;
		});
		$('#span_number_attributes').html(number);

		$('#div_data_filter').html('');
		$('#div_results').html('');
		$('#div_debug').html('');

	});




	$(document).on('click', '.check_sample_filter', function() {
		$('.sample_check_all').prop('checked', false );
	});

	$(document).on('click', '.sample_check_all', function() {
		$('.check_sample_filter').prop('checked', $(this).prop('checked') );
	});



	$(document).on('click', '#modal_data_filter_save', function() {

		var field = $('#modal_data_filter_field').val();

		var filters = [];
		$('.check_sample_filter').each(function(i, e) {
			if ($(e).is(':checked')){
				filters.push( $(e).val() );
			}
		});

		$('#data_filters_label_' + field).find('span').html( filters.length + ' Selected' );

		$('#data_filters_' + field).val( JSON.stringify(filters) );

		$('#modal_data_filter').modal('hide');
	});


	// Get Data Filter
	$(document).on('click', '#btn_show_data_filter', function() {

		var content = '<h4 class="w-100 text-success">Filter Data by Sample Attributes</h4>';

		$('.checkbox_check_individual').each(function(i, e) {

			if ($(e).is(':checked')){

				var field_name = $(e).attr('value');
				var caption  = field_name;
				if(caption == '_Platforms_ID') caption = 'Platform Name';

				content += `<button type="button" class="btn btn-outline-success mr-3 btn_data_filters" id="data_filters_label_${field_name}" field_name="${field_name}">${caption} ( <span class="text-danger ">All</span> )</button><input type="hidden" name="data_filters_${field_name}" id="data_filters_${field_name}" value="">`;

			}
		});

		$('#div_data_filter').html(content);
		$('#div_data_filter').removeClass('hidden');

		$('#btn_clear_data_filter').removeClass('hidden');
	});



	$(document).on('click', '.btn_data_filters', function() {

		var field_name = $(this).attr('field_name');
		var data_filter = $('#data_filters_' + field_name).val();
		var data_type = $('input[name=data_type]:checked').val();
		var platform_type = $('input[name=platform_type]:checked').val();
		var Sample_List = $('#Sample_List').val();

		$.ajax({
			type: 'POST',
			url: 'exe.php?action=generate_filters',
			data: {
				"platform_type": platform_type,
				"data_type": data_type,
				"field_name": field_name,
				"data_filter": data_filter,
				"Sample_List": Sample_List
			},
			success: function(response) {

				$('#modal_data_filter_field').val(field_name);

				var caption  = field_name;
				if(caption == '_Platforms_ID') caption = 'Platform Name';

				$('#modal_data_filter_title').html('Data Filters for ' + caption);
				$('#modal_data_filter_body').html(response);

				if ( $.fn.dataTable.isDataTable( '#datatable_data_filter' ) ) {
					var table = $('#datatable_data_filter').DataTable();
					table.destroy();
				}
				$('#datatable_data_filter').DataTable({ "paging": false, order: [[ 3, 'desc' ]], "columnDefs": [ { "targets": 0, "orderable": false } ] });

	        	$('#modal_data_filter').modal('show');

			}

		});

	});


	//-----------------------------------------------------------------------------
	// Generate Chart
	//-----------------------------------------------------------------------------
	var options = {
		url: 'exe.php?action=generate_plot',
 		type: 'post',
    	beforeSubmit: function(formData, jqForm, options) {

    		$('#div_results').html('');

			$('#btn_submit')
				.attr('disabled', '')
				.children(':first')
				.removeClass('fa-chart-pie')
				.addClass('fa-spin fa-spinner');

			return true;
		},
    	success: function(response){

			// console.log(response);

			$('#btn_submit')
				.removeAttr('disabled')
				.children(':first')
				.addClass('fa-chart-pie')
				.removeClass('fa-spin fa-spinner');

				$('#div_results').html(response);

			return true;
		}
	};

	$('#form_main').ajaxForm(options);

});

</script>

</body>
</html>