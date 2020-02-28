<?php
include_once("config.php");

$attribute_type = 'Sample';
$pre_checked = array('Gender', 'DiseaseState', 'Tissue', 'Treatment');

$history_post = array();
$history_output = '';
if(isset($_GET['key']) && file_exists($BXAF_CONFIG['CURRENT_SYSTEM_CACHE_DIR'] . $_GET['key'] . "/history.txt") ){
	$array = unserialize(file_get_contents($BXAF_CONFIG['CURRENT_SYSTEM_CACHE_DIR'] . $_GET['key'] . "/history.txt"));
	$history_post = $array['_POST'];
	$history_output = $array['OUTPUT'];

	$pre_checked = array();
	foreach ($history_post as $k=>$v) {
        if (preg_match("/^attributes_{$attribute_type}_/", $k) ) {
            $pre_checked[] = preg_replace("/^attributes_{$attribute_type}_/", '', $k);
        }
    }
}

if(isset($history_post['gene_names'])) $gene_names_custom = $history_post['gene_names'];
if(isset($history_post['sample_names'])) $sample_names_custom = $history_post['sample_names'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<?php include_once($BXAF_CONFIG['BXAF_PAGE_HEADER']); ?>

	<script src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/jquery/jquery.form.min.js"></script>

	<link  href="../library/canvasxpress/canvasxpress-18.5/canvasXpress.css" rel="stylesheet">
	<script src="../library/canvasxpress/canvasxpress-18.5/canvasXpress.min.js.php"></script>

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

    <?php $help_key = 'Heatmap'; include_once( dirname(__DIR__) . "/help_content.php"); ?>


	<div class="w-100">
        <form class="w-100" id="form_export">

			<div class="row w-100">
				<div class="col-md-6">
					<?php include_once(dirname(__DIR__) . '/tool_save_lists/modal_gene.php'); ?>
					<div class="text-muted my-3">Note: You must enter <span class="text-success">one or more gene names</span>.</div>

				</div>

				<div class="col-md-6">
					<?php include_once(dirname(__DIR__) . '/tool_save_lists/modal_sample.php'); ?>
					<div class="text-muted my-3">Note: You must enter <span class="text-success">one or more sample names</span>.</div>
				</div>

			</div>


			<div class="row w-100 mx-0">

				<?php

					$list = $BXAF_CONFIG['TOOL_EXPORT_COLNAMES_ALL']['Sample'];
					sort($list);

					if (isset($_GET['project_id']) && intval($_GET['project_id']) >= 0) {
					    $sql = "SELECT `" . implode("`,`", $list) . "` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES']}` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `_Projects_ID` = ?i";
					    $sample_info = $BXAF_MODULE_CONN -> get_all($sql, intval($_GET['project_id']) );
						$list_count = array();
						foreach($sample_info as $row){ foreach($row as $k=>$v){ if($v != '' && $v != 'NA') $list_count[$k] += 1; } }
						$pre_checked = array_keys($list_count);
					}


					echo '<div class="row w-100 mt-3 mx-0">';
						echo '
							<p class="w-100 mb-1">
								<span class="font-weight-bold mr-2">' . $attribute_type . ' Attributes:</span>
								<span style="background-color:lightgreen; padding:5px;">
								( <span id="number_attributes_selected">' . count($pre_checked) . '</span> selected)
								</span>

								<a class="mx-2" href="javascript:void(0);" onclick="$(\'#div_all_options\').slideToggle()">
									<i class="fas fa-angle-double-right"></i>
									Show Attributes
								</a>
							</p>';

							echo '<div id="div_all_options" style="display:none;">';

							foreach ($list as $colname) {
								$caption = str_replace("_", " ", $colname);
								echo '
									<label class="mx-2">
										<input type="checkbox" category="' . $attribute_type . '" class="checkbox_check_individual" value="' . $colname . '" name="attributes_' . $attribute_type . '_' . $colname . '" ' . (in_array($colname, $pre_checked) ? "checked " : "") . '> ' . $caption . '
									</label>';
							}

							echo "<a href='Javascript: void(0);' class='ml-3' onClick=\"$('.checkbox_check_individual').prop('checked', true ); $('#number_attributes_selected').html('all'); \" ><i class='fas fa-check'></i> Check All</a>";
							echo "<a href='Javascript: void(0);' class='ml-3' onClick=\"$('.checkbox_check_individual').prop('checked', false ); $('#number_attributes_selected').html('0'); \" ><i class='fas fa-times'></i> Check None</a>";

							echo '</div>';
						echo '</div>';

				?>
			</div>



			<button type="submit" class="btn btn-primary mt-3" id="btn_submit">
				<i class="fas fa-upload"></i> Submit
			</button>

			<button type="button" data-toggle="modal" data-target="#modal_advanced_options" class="btn btn-success mt-3">
				<i class="fas fa-cogs"></i> Advanced Options
			</button>


			<div class="w-100 my-3" id="div_results"><?php echo $history_output; ?></div>

			<div class="mt-3" id="debug"></div>

			<div id="plot_section"></div>


			<!---------------------------------------------------------------->
			<!-- Modal for Advanced Options -->
			<!---------------------------------------------------------------->
			<div class="modal" id="modal_advanced_options">
			  <div class="modal-dialog" role="document">
			    <div class="modal-content">
			      <div class="modal-header">
			        <h3 class="modal-title">Advanced Options</h3>
			        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
			          <span aria-hidden="true">&times;</span>
			        </button>
			      </div>
			      <div class="modal-body">

			        <h4>Data Options</h4>

						<div class="form-check my-1">
							<input class="form-check-input" type="checkbox" name="options_enable_log2" id="options_enable_log2" value="1" checked>
							<label class="form-check-label"> Enable Log2 Transform </label>
						</div>

						<div class="form-check my-1">
							<label class="form-check-label ml-3"> Value To Be Added For Log Transformation: </label>
							<input name="options_log_value" id="options_log_value" value="0.5" style="width:5rem;">
						</div>

						<div class="form-check my-1">
							<input class="form-check-input" type="checkbox" name="options_enable_z_score" id="options_enable_z_score" value="1" checked>
							<label class="form-check-label"> Enable Z-Score Transform </label>
						</div>

						<div class="form-check form-inline mx-0 px-0 my-1">
							<input class="form-check-input" type="checkbox" name="options_enable_upper" id="options_enable_upper" value="1" checked>
							<label class="form-check-label"> Enable Upper Limit </label>
							<input class="form-control form-control-sm ml-3" name="options_upper_value" id="options_upper_value" value="3" style="width:5rem;">
						</div>

						<div class="form-check form-inline mx-0 px-0 my-1">
							<input class="form-check-input" type="checkbox" name="options_enable_lower" id="options_enable_lower" value="1" checked>
							<label class="form-check-label"> Enable Lower Limit </label>
							<input class="form-control form-control-sm ml-3" name="options_lower_value" id="options_lower_value" value="-3" style="width:5rem;">
						</div>

						<div class="form-check my-1">
							<input class="form-check-input" type="checkbox" name="options_cluster_genes" id="options_cluster_genes" value="1" checked>
							<label class="form-check-label"> Cluster Genes </label>
						</div>

						<div class="form-check my-1">
							<input class="form-check-input" type="checkbox" name="options_cluster_samples" id="options_cluster_samples" value="1" checked>
							<label class="form-check-label"> Cluster Samples </label>
						</div>

					<h4 class="mt-3">Display Options</h4>

						<div class="form-check my-1">
							<input class="form-check-input" type="checkbox" name="options_overlay_samples" id="options_overlay_samples" value="1" checked>
							<label class="form-check-label"> Overlay Samples </label>
						</div>

						<div class="form-check my-1">
							<input class="form-check-input" type="checkbox" name="options_display_genes" id="options_display_genes" value="1" checked>
							<label class="form-check-label"> Display Gene Names</label>
						</div>

						<div class="form-check my-1">
							<input class="form-check-input" type="checkbox" name="options_display_samples" id="options_display_samples" value="1" checked>
							<label class="form-check-label"> Display Sample IDs </label>
						</div>

			      </div>
			      <div class="modal-footer">
			        <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
			      </div>
			    </div>
			  </div>
			</div>


		</form>
	</div>

</div>
</div>
<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>
</div>
</div>



<script>

$(document).ready(function() {

	$(document).on('change', '.checkbox_check_individual', function() {
		var curr = $(this);
		var category = curr.attr('category');
		var value = curr.attr('value');
		var check_option = false;
		if (curr.is(':checked')) {
			check_option = true;
		}

		var number_checked = 0;
		$('.checkbox_check_individual').each(function(i, e) {
			if ($(e).is(':checked')) {
				number_checked++;
			}
		});
		$('#number_attributes_selected').html(number_checked);
	});



  // Draw Heatmap
	var options = {
		url: 'exe.php?action=generate_heatmap',
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

				$('#div_results').html(response);

			return true;
		}
	};
	$('#form_export').ajaxForm(options);


});


</script>

</body>

</html>