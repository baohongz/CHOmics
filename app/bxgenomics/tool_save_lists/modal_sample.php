<?php
include_once( __DIR__ . '/config.php');


// Get Saved List
if (isset($_GET['action']) && $_GET['action'] == 'get_saved_lists') {

	$type_list = array('comparison', 'gene', 'project', 'sample');
	$category = strtolower($_GET['category']);
	if(! in_array($category, $type_list)){
		echo "<h4 class='text-danger'>Error: no saved list found.</h4>";
		exit();
	}

	$sql = "SELECT * FROM ?n WHERE `Species` = '" . $_SESSION['SPECIES_DEFAULT'] . "' AND `Category` = ?s AND {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} ORDER BY `Name`";
	$lists_data = $BXAF_MODULE_CONN -> get_all($sql, $BXAF_CONFIG['TBL_BXGENOMICS_USERSAVEDLISTS'], ucfirst($category));

	echo "<table class='table table-bordered table-striped table-hover w-100 datatables'>";
	echo "<thead><tr class='table-info'><th>Name</th><th>Count</th><th>Action</th></tr></thead>";
	echo "<tbody>";

	foreach ($lists_data as $row) {

		$content = implode("\n",  category_list_to_idnames(unserialize($row['Items']), 'id', $category, $_SESSION['SPECIES_DEFAULT']) );

		echo "<tr><td>{$row['Name']}</td><td>{$row['Count']}</td><td><a href='Javascript: void(0);' class='btn_select_saved_sample_lists mr-3' content='{$content}'><i class='fas fa-check-circle'></i> Select</a> <a href='../tool_save_lists/list_page.php?id={$row['ID']}' target='_blank'><i class='fas fa-list'></i> Review</a></td></tr>";
	}
	echo "</tbody></table>";


	exit();
}


// Get project samples
if (isset($_GET['action']) && $_GET['action'] == 'get_project_samples') {

	$sql = "SELECT `Name` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES']}` WHERE `Species` = '" . $_SESSION['SPECIES_DEFAULT'] . "' AND `_Projects_ID` = ?i";
	$samples = $BXAF_MODULE_CONN -> get_col($sql, $_GET['project_id']);

	if(is_array($samples) && count($samples) > 0) echo implode("\n", $samples);

	exit();
}



if (isset($_GET['action']) && $_GET['action'] == 'get_sample_list'){

	$filter = " `Species` = '" . $_SESSION['SPECIES_DEFAULT'] . "' AND " . $BXAF_CONFIG['QUERY_DEFAULT_FILTER'] . "";
	if(isset($_GET['Name']) && $_GET['Name'] != '') $filter .= " AND `Name` LIKE '%" . addslashes($_GET['Name']). "%' ";
	if(isset($_GET['DiseaseState']) && $_GET['DiseaseState'] != '') $filter .= " AND `DiseaseState` LIKE '%" . addslashes($_GET['DiseaseState']). "%' ";
	if(isset($_GET['_Platforms_ID']) && $_GET['_Platforms_ID'] != ''){

		$sql = "SELECT `ID` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_PLATFORMS']}` WHERE `Species` = '" . $_SESSION['SPECIES_DEFAULT'] . "' AND `bxafStatus` < 5 AND (`Name` LIKE '%" . addslashes($_GET['_Platforms_ID']). "%' OR `GEO_Accession` LIKE '%" . addslashes($_GET['_Platforms_ID']). "%') ";
		$platform_ids = $BXAF_MODULE_CONN -> get_col($sql);

		if(is_array($platform_ids) && count($platform_ids) > 0 ) $filter .= " AND `_Platforms_ID` IN (" . implode(",", $platform_ids). ") ";
	}

	$table = $BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES'];
    $sql2 = "SELECT * FROM `$table` WHERE {$filter} ";

    $sql = "";
    // Search Condition
    if(isset($_POST['search']['value']) && trim($_POST['search']['value']) != '') {
    	$search_array = array();
    	for ($i = 0; $i < count($_POST['columns']); $i++){

            if($_POST['columns'][$i]['data'] == '_Platforms_ID'){
				if(strlen($_POST['search']['value']) > 2 ){

					$sql_platform = "SELECT `ID` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_PLATFORMS']}` WHERE `Species` = '" . $_SESSION['SPECIES_DEFAULT'] . "' AND `bxafStatus` < 5 AND (`Name` LIKE '%" . addslashes($_POST['search']['value']) . "%' OR `GEO_Accession` LIKE '%" . addslashes($_POST['search']['value']) . "%') LIMIT 10";

					$platform_ids = $BXAF_MODULE_CONN -> get_col($sql_platform);

					if(is_array($platform_ids) && count($platform_ids) > 0 ) $search_array[] = " `_Platforms_ID` IN (" . implode(",", $platform_ids) . ") ";
				}
            }
			else {
                $search_array[] = "`" . $_POST['columns'][$i]['data'] . "` LIKE '%" . $_POST['search']['value'] . "%'";
            }
    	}
    	$sql .= " AND (" . implode(" OR ", $search_array) . ")";
    }

    // Order Condition
    $sql .= " ORDER BY ";
    $condition_array = array();
    for ($i = 0; $i < count($_POST['order']); $i++) {
        $order = $_POST['columns'][$_POST['order'][$i]['column']]['data'];
        $asc = $_POST['order'][$i]['dir'];
        if(! in_array($order, array('Actions'))){
            $condition_array[] = "`$order` $asc";
        }
    }
    $sql .= implode(", ", $condition_array);



    $sql0 = "SELECT COUNT(*) FROM `$table` WHERE {$filter} ";
    $recordsTotal = $BXAF_MODULE_CONN->get_one($sql0);

    $recordsFiltered = $BXAF_MODULE_CONN->get_one($sql0 . $sql);

    $data = $BXAF_MODULE_CONN->get_all($sql2 . $sql . " LIMIT " . $_POST['start'] . "," . $_POST['length'] . "");

    $output_array = array(
		'sql' => $BXAF_MODULE_CONN->last_query(),
    	'draw' => intval($_POST['draw']),
    	'recordsTotal' => $recordsTotal,
    	'recordsFiltered' => $recordsFiltered,
    	'data' => array()
    );

    foreach($data as $value) {
        $row = array();
        foreach($value as $k=>$v){
			if($k == 'Name'){
				$row[$k] = "" . $v . "<a title='Select this gene sample' href='Javascript: void(0);' sample_id='" . $value['ID'] . "' content='" . $v . "' class='btn_select_current_sample mx-2'><i class='fas fa-check-circle'></i> Select</a>";
			}
			else if($k == '_Platforms_ID'){
				$sql = "SELECT `Name` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_PLATFORMS']}` WHERE `ID` = ?i";
				$platform_name = $BXAF_MODULE_CONN -> get_one($sql, $v);

				$row[$k] = $platform_name;
			}
            else {
				$row[$k] = $v;
			}
        }
        $output_array['data'][] = $row;

    }
    echo json_encode($output_array);

    exit();
}






$sample_names = array();
if (isset($_GET['sample_id']) && intval($_GET['sample_id']) >= 0) {
    $sql = "SELECT DISTINCT `Name` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES']}` WHERE `Species` = '" . $_SESSION['SPECIES_DEFAULT'] . "' AND " . $BXAF_CONFIG['QUERY_DEFAULT_FILTER'] . " AND `ID` = ?i";
    $sample_names = $BXAF_MODULE_CONN -> get_col($sql, intval($_GET['sample_id']) );
}
else if (isset($_GET['sample_ids']) && trim($_GET['sample_ids']) != '') {
    $sql = "SELECT DISTINCT `Name` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES']}` WHERE `Species` = '" . $_SESSION['SPECIES_DEFAULT'] . "' AND " . $BXAF_CONFIG['QUERY_DEFAULT_FILTER'] . " AND `ID` IN (?a)";
    $sample_names = $BXAF_MODULE_CONN -> get_col($sql, explode(',', $_GET['sample_ids']) );
}
else if (isset($_GET['project_id']) && intval($_GET['project_id']) >= 0) {
    $sql = "SELECT DISTINCT `Name` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES']}` WHERE `Species` = '" . $_SESSION['SPECIES_DEFAULT'] . "' AND " . $BXAF_CONFIG['QUERY_DEFAULT_FILTER'] . " AND `_Projects_ID` = ?i";
    $sample_names = $BXAF_MODULE_CONN -> get_col($sql, intval($_GET['project_id']) );
}
else if (isset($_GET['Sample_List']) && intval($_GET['Sample_List']) > 0) {
    $sql = "SELECT `Items` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_USERSAVEDLISTS']}` WHERE `Species` = '" . $_SESSION['SPECIES_DEFAULT'] . "' AND " . $BXAF_CONFIG['QUERY_DEFAULT_FILTER'] . " AND `ID` = ?i ";
    $list_items = $BXAF_MODULE_CONN -> get_one($sql, intval($_GET['Sample_List']) );
    $ids = unserialize($list_items);

    if(is_array($ids) && count($ids) > 0) {
        $sql = "SELECT DISTINCT `Name` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES']}` WHERE `Species` = '" . $_SESSION['SPECIES_DEFAULT'] . "' AND " . $BXAF_CONFIG['QUERY_DEFAULT_FILTER'] . " AND `ID` IN (?a)";
        $sample_names = $BXAF_MODULE_CONN -> get_col($sql, $ids);
    }
}
else if (isset($_GET['sample_time']) && $_GET['sample_time'] != '' && is_array($_SESSION['SAVED_LIST']) && array_key_exists($_GET['sample_time'], $_SESSION['SAVED_LIST']) ) {
    $sql = "SELECT DISTINCT `Name` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES']}` WHERE `Species` = '" . $_SESSION['SPECIES_DEFAULT'] . "' AND " . $BXAF_CONFIG['QUERY_DEFAULT_FILTER'] . " AND `ID` IN (?a)";
    $sample_names = $BXAF_MODULE_CONN -> get_col($sql, $_SESSION['SAVED_LIST'][ $_GET['sample_time'] ] );
}
else if (isset($_GET['comparison_ids']) && trim($_GET['comparison_ids']) != '') {
    $sql = "SELECT `Case_SampleIDs`, `Control_SampleIDs` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS']}` WHERE `Species` = '" . $_SESSION['SPECIES_DEFAULT'] . "' AND " . $BXAF_CONFIG['QUERY_DEFAULT_FILTER'] . " AND `ID` IN (?a)";
    $names = $BXAF_MODULE_CONN -> get_row($sql, explode(',', $_GET['comparison_ids']) );
	if(is_array($names) && count($names) > 0) {
		$sample_names = array_unique( array_merge(preg_split("/[\;\,\s]/", $names['Case_SampleIDs']), preg_split("/[\;\,\s]/", $names['Control_SampleIDs']) ) );
	}
}


if(isset($sample_names_custom) && is_array($sample_names_custom) && count($sample_names_custom) > 0){
    $sample_names = array_merge($sample_names, array_values($sample_names_custom));
}
sort($sample_names);

?>



<div class="w-100 mb-2">
	<span class="font-weight-bold">Samples:</span>

	<a class="ml-3 btn_saved_sample_lists" href="javascript:void(0);" category="Sample" data_target="Sample_List"> <i class="fas fa-angle-double-right"></i> Load from saved lists </a>

	<a class="ml-3" href="Javascript: void(0);" id="btn_select_sample"> <i class="fas fa-search"></i> Search and Select </a>

	<a class="ml-3" href="Javascript: void(0);" id="btn_select_project"> <i class="fas fa-search"></i> Select a Project </a>

	<a class="ml-3" href="Javascript: void(0);" onclick="$('#Sample_List').val('');"> <i class="fas fa-times"></i> Clear </a>

</div>

<textarea class="form-control" style="height:10rem;" name="Sample_List" id="Sample_List" category="Sample"><?php echo implode("\n", $sample_names); ?></textarea>



<div class="modal" id="modal_select_sample" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Search Samples</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body">
		<table class="table table-bordered table-striped table-hover w-100 datatables" id="table_samples">
			<thead>
				<tr class="table-info">
					<th>Name</th>
					<th>DiseaseState</th>
					<th>Platform</th>
				</tr>
			</thead>
		</table>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
		<input type="hidden" id="modal_select_sample_initiated" value="">
      </div>
    </div>
  </div>
</div>



<div class="modal" id="modal_select_project" tabindex="-1" role="dialog" aria-labelledby="">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Search Samples</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body">
		<table class="table table-bordered table-striped table-hover w-100 datatables" id="table_projects">
			<thead>
				<tr class="table-info">
					<th>Project Name</th>
					<th>Platform</th>
					<th>Type</th>
					<th>Samples</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
<?php
	$sql = "SELECT `ID`, `Name`, `Platform`, `Platform_Type` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS']}` WHERE `Species` = '" . $_SESSION['SPECIES_DEFAULT'] . "' AND " . $BXAF_CONFIG['QUERY_DEFAULT_FILTER'] . " ORDER BY `Name`";
	$projects = $BXAF_MODULE_CONN -> get_assoc('ID', $sql );

	$sql = "SELECT `_Projects_ID`, COUNT(*) FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES']}` WHERE `Species` = '" . $_SESSION['SPECIES_DEFAULT'] . "' AND " . $BXAF_CONFIG['QUERY_DEFAULT_FILTER'] . " GROUP BY `_Projects_ID`";
	$project_samples = $BXAF_MODULE_CONN -> get_assoc('_Projects_ID', $sql );

	foreach($projects as $id => $p){
		$count = array_key_exists($id, $project_samples) ? $project_samples[$id] : 0;
		if($count <= 0) continue;
		echo "<tr>";
			echo "<td>" . $p['Name'] . "</td>";
			echo "<td>" . $p['Platform'] . "</td>";
			echo "<td>" . $p['Platform_Type'] . "</td>";
			echo "<td>" . $count . "</td>";
			echo "<td><a href='Javascript: void(0);' class='btn_select_current_project mr-3' project_id='{$id}'><i class='fas fa-check'></i> Select</a> <a href='../project.php?id={$id}' target='_blank'><i class='fas fa-list'></i> Review</a></td>";
		echo "</tr>";
	}
?>
			</tbody>
		</table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>


<div class="modal fade modal_saved_sample_lists" id="modal_saved_sample_lists" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="">My Saved List</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body"></div>
      <div class="modal-footer">
        <input type="hidden" id="target_saved_sample_lists" name="target_saved_sample_lists" value="" />
        <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>


<script>

    $(document).ready(function() {

		if( ! bxaf_find_and_load_library('css', "datatables.min.css.php", '') ){
			bxaf_find_and_load_library('css', "datatables.min.css.php", "/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/datatables/datatables.min.css.php");
		}
		if( ! bxaf_find_and_load_library('js',  "datatables.min.js", '') ){
			bxaf_find_and_load_library('js',  "datatables.min.js.php",  "/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/datatables/datatables.min.js.php");
		}


      	$(document).on('click', '#btn_select_sample', function() {

			if( $('#modal_select_sample_initiated').val() == '' ){

				$('#modal_select_sample_initiated').val(1);

				$('#table_samples').DataTable({
			        "processing": true,
			        "serverSide": true,
			        "ajax": {
			            "url": "/<?php echo $BXAF_CONFIG['BXAF_APP_SUBDIR']; ?>bxgenomics/tool_save_lists/modal_sample.php?action=get_sample_list<?php if($_SERVER['QUERY_STRING'] != '') echo '&' . $_SERVER['QUERY_STRING']; ?>",
			            "type": "POST"
			        },
			        "columns": [
						{ "data": "Name" },
						{ "data": "DiseaseState" },
						{ "data": "_Platforms_ID" }
			        ]
			    });

				$('#table_samples').on( 'draw.dt', function () {
					$('#modal_select_sample').modal('show');
				});
			}
			else {
				$('#modal_select_sample').modal('show');
			}
      	});

      	$(document).on('click', '.btn_select_current_sample', function() {
      		var name = $(this).attr('content');
              $('#Sample_List').val( name + "\n" + $('#Sample_List').val() );
      		$('#modal_select_sample').modal('hide');
      	});



		$('#table_projects').DataTable();
		$(document).on('click', '#btn_select_project', function() {
			$('#modal_select_project').modal('show');
		});
      	$(document).on('click', '.btn_select_current_project', function() {
			$.ajax({
    			type: 'GET',
    			url: '/<?php echo $BXAF_CONFIG['BXAF_APP_SUBDIR']; ?>bxgenomics/tool_save_lists/modal_sample.php?action=get_project_samples&project_id=' + $(this).attr('project_id'),
    			success: function(responseText){
					$('#Sample_List').val( responseText + "\n" + $('#Sample_List').val() );
					$('#modal_select_project').modal('hide');
    			}
    		});
      	});



        // Select from Saved List
        $(document).on('click', '.btn_saved_sample_lists', function() {
            var category = $(this).attr('category');
            $('#target_saved_sample_lists').val( $(this).attr('data_target') );

            $.ajax({
    			type: 'GET',
    			url: '/<?php echo $BXAF_CONFIG['BXAF_APP_SUBDIR']; ?>bxgenomics/tool_save_lists/modal_sample.php?action=get_saved_lists&category=' + category,
    			success: function(responseText){
    				$('#modal_saved_sample_lists').find('.modal-body').html(responseText);
                    $('#modal_saved_sample_lists').modal('show');

                    $('.datatables').DataTable();

    			}
    		});

    	});

        $(document).on('click', '.btn_select_saved_sample_lists', function() {

            var target  = $('#target_saved_sample_lists').val();

            var current_content  = $('#' + target).val();
            var new_content = $(this).attr('content');

            $('#' + target).val( new_content + "\n" + current_content );

    		$('#modal_saved_sample_lists').modal('hide');
    	});

    });

</script>
