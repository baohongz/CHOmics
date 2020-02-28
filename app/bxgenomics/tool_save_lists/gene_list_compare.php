<?php
include_once(__DIR__ . "/config.php");


// $sql = "SELECT `ID`, `Category`, `Code`, `Name`, `Gene_Counts` FROM `tbl_go_gene_list` ";
// $gene_lists_all = $BXAF_MODULE_CONN->get_assoc('ID', $sql);


if (isset($_GET['action']) && $_GET['action'] == 'list'){

	$table = 'tbl_go_gene_list';
	$filter = " `Species` = '" . $_SESSION['SPECIES_DEFAULT'] . "' ";
	// if(isset($_GET['Category']) && $_GET['Category'] != '') $filter .= " AND `Category` = '" . addslashes($_GET['Category']). "' ";
	// if(isset($_GET['Code']) && $_GET['Code'] != '') $filter .= " AND `Code` LIKE '%" . addslashes($_GET['Code']). "%' ";
	// if(isset($_GET['Name']) && $_GET['Name'] != '') $filter .= " AND `Name` LIKE '%" . addslashes($_GET['Name']). "%' ";
	// if(isset($_GET['Gene_Counts']) && $_GET['Gene_Counts'] != '') $filter .= " AND `Gene_Counts` < '" . intval($_GET['Gene_Counts']). "' ";
	// if(isset($_GET['Gene_Names']) && $_GET['Gene_Names'] != '') $filter .= " AND `Gene_Names` LIKE '%" . addslashes($_GET['Gene_Names']). "%' ";

    $sql2 = "SELECT `ID`, `Category`, `Name`, `Gene_Counts`, 'Actions' FROM `$table` WHERE $filter ";

    $sql = "";
    // Search Condition
    if(isset($_POST['search']['value']) && trim($_POST['search']['value']) != '') {
    	$search_array = array();
    	for ($i = 0; $i < count($_POST['columns']); $i++){
            if(! in_array($_POST['columns'][$i]['data'], array('Actions'))){
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



    $sql0 = "SELECT COUNT(*) FROM `$table` WHERE $filter ";
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
			if($k == 'Actions'){
				$row[$k] = '<a href="javascript:void(0);" class="btn_select_search_pathway" content="' . $value['ID'] . '" displayed_name="' . $value['Category'] . ': ' . $value['Name'] . '"><i class="fas fa-angle-double-right"></i> Select</a>';
			}
			else if($k == 'Code' || $k == 'Name'){
				$row[$k] = "" . str_replace('_', ' ', $v) . "";
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





if(isset($_GET['action']) && $_GET['action']=="get_content_detail"){
	$result = array();


	/*----------------------------------------------------------------------------------------*/
	// Type 01: Individual
	/*----------------------------------------------------------------------------------------*/
	if ($_GET['case'] == 'individual'){
		$i = $_GET['method'];
		$result['name'] = $_SESSION['Venn_name_result'][$i];
		$result['value'] = $_SESSION['Venn_value_result'][$i];
	}


	/*----------------------------------------------------------------------------------------*/
	// Type 02: Double
	/*----------------------------------------------------------------------------------------*/
	if ($_GET['case'] == 'double'){
		$i = $_GET['method'][0];
		$j = $_GET['method'][2];
		$result['name'] = $_SESSION['Venn_name_result'][$i].' & '.$_SESSION['Venn_name_result'][$j];
		$result['value'] = array_unique( array_intersect($_SESSION['Venn_value_result'][$i], $_SESSION['Venn_value_result'][$j]));
	}



	/*----------------------------------------------------------------------------------------*/
	// Type 03: Triple
	/*----------------------------------------------------------------------------------------*/

	if ($_GET['case'] == 'triple'){
		$i = $_GET['method'][0];
		$j = $_GET['method'][2];
		$k = $_GET['method'][4];
		$result['name'] = $_SESSION['Venn_name_result'][$i].' & '.$_SESSION['Venn_name_result'][$j].' & '.$_SESSION['Venn_name_result'][$k];
		$result['value'] = array_unique( array_intersect($_SESSION['Venn_value_result'][$i], $_SESSION['Venn_value_result'][$j], $_SESSION['Venn_value_result'][$k]));
	}



	/*----------------------------------------------------------------------------------------*/
	// Type 04: Union
	/*----------------------------------------------------------------------------------------*/
	if ($_GET['case'] == 'union'){
		if ($_GET['type']==0){
			$result['name'] = 'Contents for union of all groups';
			$result_value_temp = array();
			for($index = 0; $index < count($_SESSION['Venn_value_result']); $index++){
				$result_value_temp = array_merge($result_value_temp, $_SESSION['Venn_value_result'][$index]);
			}
			$result['value'] = array_unique($result_value_temp);
		}

		if ($_GET['type']==2){
			$i = $_GET['other'][0];
			$j = $_GET['other'][2];
			$result['name'] = 'Contents for union of groups';
			$result['value'] = array_unique( array_merge($_SESSION['Venn_value_result'][$i], $_SESSION['Venn_value_result'][$j]));
		}

		if ($_GET['type']==3){
			$i = $_GET['other'][0];
			$j = $_GET['other'][2];
			$k = $_GET['other'][4];
			$result['name'] = 'Contents for union of groups';
			$result['value'] = array_unique( array_merge($_SESSION['Venn_value_result'][$i], $_SESSION['Venn_value_result'][$j], $_SESSION['Venn_value_result'][$k]));
		}
	}



	/*----------------------------------------------------------------------------------------*/
	// Type 05: Total
	/*----------------------------------------------------------------------------------------*/
	if ($_GET['case'] == 'total'){
		$result['name'] = 'Intersection for union of all groups';
		$result_value_temp = array_intersect($_SESSION['Venn_value_result'][0], $_SESSION['Venn_value_result'][1]);
		for($index = 2; $index < count($_SESSION['Venn_value_result']); $index++){
			$result_value_temp = array_intersect($result_value_temp, $_SESSION['Venn_value_result'][$index]);
		}
		$result['value'] = $result_value_temp;
	}



	/*----------------------------------------------------------------------------------------*/
	// Type 06: Unique
	/*----------------------------------------------------------------------------------------*/
	if ($_GET['case'] == 'unique'){

		if($_GET['type'] == 0){
			$i = $_GET['method'][0];
			$all_other = array();
			for ($index = 0; $index < count($_SESSION['Venn_value_result']); $index++){
				if($index != $i){$all_other = array_merge($all_other, $_SESSION['Venn_value_result'][$index]); }
			}
			$result['name'] = 'Contents in '.$_SESSION['Venn_name_result'][$i].' only';
			$result['value'] = array_unique(array_diff($_SESSION['Venn_value_result'][$i], $all_other));
		}

		if($_GET['type'] == 2){
			$i = $_GET['method'][0];
			$m = $_GET['other'][0];
			$n = $_GET['other'][2];
			$result['name'] = 'Contents in '.$_SESSION['Venn_name_result'][$i].' only';
			if($i == $m){
				$result['value'] = array_unique(array_diff($_SESSION['Venn_value_result'][$i], $_SESSION['Venn_value_result'][$n]));
			} else if($i == $n){
				$result['value'] = array_unique(array_diff($_SESSION['Venn_value_result'][$i], $_SESSION['Venn_value_result'][$m]));
			}
		}

		if($_GET['type'] == 3){
			$i = $_GET['method'][0];
			$m = $_GET['other'][0];
			$n = $_GET['other'][2];
			$p = $_GET['other'][4];
			$result['name'] = 'Contents in '.$_SESSION['Venn_name_result'][$i].' only';
			if($i == $m){
				$result['value'] = array_unique(array_diff($_SESSION['Venn_value_result'][$i], $_SESSION['Venn_value_result'][$n], $_SESSION['Venn_value_result'][$p]));
			} else if($i == $n){
				$result['value'] = array_unique(array_diff($_SESSION['Venn_value_result'][$i], $_SESSION['Venn_value_result'][$m], $_SESSION['Venn_value_result'][$p]));
			} else if($i == $p){
				$result['value'] = array_unique(array_diff($_SESSION['Venn_value_result'][$i], $_SESSION['Venn_value_result'][$m], $_SESSION['Venn_value_result'][$n]));
			}
		}
	}


	unset($_SESSION['Venn_detail_result']);
	$_SESSION['Venn_detail_result'] = $result['value'];

	echo '
		<div>
			<span class="lead">Display Method:</span>
			<span>
				<input type="radio" class="content_detail_radio mx-2" name="content_detail" id="content_detail0" value="0" checked>
				One ID per line
				<input type="radio" class="content_detail_radio mx-2" name="content_detail" id="content_detail1" value="1">
				Separated by comma
			</span>
		</div>

		<hr>
		<div class="row m-0">
			<textarea id="content_detail0_div" class="p-1" style="height:300px; width:100%;">';

			foreach ($result['value'] as $value){echo $value. "\n";}
	echo	'
			</textarea>
			<textarea class="hidden" id="content_detail1_div" style="height:300px; width:100%;">'. implode(', ', $result['value']) . '</textarea>
		</div>
		';

	exit();
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
	<?php include_once($BXAF_CONFIG['BXAF_PAGE_HEADER']); ?>
<link type="text/css" rel="stylesheet" href="css/style.css" />


	<link href="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/jquery-ui/jquery-ui.min.css" rel="stylesheet">
	<script src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/jquery-ui/jquery-ui.min.js"></script>

	<link   href='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/datatables/datatables.min.css.php' rel='stylesheet' type='text/css'>
	<script src='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/datatables/datatables.min.js.php'></script>

	<script src='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/jquery/jquery.form.min.js'></script>

	<script src="js/d3.js"></script>
	<script src="js/venn.js"></script>

</head>
<body>

<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_MENU'])) include_once($BXAF_CONFIG['BXAF_PAGE_MENU']); ?>
<div id="bxaf_page_wrapper" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_WRAPPER']; ?>">
<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_LEFT'])) include_once($BXAF_CONFIG['BXAF_PAGE_LEFT']); ?>
<div id="bxaf_page_right" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT']; ?>">
<div id="bxaf_page_right_content" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT_CONTENT']; ?>">
<div class="container-fluid">

    <?php $help_key = 'Compare Gene Lists'; include_once( dirname(__DIR__) . "/help_content.php"); ?>

	<div class="w-100 my-3">

        <form class="w-100" id="form_main" method="post">

			<div class="row my-3">

				<div class="col-md-4">
					<input type="text" title="Enter a custom gene list label" placeholder="custom gene list label" class="form-control w-50" id="comparison_name1" name="comparison_name1" value="A">

					<input type="hidden" id="comparison1" name="comparison1" value="<?php echo ($_GET['comparison1'] != '') ? $_GET['comparison1'] : ""; ?>">
					<div class="w-100 my-2 p-2" id="comparison1_text">(Not selected yet)</div>

					<a href="Javascript: void(0);" class="btn_select_pathway_show_modal" target_field="comparison1">
                      <i class="fas fa-angle-double-right"></i> Select First Gene List
                    </a>
				</div>

				<div class="col-md-4">
					<input type="text" title="Enter a custom gene list label" placeholder="custom gene list label" class="form-control w-50" id="comparison_name2" name="comparison_name2" value="B">

					<input type="hidden" id="comparison2" name="comparison2" value="<?php echo ($_GET['comparison2'] != '') ? $_GET['comparison2'] : ""; ?>">
					<div class="w-100 my-2 p-2" id="comparison2_text">(Not selected yet)</div>

					<a href="Javascript: void(0);" class="btn_select_pathway_show_modal" target_field="comparison2">
                      <i class="fas fa-angle-double-right"></i> Select Second Gene List
                    </a>
				</div>

				<div class="col-md-4">
					<input type="text" title="Enter a custom gene list label" placeholder="custom gene list label" class="form-control w-50" id="comparison_name3" name="comparison_name3" value="C">

					<input type="hidden" id="comparison3" name="comparison3" value="<?php echo ($_GET['comparison3'] != '') ? $_GET['comparison3'] : ""; ?>">
					<div class="w-100 my-2 p-2" id="comparison3_text">(Not selected yet)</div>

					<a href="Javascript: void(0);" class="btn_select_pathway_show_modal" target_field="comparison3">
                      <i class="fas fa-angle-double-right"></i> Select Third Gene List
                    </a>
				</div>

			</div>

            <div class="row my-5 pl-3">
                <input class="btn btn-primary" type="submit" value="Submit">
                <input class="btn btn-default mx-2" type="reset" value="Reset">

				<label id="form_upload_file_busy" class="px-2 hidden text-danger"><i class="fas fa-spinner fa-spin"></i> Submitting ... </label>

			</div>

        </form>

        <div class="w-100 my-3">
            <div id="div_results" class="w-100 my-3"></div>
            <div id="div_debug" class="w-100 my-3"></div>
        </div>


	</div>

</div>
</div>
<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>
</div>
</div>



<!-------------------------------------------------------------------------------------------------------->
<!-- Modal to Select Comparison -->
<!-------------------------------------------------------------------------------------------------------->
<div class="modal" id="modal_select_pathway" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title">Select a Gene List</h4>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			</div>
			<div class="modal-body">
				<?php
					echo '<div class="w-100">
					<table class="table table-bordered table-striped table-hover w-100" id="table_select_pathway">
						<thead>
							<tr class="table-info">
	                            <th>ID</th>
								<th>Category</th>
								<th>Name</th>
								<th>Genes</th>
								<th>Action</th>
							</tr>
						</thead>';
					echo '</table></div>';
				?>
			</div>
			<div class="modal-footer">
				<input type="hidden" id="target_field" name="target_field" value="">
				<button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
			</div>
		</div>
	</div>
</div>


<script>

    $(document).ready(function() {

		$('#table_select_pathway').DataTable({
	        "processing": true,
	        "serverSide": true,
	        "ajax": {
	            "url": "<?php echo $_SERVER['PHP_SELF']; ?>?action=list",
	            "type": "POST"
	        },
			"pageLength": 100, "lengthMenu": [[10, 100, 500, 1000], [10, 100, 500, 1000]],
	        "columns": [
				{ "data": "ID" },
	            { "data": "Category" },
				{ "data": "Name" },
				{ "data": "Gene_Counts" },
				{ "data": "Actions" }
	        ]
	    });



        $(document).on('click', '.btn_select_pathway_show_modal', function() {
        	$('#modal_select_pathway').modal('show');
			$('#target_field').val( $(this).attr('target_field') );
        });

        $(document).on('click', '.btn_select_search_pathway', function() {

            var content = $(this).attr('content');
            var displayed_name = $(this).attr('displayed_name');

            $('#' + $('#target_field').val() ).val( content );
			$('#' + $('#target_field').val() + '_text' ).html( displayed_name );
			$('#' + $('#target_field').val() + '_text' ).addClass( 'table-info text-danger' );

            $('#modal_select_pathway').modal('hide');

        });


        // File Upload
        var options = {
            url: 'gene_list_exe.php?action=show_venn_diagram',
            type: 'post',
            beforeSubmit: function(formData, jqForm, options) {
				if( $('#comparison1').val() == '' || $('#comparison2').val() == ''){
					bootbox.alert("Please select at least two gene lists. ");
					return false;
				}

                $('#form_upload_file_busy').removeClass('hidden');

                return true;
            },
            success: function(response){
                $('#form_upload_file_busy').addClass('hidden');
                $('#div_debug').html(response);

                return true;
            }
        };
        $('#form_main').ajaxForm(options);


		$(document).on('click', '.content_detail',function(){
			var type = $(this).attr('type');
			var method = $(this).attr('method');
			var other = $(this).attr('other');
			var case0 = $(this).attr('case');
			var title = $(this).attr('title');
			$.ajax({
				method: 'POST',
				url: '<?php echo $_SERVER['PHP_SELF']; ?>?action=get_content_detail&type=' + type + '&method=' + method + '&other=' + other + '&case=' + case0,
				success: function(responseText, statusText){
					bootbox.alert({
						title: title,
						message: responseText,
						callback: function(){}
					});
				}
			})
		})

		$(document).on('change', '.content_detail_radio', function(){
			if($('#content_detail0').is(":checked")){
				$('#content_detail1_div').addClass('hidden');
				$('#content_detail0_div').hide().removeClass('hidden').fadeIn(0);
			} else {
				$('#content_detail0_div').addClass('hidden');
				$('#content_detail1_div').hide().removeClass('hidden').fadeIn(0);
			}
		})

    });

</script>


</body>
</html>