<?php
include_once('config.php');


$SPECIES = 'Human';
if(isset($_GET['species']) && in_array(ucfirst(strtolower($_GET['species'])), $SPECIES_ALL) ){
	$SPECIES = ucfirst(strtolower($_GET['species']));
}

$PAGE_TYPE = 'Comparison';
if(isset($_GET['type']) && in_array(ucfirst(strtolower($_GET['type'])), $PAGE_TYPE_ALL) ){
	$PAGE_TYPE = ucfirst(strtolower($_GET['type']));
}
else if(isset($_POST['FORM_TYPE']) && in_array(ucfirst(strtolower($_POST['FORM_TYPE'])), $PAGE_TYPE_ALL) ){
	$PAGE_TYPE = ucfirst(strtolower($_POST['FORM_TYPE']));
}

$TABLE = $TABLE_ALL[$PAGE_TYPE];

$PREFERENCE_TYPE = $PREFERENCE_TYPE_ALL[$PAGE_TYPE];





if (isset($_GET['action']) && $_GET['action'] == 'data_table_dynamic_loading') {

	$TERMS = '*';

	$sql0 = "SELECT COUNT(`ID`) FROM `{$TABLE}` WHERE `Species` = '$SPECIES' AND {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} ";
	$sql1 = "SELECT {$TERMS} FROM `{$TABLE}` WHERE `Species` = '$SPECIES' AND {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} ";

	$sql = "";

	// Search Condition
	if(isset($_POST['search']['value']) && trim($_POST['search']['value']) != '') {
		$search_array = array();

		for ($i = 0; $i < count($_POST['columns']); $i++){

			$field = $_POST['columns'][$i]['data'];
			$value = addslashes($_POST['search']['value']);

			if(	$PAGE_TYPE == 'Project'    && $field == '_Platforms_ID' ||
				$PAGE_TYPE == 'Sample'     && $field == '_Platforms_ID' ||
				$PAGE_TYPE == 'Comparison' && $field == '_Platforms_ID' ){

				$sql_temp = "SELECT `ID` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_PLATFORMS']}` WHERE `GEO_Accession` LIKE '%" . $value . "%' LIMIT 100";
				$ids = $BXAF_MODULE_CONN -> get_col($sql_temp);

				if(is_array($ids) && count($ids) > 0) $search_array[] = "`" . $field . "` IN (" . implode(",", $ids) . ")";

			}
			else if(	$PAGE_TYPE == 'Sample'     && $field == '_Projects_ID' ||
						$PAGE_TYPE == 'Comparison' && $field == '_Projects_ID' ){

				$sql_temp = "SELECT `ID` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS']}` WHERE `Name` LIKE '%" . $value . "%'";
				$ids = $BXAF_MODULE_CONN -> get_col($sql_temp);


				if(is_array($ids) && count($ids) > 0) $search_array[] = "`" . $field . "` IN (" . implode(",", $ids) . ")";

			}

			else if(	$PAGE_TYPE == 'Project'    && $field == '_Analysis_ID' ||
						$PAGE_TYPE == 'Comparison' && $field == '_Analysis_ID' ){

				$sql_temp = "SELECT `ID` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_ANALYSIS']}` WHERE `Name` LIKE '%" . $value . "%'";
				$ids = $BXAF_MODULE_CONN -> get_col($sql_temp);

				if(is_array($ids) && count($ids) > 0) $search_array[] = "`" . $field . "` IN (" . implode(",", $ids) . ")";

			}

			else if(	$PAGE_TYPE == 'Sample'     && $field == '_Samples_ID' ){

				$sql_temp = "SELECT `ID` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_SAMPLE']}` WHERE `Name` LIKE '%" . $value . "%'";
				$ids = $BXAF_MODULE_CONN -> get_col($sql_temp);

				if(is_array($ids) && count($ids) > 0) $search_array[] = "`" . $field . "` IN (" . implode(",", $ids) . ")";

			}
			else {
				$search_array[] = "`" . $field . "` LIKE '%" . $value . "%'";
			}

		}

		$sql .= " AND (" . implode(" OR ", $search_array) . ")";
		if (isset($_POST['sql']) && trim($_POST['sql']) != '') {
			$sql .= " AND " . $_POST['sql'];
		}
	}
	else {
		if (isset($_POST['sql']) && trim($_POST['sql']) != '') {
			$sql .= " AND " . $_POST['sql'];
		}
	}

	// Order Condition
	$condition_array = array();
	for ($i = 0; $i < count($_POST['order']); $i++) {
		$condition_array[] = "`" . $_POST['columns'][$_POST['order'][$i]['column']]['data'] . "` " . $_POST['order'][$i]['dir'] . "";
	}
	if(count($condition_array) > 0){
		$sql .= " ORDER BY " . implode(", ", $condition_array);
	}

	$data = $BXAF_MODULE_CONN -> get_all($sql1 . $sql . " LIMIT 40000");
	$recordsTotal = $BXAF_MODULE_CONN -> get_one($sql0 . $sql);

	$count = count($data);
	$output_array = array(
		'draw' => intval($_POST['draw']),
		'recordsTotal' => $recordsTotal,
		'recordsFiltered' => $count,
		'data' => array(),
		'sql' => $sql
	);


	foreach($data as $key => $value) {

		if($key >= intval($_POST['start']) && $key < intval($_POST['start'] + $_POST['length'])){
			// Combine ID and Index
			$value[$PAGE_TYPE . 'ID'] = $value['ID'] . '__' . $value[$PAGE_TYPE . 'Index'];

			// Project Table
			if($TABLE == $BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS'] && array_key_exists('_Platforms_ID', $value) ) {
				if( preg_match("/^GPL/", $value['Platform']) ){
					$value['_Platforms_ID'] = "<a target='_blank' href='https://www.ncbi.nlm.nih.gov/geo/query/acc.cgi?acc=" . $value['Platform'] . "'>" . $value['Platform'] . "</a>";
				}
				else {
					$value['_Platforms_ID'] = $value['Platform'];
				}

			}
			if($TABLE == $BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS'] && array_key_exists('Platform', $value) ) {
				if( preg_match("/^GPL/", $value['Platform']) ){
					$value['Platform'] = "<a target='_blank' href='https://www.ncbi.nlm.nih.gov/geo/query/acc.cgi?acc=" . $value['Platform'] . "'>" . $value['Platform'] . "</a>";
				}
			}
			if($TABLE == $BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS'] && array_key_exists('Name', $value) ) {
				$value['Name'] = "<a target='_blank' href='../project.php?name=" . $value['Name'] . "'>" . $value['Name'] . "</a>";
			}
			if($TABLE == $BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS'] && array_key_exists('Accession', $value) ) {
				if( preg_match("/^GSE/", $value['Accession']) ){
					$value['Accession'] = "<a target='_blank' href='https://www.ncbi.nlm.nih.gov/geo/query/acc.cgi?acc=" . $value['Accession'] . "'>" . $value['Accession'] . "</a>";
				}
			}

			// Comparison Table
			if($TABLE == $BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS'] && array_key_exists('_Platforms_ID', $value) ) {
				$sql = "SELECT `GEO_Accession` FROM ?n WHERE `bxafStatus` < 5 AND `ID`= ?i";
	            $platform = $BXAF_MODULE_CONN -> get_one($sql, $BXAF_CONFIG['TBL_BXGENOMICS_PLATFORMS'], $value['_Platforms_ID'] );

				if( preg_match("/^GPL/", $platform) ){
					$value['_Platforms_ID'] = "<a target='_blank' href='https://www.ncbi.nlm.nih.gov/geo/query/acc.cgi?acc=" . $platform . "'>" . $platform . "</a>";
				}
				else {
					$value['_Platforms_ID'] = $platform;
				}
			}
			if($TABLE == $BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS'] && array_key_exists('_Projects_ID', $value) ) {
				$sql = "SELECT `Name` FROM ?n WHERE `bxafStatus` < 5 AND `ID`= ?i";
	            $project_name = $BXAF_MODULE_CONN -> get_one($sql, $BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS'], $value['_Projects_ID'] );
				$value['_Projects_ID'] = "<a target='_blank' href='../project.php?id=" . $value['_Projects_ID'] . "'>" . $project_name . "</a>";
			}
			if($TABLE == $BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS'] && array_key_exists('Case_SampleIDs', $value) ) {
				$value['Case_SampleIDs'] = '<a href="Javascript: void(0);" onClick="$(this).next().toggle();">Show/Hide</a><div style="display: none;">' . str_replace(';', ' ', $value['Case_SampleIDs']) . '</div>';
			}
			if($TABLE == $BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS'] && array_key_exists('Control_SampleIDs', $value) ) {
				$value['Control_SampleIDs'] = '<a href="Javascript: void(0);" onClick="$(this).next().toggle();">Show/Hide</a><div style="display: none;">' . str_replace(';', ' ', $value['Control_SampleIDs']) . '</div>';
			}


			// Sample Table
			if($TABLE == $BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES'] && array_key_exists('_Platforms_ID', $value) ) {
				$sql = "SELECT `GEO_Accession` FROM ?n WHERE `bxafStatus` < 5 AND `ID`= ?i";
	            $platform = $BXAF_MODULE_CONN -> get_one($sql, $BXAF_CONFIG['TBL_BXGENOMICS_PLATFORMS'], $value['_Platforms_ID'] );

				if( preg_match("/^GPL/", $platform) ){
					$value['_Platforms_ID'] = "<a target='_blank' href='https://www.ncbi.nlm.nih.gov/geo/query/acc.cgi?acc=" . $platform . "'>" . $platform . "</a>";
				}
				else {
					$value['_Platforms_ID'] = $platform;
				}
			}
			if($TABLE == $BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES'] && array_key_exists('_Projects_ID', $value) ) {
				$sql = "SELECT `Name` FROM ?n WHERE `bxafStatus` < 5 AND `ID`= ?i";
	            $project_name = $BXAF_MODULE_CONN -> get_one($sql, $BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS'], $value['_Projects_ID'] );
				$value['_Projects_ID'] = "<a target='_blank' href='../project.php?id=" . $value['_Projects_ID'] . "'>" . $project_name . "</a>";
			}
			if($TABLE == $BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES'] && array_key_exists('Name', $value) ) {
				if( preg_match("/^GSM/", $value['Name']) ){
					$value['Name'] = "<a target='_blank' href='https://www.ncbi.nlm.nih.gov/geo/query/acc.cgi?acc=" . $value['Name'] . "'>" . $value['Name'] . "</a>";
				}
			}


			$output_array['data'][] = $value;
		}
	}

	echo json_encode($output_array);

	exit();
}




if (isset($_GET['action']) && $_GET['action'] == 'show_chart_page') {

	$COMPARISON_INDEX = $_POST['comparison_index'];

	$sql = "SELECT * FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS']}` WHERE `ID` = ?i";
    $COMPARISON_INFO = $BXAF_MODULE_CONN -> get_row($sql, $COMPARISON_INDEX);
    if (! is_array($COMPARISON_INFO) || count($COMPARISON_INFO) <= 0) {
        echo 'Error: No comparison found.';
        exit();
    }
    $COMPARISON_INDEX = $COMPARISON_INFO['ID'];

    $species = $COMPARISON_INFO['Species'];
	$dir_page_out = $BXAF_CONFIG['PAGE_OUTPUT'][ strtoupper($species) ];

	$csv_dir = $dir_page_out . 'comparison_' . $COMPARISON_INDEX . '_GSEA.PAGE.csv';

	$CONTENT = array();

	if (file_exists($csv_dir)) {
		$file = fopen($csv_dir, "r");
		while (! feof($file)) {
			$CONTENT[] = fgetcsv($file);
		}
		fclose($file);
	}
	else {
		$JSON_ARRAY['error'] = 'PAGE output file for this comparison is not found.';

		header('Content-Type: application/json');
		echo json_encode($JSON_ARRAY);

		exit();
	}

	function sort_by_large($a, $b) {
		return ($a['2'] - $b['2'] > 0) ? -1 : 1;
	}
	function sort_by_small($a, $b) {
		return ($a['2'] - $b['2'] < 0) ? -1 : 1;
	}

	// Get Top 10 Z-Score Records
	usort($CONTENT, 'sort_by_large');
	$RESULT_LARGE = array();
	for ($i = 0; $i < 10; $i++) {
		if(is_array($CONTENT[$i]) && count($CONTENT[$i]) > 0 && isset($CONTENT[$i][0]) && $CONTENT[$i][0] != 'Name') $RESULT_LARGE[] = $CONTENT[$i];
	}

	// Get Bottom 10 Z-Score Records
	usort($CONTENT, 'sort_by_small');
	$RESULT_SMALL = array();
	for ($i = 0; $i < 10; $i++) {
		if(is_array($CONTENT[$i]) && count($CONTENT[$i]) > 0 && isset($CONTENT[$i][0]) && $CONTENT[$i][0] != 'Name') $RESULT_SMALL[] = $CONTENT[$i];
	}

	$x_up = array();
	$y_up = array();
	$marker_size_up = array();
	$marker_color_up = array();
	$text_up = array();
	foreach ($RESULT_LARGE as $key => $value) {
		$x_up[] = $value['2'];
		$y_up[] = substr(str_replace('_', ' ', $value['0']), 0, 50) . (strlen($value['0']) > 50 ? '..' : '');

		$marker = log10($value['1']) * 10;
		$marker_size_up[] = $marker;

		if (floatval($value[4]) < 0.2) {
			$marker_color_up[] = '#FF0000';
		} else if (floatval($value[4]) < 0.5) {
			$marker_color_up[] = '#FF8989';
		} else if (floatval($value[4]) < 0.8) {
			$marker_color_up[] = '#8E8EFF';
		} else {
			$marker_color_up[] = '#0000FF';
		}

		$text =  'Name: ' . $value[0] . '<br />';
		$text .= 'Total Genes: ' . $value[1] . '<br />';
		$text .= 'Z Score: ' . $value[2] . '<br />';
		$text .= 'P-Value: ' . $value[3] . '<br />';
		$text .= 'FDR: ' . $value[4];
		$text_up[] = $text;
	}

	$x_down = array();
	$y_down = array();
	$marker_size_down = array();
	$marker_color_down = array();
	$text_down = array();
	foreach ($RESULT_SMALL as $key => $value) {
		$x_down[] = $value['2'];
		$y_down[] = substr(str_replace('_', ' ', $value['0']), 0, 50) . (strlen($value['0']) > 50 ? '..' : '');


		$marker = log10($value['1']) * 10;
		// if (floatval($value['1']/5) > 70) {
		//   $marker = log10($value['1']); //70;
		// } else if (floatval($value['1']/5) < 5) {
		//   $marker = log10($value['1']);//5;
		// } else {
		//   $marker = log10($value['1']);//floatval($value['1']/5);
		// }
		$marker_size_down[] = $marker;

		if (floatval($value[4]) < 0.2) {
			$marker_color_down[] = '#FF0000';
		} else if (floatval($value[4]) < 0.5) {
			$marker_color_down[] = '#FF8989';
		} else if (floatval($value[4]) < 0.8) {
			$marker_color_down[] = '#8E8EFF';
		} else {
			$marker_color_down[] = '#0000FF';
		}

		$text =  'Name: ' . $value[0] . '<br />';
		$text .= 'Total Genes: ' . $value[1] . '<br />';
		$text .= 'Z Score: ' . $value[2] . '<br />';
		$text .= 'P-Value: ' . $value[3] . '<br />';
		$text .= 'FDR: ' . $value[4];
		$text_down[] = $text;
	}

	// Output
	$JSON_ARRAY = array(

		'error'=>'',

		'up' => array(
		  'data' => array(),
		  'layout' => array(),
		  'setting' => array(),
		),
		'down' => array(
		  'data' => array(),
		  'layout' => array(),
		  'setting' => array(),
		),
	);

	$JSON_ARRAY['up']['data'][] = array(
		'x' => $x_up,
		'y' => $y_up,
		'mode' => 'markers',
		'marker' => array(
		  'size' => $marker_size_up,
		  'color' => $marker_color_up,
		),
		'hoverinfo' => 'text',
		'text' => $text_up,
	);

	$JSON_ARRAY['down']['data'][] = array(
		'x' => $x_down,
		'y' => $y_down,
		'mode' => 'markers',
		'marker' => array(
		'size' => $marker_size_down,
		'color' => $marker_color_down,
		),
		'hoverinfo' => 'text',
		'text' => $text_down,
	);


	$layout = array(
		'title' => 'GSEA Plot',
		'showlegend' => false,
		// 'height' => 600,
		// 'width' => 600,
		'margin' => array('l' => 400),
		'xaxis' => array('title' => 'Z Score'),
		// 'yaxis' => array('title' => 'Geneset Name'),
		'hovermode' => 'cloest',
	);

	$setting = array(
		'displaylogo' => false,
		'modeBarButtonsToRemove' => array('sendDataToCloud'),
		'scrollZoom' => true,
		'displayModeBar' => false,
	);

	$JSON_ARRAY['up']['layout'] = $layout;
	$JSON_ARRAY['up']['setting'] = $setting;
	$JSON_ARRAY['down']['layout'] = $layout;
	$JSON_ARRAY['down']['setting'] = $setting;

	$JSON_ARRAY['up']['layout']['title'] = 'PAGE Plot - Up-regulated Genes';
	$JSON_ARRAY['down']['layout']['title'] = 'PAGE Plot - Down-regulated Genes';

	header('Content-Type: application/json');
	echo json_encode($JSON_ARRAY);

	exit();
}




if (isset($_GET['action']) && $_GET['action'] == 'go_to_volcano') {

	$GENESET = trim($_POST['geneset_name']);
	$sql = "SELECT * FROM `GeneSet` WHERE `StandardName`='{$GENESET}'";
	$data = $DB -> get_row($sql);
	$MEMBERS = explode(",", $data['Members']);

	$genes_saved = implode("|", $MEMBERS);

	$dir = $BXAF_CONFIG['USER_FILES']['TOOL_VOLCANO'] . '/' . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'];
	if (!is_dir($dir)) {
		mkdir($dir, 0755, true);
	}

	file_put_contents($dir . '/page_selected_genes.txt', $genes_saved);

	exit();
}






if (isset($_GET['action']) && $_GET['action'] == 'table_save_column_preference') {

    $columns_all = $BXAF_MODULE_CONN -> get_column_names($TABLE);
    $columns_all = array_diff($columns_all, array('bxafStatus', '_Owner_ID', 'Permission', 'Time_Created'));

    $columns_selected = array();
    foreach ($columns_all as $key) {
        if(array_key_exists($key, $_POST)) $columns_selected[] = $key;
    }

	$default_values = array(
		'comparison' =>'table_column_comparison',
		'gene'       =>'table_column_gene',
		'sample'     =>'table_column_sample',
		'project'    =>'table_column_project',
	);

	if(array_key_exists(strtolower($_POST['FORM_TYPE']), $default_values)) $category = $default_values[ strtolower($_POST['FORM_TYPE']) ];
	else exit();

	$sql = "SELECT `ID` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_USERPREFERENCE']}` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `Category` = ?s ORDER BY `ID` DESC";
	$id = $BXAF_MODULE_CONN -> get_one($sql, $category);
	if($id > 0){
		$info = array( 'Detail' => serialize($columns_selected) );
	    $BXAF_MODULE_CONN -> update( $BXAF_CONFIG['TBL_BXGENOMICS_USERPREFERENCE'], $info, "`ID`=$id" );
	}
	else {
		$info = array('Category' => ucfirst($category), '_Owner_ID' => $BXAF_CONFIG['BXAF_USER_CONTACT_ID'], 'Detail' => serialize($columns_selected) );
	    $BXAF_MODULE_CONN -> insert( $BXAF_CONFIG['TBL_BXGENOMICS_USERPREFERENCE'], $info );
	}

	exit();
}





if (isset($_GET['action']) && $_GET['action'] == 'submit_search') {


	$SQL_ADDITIONAL_CONDITION = "";
	for ($i = 0; $i < count($_POST['search_field']); $i++) {

		$logic = strtoupper($_POST['search_logic'][$i]);
		if($logic != 'AND') $logic = 'OR';

		$field = trim($_POST['search_field'][$i]);
		$value = addslashes(trim($_POST['search_value'][$i]));

		if(	$PAGE_TYPE == 'Project'    && $field == 'Platform' ){
			$field = '_Platforms_ID';
		}

		if(	$PAGE_TYPE == 'Project'    && $field == '_Platforms_ID' ||
			$PAGE_TYPE == 'Sample'     && $field == '_Platforms_ID' ||
			$PAGE_TYPE == 'Comparison' && $field == '_Platforms_ID' ){

			$sql = "SELECT `ID` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_PLATFORMS']}` WHERE `GEO_Accession` = ?s";
			$value = $BXAF_MODULE_CONN -> get_one($sql, $value);

		}
		else if(	$PAGE_TYPE == 'Sample'     && $field == '_Projects_ID' ||
					$PAGE_TYPE == 'Comparison' && $field == '_Projects_ID' ){

			$sql = "SELECT `ID` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS']}` WHERE `Name` LIKE '%" . addslashes($value) . "%'";
			$value = $BXAF_MODULE_CONN -> get_one($sql);

		}

		else if(	$PAGE_TYPE == 'Project'    && $field == '_Analysis_ID' ||
					$PAGE_TYPE == 'Comparison' && $field == '_Analysis_ID' ){

			$sql = "SELECT `ID` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_ANALYSIS']}` WHERE `Name` LIKE '%" . addslashes($value) . "%'";
			$value = $BXAF_MODULE_CONN -> get_one($sql);

		}

		else if(	$PAGE_TYPE == 'Sample'     && $field == '_Samples_ID' ){

			$sql = "SELECT `ID` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_SAMPLE']}` WHERE `Name` LIKE '%" . addslashes($value) . "%'";
			$value = $BXAF_MODULE_CONN -> get_one($sql);

		}



		// 1. Logic
		if ($i > 0) $SQL_ADDITIONAL_CONDITION .= " $logic ";

		// 2. Field Name
		$SQL_ADDITIONAL_CONDITION .= " `" . $field . "` ";

		// 3. Operator & Value
		if ($_POST['search_operator'][$i] == 'is') {
			$SQL_ADDITIONAL_CONDITION .= " = '$value' ";
		}
		else {
			$SQL_ADDITIONAL_CONDITION .= " LIKE ";
			if ($_POST['search_operator'][$i] == 'contains') {
				$SQL_ADDITIONAL_CONDITION .= "'%" . $value . "%'";
			}
			if ($_POST['search_operator'][$i] == 'starts_with') {
				$SQL_ADDITIONAL_CONDITION .= "'" . $value . "%'";
			}
			if ($_POST['search_operator'][$i] == 'ends_width') {
				$SQL_ADDITIONAL_CONDITION .= "'%" . $value . "'";
			}
		}
	}

	$SQL = "SELECT COUNT(*) FROM `{$TABLE}` WHERE `Species` = '$SPECIES' AND {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND {$SQL_ADDITIONAL_CONDITION}";

	$number_rows = $BXAF_MODULE_CONN -> get_one($SQL);




	$columns_all = $BXAF_MODULE_CONN -> get_column_names($TABLE);
	$columns_all = array_diff($columns_all, array('bxafStatus', '_Owner_ID', 'Permission', 'Time_Created'));
	sort($columns_all);


	$name_captions = array();
	foreach ($columns_all as $key => $colname) {
		if(preg_match("/^_(\w+)*_ID$/", $colname)) $caption = preg_replace("/^_(\w+)*_ID$/", '\\1', $colname);
		else $caption = str_replace('_', ' ', $colname);
		$name_captions[$colname] = $caption;
	}
	asort($name_captions);


	$columns_selected = $BXAF_CONFIG['USER_PREFERENCES'][$PREFERENCE_TYPE];


	echo '<input id="number_records" value="' . $number_rows . '" hidden>';

	echo '
		<table class="table table-bordered table-striped" id="table_main" style="width:100%; font-size:14px;">
			<thead>
				<tr>';

					echo '<th>ID</th>';

					foreach ($columns_selected as $colname) {
						if($colname == 'ID') continue;
						echo '<th>' . $name_captions[$colname] . '</th>';
					}

	echo '</tr></thead><tbody></tbody></table>';

?>


<script>
$(document).ready(function() {

	var buttonCommon = {
		exportOptions: {
			format: {
				body: function ( data, row, column, node ) {
					var str = data.toString();
    				return str.replace(/<\/?[^>]+>/gi, '');
				}
			}
		}
	};


	$('#table_main').DataTable({
		"pageLength": 100, "lengthMenu": [[10, 100, 500, 1000], [10, 100, 500, 1000]],
		"dom": 'Blfrtip',
		// "buttons": [
		//   'copy', 'csv', 'excel', 'pdf', 'print'
		// ],
		buttons: [
		  $.extend( true, {}, buttonCommon, {
		      extend: 'copyHtml5'
		  } ),
		  $.extend( true, {}, buttonCommon, {
		      extend: 'excelHtml5'
		  } ),
		  $.extend( true, {}, buttonCommon, {
		      extend: 'csvHtml5'
		  } ),
		  $.extend( true, {}, buttonCommon, {
		      extend: 'pdfHtml5'
		  } )
		],
		"processing": true,
		"serverSide": true,
		"language": {
			"infoFiltered": ""
		},
		"ajax": {
			"url": "exe.php?action=data_table_dynamic_loading&type=<?php echo $PAGE_TYPE; ?>&species=<?php echo $SPECIES; ?>",
			"type": "POST",
			<?php
				if ($SQL_ADDITIONAL_CONDITION) {
					echo '"data": {"sql": " ' . addslashes($SQL_ADDITIONAL_CONDITION) . '"}';
				}
			?>
		},
		"columns": [
			<?php if ($PAGE_TYPE == 'Sample') { ?>
				{ "data": "ID", render: function(id) {
					var content = '<div class="text-nowrap">';
					content += ' <input type="checkbox" class="checkbox_save_session mr-2" rowid="' + id + '">';
					content += ' <a class="mr-2" href="view.php?type=sample&id=' + id + '" title="View Detail" target="_blank"><i class="fas fa-list-ul"></i></a>';

					content += '<a title="Gene Expression Correlation" href="../tool_correlation/index.php?sample_id=' + id + '" target="_blank" class=" mr-1"><span class="badge badge-pill table-success text-danger">C</span></a>';
					content += '<a title="Gene Expression Plot" href="../tool_gene_expression_plot/index.php?sample_id=' + id + '" target="_blank" class=" mr-1"><span class="badge badge-pill table-success text-danger">E</span></a>';
					content += '<a title="Gene Expression Heatmap" href="../tool_heatmap/index.php?sample_id=' + id + '" target="_blank" class=" mr-1"><span class="badge badge-pill table-success text-danger">H</span></a>';
					content += '<a title="PCA Analysis" href="../tool_pca/index_genes_samples.php?sample_id=' + id + '" target="_blank" class=" mr-1"><span class="badge badge-pill table-success text-danger">P</span></a>';

					content += '<span class="ml-2 badge badge-pill table-info">' + id + '</span>' + '</div>';
					return content;
				} },
			<?php } else if ($PAGE_TYPE == 'Gene') { ?>
				{ "data": "ID", render: function(id) {
					var content = '<div class="text-nowrap">';
          			content += ' <input type="checkbox" class="checkbox_save_session mr-2" rowid="' + id + '">';
          			content += ' <a class=" mr-2" href="view.php?type=gene&id=' + id + '" title="View Detail" target="_blank"><i class="fas fa-list-ul"></i></a>';

					content += ' <a class="" href="../tool_bubble_plot/index.php?gene_id=' + id + '" data-tootik="View Bubble Plot" target="_blank"><span class="badge badge-pill table-success text-danger">B</span></a>';
					content += ' <a class="" href="../tool_gene_expression_plot/index.php?gene_id=' + id + '" data-tootik="View Gene Expression" target="_blank"><span class="badge badge-pill table-success text-danger">G</span></a>';

					content += '<span class="ml-2 badge badge-pill table-info">' + id + '</span>' + '</div>';
					return content;
				} },
			<?php } else if ($PAGE_TYPE == 'Project') { ?>
				{
					"data": "ID", render: function(id) {
						var content = '<div class="text-nowrap">';
						content += ' <input type="checkbox" class="checkbox_save_session mr-2" rowid="' + id + '">';

						content += ' <a class=" mr-2" href="../project.php?id=' + id + '" title="View Detail" target="_blank"><i class="fas fa-list-ul"></i></a> ';

						content += ' <a class="" href="../tool_pathway/index.php?project_id=' + id + '" data-tootik="View WikiPathways" target="_blank"><span class="badge badge-pill table-success text-danger">W</span></a>';
						content += ' <a class="" href="../tool_pathway/reactome.php?project_id=' + id + '" data-tootik="View Reactome Pathway" target="_blank"><span class="badge badge-pill table-success text-danger">R</span></a>';
						content += ' <a class="" href="../tool_pathway/kegg.php?project_id=' + id + '" data-tootik="View KEGG Pathways" target="_blank"><span class="badge badge-pill table-success text-danger">K</span></a>';

						content += '<span class="ml-2 badge badge-pill table-info">' + id + '</span>' + '</div>';
						return content;
					}
				},
			<?php } else if ($PAGE_TYPE == 'Comparison') { ?>
				{ "data": "ID", render: function(id) {

					var content = '<div class="text-nowrap">';
					content += ' <input type="checkbox" class="checkbox_save_session mr-2" rowid="' + id + '">';

					content += ' <a href="view.php?type=comparison&id=' + id + '" title="View Detail" target="_blank" class="btn_view_detail mr-2"><i class="fas fa-list-ul"></i></a>';

					content += '<a href="../tool_bubble_plot/multiple.php?comparison_id=' + id + '" title="Bubble Plot" target="_blank" class=" mr-1"><span class="badge badge-pill table-success text-danger">B</span></a>';
					content += '<a href="../tool_meta_analysis/index.php?comparison_id=' + id + '" title="Meta Analysis" target="_blank" class=" mr-1"><span class="badge badge-pill table-success text-danger">M</span></a>';
					content += '<a href="../tool_pathway_heatmap/index.php?comparison_id=' + id + '" title="Pathway Heatmap" target="_blank" class=" mr-1"><span class="badge badge-pill table-success text-danger">H</span></a>';
					content += '<a href="../tool_pathway/changed_genes.php?comparison_id=' + id + '" title="Significantly Changed Genes" target="_blank" class=" mr-1"><span class="badge badge-pill table-success text-danger">C</span></a>';
					content += '<a href="../tool_volcano_plot/index.php?comparison_id=' + id + '" title="Volcano Plot" target="_blank" class=" mr-1"><span class="badge badge-pill table-success text-danger">V</span></a>';
					content += '<a href="../tool_pathway/index.php?comparison_id=' + id + '" title="WikiPathways" target="_blank" class=" mr-1"><span class="badge badge-pill table-success text-danger">W</span></a>';
					content += '<a href="../tool_pathway/reactome.php?comparison_id=' + id + '" title="Reactome Pathways" target="_blank" class=" mr-1"><span class="badge badge-pill table-success text-danger">R</span></a>';
					content += '<a href="../tool_pathway/kegg.php?comparison_id=' + id + '" title="KEGG Pathways" target="_blank" class=" mr-1"><span class="badge badge-pill table-success text-danger">K</span></a>';

					content += '<span class="ml-2 badge badge-pill table-info">' + id + '</span>' + '</div>';
					return content;
				} },
			<?php } ?>

			<?php
				foreach ($columns_selected as $colname) {
					if($colname == 'ID') continue;
					echo '{ "data": "' . $colname . '" },';
				}
			?>
		]
	});


});
</script>



<?php
	exit();
}



?>