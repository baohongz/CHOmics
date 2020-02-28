<?php
include_once('config.php');

//---------------------------------------------------------------------------------------------
// Add New Chart
//---------------------------------------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] == 'add_new_chart') {

	$current_index = intval($_POST['current_index']);
	$comparison_name = trim($_POST['comparison_name']);

	echo '
	<div class="chart_setting_single_container w-100">

		<div class="row">
			<div class="col-md-2 text-md-right text-muted">
				Comparison ID<br />
		        <a href="javascript:void(0);" onclick="$(this).parent().parent().parent().remove();" style="color:red">
		          <i class="fas fa-angle-double-right"></i> Remove Comp
		        </a>
			</div>

			<div class="col-md-10">

			   <div class="input-group mb-3" style="max-width:30em;">

				<input id="comparison_id_' . $current_index . '" name="comparison_id[]" class="form-control input_comparison_id" value="' . $comparison_name . '" required>

				<div class="input-group-append">
				  <button class="btn_search_comparison btn btn-success" inhouse="false" type="button" index="' . $current_index . '">
					<i class="fas fa-search"></i> Comparisons
				  </button>
				</div>
			  </div>

			  <span class="text-muted">Please enter the comparison id, e.g., GSE43696.GPL6480.test2</span>
			</div>

		</div>

		<div class="row mt-3">
			<div class="col-md-2 text-md-right text-muted">
				Y-axis Statistics:
			</div>
			<div class="col-md-10">
				<label>
					<input type="radio" name="volcano_y_statistics_' . $current_index . '" value="P-value">
					P-value
				</label>
				&nbsp;&nbsp;
				<label>
					<input type="radio" name="volcano_y_statistics_' . $current_index . '" value="FDR" checked>
					FDR
				</label>
			</div>
		</div>

		<div class="row mt-2">
			<div class="col-md-2 text-md-right pt-1 text-muted">
				Chart Name
			</div>
			<div class="col-md-10">
				<input class="form-control" name="chart_name[]" value="Volcano Chart" style="width:20em;" required>
			</div>
		</div>

		<div class="row mt-2">
			<div class="col-md-2 text-md-right pt-1 text-muted">
				Fold Change Cutoff:
			</div>
			<div class="col-md-10 form-inline">
				<select class="form-control volcano_fc_cutoff custom-select float-left m-r-1" name="volcano_fc_cutoff[]" style="width:8.6em;">
					<option value="2">2</option>
					<option value="4">4</option>
					<option value="8">8</option>
					<option value="enter_value">Enter Value</option>
				</select>
				<input class="form-control ml-2" name="volcano_fc_custom_cutoff[]" placeholder="Custom Cutoff" style="width:10.3em;" hidden>
			</div>
		</div>

		<div class="row mt-2">
			<div class="col-md-2 text-md-right pt-1 text-muted">
				Statistic Cutoff:
			</div>
			<div class="col-md-10 form-inline">
				<select class="form-control volcano_statistic_cutoff custom-select float-left m-r-1" name="volcano_statistic_cutoff[]" style="width:8.6em;">
					<option value="0.05">0.05</option>
					<option value="0.01">0.01</option>
					<option value="0.001">0.001</option>
					<option value="enter_value">Enter Value</option>
				</select>
				<input class="form-control ml-2" name="volcano_statistic_custom_cutoff[]" placeholder="Custom Cutoff" style="width:10.3em;" hidden>
			</div>
		</div>


		<hr />

	</div>';
	exit();
}





//---------------------------------------------------------------------------------------------
// Generate Volcano Chart
//---------------------------------------------------------------------------------------------

if (isset($_GET['action']) && $_GET['action'] == 'volcano_generate_chart') {

	$CHART_NUMBER = intval($_POST['chart_number']);

	$volcano_chart_width = 1000;
	if(isset($_POST['volcano_chart_width']) && intval($_POST['volcano_chart_width']) > 100 && intval($_POST['volcano_chart_width']) < 5000) $volcano_chart_width = intval($_POST['volcano_chart_width']);
	$volcano_chart_height = 1000;
	if(isset($_POST['volcano_chart_height']) && intval($_POST['volcano_chart_height']) > 100 && intval($_POST['volcano_chart_height']) < 5000) $volcano_chart_height = intval($_POST['volcano_chart_height']);


	for ($i = 0; $i < $CHART_NUMBER; $i++) {
		if ($_POST['volcano_fc_cutoff'][$i] == 'enter_value' && trim($_POST['volcano_fc_custom_cutoff'][$i]) == '') {
			echo 'Error: Please enter custom FC cutoff.';
			exit();
		}
		if ($_POST['volcano_statistic_cutoff'] == 'enter_value' && trim($_POST['volcano_statistic_custom_cutoff']) == '') {
			echo 'Error: Please enter custom statistic cutoff.';
			exit();
		}
	}


	for ($i = 0; $i < $CHART_NUMBER; $i++) {

		// Set Default Value
		$TIME = time();
		$fc_cutoff = ($_POST['volcano_fc_cutoff'][$i] == 'enter_value') ? floatval($_POST['volcano_fc_custom_cutoff'][$i]) : $_POST['volcano_fc_cutoff'][$i];
		$statistic_cutoff = ($_POST['volcano_statistic_cutoff'][$i] == 'enter_value') ? floatval($_POST['volcano_statistic_custom_cutoff'][$i]) : $_POST['volcano_statistic_cutoff'][$i];
		$CHART_NAME = trim($_POST['chart_name'][$i]);
		$significance_threshold = abs(log10($statistic_cutoff));
		$logfc_threshold = abs(log10($fc_cutoff) / log10(2));
		$X_MIN = 0;
		$X_MAX = 0;
		$Y_MIN = 0;
		$Y_MAX = 0;

		$comparison_name = $_POST['comparison_id'][$i];
		$sql = "SELECT `ID` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS']}` WHERE `Name` = ?s AND `Species` = '" . $_SESSION['SPECIES_DEFAULT'] . "' AND " . $BXAF_CONFIG['QUERY_DEFAULT_FILTER'] . "";
		$comparison_id = $BXAF_MODULE_CONN -> get_one($sql, $comparison_name);

		if ($comparison_id == '') {
			echo '
			<div id="volcano_diagram_container_' . $i . '" class="volcano">
				No comparison found for Comparison "' . $comparison_name . '".
			</div>';
			continue;
		}

		$comparison_index = intval($comparison_id);

		// Check Customized Gene Symbol
		$CUSTOMIZE_GENE = false;
		$CUSTOMIZE_GENE_LIST = array();
		$OTHER_GENE_LABEL = 'true';
		if (isset($_POST['volcano_show_gene']) && $_POST['volcano_show_gene'] == 'customize') {
			$CUSTOMIZE_GENE = true;
			$OTHER_GENE_LABEL = 'false';
			$CUSTOMIZE_GENE_LIST = category_text_to_idnames($_POST['Gene_List'], 'name', 'Gene', $_SESSION['SPECIES_DEFAULT']);
		}


		// Get All Data
		$DATA_ALL = array(
			'selected' => array(),
			'up_regulated' => array(),
			'down_regulated' => array(),
			'unregulated' => array()
		);
		$DATA_ROWS = array(
			'selected' => array("Gene ID,Gene Name," . $_POST['volcano_y_statistics_' . $i] . ",log2FC"),
			'up_regulated' => array("Gene ID,Gene Name," . $_POST['volcano_y_statistics_' . $i] . ",log2FC"),
			'down_regulated' => array("Gene ID,Gene Name," . $_POST['volcano_y_statistics_' . $i] . ",log2FC")
		);
		if ($_POST['volcano_y_statistics_' . $i] == 'P-Value') {
			$Y_COL_NAME = 'PValue';
		} else {
			$Y_COL_NAME = 'AdjustedPValue';
		}


	    // Get Comparison Data
		// $data_public  = tabix_search_records_public( array(), array($comparison_index), 'ComparisonData');
		// $data_private = tabix_search_records_private(array(), array($comparison_index), 'ComparisonData');
		// $data = array_merge($data_public, $data_private);

		ini_set('memory_limit','8G');
		$data = tabix_search_bxgenomics(  array(), array($comparison_index), 'ComparisonData');

		$data_limit = 60000;
		$data_total = count($data);
		if ($data_total > $data_limit) {
			$data = array_slice($data, 0, $data_limit, false);
		}

		$comparison_gene_data = array();
		foreach ($data as $row) {
			$comparison_gene_data[] = array(
				'GeneIndex'      => $row['GeneIndex'],
				'Log2FoldChange' => $row['Log2FoldChange'],
				'PValue'         => $row['PValue'],
				'AdjustedPValue' => $row['AdjustedPValue'],
				'Name'           => $row['Name'],
			);
		}


		foreach ($comparison_gene_data as $key => $value) {

			// Get basic info
			$x = floatval($value['Log2FoldChange']);
			$y = -log10(floatval($value[$Y_COL_NAME]));
			bcscale(5);
			$y = bcpow($y, 1);

			$sql = "SELECT `GeneName` FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_GENES'] . "` WHERE `ID`=" . intval($value['GeneIndex']);

			$gene_info = $BXAF_MODULE_CONN -> get_row($sql);
			$name = trim($gene_info['GeneName']);
			$alt_name = trim($gene_info['GeneName']);



			// Update Border
			$X_MIN = min($X_MIN, $x);
			$X_MAX = max($X_MAX, $x);
			$Y_MIN = min($Y_MIN, $y);
			$Y_MAX = max($Y_MAX, $y);

			// Group The Genes
			// 1. If the gene is entered by the user
			if (in_array($alt_name, $CUSTOMIZE_GENE_LIST)) {

				$DATA_ROWS['selected'][] = $name . ',' . $alt_name . ',' . $y . ',' . $x;

				$row_temp = array(
					'x' => $x,
					'y' => $y,
					'name' => $name,
					'alt_name' => $alt_name,
				);

				$row_temp['logfc']     = $value['Log2FoldChange'];
				$row_temp['FDR']       = $value['AdjustedPValue'];
				$row_temp['unique_id'] = $value['Name'];
				$row_temp['pvalue']    = $value['PValue'];

				$DATA_ALL['selected'][] = $row_temp;
			}
			// 2. Up-Regulated Genes
			else if ($x > $logfc_threshold && $y > $significance_threshold) {
				$DATA_ROWS['up_regulated'][] = $name . ',' . $alt_name . ',' . $y . ',' . $x;
				$DATA_ALL['up_regulated'][] = array(
					'x' => $x,
					'y' => $y,
					'name' => $name,
					'alt_name' => $alt_name
				);
			// 3. Down-Regulated Genes
			} else if ($x < (-1) * $logfc_threshold && $y > $significance_threshold) {
				$DATA_ROWS['down_regulated'][] = $name . ',' . $alt_name . ',' . $y . ',' . $x;
				$DATA_ALL['down_regulated'][] = array(
					'x' => $x,
					'y' => $y,
					'name' => $name,
					'alt_name' => $alt_name
				);
			// 4. Unregulated Genes
			} else {
				$DATA_ALL['unregulated'][] = array(
					'x' => $x,
					'y' => $y,
					'name' => $name,
					'alt_name' => $alt_name
				);
			}
		}


		// Output Chart
		echo '
		    <div class="">
		      <div class="w-100 alert alert-warning text-muted">
		        Fold Change Cutoff: ' . $fc_cutoff . ', &nbsp;
		        Log<sub>2</sub>(Fold Change Cutoff): ' . number_format(log($fc_cutoff, 2), 3) . ', &nbsp;
		        ' . $_POST['volcano_y_statistics_' . $i] . ' Cutoff: ' . $statistic_cutoff . ', &nbsp;
		         -Log<sub>10</sub>(' . $_POST['volcano_y_statistics_' . $i] . ' Cutoff): ' . number_format(log10($statistic_cutoff) * (-1), 3) . '<br />';

				if ($data_total > $data_limit) {
					echo "<p class='text-danger'>{$data_total} records found. {$data_limit} are displayed.</p>";
				}

			echo '  <a href="../tool_search/view.php?type=comparison&id=' . $comparison_index . '" target="_blank"><i class="fas fa-list"></i> View comparison details</a>
					<a class="ml-2" href="../tool_search/comparison_table.php?id=' . $comparison_index . '" target="_blank"><i class="fas fa-list"></i> View comparison genes</a>
		      </div>
		      <div id="volcano_diagram_container_' . $i . '" class="volcano"></div>
		    </div>';

		echo '
		<script>

			$(document).ready(function(){

				$(\'#volcano_diagram_container_' . $i . '\').highcharts({

					"chart":{"type":"scatter", "zoomType":"xy", "width":"' . $volcano_chart_width . '", "height":"' . $volcano_chart_height . '" },

					"title": { "text": "' . $CHART_NAME . '" },

					"xAxis":{
						"title":{
							"enabled":true,
							"text":"log2(Fold Change)"
						},
						"startOnTick":true,
						"endOnTick":true,
						"showLastLabel":true,
						"gridLineWidth":1,
						"min":' . $X_MIN . ',
						"max":' . $X_MAX . '
					},

					"yAxis":{
						"title":{
							"enabled":true,
							"text":"-log10(' . $_POST['volcano_y_statistics_' . $i] . ')"
						},
						"startOnTick":true,
						"endOnTick":true,
						"showLastLabel":true,
						"gridLineWidth":1,
						"min":' . $Y_MIN . ',
						"max":' . $Y_MAX . '
					},

					"plotOptions":{
						"scatter":{
							"allowPointSelect":true,
							"marker": {"radius":2,"states":{"hover":{"enabled":true,"lineColor":"#333333"}}},
							"states": {"hover":{"marker":{"enabled":true}}},
							"turboThreshold":50000
						},

						series: {
							cursor: \'pointer\',
							point: {
								events: {
									click: function (e) {
										$(\'#geneList\').val($(\'#geneList\').val() + \' \' + this.alt_name);

										var current_gene = this;
										// console.log(this);

										bootbox.alert(
											"<h4>Gene " + current_gene.alt_name + "</h4><hr /><ul><li><a href=\'../tool_gene_expression_plot/index.php?gene_name=" + current_gene.alt_name + "\' target=\'_blank\'>View Gene Expression</a></li><li><a href=\'../tool_bubble_plot/index.php?gene_name=" + current_gene.alt_name + "\' target=\'_blank\'>View Bubble Plot</a></li></ul>"
										);
									}
								}
							}
						}
					},

					tooltip: {
						useHTML: true,
						headerFormat: \'<span style="font-size:12px; color:green">{series.name}<br>\',
						pointFormat: "<b>name: </b>{point.alt_name}<br><b>id: </b><a href=\'http://useast.ensembl.org/Homo_sapiens/Gene/Summary?db=core;g={point.name}\' target=_blank>{point.name}</a><br><b>fold change: </b>{point.x}<br><b>significance: </b>{point.y}<br>Click to view detail"
					},

					"series": [';

					// Genes Entered By Users
					if ($CUSTOMIZE_GENE) {
						echo '
						{
							"name":"selected",
							"color":"#f79e4d",
							marker: {
								radius: 4
							},
							dataLabels: {
								enabled: true,
								x: 35,
								y: 5,
								formatter:function() {
									return this.point.alt_name;
									/*if (this.point.y>2) {
										return this.point.alt_name;
									}*/
								},
								style:{color:"black"}
							},

							"data":[';

							foreach($DATA_ALL['selected'] as $value) {
								echo '{"x":' . $value['x'] . ',"y":' . $value['y'] . ', "name":"' . $value['name'] . '", "alt_name":"' . $value['alt_name'] . '"},';
							}

						echo '
							]
						},';
					}


					echo '

						{
							"name":"up-regulated",
							"color":"#FF0000",
							dataLabels: {
								enabled: ' . $OTHER_GENE_LABEL . ',
								x: 35,
								y: 5,
								formatter:function() {
									if (this.point.y>2) {
									return this.point.alt_name;
									}
								},
								style:{color:"black"}
							},

							"data":[';

							foreach($DATA_ALL['up_regulated'] as $value) {
								echo '{"x":' . $value['x'] . ',"y":' . $value['y'] . ', "name":"' . $value['name'] . '", "alt_name":"' . $value['alt_name'] . '"},';
							}

					echo '
							]

						},


						{
						"name":"down-regulated",
						"color":"#009966",
						dataLabels: {
							enabled: ' . $OTHER_GENE_LABEL . ',
							x:-35,
							y: 5,
							formatter:function() {
								if (this.point.y>2) {
								return this.point.alt_name;
								}
							},
							style:{color:"black"}
						},

						"data":[';

							foreach($DATA_ALL['down_regulated'] as $value) {
								echo '{"x":' . $value['x'] . ',"y":' . $value['y'] . ', "name":"' . $value['name'] . '", "alt_name":"' . $value['alt_name'] . '"},';
							}

					echo '
							]
						},

						// unregulated data
						{
							"name":"unregulated",
							"color":"#AEB6BF",
							"data":[';

							$index_unregulated = 0;
							shuffle($DATA_ALL['unregulated']);
							$unregulated = array_slice($DATA_ALL['unregulated'], 0, 5000);
							foreach($unregulated as $value) {
								echo '{"x":' . $value['x'] . ',"y":' . $value['y'] . ', "name":"' . $value['name'] . '", "alt_name":"' . $value['alt_name'] . '"},';
							}

							// $index_unregulated = 0;
							// foreach($DATA_ALL['unregulated'] as $value) {
							// 	if ($index_unregulated < 5000) {
							// 		echo '{"x":' . $value['x'] . ',"y":' . $value['y'] . ', "name":"' . $value['name'] . '", "alt_name":"' . $value['alt_name'] . '"},';
							// 	}
							// 	$index_unregulated += 1;
							// }

					echo '
							]
						},


						{
							"name":"downfold threshold",
							"color":"#000000",
							"type":"line",
							"dashStyle":"Dash",
							"marker":{"enabled":false},
							"data":[[-' . $logfc_threshold . ',' . $Y_MIN . '],[-' . $logfc_threshold . ',' . 2 * $Y_MAX . ']]
						},


						{
							"name":"upfold threshold",
							"color":"#000000",
							"type":"line",
							"dashStyle":"Dash",
							"marker":{"enabled":false},
							"data":[[' . $logfc_threshold . ',' . $Y_MIN . '],[' . $logfc_threshold . ',' . 2 * $Y_MAX . ']]
						},


						{
							"name":"significance threshold",
							"color":"#000000",
							"type":"line",
							"dashStyle":"DashDot",
							"marker":{"enabled":false},
							"data":[[' . 2*$X_MIN . ',' . $significance_threshold . '],[' . 2*$X_MAX . ',' . $significance_threshold . ']]
						}
					]
				});
			});

		</script>';

	}


	// Data Table
	if (isset($DATA_ALL['selected']) && count($DATA_ALL['selected']) > 0) {
	    echo '<div class="w-100 my-5">
	    <table class="table table-bordered table-striped" id="volcano_table">
	      <thead>
	        <tr class="table-success">
	          <th>Gene Symbol</th>
	          <th>ID</th>
	          <th>Log2FC</th>
	          <th>FDR</th>
	          <th>P-Value</th>
	        </tr>
	      </thead>
	      <tbody>';
	        foreach ($DATA_ALL['selected'] as $key => $value) {

						$value['logfc'] = $value['x'];

	          // Color for LogFC
						if ($value['logfc'] >= 1) {
							$color1 = '#FF0000';
						} else if ($value['logfc'] > 0) {
							$color1 = '#FF8989';
	          } else if ($value['logfc'] == 0) {
	          	$color1 = '#E5E5E5';
	          } else if ($value['logfc'] > -1) {
	          	$color1 = '#7070FB';
	          } else {
	            $color1 = '#0000FF';
	          }
	          // Color for LogFC
	          if ($value['FDR'] > 0.05) {
							$color2 = '#9CA4B3';
						} else if ($value['FDR'] <= 0.01) {
	          	$color2 = '#015402';
	          } else {
	            $color2 = '#5AC72C';
	          }
	          // Color for P-Value
	          if ($value['pvalue'] >= 0.01) {
							$color3 = '#9CA4B3';
						} else {
	            $color3 = '#5AC72C';
	          }

	          echo '<tr>
	            <td>' . $value['name'] . '</td>
	            <td>' . $value['unique_id'] . '</td>
	            <td style="color:' . $color1 . ';">' . $value['logfc'] . '</td>
	            <td style="color:' . $color2 . ';">' . $value['FDR'] . '</td>
	            <td style="color:' . $color3 . ';">' . $value['pvalue'] . '</td>
	          </tr>';
	        }
	      echo '
	      </tbody>
	    </table>
		</div>
		';
	}

	exit();
}

?>