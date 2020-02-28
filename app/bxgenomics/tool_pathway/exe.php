<?php
include_once("config.php");



// Upload File
if (isset($_GET['action']) && $_GET['action'] == 'upload_file') {

	header('Content-Type: application/json');

	$OUTPUT = array();

    // Create Folders
    $dir = $BXAF_CONFIG['CURRENT_SYSTEM_CACHE'];
	$url = $BXAF_CONFIG['CURRENT_SYSTEM_CACHE_URL'];
    if (!is_dir($dir))  mkdir($dir, 0755, true);

	$TIME = mt_rand(10000001, 99999999);
	$target_file = "{$dir}{$TIME}.csv";
	if(file_exists($target_file)) unlink($target_file);

	if(isset($_POST["analysis_file"]) && $_POST["analysis_file"] != '') {
		$analysis_file = bxaf_decrypt($_POST["analysis_file"], $BXAF_CONFIG['BXAF_KEY']);
		if(file_exists($analysis_file)){
			copy($analysis_file, $target_file);
			$OUTPUT['file'] = basename($analysis_file);
		}
		else {
			$OUTPUT['type'] = 'Error';
			$OUTPUT['detail'] = 'Error: The Analysis file is not found on the server.';
			echo json_encode($OUTPUT);
			exit();
		}
	}
	// Demo File
	else if(! isset($_FILES["file"])) {
		$demo_file = dirname(__FILE__) . '/files/demo.csv';
		copy($demo_file, $target_file);

		$OUTPUT['file'] = 'demo.csv';
	}
	else if(isset($_FILES["file"])) {

		if ($_FILES["file"]["error"] > 0) {
			$OUTPUT['type'] = 'Error';
			$OUTPUT['detail'] = 'Error: ' . $_FILES["file"]["error"];
			echo json_encode($OUTPUT);
			exit();
		}

		// check file type
		if (intval($_FILES["file"]["size"]) <= 0 || ! in_array($_FILES["file"]["type"], array('application/vnd.ms-excel','text/plain','text/csv','text/tsv'))) {
			$OUTPUT['type'] = 'Error';
			$OUTPUT['detail'] = 'Please upload a csv file.';
			echo json_encode($OUTPUT);
			exit();
		};

		move_uploaded_file($_FILES["file"]["tmp_name"], $target_file);

		$OUTPUT['file'] = $_FILES["file"]["name"];

	}

	// Get the header row and determine the ID (first column) type: GeneName, EntrezID, Ensembl, Uniprot
	$OUTPUT['header'] = array();
	if (($handle = fopen("{$dir}{$TIME}.csv", "r")) !== FALSE) {
	    if (($data = fgetcsv($handle)) !== FALSE) {
			$OUTPUT['header'] = $data;
	    }
	    fclose($handle);
	}

	$OUTPUT['time'] = $TIME;
	$OUTPUT['url'] = "{$url}{$TIME}.csv";
	$OUTPUT['type'] = 'Success';
	echo json_encode($OUTPUT);
	exit();
}






// Generate PVJS Chart
if (isset($_GET['action']) && $_GET['action'] == 'generate_pathway_chart') {

	$file_times = array();
	$comparison_names = array();
	$keys = array('comparisonname', 'color1', 'color2field', 'color2', 'Log2FoldChange', 'PValue', 'AdjustedPValue');
	foreach($_POST as $key=>$val){
		foreach($keys as $k){
			if(preg_match("/^{$k}_/", $key)){
				list($time, $number) = explode('_', str_replace("{$k}_", '', $key));
				$file_times[$time][$number][$k] = $val;
			}
			if(preg_match("/^comparisonname_/", $key)){
				$comparison_names[] = $val;
			}
		}
	}
	foreach($file_times as $time=>$comparisons){
		if($time > 10000000){
			foreach($comparisons as $i=>$c){
				if($c[ 'PValue' ] == '' && $c[ 'color2field'] == 'PValue') $file_times[$time][$i][ 'color2' ] = '';
				if($c[ 'AdjustedPValue' ] == '' && $c[ 'color2field'] == 'AdjustedPValue') $file_times[$time][$i][ 'color2' ] = '';
			}
		}
	}

	$comparison_names = array_values(array_unique($comparison_names));

	if(count($comparison_names) <= 0){
		header('Content-Type: application/json');
		$OUTPUT['type'] = 'Error';
		$OUTPUT['detail'] = "Please process comparison list, upload a comparison file, or use a demo file.";
		echo json_encode($OUTPUT);
		exit();
	}


	$validations = array();
	$validations['pathway'] = true;
	$validations['genes'] = true;
	$validations['comparisons'] = true;
	$validations['files'] = true;

	$pathway_file = '';
	// Check Pathway
	if($validations['pathway']){
		if ($_POST['pathway'] == '' || ! array_key_exists($_POST['pathway'], $BXAF_CONFIG['PATHWAY_LIST']) ) {
			header('Content-Type: application/json');
			$OUTPUT['type'] = 'Error';
			$OUTPUT['detail'] = 'Please select a pathway.';
			echo json_encode($OUTPUT);
			exit();
		}

		$pathway_file = "pathway/" . $_SESSION['SPECIES_DEFAULT'] . "/" . $_POST['pathway'];

		$sql = "SELECT `Gene_Name`, `Gene_Index` FROM `tbl_wikipathways_info` WHERE `Species` = ?s AND `Gene_Index` > 0 AND `File` = ?s";
		$genes_list = $BXAF_MODULE_CONN->get_assoc('Gene_Name', $sql, $_SESSION['SPECIES_DEFAULT'], $_POST['pathway']);


		$genes_list_flip = array_flip($genes_list);
	}


	// Check Genes
	if($validations['genes']){

		$sql = "SELECT `TEXTLABEL`, `Gene_Index` FROM `tbl_wikipathways_info` WHERE `Species` = ?s AND `Gene_Index` > 0";
		$genes_nameindex = $BXAF_MODULE_CONN->get_assoc('TEXTLABEL', $sql, $_SESSION['SPECIES_DEFAULT']);

		$sql = "SELECT `ID`, `GeneName`, `Alias`, `Ensembl`, `EntrezID`, `Description`  FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_GENES']}` WHERE `ID` IN (?a)";
		$gene_info = $BXAF_MODULE_CONN -> get_assoc('ID', $sql, array_values($genes_list) );

	}


	// Check Comparisons
	$all_genes_data = array();
	$all_genes_data_simple = array();


	// Check uploaded files
	if($validations['files']){

		$comparisons_info = array();

		$files_uploaded_dir = $BXAF_CONFIG['CURRENT_SYSTEM_CACHE'];

		foreach($file_times as $t=>$file_comparisons){

			if($t > 10000000){

				$f = "{$files_uploaded_dir}{$t}.csv";

				if (($handle = fopen($f, "r")) !== FALSE) {
					if (($header = fgetcsv($handle)) !== FALSE) {

						$header_flip = array_flip($header);

						while (($row = fgetcsv($handle)) !== FALSE) {

							if(array_key_exists($row[0], $genes_nameindex)){

								$g_index = $genes_nameindex[ $row[0] ];
								foreach($file_comparisons as $c){

									$all_genes_data[ $g_index ][ $c[ 'comparisonname'] ] [] = array(
										'GeneName'       => $genes_list_flip[ $g_index ],
										'comparisonname' => $c[ 'comparisonname'],
										'color1'         => $c[ 'color1' ],
										'color2field'    => $c[ 'color2field'],
										'color2'         => $c[ 'color2' ],

										'pval'           => $c[ 'PValue'],

										'Log2FoldChange' => $c[ 'Log2FoldChange' ] != '' ? $row[ $header_flip[ $c[ 'Log2FoldChange' ] ] ] : 'NA',
										'PValue'         => $c[ 'PValue' ] != '' ? $row[ $header_flip[ $c[ 'PValue' ] ] ] : 'NA',
										'AdjustedPValue' => $c[ 'AdjustedPValue' ] != '' ? $row[ $header_flip[ $c[ 'AdjustedPValue' ] ] ] : 'NA'
									);

									$all_genes_data_simple[ $genes_list_flip[ $g_index ] ][] = array(
										'GeneIndex'      => $g_index,
										'GeneName'       => $gene_info[ $g_index ]['GeneName'],
										'Alias'          => $gene_info[ $g_index ]['Alias'],
										'Ensembl'        => $gene_info[ $g_index ]['Ensembl'],
										'EntrezID'       => $gene_info[ $g_index ]['EntrezID'],
										'Description'    => $gene_info[ $g_index ]['Description'],
										'comparisonname' => $c[ 'comparisonname'],
										'pval'           => $c[ 'PValue'],

										'Log2FoldChange' => $c[ 'Log2FoldChange' ] != '' ? sprintf("%.4f", $row[ $header_flip[ $c[ 'Log2FoldChange' ] ] ]) : 'NA',
										'PValue'         => $c[ 'PValue' ] != '' ? sprintf("%.4f", $row[ $header_flip[ $c[ 'PValue' ] ] ]) : 'NA',
										'AdjustedPValue' => $c[ 'AdjustedPValue' ] != '' ? sprintf("%.4f", $row[ $header_flip[ $c[ 'AdjustedPValue' ] ] ]) : 'NA'
									);
								}

							}

					    }

					}
				    fclose($handle);
				}

			}
			else { // From selected comparison
				foreach($file_comparisons as $c){

					$sql = "SELECT `ID` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS']}` WHERE `Name` = ?s";
			        $comparison_id = $BXAF_MODULE_CONN -> get_one($sql, $c[ 'comparisonname']);

			  		if ($comparison_id == '') {
						header('Content-Type: application/json');
						$OUTPUT['type'] = 'Error';
				 		$OUTPUT['detail'] = 'Error: No comparison found: <strong class="red">' . $c[ 'comparisonname'] . '</strong>. Please revise.';
				 		echo json_encode($OUTPUT);
				 		exit();
			 		}
			  		else {
						$comparisons_info[$comparison_id]['comparisonname'] = $c[ 'comparisonname'];
						$comparisons_info[$comparison_id]['color1']         = $c[ 'color1'];
						$comparisons_info[$comparison_id]['color2field']    = $c[ 'color2field'];
						$comparisons_info[$comparison_id]['color2']         = $c[ 'color2'];
					}

				}


			}
		}

		if(count($comparisons_info) > 0){

	        ini_set('memory_limit','8G');
			$tabix_results = tabix_search_bxgenomics(  array_values($genes_list), array_keys($comparisons_info), 'ComparisonData' );


			foreach($tabix_results as $row){

				$g_index = $row['GeneIndex'];
				$g_name  = $genes_list_flip[ $g_index ];
				$c_index = $row['ComparisonIndex'];
				$c_name  = $comparisons_info[$c_index]['comparisonname'];

				$all_genes_data[ $g_index ][ $c_name ] [] = array(
					'genename'       => $g_name,
					'comparisonname' => $c_name,
					'color1'         => $comparisons_info[ $c_index ][ 'color1' ],
					'color2field'    => $comparisons_info[ $c_index ][ 'color2field'],
					'color2'         => $comparisons_info[ $c_index ][ 'color2' ],
					'pval'           => $row[ 'PValue' ],

					'Log2FoldChange' => sprintf("%.4f", $row[ 'Log2FoldChange' ] ),
					'PValue'         => sprintf("%.4f", $row[ 'PValue' ] ),
					'AdjustedPValue' => sprintf("%.4f", $row[ 'AdjustedPValue' ] )
				);

				$all_genes_data_simple[ $g_name ][] = array(
					'GeneIndex'      => $g_index,
					'GeneName'       => $gene_info[ $g_index ]['GeneName'],
					'Alias'          => $gene_info[ $g_index ]['Alias'],
					'Ensembl'        => $gene_info[ $g_index ]['Ensembl'],
					'EntrezID'       => $gene_info[ $g_index ]['EntrezID'],
					'Description'    => $gene_info[ $g_index ]['Description'],
					'pval'           => $row[ 'PValue' ],

					'comparisonname' => $c_name,
					'Log2FoldChange' => sprintf("%.4f", $row[ 'Log2FoldChange' ] ),
					'PValue'         => sprintf("%.4f", $row[ 'PValue' ] ),
					'AdjustedPValue' => sprintf("%.4f", $row[ 'AdjustedPValue' ] )
				);

			}

		}
	}

	foreach($all_genes_data as $gene_index => $gene_data){

		$gene_name = $genes_list_flip[$gene_index];

		foreach($comparison_names as $key=>$c_name){

			$min_data = array();
			$min_data_key = '';
			foreach($gene_data[$c_name] as $k => $d){
				if(count($min_data) <= 0){ $min_data = $d; $min_data_key = $k; }
				else if(floatval($d['PValue']) < floatval($min_data['PValue'])) { $min_data = $d; $min_data_key = $k; }
			}

			foreach($gene_data[$c_name] as $k => $d){
				if($k != $min_data_key){
					unset( $all_genes_data[$gene_index][$c_name][$k] );
				}
			}

			foreach($all_genes_data_simple[$gene_name] as $k => $d){
				if($d['comparisonname'] == $c_name && $d['pval'] != $gene_data[$c_name][$min_data_key]['pval'] ){
					unset( $all_genes_data_simple[ $gene_name ][$k] );
				}
			}

		}
	}


	$ALL_COLORING_GENE = array();

	foreach($all_genes_data as $gene_index => $gene_data){

		$gene_name = $genes_list_flip[$gene_index];

		$ALL_COLORING_GENE[$gene_name] = array(
			'Gene_Index' => $gene_index,
			'Color' => array()
		);


		foreach($comparison_names as $key=>$c_name){

			$min_data = current($gene_data[$c_name]);

			$ALL_COLORING_GENE[$gene_name]['Color'][] = mapToGradient($min_data['Log2FoldChange'], 1 + $min_data['color1']);

			if($min_data['color2'] != ''){
				if($min_data['color2field'] == 'PValue'){
					$ALL_COLORING_GENE[$gene_name]['Color'][] = mapToGradientPValue($min_data['PValue'], $min_data['color2']);
				}
				else if($min_data['color2field'] == 'AdjustedPValue'){
					$ALL_COLORING_GENE[$gene_name]['Color'][] = mapToGradientPValue($min_data['AdjustedPValue'], $min_data['color2']);
				}
			}
		}
	}



	// Generate Legends
	$LEGEND_INFO = '<div style="width:100%;"><h3>Legend</h3>';
	$legend_font_size = 10;
	$LEGEND_JS_SVG_CODE =  "var svgNS = 'http://www.w3.org/2000/svg';"; // Javascript Code to Update SVG
	$LEGEND_JS_SVG_CODE .= "var newText = document.createElementNS(svgNS,'text');";
	$LEGEND_JS_SVG_CODE .= "newText.setAttributeNS(null,'x',0);";
	$LEGEND_JS_SVG_CODE .= "newText.setAttributeNS(null,'y',42); ";
	$LEGEND_JS_SVG_CODE .= "newText.setAttributeNS(null,'font-size','" . $legend_font_size . "px');";
	$LEGEND_JS_SVG_CODE .= "var textNode = document.createTextNode('Legend: ');";
	$LEGEND_JS_SVG_CODE .= "newText.appendChild(textNode);";
	$LEGEND_JS_SVG_CODE .= "document.getElementById('info-box-0').appendChild(newText);";

	$y_svg = 42;
	$legend_number = 2;

	// $file_times[$time][$number][$k] = $val;
	foreach($file_times as $time=>$f_info){
		foreach($f_info as $c_info){

			$c_name = $c_info['comparisonname'];

			$LEGEND_INFO .= '<div style="margin-top:15px;"><strong>' . $c_name . '</strong></div>';

			$y_svg += 14;
			$LEGEND_JS_SVG_CODE .= "var newText = document.createElementNS(svgNS,'text');";
			$LEGEND_JS_SVG_CODE .= "newText.setAttributeNS(null,'x',0);";
			$LEGEND_JS_SVG_CODE .= "newText.setAttributeNS(null,'y'," . $y_svg . "); ";
			$LEGEND_JS_SVG_CODE .= "newText.setAttributeNS(null,'font-size','" . $legend_font_size . "px');";
			$LEGEND_JS_SVG_CODE .= "var textNode = document.createTextNode('" . $c_name . "');";
			$LEGEND_JS_SVG_CODE .= "newText.appendChild(textNode);";
			$LEGEND_JS_SVG_CODE .= "document.getElementById('info-box-0').appendChild(newText);";

			$LEGEND_INFO .= getColorLegend('Log2FoldChange', $c_info['color1']);
			$legend_number += 2;

			$y_svg += 14;
			$LEGEND_JS_SVG_CODE .= getColorLegendSVG('Log2FoldChange', $c_info['color1'], $y_svg, $legend_font_size);

			if($c_info['color2'] != ''){
				$LEGEND_INFO .= getColorLegend($c_info['color2field'], $c_info['color2']);
				$y_svg += 14;
				$LEGEND_JS_SVG_CODE .= getColorLegendSVG($c_info['color2field'], $c_info['color2'], $y_svg, $legend_font_size);
			}

			$legend_number += 1;

		}
	}

	$LEGEND_INFO .= '</div>';

	$LEGEND_INFO = addslashes(str_replace("\n", '', $LEGEND_INFO));






	$CHART_OUTPUT = '';

	$CHART_OUTPUT .= '
	<script type="text/javascript" src="javascript/jquery.mousewheel.js"></script>
	<script type="text/javascript" src="javascript/jquery.layout.min-1.3.0.js"></script>
	<script type="text/javascript" src="javascript/d3.min.js"></script>
	<script type="text/javascript" src="javascript/mithril.min.js"></script>
	<script type="text/javascript" src="javascript/polyfills.bundle.min.js"></script>
	<script type="text/javascript" src="javascript/pvjs.core.min.js"></script>
	<script type="text/javascript" src="javascript/pvjs.custom-element.min.js"></script>

	<wikipathways-pvjs
		id="pvjs-widget"
		src="'. $BXAF_CONFIG['BXAF_APP_URL'] . "bxgenomics/tool_pathway/" . $pathway_file . '"
		display-errors="true"
		display-warnings="true"
		fit-to-container="true"
		editor="disabled"
    style="width:100%; min-height:800px; padding: 10px;">
	</wikipathways-pvjs>

	<script>
	kaavioHighlights = [';

		// Define Area ID
		foreach ($ALL_COLORING_GENE as $key => $value) {
			$CHART_OUTPUT .= '{"selector":"' . $key . '","backgroundColor":"url(#solids_' . str_replace(' ', '_', $key) . ')","borderColor":"#B0B0B0"},';
		}

	$CHART_OUTPUT .= '
	]
	</script>';

	$CHART_OUTPUT .= "
	<script>
	checkReady();
	function checkReady() {
		if ($('svg')[0] == null) {
			setTimeout('checkReady()', 300);
		} else {

			$('#btn_save_svg').parent().removeClass('hidden');

			createGradient($('svg')[0],'gradient_0',[
				{offset:'5%', 'stop-color':'#0000FF'},
				{offset:'50%','stop-color':'#FFFFFF'},
				{offset:'95%','stop-color':'#FF0000'}
			]);
			createGradient($('svg')[0],'gradient_1',[
				{offset:'5%', 'stop-color':'#008000'},
				{offset:'50%','stop-color':'#FFFFFF'},
				{offset:'95%','stop-color':'#FF0000'}
			]);
			createGradient($('svg')[0],'gradient_2',[
				{offset:'5%', 'stop-color':'#FFD700'},
				{offset:'50%','stop-color':'#FFFFFF'},
				{offset:'95%','stop-color':'#0000FF'}
			]);
			createGradient($('svg')[0],'gradient_3',[
				{offset:'5%', 'stop-color':'#FFD700'},
				{offset:'50%','stop-color':'#FFA500'},
				{offset:'95%','stop-color':'#FF0000'}
			]);

	";


	$CHART_OUTPUT .= "var legend_html = '<div class=\"kaavio-highlighter\" id=\"lengend_div\" style=\"top:40px; right:25px; width:250px; height:" . $legend_number * 35 . "px; background-color:rgba(0,0,0,0.1); padding:10px; border-radius:10px; border: 1px solid #CCCCCC;\">" . $LEGEND_INFO. "</div>';
	$('wikipathways-pvjs').append(legend_html);";

	$CHART_OUTPUT .= "var toggle_legend_html = '<div class=\"kaavio-highlighter\" style=\"top:6px; right:265px; width:50px; height:50px;\"><button class=\"btn btn-sm btn-secondary\" style=\"height:24px;padding-top:3px;\" onclick=\"$(\'#lengend_div\').slideToggle(300);\">Toggle Legend</button></div>'; $('wikipathways-pvjs').append(toggle_legend_html);";


	// Draw Area Color for Each Box Area
	foreach ($ALL_COLORING_GENE as $key => $value) {
		$CHART_OUTPUT .= "
		createGradient($('svg')[0],'solids_" . str_replace(' ', '_', $key) . "',[
			{offset:'0%', 'stop-color':'#" . $value['Color'][0] . "'},";

		for ($i = 1; $i < count($value['Color']); $i++) {
			$border_temp = $i * 100.0 / count($value['Color']);
			$CHART_OUTPUT .= "
			{offset:'" . intval($border_temp) . "%','stop-color':'#" . $value['Color'][$i - 1] . "'},
			{offset:'" . intval($border_temp) . "%','stop-color':'#" . $value['Color'][$i] . "'},";
		}
		$CHART_OUTPUT .= "]);";
	}


	// Include SVG Legend if Checked
	if (isset($_POST['show_svg_legend']) && $_POST['show_svg_legend'] == 'on') {
		$CHART_OUTPUT .= $LEGEND_JS_SVG_CODE;
	}

	$CHART_OUTPUT .= "
		}
	}
	function createGradient(svg,id,stops){
		var svgNS = svg.namespaceURI;
		var grad  = document.createElementNS(svgNS,'linearGradient');
		grad.setAttribute('id',id);
		for (var i=0;i<stops.length;i++){
			var attrs = stops[i];
			var stop = document.createElementNS(svgNS,'stop');
			for (var attr in attrs){
				if (attrs.hasOwnProperty(attr)) stop.setAttribute(attr,attrs[attr]);
			}
			grad.appendChild(stop);
		}

		var defs = svg.querySelector('defs') || svg.insertBefore( document.createElementNS(svgNS,'defs'), svg.firstChild );
		return defs.appendChild(grad);
	}";

	$CHART_OUTPUT .= "</script>";




	$header_types = array('Log2FoldChange'=>'Log2FC', 'PValue'=>'P-Value', 'AdjustedPValue'=>'FDR');

	$table_body_data_temp = array();
	foreach ($all_genes_data as $gene_index => $value) {
		$gene_name = $genes_list_flip[ $gene_index ];
		foreach($value as $c_name=>$found_values){
			foreach($found_values as $found_value){
				$table_body_data_temp[$gene_index][$c_name][ $found_value[$k] ] = $found_value;
			}
		}
	}

	$table_body_data = array();
	$table_header_th = array('<th>Gene Name</th>', '<th>Description</th>');
	foreach($comparison_names as $c_name){
		foreach($header_types as $k=>$v){
			$table_header_th[] = "<th>$c_name $v</th>";
		}
	}

	foreach ($table_body_data_temp as $gene_index => $values) {
		$vals= array();
		foreach($values as $c_name=>$found_values){
			asort($found_values);
			$found_value = current($found_values);
			$vals[$c_name] = $found_value;
		}

		$gene_name = $genes_list_flip[ $gene_index ];

		$row = '<tr>';
		$row .= "<td><a href='../tool_search/view.php?type=gene&id=" . $genes_list[ $gene_name ] . "' target='_blank'>" . $gene_name . "</a></td>";
		$row .= '<td>' . $gene_info[$gene_index]['Description'] . '</td>';

		foreach($comparison_names as $c_name){
			if(is_array($vals) && array_key_exists($c_name, $vals)){
				$found_value = $vals[$c_name];
				foreach($header_types as $k=>$v){
					if($found_value[$k] == 'NA')
						$row .= '<td class="text-muted">NA</td>';
					else
						$row .= '<td style="color:' . get_stat_scale_color2($found_value[$k], $k) . ';">' . sprintf("%.4f", $found_value[$k]) . '</td>';
				}
			}
			else {
				foreach($header_types as $k=>$v){
					$row .= '<td class="text-muted">NA</td>';
				}
			}
		}
		$row .= '</tr>';
		$table_body_data[] = $row;
	}



	$output_time = time();

	$DATA_TABLE_OUTPUT = '<div class="w-100 my-3">Note: You can <a class="text-danger" href="single_svg.php?time=' . $output_time . '" target="_blank">click here</a> to open pathway diagram in a new window.</div>';

	$DATA_TABLE_OUTPUT .= '<h2 class="w-100 my-3">Data Information Table</h2>';

	$DATA_TABLE_OUTPUT .= '<table class="table table-bordered table-striped table-hover w-100" style="font-size: 0.8rem;" id="table_chart_info"><thead><tr class="table-info">';
	$DATA_TABLE_OUTPUT .= implode("", $table_header_th);
	$DATA_TABLE_OUTPUT .= '</tr></thead><tbody>';
	$DATA_TABLE_OUTPUT .= implode("", $table_body_data);
	$DATA_TABLE_OUTPUT .= '</tbody></table>';

	$DATA_TABLE_OUTPUT .= "
	<script>
	$(document).ready(function() {
		$('#table_chart_info').DataTable({ dom: 'Blfrtip', buttons: ['colvis','copy','csv'], 'pageLength': 100, 'lengthMenu': [[10, 100, 500, 1000], [10, 100, 500, 1000]] });
	});
	</script>";


	$file_dir = $BXAF_CONFIG['CURRENT_SYSTEM_CACHE'] . $output_time;
	if (!is_dir($file_dir))  mkdir($file_dir . '/', 0775, true);

	file_put_contents($file_dir . '/svg_code.txt', $CHART_OUTPUT);
	file_put_contents($file_dir . '/svg_data.txt', json_encode($all_genes_data_simple));

	header('Content-Type: application/json');

	$OUTPUT = array(
		'type' => 'Success',
		'time' => $output_time,
		'datatable' => $DATA_TABLE_OUTPUT,
		'raw'  => "" //"<pre>" . print_r($_POST, true) . print_r($file_times, true) . print_r($all_genes_data, true) . "</pre>"
	);

	echo json_encode($OUTPUT);
	exit();


}



if (isset($_GET['action']) && $_GET['action'] == 'show_kegg_diagram') {

	$time = time();
	$CURRENT_DIR = $BXAF_CONFIG['CURRENT_SYSTEM_CACHE'] . $time;
	if(! file_exists($CURRENT_DIR)) mkdir($CURRENT_DIR, 0775, true);
	$CURRENT_URL = $BXAF_CONFIG['CURRENT_SYSTEM_CACHE_URL'] . $time;


	$genename_geneids = array();
	$geneindex_genenames = array();
	$content_map = '';
	if ($_POST['KEGG_Identifier'] == '' || ! array_key_exists($_POST['KEGG_Identifier'], $BXAF_CONFIG['KEGG_PATHWAY_LIST']) ){
		echo "Please select a KEGG ID and try again.";
		exit();
	}
	else {

		$filename = __DIR__ . "/kegg/html_map/" . $_POST['KEGG_Identifier'] . ".map.html";
		$content_map = file_get_contents($filename);

		$matches = array();
		$pattern = "|(?<=\ttitle=\")(?:\d+ \(\w+\)(?:, \d+ \(\w+\))*)(?=\" )|mU";
		preg_match_all($pattern, $content_map, $matches, PREG_SET_ORDER);

		foreach($matches as $row){
		    if($row[0] != ''){
		        $matches = array();
		        $pattern = "|(\d+) \((\w+)\)|mU";
		        preg_match_all($pattern, $row[0], $matches, PREG_SET_ORDER);
		        foreach($matches as $k=>$v) $genename_geneids[ $v[2] ] = $v[1];
		    }
		}
		$geneid_genenames = array_flip($genename_geneids);

		$sql = "SELECT `GeneIndex`, `GeneName`  FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_GENES_INDEX']}` WHERE `Species` = '" . $_SESSION['SPECIES_DEFAULT'] . "' AND `Name` IN (?a)";
		$geneindex_genenames = $BXAF_MODULE_CONN -> get_assoc('GeneIndex', $sql, array_keys($genename_geneids));
		$genename_geneindex = array_flip($geneindex_genenames);

		$sql = "SELECT `Name`, `GeneIndex` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_GENES_INDEX']}` WHERE `Species` = '" . $_SESSION['SPECIES_DEFAULT'] . "' AND `GeneIndex` IN (?a)";
		$geneindex_genenames_all = $BXAF_MODULE_CONN -> get_assoc('Name', $sql, array_keys($geneindex_genenames));

		$sql = "SELECT `GeneName`, `Description`  FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_GENES']}` WHERE `Species` = '" . $_SESSION['SPECIES_DEFAULT'] . "' AND `ID` IN (?a)";
		$gene_name_desc = $BXAF_MODULE_CONN -> get_assoc('GeneName', $sql, array_keys($geneindex_genenames));

	}


	if (($_POST['Visualization'] > 3) || ($_POST['Visualization'] < 1)){
		$_POST['Visualization'] = 1;
	}



	$cvs_contents = array();
	$cvs_contents_PValue = array();
	$cvs_contents_AdjustedPValue = array();
	$header = array('GeneID');

	if(isset($_FILES) && is_array($_FILES) && count($_FILES['comparison_file']) > 0){
		if ($_FILES["comparison_file"]["error"] == UPLOAD_ERR_OK && is_uploaded_file($_FILES['comparison_file']['tmp_name']) ) {
	        move_uploaded_file($_FILES["comparison_file"]["tmp_name"], "$CURRENT_DIR/uploaded.csv");

			if (($handle = fopen("$CURRENT_DIR/uploaded.csv", "r")) !== FALSE) {

				$header = fgetcsv($handle);
				$header[0] = 'GeneID';

			    while (($data = fgetcsv($handle)) !== FALSE) {
					$name = array_shift($data);
					// foreach($data as $j=>$d) if($d == '.'){ break; continue; }
					if(array_key_exists($name, $geneindex_genenames_all)){
						$geneindex = $geneindex_genenames_all[$name];
						$name = $geneindex_genenames[$geneindex];
						$geneid = $genename_geneids[$name];
						array_unshift($data, $geneid);
						$cvs_contents[] = $data;
					}
			    }
			    fclose($handle);


				$csv_file = "$CURRENT_DIR/logfc.csv";
				$handle_csv_file = fopen($csv_file, "w");
				if($handle_csv_file){
					fputcsv($handle_csv_file, $header);
					foreach($cvs_contents as $row){
						fputcsv($handle_csv_file, $row);
					}
					fclose($handle_csv_file);
				}

				$R_COMMAND = "#!/usr/bin/bash\n";
				$R_COMMAND .= "cd $CURRENT_DIR\n";
				$R_COMMAND .= "/usr/bin/Rscript " . __DIR__ . "/kegg_draw.R logfc.csv {$_POST['KEGG_Identifier']} {$_POST['Visualization']} " . __DIR__ . "/kegg/xml_png/\n";

				file_put_contents("$CURRENT_DIR/run.bash", $R_COMMAND);
				chmod("$CURRENT_DIR/run.bash", 0775);
				shell_exec("$CURRENT_DIR/run.bash");

				$png_file_url = '';
				if(file_exists($BXAF_CONFIG['CURRENT_SYSTEM_CACHE'] .     "$time/{$_POST['KEGG_Identifier']}.logfc.multi.png")){
					$png_file_url = $BXAF_CONFIG['CURRENT_SYSTEM_CACHE_URL'] . "$time/{$_POST['KEGG_Identifier']}.logfc.multi.png";
				}
				else if(file_exists($BXAF_CONFIG['CURRENT_SYSTEM_CACHE'] .     "$time/{$_POST['KEGG_Identifier']}.logfc.png")){
					$png_file_url = $BXAF_CONFIG['CURRENT_SYSTEM_CACHE_URL'] . "$time/{$_POST['KEGG_Identifier']}.logfc.png";
				}

				// Map
				$OUTPUT = "";
				if($png_file_url != ''){
					$OUTPUT .= "<div class='w-100 my-5'>";
						$OUTPUT .= "<img src='$png_file_url' name='pathwayimage' usemap='#mapdata' border='0' />";
						$OUTPUT .= "<map name='mapdata'>";
							$OUTPUT .= $content_map;
						$OUTPUT .= "</map>";
					$OUTPUT .= "</div>";
				}

				//Table
				$table_contents = '';
				$table_contents .= "<div class='w-100 my-5'><table id='resultTable' class='table table-bordered table-hover'><thead><tr class='table-success'>";
				foreach($header as $k=>$v) $table_contents .= "<th>$v</th>";
				$table_contents .= "</tr></thead><tbody>";

				foreach($cvs_contents as $row){
					$found_data = false;
					foreach($row as $k=>$v){
						if($k != 'GeneID' && $v != 'NA' && $v != '') $found_data = true;
					}
					if($found_data){
						$table_contents .= "<tr>";
						foreach($row as $k=>$v){
							if($k == 'GeneID') $table_contents .= "<td><a href='../tool_search/view.php?type=gene&id=" . $genename_geneindex[ $geneid_genenames[$v] ] . "' target='_blank'>" . $geneid_genenames[$v] . "</a></td>";
							else if($v == 'NA') $table_contents .= "<td></td>";
							else $table_contents .= "<td style='color: " . get_stat_scale_color2($v, 'Log2FoldChange') . ";'>" . sprintf("%.4f", $v) . "</td>";
						}
						$table_contents .= "</tr>";
					}
				}

				$table_contents .= "</tbody></table></div>";

				$OUTPUT .= $table_contents;

				echo $OUTPUT;


			}
	    }

		exit();
	}


	$list = preg_split("/[\s,]+/", $_POST['Comparison_List'], NULL, PREG_SPLIT_NO_EMPTY);

	$comparison_indexnames = category_text_to_idnames($_POST['Comparison_List'], 'name', 'comparison', $_SESSION['SPECIES_DEFAULT']);
	$comparison_nameindexes = array_flip($comparison_indexnames);

	if ( ! is_array($comparison_indexnames) || count($comparison_indexnames) <= 0 ){
		echo "<div class='lead text-danger'>Error: No valid comparison is found. Please enter at least one valid comparison ID.</div>";
		exit();
	}
	else {
		foreach($list as $c){
			if(! in_array($c, $comparison_indexnames)){
				echo "<div class='lead text-danger'>Error: Comparison '$c' is not found.</div>";
				exit();
			}
		}
	}


    ini_set('memory_limit','8G');

	$tabix_results = tabix_search_bxgenomics(  array_keys($geneindex_genenames), array_keys($comparison_indexnames), 'ComparisonData');

	$all_genes_data = array();
	$all_comparison_index = array();
	foreach($tabix_results as $row){
		$g_index = $row['GeneIndex'];
		$c_index = $row['ComparisonIndex'];
		$all_comparison_index[] = $c_index;
		$all_genes_data[ $g_index ][ $c_index ] [] = $row;
	}

	foreach($all_genes_data as $gene_index => $row){
		foreach($all_comparison_index as $c_index){
			$min_data = array();
			$min_data_key = '';
			foreach($row[$c_index] as $k => $d){
				if(count($min_data) <= 0){ $min_data = $d; $min_data_key = $k; }
				else if(floatval($d['PValue']) < floatval($min_data['PValue'])) { $min_data = $d; $min_data_key = $k; }
			}
			foreach($row[$c_index] as $k => $d){
				if($k != $min_data_key){
					unset( $all_genes_data[$gene_index][$c_index][$k] );
				}
			}
		}
	}
	$tabix_results = array();
	foreach($all_genes_data as $gene_index => $rows){
		foreach($rows as $c_index => $row){
			$tabix_results[] = array_shift($row);
		}
	}



	foreach($comparison_indexnames as $id=>$name) $header[] = $name;

	foreach($geneindex_genenames as $geneindex=>$genename){
		$row = array();
		$row_PValue = array();
		$row_AdjustedPValue = array();
		foreach($header as $c){
			if($c == 'GeneID'){
				$row['GeneID'] = $genename_geneids[ $genename ];
				$row_PValue['GeneID'] = $genename_geneids[ $genename ];
				$row_AdjustedPValue['GeneID'] = $genename_geneids[ $genename ];
			}
			else {
				foreach($tabix_results as $val){
					if($val['ComparisonIndex'] == $comparison_nameindexes[$c] && $val['GeneIndex'] == $geneindex ){
						if($val['Log2FoldChange'] != '.') $row[$c] = $val['Log2FoldChange'];
						if($val['PValue'] != '.') $row_PValue[$c] = $val['PValue'];
						if($val['AdjustedPValue'] != '.') $row_AdjustedPValue[$c] = $val['AdjustedPValue'];
					}
				}
				if(! array_key_exists($c, $row)) $row[$c] = 'NA';
				if(! array_key_exists($c, $row_PValue)) $row_PValue[$c] = 'NA';
				if(! array_key_exists($c, $row_AdjustedPValue)) $row_AdjustedPValue[$c] = 'NA';
			}
		}
		$cvs_contents[] = $row;
		$cvs_contents_PValue[] = $row_PValue;
		$cvs_contents_AdjustedPValue[] = $row_AdjustedPValue;
	}


	$csv_file = "$CURRENT_DIR/logfc.csv";
	$handle_csv_file = fopen($csv_file, "w");
	if($handle_csv_file){
		fputcsv($handle_csv_file, $header);
		foreach($cvs_contents as $row){
			fputcsv($handle_csv_file, $row);
		}
		fclose($handle_csv_file);
	}

	$csv_file = "$CURRENT_DIR/pvalue.csv";
	$handle_csv_file = fopen($csv_file, "w");
	if($handle_csv_file){
		fputcsv($handle_csv_file, $header);
		foreach($cvs_contents_PValue as $row){
			fputcsv($handle_csv_file, $row);
		}
		fclose($handle_csv_file);
	}

	$csv_file = "$CURRENT_DIR/adjpvalue.csv";
	$handle_csv_file = fopen($csv_file, "w");
	if($handle_csv_file){
		fputcsv($handle_csv_file, $header);
		foreach($cvs_contents_AdjustedPValue as $row){
			fputcsv($handle_csv_file, $row);
		}
		fclose($handle_csv_file);
	}


	$R_COMMAND = "#!/usr/bin/bash\n";
	$R_COMMAND .= "cd $CURRENT_DIR\n";
	$R_COMMAND .= "/usr/bin/Rscript " . __DIR__ . "/kegg_draw.R logfc.csv {$_POST['KEGG_Identifier']} {$_POST['Visualization']} " . __DIR__ . "/kegg/xml_png/\n";

	file_put_contents("$CURRENT_DIR/run.bash", $R_COMMAND);
	chmod("$CURRENT_DIR/run.bash", 0775);
	shell_exec("$CURRENT_DIR/run.bash");

	$png_file_url = '';
	if(file_exists($BXAF_CONFIG['CURRENT_SYSTEM_CACHE'] .     "$time/{$_POST['KEGG_Identifier']}.logfc.multi.png")){
		$png_file_url = $BXAF_CONFIG['CURRENT_SYSTEM_CACHE_URL'] . "$time/{$_POST['KEGG_Identifier']}.logfc.multi.png";
	}
	else if(file_exists($BXAF_CONFIG['CURRENT_SYSTEM_CACHE'] .     "$time/{$_POST['KEGG_Identifier']}.logfc.png")){
		$png_file_url = $BXAF_CONFIG['CURRENT_SYSTEM_CACHE_URL'] . "$time/{$_POST['KEGG_Identifier']}.logfc.png";
	}

	// Map
	$OUTPUT = "";
	if($png_file_url != ''){
		$OUTPUT .= "<div class='w-100 my-5'>";
			$OUTPUT .= "<img src='$png_file_url' name='pathwayimage' usemap='#mapdata' border='0' />";
			$OUTPUT .= "<map name='mapdata'>";
				$OUTPUT .= $content_map;
			$OUTPUT .= "</map>";
		$OUTPUT .= "</div>";
	}



	//Table
	$table_contents = '';

	$table_contents .= "<h2 class='mt-3'>Log<sub>2</sub> (Fold Change), P-Value and FDR</h2>";
	$table_contents .= "<div class='my-3'>Download Data: <a href='$CURRENT_URL/logfc.csv'>Log<sub>2</sub> (Fold Change)</a> - <a href='$CURRENT_URL/pvalue.csv'>P-Value</a> - <a href='$CURRENT_URL/adjpvalue.csv'>FDR</a></div>";

	$options = array('Log2FoldChange'=>'Log<sub>2</sub> (Fold Change)','PValue'=>'P-Value','AdjustedPValue'=>'FDR');
	$table_contents .= "<div class='w-100 my-5'><table id='resultTable' class='table table-bordered table-hover'><thead><tr class='table-success'><th>GeneID</th><th>Description</th>";
	foreach($header as $k=>$v){
		if($v == 'GeneID') continue;
		foreach($options as $k1=>$v1){
			$table_contents .= "<th>$v $v1</th>";
		}
	}
	$table_contents .= "</tr></thead><tbody>";

	$n_rows = count($cvs_contents);
	for($i = 0; $i < $n_rows; $i++){

		$cols = array();
		$empty = 0;
		foreach($cvs_contents[$i] as $k=>$v){

			if($k == 'GeneID') continue;

			foreach($options as $k1=>$v1){

				if($k1 == 'Log2FoldChange') $v = $cvs_contents[$i][$k];
				else if($k1 == 'PValue') $v = $cvs_contents_PValue[$i][$k];
				else if($k1 == 'AdjustedPValue') $v = $cvs_contents_AdjustedPValue[$i][$k];

				if($v == 'NA'){
					$cols[] = "<td class='text-muted'>NA</td>";
					$empty++;
				}
				else {
					$cols[] = "<td style='color: " . get_stat_scale_color2($v, $k1) . ";'>" . sprintf("%.4f", $v) . "</td>";
				}
			}
		}

		if($empty < (count($cvs_contents[$i]) - 1) * count($options) ){
			$table_contents .= "<tr>";

			$v = $cvs_contents[$i]['GeneID'];

			$table_contents .= "<td><a href='../tool_search/view.php?type=gene&id=" . $genename_geneindex[ $geneid_genenames[$v] ] . "' target='_blank'>" . $geneid_genenames[$v] . "</a></td><td>" . $gene_name_desc[ $geneid_genenames[$v] ] . "</td>" . implode("", $cols);

			$table_contents .= "</tr>";
		}
	}

	$table_contents .= "</tbody></table></div>";

	$OUTPUT .= $table_contents;

	echo $OUTPUT;


	exit();
}







if (isset($_GET['action']) && $_GET['action'] == 'show_changed_genes') {


	if($_POST['fc_cutoff'] == '') $log2fc_cutoff = floatval($_POST['fc_custom']);
	else $log2fc_cutoff = floatval($_POST['fc_cutoff']);

	if($log2fc_cutoff > 0) $log2fc_cutoff = abs(log($log2fc_cutoff, 2));
	else $log2fc_cutoff = '';

	$statistic_field = $_POST['statistic_field'];

	if($_POST['statistic_cutoff'] == '') $statistic_cutoff = abs(floatval($_POST['statistic_custom']));
	else $statistic_cutoff = abs(floatval($_POST['statistic_cutoff']));




	$time = time();
	$CURRENT_DIR = $BXAF_CONFIG['CURRENT_SYSTEM_CACHE'] . $time;
	if(! file_exists($CURRENT_DIR)) mkdir($CURRENT_DIR, 0775, true);

	$results = array();
	$header = array();

	$limit = 100000;

	if(isset($_FILES) && is_array($_FILES) && count($_FILES['comparison_file']) > 0){

		// convert to lower case to compare
		$column_names = array(
			'Log2FoldChange'=>array('log2foldchange','logfc','log2fc','logfoldchange', 'log fc', 'log2 foldchange', 'log foldchange', 'log fold change'),
			'PValue'=>array('pvalue','p.value','p.val','p value', 'pval', 'p val'),
			'AdjustedPValue'=>array('adjustedpvalue','adj.p.val','fdr','adj.p.value','adj p val', 'adj p value', 'adjusted p value', 'adjusted p val')
		);

		$data_type = 'File';
		$results1 = array();

		if ($_FILES["comparison_file"]["error"] == UPLOAD_ERR_OK && is_uploaded_file($_FILES['comparison_file']['tmp_name']) ) {

			$file_name = $_FILES['comparison_file']['name'];
	        move_uploaded_file($_FILES["comparison_file"]["tmp_name"], "$CURRENT_DIR/uploaded.csv");

			if (($handle = fopen("$CURRENT_DIR/uploaded.csv", "r")) !== FALSE) {

				$first_row = fgetcsv($handle);

				$pos_Log2FoldChange = 0;
				$pos_PValue = 0;
				$pos_AdjustedPValue = 0;
				foreach($first_row as $i=>$name){

					if(in_array(strtolower($name), $column_names['Log2FoldChange'])) $pos_Log2FoldChange = $i;
					else if(in_array(strtolower($name), $column_names['PValue'])) $pos_PValue = $i;
					else if(in_array(strtolower($name), $column_names['AdjustedPValue'])) $pos_AdjustedPValue = $i;
				}

				$n = 0;
			    while (($data = fgetcsv($handle)) !== FALSE) {

					$gene_name = strtolower($data[0]);
					$value_Log2FoldChange = $pos_Log2FoldChange > 0 ? $data[$pos_Log2FoldChange] : '';
					$value_PValue = $pos_PValue > 0 ? $data[$pos_PValue] : '';
					$value_AdjustedPValue = $pos_AdjustedPValue > 0 ? $data[$pos_AdjustedPValue] : '';

					if($statistic_field == 'PValue'){
						if($value_PValue > $statistic_cutoff) continue;
					}
					else if($statistic_field == 'AdjustedPValue'){
						if($value_AdjustedPValue > $statistic_cutoff) continue;
					}

					if($log2fc_cutoff != ''){
						if($_POST['fc_direction'] == 'Up'){
							if($value_Log2FoldChange < $log2fc_cutoff) continue;
						}
						else if($_POST['fc_direction'] == 'Down'){
							if($value_Log2FoldChange > -1 * $log2fc_cutoff) continue;
						}
						else {
							if(abs($value_Log2FoldChange) < $log2fc_cutoff) continue;
						}
					}

					$results[$gene_name][$file_name]['Log2FoldChange'] = sprintf("%.4f", $value_Log2FoldChange);
					$results[$gene_name][$file_name]['PValue'] = sprintf("%.4f", $value_PValue);
					$results[$gene_name][$file_name]['AdjustedPValue'] = sprintf("%.4f", $value_AdjustedPValue);

					$n++;

					if($n > $limit) break;

			    }
			    fclose($handle);
			}
	    }

		$rs_all = array();
		$list = array_keys($results);
		$number_list = count($list);
		$n=0;
		do{
			$list_partial = array_slice($list, $n, 100);
			$n += 100;

			$sql = "SELECT `Name`, `GeneIndex` FROM ?n WHERE `Species` = ?s AND `Name` IN (?a)";
			$rs = $BXAF_MODULE_CONN -> get_assoc('Name', $sql, $BXAF_CONFIG['TBL_BXGENOMICS_GENES_INDEX'], $_SESSION['SPECIES_DEFAULT'], $list_partial );
			if(is_array($rs)) $rs_all = $rs_all + $rs;

		} while($n <= $number_list);

		$gene_nameindexes = array();
		foreach($rs_all as $k=>$v) $gene_nameindexes[strtolower($k)] = $v;

		$gene_indexnames = category_list_to_idnames(array_values($gene_nameindexes), 'id', 'gene', $_SESSION['SPECIES_DEFAULT']);

		$comparison_indexnames = array();
	}
	else {

		$data_type = 'Comparison';

		$comparison_indexnames = category_text_to_idnames($_POST['Comparison_List'], 'name', 'comparison', $_SESSION['SPECIES_DEFAULT']);
		$comparison_nameindexes = array_flip($comparison_indexnames);

		if ( ! is_array($comparison_indexnames) || count($comparison_indexnames) <= 0 ){
			echo "Please enter at least a comparison ID.";
			exit();
		}

		$primaryIndex = array();
		$secondaryIndex = array_keys($comparison_indexnames);
		if(is_array($BXAF_CONFIG['TABIX_INDEX'][ $_SESSION['SPECIES_DEFAULT'] ]) && count( $BXAF_CONFIG['TABIX_INDEX'][ $_SESSION['SPECIES_DEFAULT'] ] ) > 0) {
			$tabix_file_public  =  tabix_search_records_public2($primaryIndex, $secondaryIndex, 'ComparisonData');
			$tabix_file_private = tabix_search_records_private2($primaryIndex, $secondaryIndex, 'ComparisonData');
			$tabix_files = array('Public'=>$tabix_file_public , 'Private'=>$tabix_file_private );
		}
		else {
			$tabix_file_private = tabix_search_records_private2($primaryIndex, $secondaryIndex, 'ComparisonData');
			$tabix_files = array('Private'=>$tabix_file_private );
		}

		$n = 0;

		foreach($tabix_files as $k=>$filename){

			if($k == 'Public'){
				$columnOrder = array('ComparisonIndex', 'GeneIndex', 'Name', 'Log2FoldChange', 'PValue', 'AdjustedPValue', 'NumeratorValue', 'DenominatorValue');
			}
			else if($k == 'Private'){
				$columnOrder = array('GeneIndex', 'ComparisonIndex', 'Name', 'ComparisonName', 'Log2FoldChange', 'PValue', 'AdjustedPValue');
			}
			else {
				break;
			}

			$columnOrder_flip = array_flip($columnOrder);
			$columnOrder_length = count($columnOrder);

			$k1 = $columnOrder_flip['Log2FoldChange'];
			$k2 = $columnOrder_flip['PValue'];
			$k3 = $columnOrder_flip['AdjustedPValue'];

			$k_GeneIndex = $columnOrder_flip['GeneIndex'];
			$k_ComparisonIndex = $columnOrder_flip['ComparisonIndex'];
			$k_log2fc = $columnOrder_flip['Log2FoldChange'];
			$k_statistic = $columnOrder_flip[ $statistic_field ];

			if ( ($handle = fopen($filename, 'r')) !== FALSE ) {

				while (($row = fgets($handle)) !== FALSE) {

					$row_exploded = array_pad( array_slice(explode("\t", trim($row)), 0, $columnOrder_length), $columnOrder_length, 0);

					if(($row_exploded[$k1] == 0 || $row_exploded[$k1] == '.') && ($row_exploded[$k2] == 0 || $row_exploded[$k2] == '.') && ($row_exploded[$k3] == 0 || $row_exploded[$k3] == '.')) continue;

					if($row_exploded[ $k_statistic ] > $statistic_cutoff) continue;

					if($log2fc_cutoff != ''){
						if($_POST['fc_direction'] == 'Up'){
							if($row_exploded[$k_log2fc] < $log2fc_cutoff) continue;
						}
						else if($_POST['fc_direction'] == 'Down'){
							if($row_exploded[$k_log2fc] > -1 * $log2fc_cutoff) continue;
						}
						else {
							if(abs($row_exploded[$k_log2fc]) < $log2fc_cutoff) continue;
						}
					}

					$results[$row_exploded[$k_GeneIndex]][$row_exploded[$k_ComparisonIndex]]['Log2FoldChange'] = sprintf("%.4f", $row_exploded[$k1]);
					$results[$row_exploded[$k_GeneIndex]][$row_exploded[$k_ComparisonIndex]]['PValue'] = sprintf("%.4f", $row_exploded[$k2]);
					$results[$row_exploded[$k_GeneIndex]][$row_exploded[$k_ComparisonIndex]]['AdjustedPValue'] = sprintf("%.4f", $row_exploded[$k3]);

					$n++;

					if($n > $limit) break;
				}

				fclose($handle);
			}

		}

		if($n >= $limit){
			echo "<h3 class='text-danger my-3'>Warning: too many genes found! Results are truncated with $limit genes.</h3>";
		}

		if($_POST['List_Genes'] == 'Common'){
			// Show common genes
			$n_comparisons = count($comparison_indexnames);
			foreach($results as $k=>$v){
				if(count($v) != $n_comparisons) unset($results[$k]);
			}
		}
		else {
			// Fill in missing data
			foreach($tabix_files as $k=>$filename){

				if($k == 'Public'){
					$columnOrder = array('ComparisonIndex', 'GeneIndex', 'Name', 'Log2FoldChange', 'PValue', 'AdjustedPValue', 'NumeratorValue', 'DenominatorValue');
				}
				else if($k == 'Private'){
					$columnOrder = array('GeneIndex', 'ComparisonIndex', 'Name', 'ComparisonName', 'Log2FoldChange', 'PValue', 'AdjustedPValue');
				}
				else {
					break;
				}

				$columnOrder_flip = array_flip($columnOrder);
				$columnOrder_length = count($columnOrder);

				$k1 = $columnOrder_flip['Log2FoldChange'];
				$k2 = $columnOrder_flip['PValue'];
				$k3 = $columnOrder_flip['AdjustedPValue'];

				$k_GeneIndex = $columnOrder_flip['GeneIndex'];
				$k_ComparisonIndex = $columnOrder_flip['ComparisonIndex'];
				$k_log2fc = $columnOrder_flip['Log2FoldChange'];
				$k_statistic = $columnOrder_flip[ $statistic_field ];

				if ( ($handle = fopen($filename, 'r')) !== FALSE ) {

					while (($row = fgets($handle)) !== FALSE) {

						$row_exploded = array_pad( array_slice(explode("\t", trim($row)), 0, $columnOrder_length), $columnOrder_length, 0);

						if(! array_key_exists($row_exploded[$k_GeneIndex], $results)) continue;

						if(($row_exploded[$k1] == 0 || $row_exploded[$k1] == '.') && ($row_exploded[$k2] == 0 || $row_exploded[$k2] == '.') && ($row_exploded[$k3] == 0 || $row_exploded[$k3] == '.')) continue;

						if(! is_array($results[$row_exploded[$k_GeneIndex]]) || ! array_key_exists($row_exploded[$k_ComparisonIndex], $results[$row_exploded[$k_GeneIndex]])){
							$results[$row_exploded[$k_GeneIndex]][$row_exploded[$k_ComparisonIndex]]['Log2FoldChange'] = sprintf("%.4f", $row_exploded[$k1]);
							$results[$row_exploded[$k_GeneIndex]][$row_exploded[$k_ComparisonIndex]]['PValue'] = sprintf("%.4f", $row_exploded[$k2]);
							$results[$row_exploded[$k_GeneIndex]][$row_exploded[$k_ComparisonIndex]]['AdjustedPValue'] = sprintf("%.4f", $row_exploded[$k3]);
						}

					}

					fclose($handle);
				}

			}
		}


		$gene_indexnames = category_list_to_idnames(array_keys($results), 'id', 'gene', $_SESSION['SPECIES_DEFAULT']);

	}

	if(count($results) <= 0){
		echo "<h3 class='text-danger my-3'>Warning: no genes found.</h3>";
		exit();
	}

	$gene_info = array();
	$list = array_keys($gene_indexnames);
	$number_list = count($list);
	$n=0;
	do{
		$list_partial = array_slice($list, $n, 1000);
		$n += 1000;

		$sql = "SELECT `ID`, `GeneName`, `Alias`, `Ensembl`, `EntrezID`, `Description`  FROM ?n WHERE `ID` IN (?a)";
		$rs = $BXAF_MODULE_CONN -> get_assoc('ID', $sql, $BXAF_CONFIG['TBL_BXGENOMICS_GENES'], $list_partial );
		if(is_array($rs)) $gene_info = $gene_info + $rs;

	} while($n <= $number_list);

	if($data_type == 'Comparison'){
		foreach($gene_indexnames as $id=>$name){
			if(! array_key_exists($id, $gene_info)) {
				$gene_info[$id]['GeneName'] = "";
				$gene_info[$id]['Alias'] = "";
				$gene_info[$id]['Ensembl'] = "";
				$gene_info[$id]['EntrezID'] = "";
				$gene_info[$id]['Description'] = "";
			}
		}
	}

	$header_names = array(
		'Log2FoldChange'=>'Log2FC',
		'PValue'=>'P.Value',
		'AdjustedPValue'=>'FDR'
	);

	$header0 = array('GeneName', 'Description');
	if($data_type == 'File'){
		$header = $header0;
		foreach($_POST['Display_Options'] as $k){
			$header[] = $header_names[$k];
		}
	}
	else if($data_type == 'Comparison'){
		$header = $header0;
		foreach($comparison_indexnames as $comparison_index => $comparison_name){
			foreach($_POST['Display_Options'] as $k){
				$header[] = $comparison_name . " - " . $header_names[$k];
			}
		}
	}




    echo '<div class="w-100 mb-3">';
        echo '<a class="m-1 btn btn-sm btn-primary btn_comparison_actions" action_type="../tool_save_lists/new_list.php?category=comparison" href="Javascript: void(0);"><i class="fas fa-caret-right mx-1"></i> Save Comparison List</a>';
        echo '<a class="m-1 btn btn-sm btn-primary btn_comparison_actions" action_type="../tool_bubble_plot/multiple.php?category=comparison" href="Javascript: void(0);"><i class="fas fa-caret-right mx-1"></i> Bubble Plot</a>';
        // echo '<a class="m-1 btn btn-sm btn-primary btn_comparison_actions" action_type="../tool_pathway/changed_genes.php?category=comparison" href="Javascript: void(0);"><i class="fas fa-caret-right mx-1"></i> Significantly Changed Genes</a>';
        echo '<a class="m-1 btn btn-sm btn-primary btn_comparison_actions" action_type="../tool_pathway_heatmap/index.php?category=comparison" href="Javascript: void(0);"><i class="fas fa-caret-right mx-1"></i> Pathway Heatmap</a>';
        echo '<a class="m-1 btn btn-sm btn-primary btn_comparison_actions" action_type="../tool_meta_analysis/index.php?category=comparison" href="Javascript: void(0);"><i class="fas fa-caret-right mx-1"></i> Meta Analysis</a>';
        echo '<a class="m-1 btn btn-sm btn-primary btn_comparison_actions" action_type="../tool_export/genes_comparisons.php?category=comparison" href="Javascript: void(0);"><i class="fas fa-caret-right mx-1"></i> Export Comparison Data</a>';

		echo '<a class="m-1 btn btn-sm btn-primary btn_comparison_actions" action_type="../tool_pathway/index.php?category=comparison" href="Javascript: void(0);"><i class="fas fa-caret-right mx-1"></i> WikiPathways</a>';
		echo '<a class="m-1 btn btn-sm btn-primary btn_comparison_actions" action_type="../tool_pathway/reactome.php?category=comparison" href="Javascript: void(0);"><i class="fas fa-caret-right mx-1"></i> Reactome Pathways</a>';
		echo '<a class="m-1 btn btn-sm btn-primary btn_comparison_actions" action_type="../tool_pathway/kegg.php?category=comparison" href="Javascript: void(0);"><i class="fas fa-caret-right mx-1"></i> KEGG Pathways</a>';

    echo '</div>';

    echo '<div class="w-100 mb-3">';
		echo '<a class="m-1 btn btn-sm btn-success btn_gene_actions" action_type="../tool_save_lists/new_list.php?category=gene" href="Javascript: void(0);"><i class="fas fa-caret-right mx-1"></i> Save Gene List</a>';
        echo '<a class="m-1 btn btn-sm btn-success btn_gene_actions" action_type="../tool_gene_expression_plot/index.php?category=gene" href="Javascript: void(0);"><i class="fas fa-caret-right mx-1"></i> Gene Expression Plot</a>';
        echo '<a class="m-1 btn btn-sm btn-success btn_gene_actions" action_type="../tool_heatmap/index.php?category=gene" href="Javascript: void(0);"><i class="fas fa-caret-right mx-1"></i> Heatmap</a>';
        echo '<a class="m-1 btn btn-sm btn-success btn_gene_actions" action_type="../tool_correlation/index.php?category=gene" href="Javascript: void(0);"><i class="fas fa-caret-right mx-1"></i> Correlation Tool</a>';
        echo '<a class="m-1 btn btn-sm btn-success btn_gene_actions" action_type="../tool_pca/index_genes_samples.php?category=gene" href="Javascript: void(0);"><i class="fas fa-caret-right mx-1"></i> PCA Analysis</a>';
        echo '<a class="m-1 btn btn-sm btn-success btn_gene_actions" action_type="../tool_export/genes_samples.php?category=gene" href="Javascript: void(0);"><i class="fas fa-caret-right mx-1"></i> Export Expression Data</a>';
    echo '</div>';

	//Table
	$table_contents = '';
	$table_contents .= "<div class='w-100 my-3'><table id='resultTable' class='table table-bordered table-hover'><thead><tr class='table-success'><th class='text-center'><input type='checkbox' class='bxaf_checkbox bxaf_checkbox_all' /></th>";
	foreach($header as $col) $table_contents .= "<th>$col</th>";
	$table_contents .= "</tr></thead><tbody>";

	$input_all_gene_ids = array();
	foreach($results as $gene=>$values){

		if($data_type == 'File'){

			if(! array_key_exists($gene, $gene_nameindexes) || ! array_key_exists($gene_nameindexes[$gene], $gene_info) ) continue;

			$gene_index = $gene_nameindexes[$gene];

			if($gene_info[$gene_index]['GeneName'] == '') continue;

			$table_contents .= "<tr>";
			$table_contents .= "<td class='text-center'><input type='checkbox' class='bxaf_checkbox bxaf_checkbox_one' rowid='$gene_index' /></td>";
			foreach($header0 as $k){
				if($k == 'GeneName') $table_contents .= "<td><a href='../tool_search/view.php?type=gene&id=$gene_index' target='_blank'>" . $gene_info[$gene_index][$k] . "</a></td>";
				else $table_contents .= "<td>" . $gene_info[$gene_index][$k] . "</td>";
			}
			foreach($_POST['Display_Options'] as $k){
				$table_contents .= "<td style='color: " . get_stat_scale_color2($results[$gene][$file_name][$k], $k) . ";'>" . $results[$gene][$file_name][$k] . "</td>";
			}
			$table_contents .= "</tr>";

			$input_all_gene_ids[] = $gene_index;
		}

		else if($data_type == 'Comparison'){

			if( trim($gene_info[$gene]['GeneName']) == '') continue;

			$table_contents .= "<tr>";
			$table_contents .= "<td class='text-center'><input type='checkbox' class='bxaf_checkbox bxaf_checkbox_one' rowid='$gene' /></td>";
			foreach($header0 as $k){
				if($k == 'GeneName') $table_contents .= "<td><a href='../tool_search/view.php?type=gene&id=$gene' target='_blank'>" . $gene_info[$gene][$k] . "</a></td>";
				else $table_contents .= "<td>" . $gene_info[$gene][$k] . "</td>";
			}
			foreach($comparison_indexnames as $comparison_index => $comparison_name){
				foreach($_POST['Display_Options'] as $k){
					$table_contents .= "<td style='color: " . get_stat_scale_color2($results[$gene][$comparison_index][$k], $k) . ";'>" . $results[$gene][$comparison_index][$k] . "</td>";
				}
			}
			$table_contents .= "</tr>";

			$input_all_gene_ids[] = $gene;
		}

	}

	$table_contents .= "</tbody></table></div>";

	echo $table_contents;

	echo "<div class='w-100'><span class='font-weight-bold'>Color Scheme:</span> ";
		echo "<span class='mx-2 px-2 py-1' style='background-color: #FF0000;'>LogFC &gt; 1</span>";
		echo "<span class='mx-2 px-2 py-1' style='background-color: #FF8989;'>LogFC &gt; 0</span>";
		echo "<span class='mx-2 px-2 py-1' style='background-color: #E5E5E5;'>LogFC = 0</span>";
		echo "<span class='mx-2 px-2 py-1' style='background-color: #7070FB;'>LogFC &gt; -1</span>";
		echo "<span class='mx-2 px-2 py-1' style='background-color: #0000FF; color:#FFFFFF'>LogFC &lt; -1</span>";

		echo "<span class='ml-5 px-2 py-1' style='background-color: #9CA4B3;'>P.Value &gt;= 0.01</span>";
		echo "<span class='mx-2 px-2 py-1' style='background-color: #5AC72C;'>P.Value &lt; 0.01</span>";

		echo "<span class='ml-5 px-2 py-1' style='background-color: #9CA4B3;'>adj.P.Val &gt; 0.05</span>";
		echo "<span class='mx-2 px-2 py-1' style='background-color: #5AC72C;'>0.01 &gt; adj.P.Val &lt; 0.05</span>";
		echo "<span class='mx-2 px-2 py-1' style='background-color: #015402; color:#FFFFFF'>adj.P.Val &lt; 0.01</span>";

	echo "</div>";

	echo "<input type='hidden' id='input_all_gene_ids' value='" . implode(",", $input_all_gene_ids ) . "' />";
	echo "<input type='hidden' id='input_all_comparison_ids' value='" . implode(",", array_keys($comparison_indexnames) ) . "' />";

	exit();
}

?>