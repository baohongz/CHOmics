<?php
include_once('config.php');


function get_stat_scale_color($value, $type='logFC') {
  if ($type == 'logFC') {
    if ($value >= 1) {
      return '#FF0000';
    } else if ($value > 0) {
      return '#FF8989';
    } else if ($value == 0) {
      return '#E5E5E5';
    } else if ($value > -1) {
      return '#7070FB';
    } else {
      return '#0000FF';
    }
  }
  if ($type == 'FDR') {
    if ($value > 0.05) {
      return '#9CA4B3';
    } else if ($value <= 0.01) {
      return '#015402';
    } else {
      return '#5AC72C';
    }
  }
  if ($type == 'PVal') {
    if ($value >= 0.01) {
      return '#9CA4B3';
    } else {
      return '#5AC72C';
    }
  }
  return;
}



$comparison_id = '';
$comparison_name = '';

if(is_array($_GET) && array_key_exists('id', $_GET) && $_GET['id'] != '') {
	$comparison_id = intval($_GET['id']);
	$sql = "SELECT ?n FROM ?n WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND ?n = ?i";
	$comparison_name = $BXAF_MODULE_CONN -> get_one($sql, 'Name', $BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS'], 'ID', $comparison_id);
	$sql = "SELECT ?n FROM ?n WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND ?n = ?s";
	$comparison_id = $BXAF_MODULE_CONN -> get_one($sql, 'ID', $BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS'], 'Name', $comparison_name);
}
else if(is_array($_GET) && array_key_exists('name', $_GET) && $_GET['name'] != '') {
	$comparison_name = trim($_GET['name']);
	$sql = "SELECT ?n FROM ?n WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND ?n = ?s";
	$comparison_id = $BXAF_MODULE_CONN -> get_one($sql, 'ID', $BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS'], 'Name', $comparison_name);
	$sql = "SELECT ?n FROM ?n WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND ?n = ?i";
	$comparison_name = $BXAF_MODULE_CONN -> get_one($sql, 'Name', $BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS'], 'ID', $comparison_id);
}


$term1 = array_key_exists('term1', $_GET) ? $_GET['term1'] : 'Log2FoldChange';
$value1 = (array_key_exists('value1', $_GET) && floatval($_GET['value1']) != 0) ? floatval($_GET['value1']) : '';
$operator1 = array_key_exists('operator1', $_GET) ? $_GET['operator1'] : '>';

$term2 = array_key_exists('term2', $_GET) ? $_GET['term2'] : '';
$value2 = (array_key_exists('value2', $_GET) && floatval($_GET['value2']) != 0) ? floatval($_GET['value2']) : '';
$operator2 = array_key_exists('operator2', $_GET) ? $_GET['operator2'] : '';

$operator = array_key_exists('operator', $_GET) ? $_GET['operator'] : '';





if(is_array($_GET) && array_key_exists('action', $_GET) && $_GET['action'] == 'get_comparison_details') {

	ini_set('memory_limit','8G');
	$data = tabix_search_bxgenomics(  array(), array($comparison_id), 'ComparisonData');

	$genes = array();
	foreach ($data as $i => $row) {
		if($value1 != '' &&  $operator != '' && $term2 != '' && $operator2 != '' && $value2 != ''){
			$test = eval('return ' . $row[$term1] . " $operator1 " . $value1 . " $operator " . $row[$term2] . " $operator2 " . $value2 . ';');
			if(! $test){ unset($data[$i]); continue; }
		}
		else if($value1 != ''){
			$test = eval('return ' . $row[$term1] . " $operator1 " . $value1 . ';');
			if(! $test){ unset($data[$i]); continue; }
		}
		$genes[$i] = $row['GeneIndex'];

	}

	$sql = "SELECT `ID`, `GeneName`, `Description`  FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_GENES']}` WHERE `Species` = '{$_SESSION['SPECIES_DEFAULT']}' AND `ID` IN (?a) ";
	$all_genes_info = $BXAF_MODULE_CONN -> get_assoc('ID', $sql, $genes);

	$data_limit = 5000;
	$n = 0;
	$gene_ids = array();
	foreach ($data as $i => $row) {

		if(! array_key_exists($genes[$i], $all_genes_info) || $all_genes_info[ $genes[$i] ]['GeneName'] == '') continue;

		$gene_ids[] = $genes[$i];

		echo "<tr>";
			echo "<td><a href='view.php?type=gene&name=" . $all_genes_info[ $genes[$i] ]['GeneName'] . "'>" . $all_genes_info[ $genes[$i] ]['GeneName'] . "</a></td>";
			echo "<td>" . $all_genes_info[ $genes[$i] ]['Description'] . "</td>";
			echo "<td style='color:" . get_stat_scale_color($row['Log2FoldChange'], 'logFC') . "'>" . sprintf("%.4f", $row['Log2FoldChange']) . "</td>";
			echo "<td style='color:" . get_stat_scale_color($row['PValue'], 'PVal') . "'>" . sprintf("%.4f", $row['PValue']) . "</td>";
			echo "<td style='color:" . get_stat_scale_color($row['AdjustedPValue'], 'FDR') . "'>" . sprintf("%.4f", $row['AdjustedPValue']) . "</td>";
		echo "</tr>";

		$n++;
		if($n >= $data_limit) break;
	}

    $_SESSION['SAVED_LIST']['comparison_genes'] = $gene_ids;


	exit();
}




?>
<!DOCTYPE html>
<html lang="en">
<head>
	<?php include_once($BXAF_CONFIG['BXAF_PAGE_HEADER']); ?>

    <script src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/jquery/jquery.form.min.js"></script>

	<link   href='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/datatables/datatables.min.css.php' rel='stylesheet' type='text/css'>
	<script src='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/datatables/datatables.min.js.php'></script>

</head>
<body>

<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_MENU'])) include_once($BXAF_CONFIG['BXAF_PAGE_MENU']); ?>
	<div id="bxaf_page_wrapper" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_WRAPPER']; ?>">
		<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_LEFT'])) include_once($BXAF_CONFIG['BXAF_PAGE_LEFT']); ?>
		<div id="bxaf_page_right" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT']; ?>">
			<div id="bxaf_page_right_content" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT_CONTENT']; ?>">

    		<div class="container-fluid pt-3">
	    		<h1 class="">
					Comparison Genes
					<button class="btn_search_comparison btn btn-success ml-5" type="button">
						<i class="fas fa-search"></i> Select a comparison
					</button>
	    		</h1>

				<?php if($comparison_name != '') { ?>
				<hr />

				<div class="my-3">
					<label class="text-success" style="font-size: 2rem;"><?php echo $comparison_name; ?></label>
					<label class="ml-3">
						<a href="view.php?type=comparison&id=<?php echo $comparison_id; ?>">
							<i class="fas fa-angle-double-right"></i>
							View Comparison Details
						</a>

					</label>
					<label class="ml-3">
						<a href="../tool_save_lists/new_list.php?Category=Gene&time=comparison_genes">
							<i class="fas fa-angle-double-right"></i>
							Save Filtered Genes
						</a>
					</label>
				</div>

				<div class="my-3 border rounded p-2 table-warning">
					<form class="form-inline" method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>">
						<label class="mr-3 font-weight-bold">Filter: </label>

						<select class="form-control form-control-sm custom-select" name="term1" style="">
						  <option value="Log2FoldChange" <?php echo $term1 == 'Log2FoldChange' ? 'selected' : ''; ?>>Log2FoldChange</option>
						  <option value="PValue" <?php echo $term1 == 'PValue' ? 'selected' : ''; ?>>PValue</option>
						  <option value="AdjustedPValue" <?php echo $term1 == 'AdjustedPValue' ? 'selected' : ''; ?>>AdjustedPValue</option>
						</select>

						<select class="form-control form-control-sm custom-select mx-2" name="operator1" style="">
						  <option value="&gt;=" <?php echo $operator1 == '>=' ? 'selected' : ''; ?>>&gt;=</option>
						  <option value="&lt;" <?php echo $operator1 == '<' ? 'selected' : ''; ?>>&lt;</option>
						</select>

						<input type="text" class="form-control form-control-sm mr-2" name="value1" value="<?php echo $value1; ?>" style="width:5em;">

						<select class="form-control form-control-sm custom-select mx-2" name="operator" style="">
							<option value="" <?php echo $operator == '' ? 'selected' : ''; ?>></option>
							<option value="||" <?php echo $operator == '||' ? 'selected' : ''; ?>>OR</option>
							<option value='&&' <?php echo $operator == '&&' ? 'selected' : ''; ?>>AND</option>
						</select>

						<select class="form-control form-control-sm custom-select" name="term2" style="">
							<option value="" <?php echo $term2 == '' ? 'selected' : ''; ?>></option>
						  <option value="Log2FoldChange" <?php echo $term2 == 'Log2FoldChange' ? 'selected' : ''; ?>>Log2FoldChange</option>
						  <option value="PValue" <?php echo $term2 == 'PValue' ? 'selected' : ''; ?>>PValue</option>
						  <option value="AdjustedPValue" <?php echo $term2 == 'AdjustedPValue' ? 'selected' : ''; ?>>AdjustedPValue</option>
						</select>

						<select class="form-control form-control-sm custom-select mx-2" name="operator2" style="">
							<option value="" <?php echo $operator2 == '' ? 'selected' : ''; ?>></option>
						  <option value="&gt;=" <?php echo $operator2 == '>=' ? 'selected' : ''; ?>>&gt;=</option>
						  <option value="&lt;" <?php echo $operator2 == '<' ? 'selected' : ''; ?>>&lt;</option>
						</select>

						<input type="text" class="form-control form-control-sm mr-2" name="value2" value="<?php echo $value2; ?>" style="width:5em;">

						<input type="hidden" name="name" value="<?php echo $comparison_name; ?>" />
						<button type="submit" class="btn btn-primary btn-sm">Update</button>
					</form>
				</div>

				<div class="my-3">
					<table class="table table-bordered table-striped table-hover w-100 datatables" id="table_comparison_details">
						<thead>
							<tr class="table-info">
								<th>Gene Name</th>
								<th>Gene Description</th>
								<th>Log2FC</th>
								<th>P.Val</th>
								<th>Adj.P.Val</th>
							</tr>
						</thead>
						<tbody id="table_comparison_details_tbody"><tr><td colspan='5'><i class="fas fa-spin fa-spinner"></i></td></tr></tbody>
					</table>
				</div>
				<?php } // if($comparison_name != '') { ?>

				<div class="my-3" id="div_debug"></div>
    		</div>

		</div>
		<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>
	</div>
</div>




<div class="modal" id="modal_select_comparison" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Search Comparison</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body">
        <?php
          echo '
          <table class="table table-bordered table-striped table-hover w-100 datatables" id="table_search_comparison">
            <thead>
            <tr class="table-info">
              <th>Name</th>
              <th>Disease State</th>
              <th>Cell Type</th>
            </tr>
            </thead>
            <tbody>';

            $sql = "SELECT `ID`, `Name`, `Case_CellType`, `Case_DiseaseState` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS']}` WHERE `Species` = '{$_SESSION['SPECIES_DEFAULT']}' ORDER BY `Name`";
            $comparisons = $BXAF_MODULE_CONN -> get_all($sql);

            foreach ($comparisons as $comparison) {
              echo '
              <tr>
                <td class="text-nowrap">' . $comparison['Name'] . '
                  <a href="javascript:void(0);" class="btn_select_search_comparison ml-2" content="' . $comparison['Name'] . '"><i class="fas fa-angle-double-right"></i> Select</a>
                </td>
                <td>' . $comparison['Case_DiseaseState'] . '</td>
                <td>' . $comparison['Case_CellType'] . '</td>
              </tr>';
            }
          echo '
            </tbody>
          </table>
          ';
        ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>



<script>

$(document).ready(function() {

	$('#table_search_comparison').DataTable();

	$(document).on('click', '.btn_search_comparison', function() {
  		$('#modal_select_comparison').modal('show');
  	});

  	$(document).on('click', '.btn_select_search_comparison', function() {
  		$('#modal_select_comparison').modal('hide');
		window.location = "<?php echo $_SERVER['PHP_SELF']; ?>?name=" + $(this).attr('content');
  	});

	<?php if ($comparison_name != '') { ?>

	  	$.ajax({
	  		type: 'GET',
	  		url: '<?php echo $_SERVER['PHP_SELF']; ?>?action=get_comparison_details&name=<?php echo urlencode($comparison_name); ?>&term1=<?php echo $term1; ?>&operator1=<?php echo urlencode($operator1); ?>&value1=<?php echo $value1; ?>&operator=<?php echo urlencode($operator); ?>&term2=<?php echo $term2; ?>&operator2=<?php echo urlencode($operator2); ?>&value2=<?php echo $value2; ?>',
	  		success: function(responseText) {
				// $('#div_debug').html(responseText);
		        $('#table_comparison_details_tbody').html(responseText);
				$('#table_comparison_details').DataTable({ "pageLength": 100, "lengthMenu": [[10, 100, 500, 1000], [10, 100, 500, 1000]], dom: 'Blfrtip', buttons: ['colvis','copy','csv'] });
	  		}
	  	});

	<?php } ?>

});


</script>


</body>
</html>