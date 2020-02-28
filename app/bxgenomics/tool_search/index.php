<?php
include_once('config.php');


$current_species = $_SESSION['SPECIES_DEFAULT'];
if (isset($_GET['species']) && in_array(ucfirst(strtolower($_GET['species'])), array('Human', 'Mouse')) && $current_species != ucfirst(strtolower($_GET['species'])) ) {

	$current_species = ucfirst(strtolower($_GET['species']));

	// Check exists
	$sql = "SELECT `ID` FROM ?n WHERE `bxafStatus` < 5 AND `Category`= 'Default Species' AND `_Owner_ID` = ?i";
	$record_id = $BXAF_MODULE_CONN -> get_one($sql, $BXAF_CONFIG['TBL_BXGENOMICS_USERPREFERENCE'], $BXAF_CONFIG['BXAF_USER_CONTACT_ID'] );

	// Update record
	if ($record_id != '') {
		$info = array(
			'_Owner_ID'   => $BXAF_CONFIG['BXAF_USER_CONTACT_ID'],
			'Category'    => 'Default Species',
			'Detail'      => $current_species
		);
		$BXAF_MODULE_CONN -> update($BXAF_CONFIG['TBL_BXGENOMICS_USERPREFERENCE'], $info, "`ID`=" . intval($record_id) );
	}
	// Create new record
	else {
		$info = array(
			'_Owner_ID'   => $BXAF_CONFIG['BXAF_USER_CONTACT_ID'],
			'Category'    => 'Default Species',
			'Detail'      => $current_species,
			'bxafStatus'      => 0
		);
		$BXAF_MODULE_CONN -> insert($BXAF_CONFIG['TBL_BXGENOMICS_USERPREFERENCE'], $info);
	}
	$_SESSION['SPECIES_DEFAULT'] = $current_species;
}


$PAGE_TYPE = '';
if (isset($_GET['type']) && strtolower($_GET['type']) == 'sample') {
	$PAGE_TYPE = 'Sample';
}
else if (isset($_GET['type']) && strtolower($_GET['type']) == 'gene') {
	$PAGE_TYPE = 'Gene';
}
else if (isset($_GET['type']) && strtolower($_GET['type']) == 'project') {
	$PAGE_TYPE = 'Project';
}
else {
	$PAGE_TYPE = 'Comparison';
}

$TABLE = $TABLE_ALL[$PAGE_TYPE];
$PREFERENCE_TYPE = $PREFERENCE_TYPE_ALL[$PAGE_TYPE];



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





$SQL_ADDITIONAL_CONDITION = false;

// Samples for a comparison
if ($PAGE_TYPE == 'Sample' && isset($_GET['comparison_id']) && intval($_GET['comparison_id']) != 0) {

	$sql = "SELECT `Case_SampleIDs`, `Control_SampleIDs` FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS'] . "` WHERE `ID`=" . intval($_GET['comparison_id']);
	$data = $BXAF_MODULE_CONN -> get_row($sql);

	$ALL_SAMPLES = array_unique( array_merge( preg_split("/[\;\,\s]/", $data['Case_SampleIDs']), preg_split("/[\;\,\s]/", $data['Control_SampleIDs']) ) );

	if(count($ALL_SAMPLES) > 0) $SQL_ADDITIONAL_CONDITION = " `Name` IN ('" . implode("','", $ALL_SAMPLES) . "') ";
}


// Samples for a project
else if ($PAGE_TYPE == 'Sample' && isset($_GET['project_id']) && trim($_GET['project_id']) != '') {

	$sql = "SELECT `ID` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES']}` WHERE `_Projects_ID`=" . intval($_GET['project_id']);
	$ALL_SAMPLE_ID = $BXAF_MODULE_CONN -> get_col($sql);

	if(count($ALL_SAMPLE_ID) > 0) $SQL_ADDITIONAL_CONDITION = " `ID` IN (" . implode(",", $ALL_SAMPLE_ID) . ") ";
}

// echo "<pre>" . print_r($columns_selected, true) . "</pre>";


?>
<!DOCTYPE html>
<html lang="en">
<head>
	<?php include_once($BXAF_CONFIG['BXAF_PAGE_HEADER']); ?>

	<script src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/jquery/jquery.form.min.js"></script>

	<link href="../library/tootik.min.css" rel="stylesheet">

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


					<div class="d-flex align-items-center">
			      		<h1 class="mr-3">
			      			Search
						</h1>

						<select id="select_page_type" class="custom-select form-control-lg font-weight-bold mx-1" style="width: 12rem;">
							<?php
								foreach (array('Comparison', 'Gene', 'Project', 'Sample') as $term) {
									echo '<option value="' . strtolower($term) . '"';
									if ($PAGE_TYPE == $term) echo ' selected';
									echo '>' . $term . '</option>';
								}
							?>
						</select>
					</div>

		      		<p class="text-muted">Note: You can do quick search using the search box on the top-right of the table, or apply advanced search below.</p>

					<hr />

					<div class="w-100">
						<button class="btn btn-primary mx-1" id="btn_toggle_search_div">
							<i class="fas fa-search"></i> Advanced Search
						</button>

						<button type="button" class="btn btn-success mx-1" data-toggle="modal" data-target="#modal_preference_change_col_<?php echo $PAGE_TYPE; ?>">
							<i class="fas fa-cogs"></i> Change Column Settings
						</button>

						<button class="btn btn-warning mx-1 btn_save_session hidden" type="<?php echo $PAGE_TYPE; ?>" to_type='<?php echo $PAGE_TYPE; ?>' title="Create a list from selected records">
			               <i class="fas fa-save"></i> Create <?php echo $PAGE_TYPE; ?> List
			            </button>

<?php
	if ($PAGE_TYPE == 'Project'){
		echo "<button class='btn btn-warning mx-1 btn_save_session hidden' to_type='Comparison' type='Project' title='Create a list from selected records'> <i class='fas fa-save'></i> Create a Comparison List </button>";

		echo "<button class='btn btn-warning mx-1 btn_save_session hidden' to_type='Sample' type='Project' title='Create a list from selected records'> <i class='fas fa-save'></i> Create a Sample List </button>";
	}
	else if ($PAGE_TYPE == 'Comparison'){
		echo "<button class='btn btn-warning mx-1 btn_save_session hidden' to_type='Sample' type='Comparison' title='Create a list from selected records'> <i class='fas fa-save'></i> Create a Sample List </button>";
	}
?>

						<a class="mx-2" href="javascript:void(0);" onclick="location.reload(true);"><i class="fas fa-angle-double-right"></i> Reset search conditions</a>
					</div>

      		<div id="search_condition_div" class="row mx-0 w-100 alert alert-warning" style="display:none;">

      			<form class="mb-0 w-100" id="form_submit_search" method="post">

					<div class="">
						<table class="">
							<thead class="">
								<tr>
				      				<th style="min-width: 100px;">&nbsp;</th>
				      				<th class="text-center">Field to Search</th>
				      				<th class="text-center">Operator</th>
				      				<th class="text-center">Value</th>
								</tr>
			      			</thead>

							<tbody id="search_condition_tbody">
			      				<tr class="">
			      					<td>
			      						<input class="hidden" name="search_logic[]">
			      					</td>
			      					<td>
			      						<select class="custom-select" name="search_field[]">
			      						<?php
			      							foreach($name_captions as $colname => $caption) {
			      								echo '<option value="' . $colname . '">' . $caption . '</option>';
			      							}
			      						?>
			      						</select>
			      					</td>
			      					<td>
			      						<select class="custom-select"  name="search_operator[]">
			      							<option value="is">is</option>
			      							<option value="contains">contains</option>
			      							<option value="starts_with">starts with</option>
			      							<option value="ends_width">ends with</option>
			      						</select>
			      					</td>
			      					<td>
			      						<input class="form-control" name="search_value[]" required>
			      					</td>
			      				</tr>
							</tbody>
		      			</table>
					</div>

					<div class="my-3">
						<button style="margin-left: 100px;" class="mr-5 btn btn-outline-primary" id="btn_submit"> <i class="fas fa-check-circle"></i> Search </button>
						<a href="javascript:void(0);" id="btn_add_search_condition"> <i class="fas fa-angle-double-right"></i> Add Search Condition </a>
					</div>

					<div id="div_number_records" class="row mx-0 m-t-1"></div>

      			</form>
      		</div>


			<div class="w-100 mt-3">
				<label>
	      			<input type="checkbox" class="check_all">
	      			Check/Uncheck All
	      		</label>
			</div>

      		<div class="w-100 mt-3" id="div_main_table">

	      		<?php
	        		echo '
						<table class="table table-bordered table-striped w-100" id="table_main">
							<thead>
								<tr class="table-info">';

	        		echo '<th>ID</th>';

					foreach ($columns_selected as $colname) {
						if($colname == 'ID') continue;
	        			echo '<th>' . $name_captions[$colname] . '</th>';
	        		}

	        		echo '</tr></thead><tbody></tbody></table>';
	      		?>
      		</div>
        </div>








      </div>
      <?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>
    </div>
 </div>










<?php

echo '
   <form id="form_preference_change_col_' . $PAGE_TYPE . '">

	   <div class="modal fade" id="modal_preference_change_col_' . $PAGE_TYPE . '">
	     <div class="modal-dialog modal-lg" role="document">
	       <div class="modal-content">
	         <div class="modal-header">
	           <h5 class="modal-title">Displayed Columns for ' . $PAGE_TYPE . 's</h5>
	           <button type="button" class="close" data-dismiss="modal" aria-label="Close">
	             <span aria-hidden="true">&times;</span>
	           </button>
	         </div>
	         <div class="modal-body">
	           <input name="FORM_TYPE" value="' . $PAGE_TYPE . '" hidden>';

	             $key = 0;
	             foreach ($name_captions as $colname=>$caption) {

	                 if ($key % 3 == 0) echo '<div class="row mx-0 w-100">';

	                   echo '
	                     <div class="row col-sm-4">
	                         <label>
	                             <input type="checkbox" class="checkbox_change_col_' . $PAGE_TYPE . '" category="' . $PAGE_TYPE . '" name="' . $colname . '"' . (in_array($colname, $columns_selected) ? " checked " : "") . ' /> ' . $caption . '
	                         </label>
	                     </div>';

	                 if ($key % 3 == 2 || $key == count($columns_all) - 1) echo '</div>';
	                 $key++;
	             }



	   echo '
	         </div>
	         <div class="modal-footer">
	           <button type="submit" id="btn_submit_change_col_' . $PAGE_TYPE . '" class="btn btn-primary">
	             <i class="fas fa-save"></i> Save Changes
	           </button>
	           <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
	         </div>
	       </div>
	     </div>
	   </div>

   </form>';


echo "
<script>
$(document).ready(function() {

	var options_" . $PAGE_TYPE . " = {
		url: 'exe.php?action=table_save_column_preference',
		type: 'post',
		beforeSubmit: function(formData, jqForm, options) {
			$('#btn_submit_change_col_" . $PAGE_TYPE . "').attr('disabled', '').children(':first').removeClass('fa-upload').addClass('fa-spin fa-spinner');
			return true;
		},
		success: function(response){
			// bootbox.alert(response);
			window.location='index.php?type=" . $PAGE_TYPE . "';
			return true;
		}
	};
	$('#form_preference_change_col_" . $PAGE_TYPE . "').ajaxForm(options_" . $PAGE_TYPE . ");

});
</script>";


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
		dom: 'Blfrtip',
		buttons: ['colvis','copy','csv'],
		"processing": true,
		"serverSide": true,
		"language": {
			"infoFiltered": ""
		},
		"ajax": {
			"url": "exe.php?action=data_table_dynamic_loading&type=<?php echo $PAGE_TYPE; ?>&species=<?php echo $current_species; ?>",
			"type": "POST",
			<?php
				if ($SQL_ADDITIONAL_CONDITION) {
					echo '"data": {"sql": " ' . addslashes($SQL_ADDITIONAL_CONDITION) . '"}';
				}
			?>
		},
		"pageLength": 100, "lengthMenu": [[10, 100, 500, 1000], [10, 100, 500, 1000]],
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



  var add_search_condition_content = '';
	add_search_condition_content += 	'<tr class="">';
	add_search_condition_content += 		'<td>';
	add_search_condition_content += 			'<select class="custom-select" name="search_logic[]">';
	add_search_condition_content += 				'<option value="and">AND</option>';
	add_search_condition_content += 				'<option value="or">OR</option>';
	add_search_condition_content += 			'</select>';
	add_search_condition_content += 		'</td>';
	add_search_condition_content += 		'<td>';
	add_search_condition_content += 			'<select class="custom-select" name="search_field[]">';
	<?php
		foreach ($name_captions as $colname => $caption) {
			echo 'add_search_condition_content += \'<option value="' . $colname . '">' . $caption . '</option>\';';
		}
	?>
	add_search_condition_content += 			'</select>';
	add_search_condition_content += 		'</td>';
	add_search_condition_content += 		'<td>';
	add_search_condition_content += 			'<select class="custom-select" name="search_operator[]">';
	add_search_condition_content += 				'<option value="is">is</option>';
	add_search_condition_content += 				'<option value="contains">contains</option>';
	add_search_condition_content += 				'<option value="starts_with">starts with</option>';
	add_search_condition_content += 				'<option value="ends_width">ends with</option>';
	add_search_condition_content += 			'</select>';
	add_search_condition_content += 		'</td>';
	add_search_condition_content += 		'<td>';
	add_search_condition_content += 			'<input class="form-control" name="search_value[]">';
	add_search_condition_content += 		'</td>';
	add_search_condition_content += 	'</tr>';



  /* Toggle Search Condition */
  $(document).on('click', '#btn_toggle_search_div', function() {
    $('#search_condition_div').slideToggle(300);
  });

  /* Add Search Condition */
	$(document).on('click', '#btn_add_search_condition', function() {
		$('#search_condition_tbody').append(add_search_condition_content);
	});



  //--------------------------------------------------------------------------------------
  // Search
  //--------------------------------------------------------------------------------------
  var options = {
		url: 'exe.php?action=submit_search&type=<?php echo $PAGE_TYPE; ?>&species=<?php echo $current_species; ?>',
 		type: 'post',
        beforeSubmit: function(formData, jqForm, options) {
			$('#btn_submit').children(':first').removeClass('fa-check-circle').addClass('fa-spin fa-spinner');
			$('#btn_submit').attr('disabled', '');
			return true;
		},
        success: function(responseText, statusText){
			$('#btn_submit').children(':first').removeClass('fa-spin fa-spinner').addClass('fa-check-circle');
			$('#btn_submit').removeAttr('disabled');

			if(responseText.substring(0, 5) == 'Error'){
				bootbox.alert(responseText);
			} else {
				$('#div_main_table').html(responseText);
				$('#div_number_records').html( $('#number_records').val() + ' records found. ' );
			}

			return true;
		}
    };
	$('#form_submit_search').ajaxForm(options);



	/* Check All */
	$(document).on('click', '.check_all', function() {
		if ($(this).is(':checked')) {
			$('.checkbox_save_session').prop('checked', true);

			$('.btn_save_session').removeClass('hidden');
		}
		else {
			$('.checkbox_save_session').prop('checked', false);

			$('.btn_save_session').each(function(i, e) {
				if (! $(e).hasClass('hidden')) {
					$(e).addClass('hidden');
				}
			});
		}
	});

	// Show or hide action buttons when checking or unchecking checkboxes
	$(document).on('click', '.checkbox_save_session', function() {
		$('.check_all').prop('checked', false);

		var data = [];
		$('.checkbox_save_session').each(function(i, e) {
			if ($(e).is(':checked')) {
				data.push($(e).attr('rowid'));
			}
		});
		if (data.length == 0) {
			$('.btn_save_session').each(function(i, e) {
				if (! $(this).hasClass('hidden')) {
					$(this).addClass('hidden');
				}
			});
		} else {
			$('.btn_save_session').removeClass('hidden');
		}
	});


	$(document).on('click', '.btn_save_session', function() {
		var type = $(this).attr('type');
		var to_type = $(this).attr('to_type');

		var rowid = '';
		$('.checkbox_save_session').each(function(index, element) {
			if ( element.checked ) {
				if(rowid == '') rowid = $(element).attr('rowid');
				else rowid += ',' + $(element).attr('rowid');
			}
		});
		if (rowid == '') {
			bootbox.alert('No record selected.');
		} else {
			$.ajax({
				type: 'POST',
				url: '../tool_save_lists/exe.php?action=save_session_list&category=' + to_type,
				data: { 'list': rowid },
				success: function(response) {
					window.location = response + '&type=' + type;
				}
			});
		}
	});

	$(document).on('change', '#select_page_type', function() {
		var type = $(this).val();
		window.location = 'index.php?type=' + type + '';
	});



});


</script>



</body>
</html>