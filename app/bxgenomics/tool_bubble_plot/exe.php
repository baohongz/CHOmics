<?php

include_once('config.php');


if (isset($_GET['action']) && $_GET['action'] == 'bubble_pre_generate_chart') {
	// echo '<pre>'; print_r($_POST); echo '</pre>'; exit();

	$gene_name       = trim($_POST['gene_name']);
	$Y_FIELD         = $_POST['select_y_field'];
	$COLORING_FIELD  = $_POST['select_coloring_field'];
	$COMPARISON_TYPE = $_POST['select_comparison_type'];

	// Check
	if ($Y_FIELD == $COLORING_FIELD) {
		echo 'Error: Y-axis field has to be different from the coloring field.';
		exit();
	}

	// Get GeneIndex
	$sql = "SELECT `GeneIndex` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_GENES_INDEX']}` WHERE `Species` = '{$_SESSION['SPECIES_DEFAULT']}' AND `GeneName`= ?s";
	$gene_index = $BXAF_MODULE_CONN -> get_one($sql, $gene_name);

	if ($gene_index == '') {
		echo 'Error: No gene found.';
		exit();
	}



	ini_set('memory_limit','8G');

	if($COMPARISON_TYPE == 'public'){
		$tabix_results = tabix_search_bxgenomics(array($gene_index), array(), 'ComparisonData', 'public' );
	}
	else if($COMPARISON_TYPE == 'private'){
		$tabix_results = tabix_search_bxgenomics(array($gene_index), array(), 'ComparisonData', 'private' );
	}
	else {
		$tabix_results = tabix_search_bxgenomics(array($gene_index), array(), 'ComparisonData' );
	}
	$data_comparison = array();
	foreach ($tabix_results as $row) {
		$data_comparison[$row['GeneIndex'] . '_' . $row['ComparisonIndex']]  = array(
		  'ComparisonIndex' => $row['ComparisonIndex'],
		  'Log2FoldChange'  => $row['Log2FoldChange'],
		  'PValue'          => $row['PValue'],
		  'AdjustedPValue'  => $row['AdjustedPValue']
		);
	}


	$Y_FIELD_LIST = array();
	$COLORING_FIELD_LIST = array();
	$Y_FIELD_NUMBER = array(); // Appear times
	$COLORING_FIELD_NUMBER = array();

	foreach ($data_comparison as $comparison) {
		$sql = "SELECT `{$Y_FIELD}`, `{$COLORING_FIELD}`, `bxafStatus` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS']}` WHERE `Species` = '{$_SESSION['SPECIES_DEFAULT']}' AND {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `ID`=?i";
		$comparison_row = $BXAF_MODULE_CONN -> get_row($sql, $comparison['ComparisonIndex']);

		if (!is_array($comparison_row) || count($comparison_row) <= 1 || $comparison_row['bxafStatus'] > 5) continue;

		if (trim($comparison['Log2FoldChange']) == ''
			|| trim($comparison['Log2FoldChange']) == '.'
			|| trim($comparison['Log2FoldChange']) == 'NA'
			|| trim($comparison['PValue']) == ''
			|| trim($comparison['PValue']) == '.'
			|| trim($comparison['PValue']) == 'NA'
			|| trim($comparison_row[$Y_FIELD]) == ''
			|| trim($comparison_row[$Y_FIELD]) == 'NA'
			|| trim($comparison_row[$COLORING_FIELD]) == ''
			|| trim($comparison_row[$COLORING_FIELD]) == 'NA') {
			continue;
		}


		// 统计出现次数
		if (!in_array($comparison_row[$Y_FIELD], array_keys($Y_FIELD_NUMBER))) {
			$Y_FIELD_NUMBER[$comparison_row[$Y_FIELD]] = 1;
		} else {
			$Y_FIELD_NUMBER[$comparison_row[$Y_FIELD]] += 1;
		}
		if (!in_array($comparison_row[$COLORING_FIELD], array_keys($COLORING_FIELD_NUMBER))) {
			$COLORING_FIELD_NUMBER[$comparison_row[$COLORING_FIELD]] = 1;
		} else {
			$COLORING_FIELD_NUMBER[$comparison_row[$COLORING_FIELD]] += 1;
		}

	}

	arsort($Y_FIELD_NUMBER);
	arsort($COLORING_FIELD_NUMBER);


	echo '
	<input name="select_y_field" value="' . $Y_FIELD . '" hidden>
	<input name="select_coloring_field" value="' . $COLORING_FIELD . '" hidden>
	<input name="gene_name" value="' . $gene_name . '" hidden>
	<input name="select_comparison_type" value="' . $COMPARISON_TYPE . '" hidden>


	<div class="row mt-1">
		<div class="col-md-2 text-md-right text-muted">
			Marker Area
		</div>
		<div class="col-md-10">
			<label class="m-r-1">
				<input type="radio" name="area_setting" value="PValue">
				P-Value
			</label>
			<label class="m-r-1">
				<input type="radio" name="area_setting" value="AdjustedPValue" checked>
				Adjusted P-Value
			</label>
		</div>
	</div>


	<div class="row mt-1">
		<div class="col-md-2 text-md-right text-muted">
			Y-axis Setting
		</div>
		<div class="col-md-10">
			<strong class="green">' . $Y_FIELD . '</strong><br />
			<label class="m-r-1">
				<input class="radio_y_setting" type="radio" name="y_setting" value="top_10">
				Show Top 10
			</label>
			<label class="m-r-1">
				<input class="radio_y_setting" type="radio" name="y_setting" value="top_20" checked>
				Show Top 20
			</label>
			<label class="m-r-1">
				<input class="radio_y_setting" type="radio" name="y_setting" value="all">
				Show All
			</label>
			<label class="m-r-1">
				<input class="radio_y_setting" type="radio" name="y_setting" value="customize">
				Customize
			</label>
			<br />
			<div class="alert alert-warning m-b-0" id="y_customize_div" style="display:none;">
			</div>

			<div class="modal fade bd-example-modal-lg" id="modal_y" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
				<div class="modal-dialog modal-lg">
					<div class="modal-content">
						<div class="modal-header">
							<h4 class="modal-title" id="gridModalLabel">' . substr($Y_FIELD, 5) . '</h4>
							<a href="javascript:void(0);" class="btn_customize_sort" gene_id="' . $GENE_INDEX . '" category="y" type="category" field="' . $Y_FIELD . '">
								<i class="fas fa-sort-alpha-down" aria-hidden="true"></i>
								Sort by Category
							</a> &nbsp;
							<a href="javascript:void(0);" class="btn_customize_sort" gene_id="' . $GENE_INDEX . '" category="y" type="occurence" field="' . $Y_FIELD . '">
								<i class="fas fa-sort-numeric-down" aria-hidden="true"></i>
								Sort by Occurence
							</a>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
								<span aria-hidden="true">&times;</span>
							</button>
						</div>

						<div class="modal-body" id="modal_y_content">
							<div style="height:70vh; overflow-y:scroll;">

								<label>
									<input type="checkbox" id="check_all_y">
									Check / Uncheck All
								</label>

								<table class="table table-bordered table-striped" style="font-size:14px;">
									<tr>
										<th>&nbsp;</th>
										<th>Name</th>
										<th>Occurence</th>
									</tr>';
									foreach ($Y_FIELD_NUMBER as $key => $value) {
										echo '
										<tr>
											<td><input type="checkbox" class="checkbox_customize_y" name="y_' . $key . '" value="' . $key . '"></td>
											<td>' . $key . '</td>
											<td>' . $value . '</td>
										</tr>';
									}
							echo '
								</table>';
						echo '
							</div>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
							<button type="button" class="btn btn-primary" id="btn_save_customize_y">Save changes</button>
						</div>
					</div>
				</div>
			</div>



		</div>
	</div>



	<div class="row mt-1">
		<div class="col-md-2 text-md-right text-muted">
			Coloring Setting
		</div>
		<div class="col-md-10">
			<strong class="green">' . $COLORING_FIELD . '</strong><br />
			<label class="m-r-1">
				<input class="radio_coloring_setting" type="radio" name="coloring_setting" value="top_10">
				Show Top 10
			</label>
			<label class="m-r-1">
				<input class="radio_coloring_setting" type="radio" name="coloring_setting" value="top_20" checked>
				Show Top 20
			</label>
			<label class="m-r-1">
				<input class="radio_coloring_setting" type="radio" name="coloring_setting" value="all">
				Show All
			</label>
			<label class="m-r-1">
				<input class="radio_coloring_setting" type="radio" name="coloring_setting" value="customize">
				Customize
			</label>
			<br />
			<div class="alert alert-warning m-b-0" id="color_customize_div" style="display:none;">
			</div>

			<div class="modal fade bd-example-modal-lg" id="modal_color" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
				<div class="modal-dialog modal-lg">
					<div class="modal-content">
						<div class="modal-header">
							<h4 class="modal-title" id="gridModalLabel">' . substr($COLORING_FIELD, 5) . '</h4>

							<a href="javascript:void(0);" class="btn_customize_sort" gene_id="' . $GENE_INDEX . '" category="color" type="category" field="' . $COLORING_FIELD . '">
								<i class="fas fa-sort-alpha-down" aria-hidden="true"></i>
								Sort by Category
							</a> &nbsp;
							<a href="javascript:void(0);" class="btn_customize_sort" gene_id="' . $GENE_INDEX . '" category="color" type="occurence" field="' . $COLORING_FIELD . '">
								<i class="fas fa-sort-numeric-down" aria-hidden="true"></i>
								Sort by Occurence
							</a>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
								<span aria-hidden="true">&times;</span>
							</button>
						</div>
						<div class="modal-body" id="modal_color_content">
							<div style="height:70vh; overflow-y:scroll;">

								<label>
									<input type="checkbox" id="check_all_color">
									Check / Uncheck All
								</label>

								<table class="table table-bordered table-striped" style="font-size:14px;">
									<tr>
										<th>&nbsp;</th>
										<th>Name</th>
										<th>Occurence</th>
									</tr>';
									foreach ($COLORING_FIELD_NUMBER as $key => $value) {
										echo '
										<tr>
											<td><input type="checkbox" class="checkbox_customize_color" name="color_' . $key . '" value="' . $key . '"></td>
											<td>' . $key . '</td>
											<td>' . $value . '</td>
										</tr>';
									}
							echo '
								</table>';


						echo '
							</div>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
							<button type="button" class="btn btn-primary" id="btn_save_customize_color">Save changes</button>
						</div>
					</div>
				</div>
			</div>

		</div>
	</div>

	<div class="row mt-1">
		<div class="col-md-2 text-md-right text-muted"></div>
		<div class="col-md-10">
			<button type="submit" class="btn btn-primary" id="btn_submit_generate"><i class="fas fa-chart-pie"></i> Plot</button>
		</div>
	</div>
	';

	echo "
	<script>
	$(document).ready(function() {

		$(document).on('change', '#check_all_y', function() {
			if ($('#check_all_y').is(':checked')) {
				$('.checkbox_customize_y').each(function(index, element) {
					$(element).prop('checked', true);
				});
			} else {
				$('.checkbox_customize_y').each(function(index, element) {
					$(element).prop('checked', false);
				});
			}
		});

		$(document).on('change', '#check_all_color', function() {
			if ($('#check_all_color').is(':checked')) {
				$('.checkbox_customize_color').each(function(index, element) {
					$(element).prop('checked', true);
				});
			} else {
				$('.checkbox_customize_color').each(function(index, element) {
					$(element).prop('checked', false);
				});
			}
		});


		$(document).on('change', '.radio_y_setting', function() {
			if ($(this).val() == 'customize') {
				$('#modal_y').modal('show');
			} else {
				$('#y_customize_div').hide();
			}
		});

		$(document).on('change', '.radio_coloring_setting', function() {
			if ($(this).val() == 'customize') {
				$('#modal_color').modal('show');
			} else {
				$('#color_customize_div').hide();
			}
		});

		$(document).on('click', '#btn_save_customize_y', function() {
			$('#modal_y').modal('hide');
			var y_selected = [];
			$('.checkbox_customize_y').each(function(index, element) {
				if ($(element).is(':checked')) {
					y_selected.push($(element).val());
				}
			});
			$('#y_customize_div').html('<strong>' + y_selected.length + ' options selected: </strong><br />' + y_selected);
			$('#y_customize_div').show();
		});

		$(document).on('click', '#btn_save_customize_color', function() {
			$('#modal_color').modal('hide');
			var color_selected = [];
			$('.checkbox_customize_color').each(function(index, element) {
				if ($(element).is(':checked')) {
					color_selected.push($(element).val());
				}
			});
			$('#color_customize_div').html('<strong>' + color_selected.length + ' options selected: </strong><br />' + color_selected);
			$('#color_customize_div').show();
		});



		$(document).on('click', '.btn_customize_sort', function() {
			var gene_id = $(this).attr('gene_id');
			var category = $(this).attr('category');
			var type = $(this).attr('type');
			var field = $(this).attr('field');
			$.ajax({
				type: 'POST',
				url: 'exe.php?action=customize_sort',
				data: {gene_id: gene_id, category: category, type: type, field: field},
				success: function(responseText){
					$('#modal_' + category + '_content').html(responseText);
				}
			});
		});



		var options_generate = {
			url: 'exe.php?action=bubble_generate_chart',
			type: 'post',
			beforeSubmit: function(formData, jqForm, options) {
				// Loader
				$('body').prepend('<div class=\"loader loader-default is-active\" data-text=\"Drawing...\" style=\"margin-left:0px; margin-top:0px;\"></div>');
				$('#btn_submit_generate').html('<i class=\"fas fa-spinner fa-pulse\"></i> Plotting...');
				$('#btn_submit_generate').attr('disabled', '');
				return true;
			},
			success: function(responseText, statusText){
				$('#btn_submit_generate').html('<i class=\"fas fa-chart-pie\"></i> Plot');
				$('#btn_submit_generate').removeAttr('disabled');
				$('#chart_div').html(responseText);
				$('#first_form_div, #second_form_div').slideUp(200);
				console.log(responseText);

				return true;
			}
		};
		$('#form_bubble_plot_filter').ajaxForm(options_generate);

	});

	</script>";



	exit();
}






else if (isset($_GET['action']) && $_GET['action'] == 'bubble_generate_chart') {

	$TIME = time();
	$gene_name           = trim($_POST['gene_name']);
	$Y_FIELD             = $_POST['select_y_field'];
	$COLORING_FIELD      = $_POST['select_coloring_field'];
	$AREA_FIELD          = $_POST['area_setting'];
	$AREA_FIELD_MODIFIED = ($AREA_FIELD == 'PValue') ? 'PVALUE' : 'ADJPVALUE';
	$COMPARISON_TYPE     = $_POST['select_comparison_type'];

	// Get GeneIndex
	$sql = "SELECT `GeneIndex` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_GENES_INDEX']}` WHERE `Species` = '{$_SESSION['SPECIES_DEFAULT']}' AND `GeneName`= ?s";
	$gene_index = $BXAF_MODULE_CONN -> get_one($sql, $gene_name);

	if ($gene_index == '') {
		echo 'Error: No gene found.';
		exit();
	}


	// Get ComparisonData From Tabix & Database

    ini_set('memory_limit','8G');

	if($COMPARISON_TYPE == 'public'){
		$tabix_results = tabix_search_bxgenomics(array($gene_index), array(), 'ComparisonData', 'public' );
	}
	else if($COMPARISON_TYPE == 'private'){
		$tabix_results = tabix_search_bxgenomics(array($gene_index), array(), 'ComparisonData', 'private' );
	}
	else {
		$tabix_results = tabix_search_bxgenomics(array($gene_index), array(), 'ComparisonData' );
	}

	$data_comparison = array();
	foreach ($tabix_results as $row) {
		$data_comparison[$row['GeneIndex'] . '_' . $row['ComparisonIndex']]  = array(
		  'ComparisonIndex' => $row['ComparisonIndex'],
		  'Log2FoldChange'  => $row['Log2FoldChange'],
		  'PValue'          => $row['PValue'],
		  'AdjustedPValue'  => $row['AdjustedPValue']
		);
	}


	$Y_FIELD_LIST          = array();
	$COLORING_FIELD_LIST   = array();
	$Y_FIELD_NUMBER        = array(); // Appear times
	$COLORING_FIELD_NUMBER = array();

	foreach ($data_comparison as $comparison_key => $comparison) {
		$sql = "SELECT `{$Y_FIELD}`, `{$COLORING_FIELD}`, `Name` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS']}` WHERE `Species` = '{$_SESSION['SPECIES_DEFAULT']}' AND {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `ID`= ?i ";
		$comparison_row = $BXAF_MODULE_CONN -> get_row($sql, $comparison['ComparisonIndex']);

		if (!is_array($comparison_row) || count($comparison_row) <= 1) {
			unset($data_comparison[$comparison_key]);
			continue;
		}

		if (trim($comparison['Log2FoldChange']) == ''
			|| trim($comparison['Log2FoldChange']) == '.'
			|| trim($comparison['Log2FoldChange']) == 'NA'
			|| trim($comparison['PValue']) == ''
			|| trim($comparison['PValue']) == '.'
			|| trim($comparison['PValue']) == 'NA') {
			continue;
		}

		if (trim($comparison_row[$Y_FIELD]) == '' || trim($comparison_row[$Y_FIELD]) == 'NA') {
			$comparison_row[$Y_FIELD] = '(NA)';
		}
		if (trim($comparison_row[$COLORING_FIELD]) == '' || trim($comparison_row[$COLORING_FIELD]) == 'NA') {
			$comparison_row[$COLORING_FIELD] = '(NA)';
		}

		// Count Number
		if (!in_array($comparison_row[$Y_FIELD], array_keys($Y_FIELD_NUMBER))) {
			$Y_FIELD_NUMBER[$comparison_row[$Y_FIELD]] = 1;
		} else {
			$Y_FIELD_NUMBER[$comparison_row[$Y_FIELD]] += 1;
		}
		if (!in_array($comparison_row[$COLORING_FIELD], array_keys($COLORING_FIELD_NUMBER))) {
			$COLORING_FIELD_NUMBER[$comparison_row[$COLORING_FIELD]] = 1;
		} else {
			$COLORING_FIELD_NUMBER[$comparison_row[$COLORING_FIELD]] += 1;
		}

	}

	arsort($Y_FIELD_NUMBER);
	arsort($COLORING_FIELD_NUMBER);


	// Filter y field and coloring field
	if ($_POST['y_setting'] == 'top_10') {
		$index = 0;
		foreach($Y_FIELD_NUMBER as $key => $value) {
			if (trim($key) == 'normal control' || $index >= 10) {
				unset($Y_FIELD_NUMBER[$key]);
			}
			if (trim($key) != 'normal control') $index++;
		}
	} else if ($_POST['y_setting'] == 'top_20') {
		$index = 0;
		foreach($Y_FIELD_NUMBER as $key => $value) {
			if (trim($key) == 'normal control' || $index >= 20) {
				unset($Y_FIELD_NUMBER[$key]);
			}
			if (trim($key) != 'normal control') $index++;
		}
	} else if ($_POST['y_setting'] == 'all') {

	} else if ($_POST['y_setting'] == 'customize') {
		foreach($Y_FIELD_NUMBER as $key => $value) {
			if (trim($key) == 'normal control' || !isset($_POST['y_' . str_replace(' ', '_', $key)])) {
				unset($Y_FIELD_NUMBER[$key]);
			}
		}
	}

	if ($_POST['coloring_setting'] == 'top_10') {
		$index = 0;
		foreach($COLORING_FIELD_NUMBER as $key => $value) {
			if (trim($key) == 'normal control' || $index >= 10) {
				unset($COLORING_FIELD_NUMBER[$key]);
			}
			if (trim($key) != 'normal control') $index++;
		}
	} else if ($_POST['coloring_setting'] == 'top_20') {
		$index = 0;
		foreach($COLORING_FIELD_NUMBER as $key => $value) {
			if (trim($key) == 'normal control' || $index >= 20) {
				unset($COLORING_FIELD_NUMBER[$key]);
			}
			if (trim($key) != 'normal control') $index++;
		}
	} else if ($_POST['coloring_setting'] == 'all') {
		foreach($COLORING_FIELD_NUMBER as $key => $value) {
			if (trim($key) == 'normal control') {
				unset($COLORING_FIELD_NUMBER[$key]);
			}
		}
	} else if ($_POST['coloring_setting'] == 'customize') {
		foreach($COLORING_FIELD_NUMBER as $key => $value) {
			if (trim($key) == 'normal control' || !isset($_POST['color_' . str_replace(' ', '_', $key)])) {
				unset($COLORING_FIELD_NUMBER[$key]);
			}
		}
	}

	// Get All Values
	// Grouped by coloring settings
	$ALL_MARKER = array();
	$ALL_GENES = array();
	$ALL_APPEARED_Y = array();
	$NUMBER_MISSING_DATA = 0;
	foreach ($data_comparison as $comparison) {

		$sql = "SELECT `{$Y_FIELD}`, `{$COLORING_FIELD}`, `Name`, `ID`, `ComparisonCategory`, `ComparisonContrast` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS']}` WHERE `Species` = '{$_SESSION['SPECIES_DEFAULT']}' AND {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `ID`=?i";
		$comparison_row = $BXAF_MODULE_CONN -> get_row($sql, $comparison['ComparisonIndex']);

    	if (!is_array($comparison_row) || count($comparison_row) <= 1) continue;

		if (trim($comparison['Log2FoldChange']) == ''
			|| trim($comparison['Log2FoldChange']) == '.'
			|| trim($comparison['Log2FoldChange']) == 'NA'
			|| trim($comparison['PValue']) == ''
			|| trim($comparison['PValue']) == '.'
			|| trim($comparison['PValue']) == 'NA') {
      		$NUMBER_MISSING_DATA++;
			continue;
		}

		if (trim($comparison_row[$Y_FIELD]) == '' || trim($comparison_row[$Y_FIELD]) == 'NA') {
			$comparison_row[$Y_FIELD] = '(NA)';
		}
		if (trim($comparison_row[$COLORING_FIELD]) == '' || trim($comparison_row[$COLORING_FIELD]) == 'NA') {
			$comparison_row[$COLORING_FIELD] = '(NA)';
		}

		// Skip unselected y&coloring option
		$y_temp = $comparison_row[$Y_FIELD];
		$color_temp = $comparison_row[$COLORING_FIELD];

		if (!in_array($y_temp, array_keys($Y_FIELD_NUMBER))
			|| !in_array($color_temp, array_keys($COLORING_FIELD_NUMBER))) {
			if ($_POST['y_setting'] == 'all' && $_POST['coloring_setting'] == 'all') {
				$NUMBER_MISSING_DATA++;
			}
			continue;
		}


		// Save appeared y option and point info
		if (!in_array($y_temp, $ALL_APPEARED_Y)) {
			$ALL_APPEARED_Y[] = $y_temp;
		}

		if (!in_array($color_temp, array_keys($ALL_MARKER))) {
			$ALL_MARKER[$color_temp] = array(
				array(
					'Y_FIELD' =>$y_temp,
					'COLORING_FIELD' => $color_temp,
					'LOGFC' => $comparison['Log2FoldChange'],
					'PVALUE' => $comparison['PValue'],
					'ADJPVALUE' => $comparison['AdjustedPValue'],
					'COMPARISON_ID' => $comparison_row['Name'],
					'COMPARISON_INDEX' => $comparison_row['ID'],
					'COMPARISON_CATEGORY' => $comparison_row['ComparisonCategory'],
					'COMPARISON_CONTRAST' => $comparison_row['ComparisonContrast'],
				)
			);
		} else {
			$ALL_MARKER[$color_temp][] = array(
				'Y_FIELD' =>$y_temp,
				'COLORING_FIELD' => $color_temp,
				'LOGFC' => $comparison['Log2FoldChange'],
				'PVALUE' => $comparison['PValue'],
				'ADJPVALUE' => $comparison['AdjustedPValue'],
				'COMPARISON_ID' => $comparison_row['Name'],
				'COMPARISON_INDEX' => $comparison_row['ID'],
				'COMPARISON_CATEGORY' => $comparison_row['ComparisonCategory'],
				'COMPARISON_CONTRAST' => $comparison_row['ComparisonContrast'],
			);
		}


		// Save all genes to search
		$ALL_GENES[] = array(
			'x' => $comparison['Log2FoldChange'],
			'y' => $y_temp,
			'comparison_index' => $comparison['ComparisonIndex'],
			'gene_index' => $GENE_INDEX,
		);
	}



	asort($ALL_APPEARED_Y);
	$HEIGHT = max(800, count($ALL_APPEARED_Y) * 16 + 190);
	$ALL_APPEARED_Y_ORDERED = array();
	foreach ($ALL_APPEARED_Y as $option) {
		$ALL_APPEARED_Y_ORDERED[] = $option;
	}
	$dir = $BXAF_CONFIG['USER_FILES']['TOOL_BUBBLE_PLOT'] . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'];
	if (!is_dir($dir)) {
		mkdir($dir, 0755, true);
	}
	file_put_contents($dir . '/y_field_options.txt', serialize($ALL_APPEARED_Y_ORDERED));
	file_put_contents($dir . '/all_genes.txt', serialize($ALL_GENES));



  //-----------------------------------------------------------------------------
  // Save CSV File for Users to Download
  $csv_info = array();
  foreach ($ALL_MARKER as $markers) {
    foreach ($markers as $marker) {
      $csv_info[] = array(
        $gene_name,
        $marker['COMPARISON_ID'],
        $marker['LOGFC'],
        $marker['PVALUE'],
        $marker['ADJPVALUE']
      );
    }
  }
  $file = fopen($dir . '/download.csv',"w");
  fputcsv($file, array('GeneName', 'ComparisonName', 'Log2FC', 'PValue', 'FDR'));
  foreach ($csv_info as $line){
    fputcsv($file, $line);
  }
  fclose($file);
  chmod($dir . '/download.csv', 0755);




	// Output
	echo '
	<div class="row mt-1">
		<div class="col-md-2">
      <button class="btn btn-sm btn-primary" id="btn_modify_settings"
        onclick="$(\'#first_form_div, #second_form_div\').slideToggle(300);">
        <i class="fas fa-cogs"></i> Modify Settings
      </button>
      <a class="mt-1 btn btn-sm btn-info" href="' . $BXAF_CONFIG['BXAF_URL'] . 'app_data/cache/user_files_bubble_plot/' . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'] . '/download.csv" download>
        <i class="fas fa-download"></i> Download Data
      </a>
			<a class="mt-1 btn btn-sm btn-warning btn_save_svg_' . $TIME . '" id="btn_save_svg" href="javascript:void(0);">
        <i class="fas fa-download"></i> Download SVG
      </a>

    </div>
		<div class="col-md-10">
			<div class="alert alert-success" style="width:35em">
			<p>The plot contains <strong>' . count($ALL_GENES) . '</strong> out of <strong>' . intval(count($data_comparison) - $NUMBER_MISSING_DATA) . '</strong> data points.</p>
			</div>
		</div>
	</div>
	<div class="w-100" style="min-height:' . $HEIGHT . 'px;" id="plot_div"></div>';
	echo "<script>";



	$n_color = 0;
	$color_scheme = $COLOR_SCHEME_50;
	if(count($ALL_MARKER) < 10){
		$color_scheme = $COLOR_SCHEME_10;
	}
	else if(count($ALL_MARKER) > 10 && count($ALL_MARKER) <= 20){
		$color_scheme = $COLOR_SCHEME_20;
	}

	$index = 0;
	foreach ($ALL_MARKER as $key => $value) {
		$temp_logfc = array();
		$temp_y = array();
		$temp_area = array();
		$temp_text = array();
		$temp_comparison_index = array();
		$temp_comparison_id = array();
		foreach ($value as $k => $v) {
			$temp_logfc[] = $v['LOGFC'];

			// Set Y Axis Label
			$temp_y[] = "'" . addslashes($v['Y_FIELD']) . "'";


			$temp_text[] = "'Comparison ID: " . $v['COMPARISON_ID']  . "<br />Category: " . $v['COMPARISON_CATEGORY']  . "<br />Contrast: " . addslashes($v['COMPARISON_CONTRAST'])  . "<br />" . substr($Y_FIELD, strpos($Y_FIELD, '_')+1) . ": " .  addslashes($v['Y_FIELD']) . "<br />"  . substr($COLORING_FIELD, strpos($COLORING_FIELD, '_')+1) . ": " . addslashes($v['COLORING_FIELD']) . "<br />P-value: " . $v['PVALUE'] . "<br />Adj P-value: " . $v['ADJPVALUE'] . "<br />log2FC: " . $v['LOGFC'] . "<br />'";

			if ((-1000) * log10($v[$AREA_FIELD_MODIFIED]) < 5000 && (-1000) * log10($v[$AREA_FIELD_MODIFIED]) > 100) {
				$temp_area[] = (-1000) * log10($v[$AREA_FIELD_MODIFIED]);
			} else if ((-1000) * log10($v[$AREA_FIELD_MODIFIED]) > 5000) {
				$temp_area[] = 5000;
			} else {
				$temp_area[] = 100;
			}

			$temp_comparison_index[] = $v['COMPARISON_INDEX'];
			$temp_comparison_id[] = $v['COMPARISON_ID'];
		}



		echo "
		var trace" . $index . " = {
			x: [" . implode(', ', $temp_logfc) . "],
			y: [" . implode(', ', $temp_y) . "],
			name: '";
			$key_modified = str_replace(';', '<br>', $key);
			echo addslashes($key_modified);


		echo "',
			hoverinfo: 'text',
			text: [" . implode(', ', $temp_text) . "],
			mode: 'markers',
			marker: {
				'color': '#" . $color_scheme[$n_color] . "',
				'size': [" . implode(', ', $temp_area) . "],
				'sizeref': 7,
				'sizemode': 'area',
				'comparison_index': ['" . implode("', '", $temp_comparison_index) . "'],
				'comparison_id': ['" . implode("', '", $temp_comparison_id) . "'],
			}
		};";
		$index++;

		$n_color++;
		if($n_color >= count($color_scheme)) $n_color = 0;
	}


	echo "
	var data = [trace0";

	for ($i = 1; $i < $index; $i++) {
		echo ", trace".$i;
	}

	echo "];

	var layout = {
		margin: {
			l: 300
		},

		title: 'Bubble Chart for " . addslashes($gene_name) . "<br>Colored by " . $COLORING_FIELD . "',
		showlegend: true,
		height: " . $HEIGHT . ",
		//width: 1200,
		xaxis: {
			title: 'Log 2 Fold Change',
		},
		yaxis: {
			// title: '" . addslashes($Y_FIELD) . "',
			categoryorder: 'category ascending',
      range: [-0.5, " . count($ALL_APPEARED_Y) . ".5]
		},
		hovermode: 'closest',
	};

	main_plot = Plotly
    .plot('plot_div', data, layout, {displaylogo:false, modeBarButtonsToRemove:['sendDataToCloud'], scrollZoom:true, displayModeBar: true})
    .then(function(gd){

      $(document).on('click', '.btn_save_svg_" . $TIME . "', function() {
        Plotly
          .downloadImage(gd, {
						filename: 'bubblePlot',
						format:'svg',
						height:" . $HEIGHT . ",
						width:1600
					})
          .then(function(filename){
              console.log(filename);
          });
      });
      $('.loader').remove();
    });


	$(document).ready(function() {
		var graphDiv = document.getElementById('plot_div');

		graphDiv.on('plotly_click', function(data){
			var comparison = data.points[0].data.marker.comparison_id[data.points[0].pointNumber];
			var comparison_index = data.points[0].data.marker.comparison_index[data.points[0].pointNumber];
			console.log(data.points[0]);
			bootbox.alert('<h4>Comparison ' + comparison + '</h4><hr /><ul><li><a href=\"../tool_search/view.php?type=comparison&id='+comparison_index+'\" target=\"_blank\">Comparison Details</a></li><li><a href=\"../tool_volcano_plot/index.php?comparison_id='+comparison_index+'\" target=\"_blank\">Comparison Volcano Chart</a></li><li><a href=\"../tool_pathway/index.php?comparison_id='+comparison_index+'\" target=\"_blank\">Pathway View</a></li><li><a href=\"../tool_search/index.php?type=sample&comparison_id='+comparison_index+'\" target=\"_blank\">Related Samples</a></li></ul>');
		});


		graphDiv.on('plotly_selected', function(eventData) {
			var x = [];
			var y = [];
			eventData.points.forEach(function(pt) {
				x.push(pt.x);
				y.push(pt.y);
			});
			$.ajax({
				type: 'POST',
				url: 'exe.php?action=show_table&type=lasso_select',
				data: {x:x, y:y},
				success: function(responseText) {
          console.log(responseText);
					$('#table_div').html(responseText);
				}
			});
		});
	});

	</script>";
	exit();
}






// Generate Chart for Genes .vs. Comparisons
else if (isset($_GET['action']) && $_GET['action'] == 'genes_comparisons_generate_chart') {

    header('Content-Type: application/json');
    $OUTPUT['type'] = 'Error';


	$gene_idnames   = category_text_to_idnames($_POST['Gene_List'], 'name', 'gene', $_SESSION['SPECIES_DEFAULT']);
	if (! is_array($gene_idnames) || count($gene_idnames) <= 0) {
        $OUTPUT['detail'] = 'No genes found. Please enter at least one gene name to continue.' ;
        echo json_encode($OUTPUT);
        exit();
    }

    $comparison_indexnames = category_text_to_idnames($_POST['Comparison_List'], 'name', 'comparison', $_SESSION['SPECIES_DEFAULT']);
	if (! is_array($comparison_indexnames) || count($comparison_indexnames) <= 0) {
        $OUTPUT['detail'] = 'No comparisons found. Please enter at least one comparison name to continue.' ;
        echo json_encode($OUTPUT);
        exit();
    }

	$ALL_COMPARISONS = array();
	$sql = "SELECT `ID`, `Name`, `ComparisonCategory`, `ComparisonContrast`  FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS']}` WHERE `Species` = '{$_SESSION['SPECIES_DEFAULT']}' AND {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `Name` IN (?a)";
	$results = $BXAF_MODULE_CONN -> get_all($sql, $comparison_indexnames);
	foreach($results as $data){
		$ALL_COMPARISONS[$data['ID']] = array(
			'ID'       => $data['Name'],
			'Category' => $data['ComparisonCategory'],
			'Contrast' => $data['ComparisonContrast']
		);
	}

	// Generate Ouput Data
	$ALL_DATA = array();
	$EXISTING_NUMBER = array( // Save all appeared x-coordinage & y-coordinage
		'gene'                         => array(),
		'comparison'                   => array()
	);
	$downloaded_csv_info = array();

	$geneIndexes       = array_keys($gene_idnames);
	$comparisonIndexes = array_keys($comparison_indexnames);

	// $data_public  = tabix_search_records_public( $geneIndexes, $comparisonIndexes, 'ComparisonData');
	// $data_private = tabix_search_records_private($geneIndexes, $comparisonIndexes, 'ComparisonData');
	// $data_comparisons  = array_merge($data_public, $data_private);

    ini_set('memory_limit','8G');
    $data_comparisons = tabix_search_bxgenomics($geneIndexes, $comparisonIndexes, 'ComparisonData');


	// Group Data by Comp & Genes
	$ALL_DATA_SRC = array();
	$src_data_row = array();
	foreach ($geneIndexes as $geneIndex) {
		$src_data_row[$geneIndex] = array();
	}
	foreach ($comparisonIndexes as $comparisonIndex) {
		$ALL_DATA_SRC[$comparisonIndex] = $src_data_row;
	}

	foreach ($data_comparisons as $tabix_data_row) {
		// Use smallest p-val for duplicate geneIndex & compIndex
		if (count($ALL_DATA_SRC[$tabix_data_row['ComparisonIndex']][$tabix_data_row['GeneIndex']]) > 0
			&& $ALL_DATA_SRC[$tabix_data_row['ComparisonIndex']][$tabix_data_row['GeneIndex']]['PValue'] < $tabix_data_row['PValue']) {
			continue;
		}
		$ALL_DATA_SRC[$tabix_data_row['ComparisonIndex']][$tabix_data_row['GeneIndex']] = $tabix_data_row;
	}


	$n_color = 0;
	$color_scheme = $COLOR_SCHEME_50;
	if(count($comparisonIndexes) < 10){
		$color_scheme = $COLOR_SCHEME_10;
	}
	else if(count($comparisonIndexes) > 10 && count($comparisonIndexes) <= 20){
		$color_scheme = $COLOR_SCHEME_20;
	}
	foreach ($comparisonIndexes as $comparisonIndex) {
	    $value = $ALL_DATA_SRC[$comparisonIndex];

	    $temp_x                        = array();
	    $temp_y                        = array();
	    $temp_text                     = array();
	    $temp_area                     = array();
	    $temp_gene_index               = array();
	    $temp_gene_name                = array();
	    $temp_comparison_index         = array();
	    $temp_comparison_name          = array();

	    foreach ($value as $geneIndex => $v) {
			$downloaded_csv_info_row     = array();
			$temp_x[]                    = $v['Log2FoldChange'];
			$temp_y[]                    = $gene_idnames[$geneIndex];
			$text                        = 'Log2FC: ' . $v['Log2FoldChange'] . '<br />';
			$text                       .= 'Category: ' . $ALL_COMPARISONS[$comparisonIndex]['Category'] . '<br />';
			$text                       .= 'Contrast: ' . $ALL_COMPARISONS[$comparisonIndex]['Contrast'] . '<br />';
			$text                       .= 'Gene: ' . $gene_idnames[$geneIndex] . '<br />';
			$text                       .= 'Comparison: ' . $ALL_COMPARISONS[$comparisonIndex]['ID'] . '<br />';
			$text                       .= 'FDR: ' . $v['AdjustedPValue'] . '<br />';
			$temp_text[]                 = $text;

			$marker                    = (-17) * log10($v['AdjustedPValue']);
			if ($marker > 50)  $marker = 50;
			if ($marker < 8)   $marker = 8;
			$temp_area[]               = $marker;

			$temp_gene_index[]         = $geneIndex;
			$temp_comparison_index[]   = $comparisonIndex;
			$temp_gene_name[]          = $gene_idnames[$geneIndex];
			$temp_comparison_name[]    = $ALL_COMPARISONS[$comparisonIndex]['ID'];

			$downloaded_csv_info_row[] = $gene_idnames[$geneIndex];
			$downloaded_csv_info_row[] = $ALL_COMPARISONS[$comparisonIndex]['ID'];
			$downloaded_csv_info_row[] = $v['Log2FoldChange'];
			$downloaded_csv_info_row[] = $v['PValue'];
			$downloaded_csv_info_row[] = $v['AdjustedPValue'];
			$downloaded_csv_info[]     = $downloaded_csv_info_row;
	    }



	    // Ignore empty data trace
		if (count($temp_x) > 0) {
			$ALL_DATA[] = array(
				'x'                        => $temp_x,
				'y'                        => $temp_y,
				'name'                     => $ALL_COMPARISONS[$comparisonIndex]['ID'],
				'mode'                     => 'markers',
				'hoverinfo'                => 'text',
				'text'                     => $temp_text,
				'marker'                   => array(
					'color'                => '#' . $color_scheme[$n_color],
					'size'                   => $temp_area,
					'gene'                   => $temp_gene_index,
					'gene_name'              => $temp_gene_name,
					'comparison'             => $temp_comparison_index,
					'comparison_name'        => $temp_comparison_name,
		        )
			);

			$n_color++;
			if($n_color >= count($color_scheme)) $n_color = 0;

			// Save appeared x & y
			$EXISTING_NUMBER['comparison'][] = $ALL_COMPARISONS[$comparisonIndex];
			$temp_genes = array_unique(array_merge($EXISTING_NUMBER['gene'], $temp_y));
			$EXISTING_NUMBER['gene'] = array();
			foreach ($temp_genes as $gene) {
				$EXISTING_NUMBER['gene'][] = $gene;
			}
		}

	}


	//-----------------------------------------------------------------------------
	// Save CSV File for Users to Download

	$dir = $BXAF_CONFIG['USER_FILES']['TOOL_BUBBLE_PLOT'] . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'];
	if (!is_dir($dir)) mkdir($dir, 0755, true);
	$file = fopen($dir . '/download.csv',"w");
	fputcsv($file, array('GeneName', 'ComparisonName', 'Log2FC', 'PValue', 'FDR'));
	foreach ($downloaded_csv_info as $line){
		fputcsv($file, $line);
	}
	fclose($file);
	chmod($dir . '/download.csv', 0755);

	$dir_to = dirname(__FILES__) . "/files/{$BXAF_CONFIG['BXAF_USER_CONTACT_ID']}";
	if (!is_dir($dir_to)) mkdir($dir_to, 0755, true);
	copy("{$dir}/download.csv", "{$dir_to}/download.csv");


  //-----------------------------------------------------------------------------
  // DataTable HTML Code
  $TABLE_DATA = array();
  $TABLE_HEADER = array();
  $data_default_row = array();   // Default row for table data
  foreach ($ALL_COMPARISONS as $comp) {
    $TABLE_HEADER[] = $comp['ID'] . '_logFC';
    $TABLE_HEADER[] = $comp['ID'] . '_PVal';
    $TABLE_HEADER[] = $comp['ID'] . '_FDR';
  }
  foreach ($TABLE_HEADER as $header) {
    $data_default_row[$header] = '.';
  }
  foreach ($geneIndexes as $geneIndex) {
    $TABLE_DATA[$geneIndex] = $data_default_row;
  }

  foreach ($data_comparisons as $row) {
    $temp_comp_id = $ALL_COMPARISONS[$row['ComparisonIndex']]['ID'];

    if (isset($TABLE_DATA[$row['GeneIndex']][$temp_comp_id . '_PVal'])
      && $TABLE_DATA[$row['GeneIndex']][$temp_comp_id . '_PVal'] != '.'
      && $TABLE_DATA[$row['GeneIndex']][$temp_comp_id . '_PVal'] < $row['PValue']) {
      continue;
    }

    $TABLE_DATA[$row['GeneIndex']][$temp_comp_id . '_logFC'] = $row['Log2FoldChange'];
    $TABLE_DATA[$row['GeneIndex']][$temp_comp_id . '_PVal'] = $row['PValue'];
    $TABLE_DATA[$row['GeneIndex']][$temp_comp_id . '_FDR'] = $row['AdjustedPValue'];
  }



  $TIME = time();
  $TABLE = '<div class="w-100 table-responsive">
    <table class="table table-bordered" id="datatable_' . $TIME . '">
      <thead>
        <tr>
          <th>GeneName</th>
          <th>GeneIndex</th>';

          foreach ($TABLE_HEADER as $header) {
						if (!isset($_POST['table_option_logfc']) && substr($header, -5)=='logFC'
							|| !isset($_POST['table_option_pval']) && substr($header, -4)=='PVal'
							|| !isset($_POST['table_option_fdr']) && substr($header, -3)=='FDR') {
							continue;
						}
            $TABLE .= "<th style='width:250px !important;'>";
						if (strpos($header, '.') !== false) {
							$TABLE .= substr($header, 0, 17) . '<br />' . substr($header, 17);
						} else {
							$TABLE .= $header;
						}
						$TABLE .= "</th>";
          }

  $TABLE .= '
        </tr>
      </thead>
      <tbody>';

	foreach ($geneIndexes as $geneIndex) {
  		$TABLE .= '<tr>';
  		$TABLE .= '
  		<td>' . $gene_idnames[$geneIndex] . '</td>
  		<td>' . $geneIndex . '</td>';

  		$index = 0;
  		foreach ($TABLE_DATA[$geneIndex] as $key => $value) {

  			if (!isset($_POST['table_option_logfc']) && substr($key, -5)=='logFC'
  				|| !isset($_POST['table_option_pval']) && substr($key, -4)=='PVal'
  				|| !isset($_POST['table_option_fdr']) && substr($key, -3)=='FDR') {
  				$index++;
  				continue;
  			}
  			$header = $TABLE_HEADER[$index];

  			$value = sprintf("%.5f", $value);

  			if (substr($header, -5)=='logFC') {
  				$TABLE .= "<td style='width:250px !important;color:" . get_stat_scale_color($value, 'logFC') . ";'>{$value}</td>";
  			} else if (substr($header, -4)=='PVal') {
  				$TABLE .= "<td style='width:250px !important;color:" . get_stat_scale_color($value, 'PVal') . ";'>{$value}</td>";
  			} else if (substr($header, -3)=='FDR') {
  				$TABLE .= "<td style='width:250px !important;color:" . get_stat_scale_color($value, 'FDR') . ";'>{$value}</td>";
  			}
  			$index++;
  		}
  		$TABLE .= '</tr>';
  	}

  $TABLE .= '
      </tbody>
    </table></div>';



  // Generate Output
  $OUTPUT = array(
    'data'                         => $ALL_DATA,
    'layout'                       => array(),
    'settings'                     => array()
  );

  $OUTPUT['layout'] = array(
    'title'                        => 'Bubble Plot',
    'xaxis'                        => array('title' => 'Log 2 Fold Change'),
    'yaxis'                        => array('range' => array(-2, count($EXISTING_NUMBER['gene']) + 1)),
  	'margin'                       => array('l' => intval(100 * floatval($_POST['left_factor']))),
    'hovermode'                    => 'closest',
    'height'                       => intval(floatval($_POST['height_factor']) * max(500, count($EXISTING_NUMBER['gene']) * 16 + 200)),
    // 'width'                     => 400
  );

	$OUTPUT['settings'] = array(
		'displaylogo'                  => false,
		'modeBarButtonsToRemove'       => array('sendDataToCloud'),
		'scrollZoom'                   => true,
		'displayModeBar'               => true,
	);

	$OUTPUT['Number'] = $EXISTING_NUMBER;
	$OUTPUT['userid'] = $BXAF_CONFIG['BXAF_USER_CONTACT_ID'];
	$OUTPUT['time'] = $TIME;
	$OUTPUT['table'] = $TABLE;


	$OUTPUT['type'] = 'Success';
	echo json_encode($OUTPUT);

	exit();
}


?>