<?php

include_once(__DIR__ . "/config.php");


if (!isset($_GET['id']) || trim($_GET['id']) == '') {
	header("Location: index.php");
	exit();
}

$comparison_id = intval($_GET['id']);
$DIRECTION = $_GET['direction'];



$sql = "SELECT `Species` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS']}` WHERE `ID` = ?i";
$species = $BXAF_MODULE_CONN -> get_one($sql, $comparison_id);
if($species == '') $species = $_SESSION['SPECIES_DEFAULT'];

$page_out_dir = $BXAF_CONFIG['PAGE_OUTPUT'][ strtoupper( $species ) ];
$report_file = $page_out_dir . 'comparison_' . $comparison_id . '_GSEA.PAGE.csv';


$file = fopen($report_file,"r") or die('Unable to open PAGE result file.');
$FILE_CONTENT = array();
while(! feof($file)) {
	$row = fgetcsv($file);

	if (is_array($row) && count($row) > 1 && trim($row[0]) != 'Name') {
  	     $FILE_CONTENT[] = $row;
	}
}
fclose($file);

if (isset($_GET['number']) && $_GET['number'] == 'All') {
  $NUMBER = 'All';
}
else if (intval($_GET['number']) > 0) {
  $NUMBER = intval($_GET['number']);
}
else {
  $NUMBER = 1000;
}

if($NUMBER > count($FILE_CONTENT)) $NUMBER = count($FILE_CONTENT);


// echo "<pre>" . print_r($FILE_CONTENT, true) . "</pre>";

function sort_by_large($a, $b) {
    return ($a['2'] - $b['2'] > 0) ? -1 : 1;
}
function sort_by_small($a, $b) {
    return ($a['2'] - $b['2'] < 0) ? -1 : 1;
}

if ($DIRECTION == 'up') {
	usort($FILE_CONTENT, 'sort_by_large');
	$RESULT_DATA = array();

	if ($NUMBER == 'All') {
		foreach ($FILE_CONTENT as $row) {
			$RESULT_DATA[] = $row;
		}
	}
	else {
	  for ($i = 0; $i < $NUMBER; $i++) {
	    $RESULT_DATA[] = $FILE_CONTENT[$i];
	  }
	}
}
else {
	// Get Bottom 10 Z-Score Records
	usort($FILE_CONTENT, 'sort_by_small');
	$RESULT_DATA = array();

	if ($NUMBER == 'All') {
		foreach ($FILE_CONTENT as $row) {
			$RESULT_DATA[] = $row;
		}
	}
	else {
	  for ($i = 0; $i < $NUMBER; $i++) {
	    $RESULT_DATA[] = $FILE_CONTENT[$i];
	  }
	}
}



?><!DOCTYPE html>
<html lang="en">
<head>
	<?php include_once($BXAF_CONFIG['BXAF_PAGE_HEADER']); ?>

	<link   href='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/datatables/datatables.min.css.php' rel='stylesheet' type='text/css'>
	<script src='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/datatables/datatables.min.js.php'></script>

	<script type="text/javascript">
		$(document).ready(function(){
			$('.datatables').DataTable({ "pageLength": 10, "lengthMenu": [[10, 100, 500, 1000], [10, 100, 500, 1000]], dom: 'Blfrtip', buttons: ['colvis','copy','csv'] });
		});

	</script>

</head>
<body>
	<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_MENU'])) include_once($BXAF_CONFIG['BXAF_PAGE_MENU']); ?>
	<div id="bxaf_page_wrapper" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_WRAPPER']; ?>">
		<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_LEFT'])) include_once($BXAF_CONFIG['BXAF_PAGE_LEFT']); ?>
		<div id="bxaf_page_right" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT']; ?>">
			<div id="bxaf_page_right_content" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT_CONTENT']; ?>">
				<div class="container-fluid">



					<div class="d-flex flex-row mt-3">
						<h1 class="align-self-baseline"><?php echo strtoupper($DIRECTION); ?>-Regulated PAGE Report</h1>
						<p class="align-self-baseline ml-5 lead"><a href="view.php?type=comparison&id=<?php echo $_GET['id']; ?>" class=""><i class='fas fa-undo'></i> Back to Comparison Details</a></p>
					</div>

					<hr class='w-100' />

                    <div class="w-100 my-3">

            			<table class="table table-bordered table-striped datatables">
            				<thead>
            					<tr class="table-info">
            						<th class="text-nowrap">Gene Set Name</th>
            						<th class="text-nowrap"># Genes</th>
            						<th class="text-nowrap">Z Score</th>
            						<th class="text-nowrap">P Value</th>
            						<th class="text-nowrap">FDR</th>
            					</tr>
            				</thead>
            				<tbody>
            					<?php
            						foreach ($RESULT_DATA as $key => $value) {

										if (floatval($value[2]) > 1) {
											$z_color = '#FF0000';
										} else if (floatval($value[2]) > 0) {
											$z_color = '#FF9C9C';
										} else if (floatval($value[2]) == 0) {
											$z_color = '#979797';
										} else if (floatval($value[2]) > -1) {
											$z_color = '#81C86E';
										} else {
											$z_color = '#02CA2D';
										}


										if (floatval($value[3]) < 0.01) {
											$p_color = '#02CA2D';
										} else {
											$p_color = '#979797';
										}

										if (floatval($value[4]) < 0.05) {
											$fdr_color = '#02CA2D';
										} else {
											$fdr_color = '#979797';
										}

            							echo '
            								<tr>
            									<td>' . $value[0] . '</td>
            									<td>' . $value[1] . '</td>
            									<td style="color:' . $z_color . '">' . $value[2] . '</td>
            									<td style="color:' . $p_color . '">' . $value[3] . '</td>
            									<td style="color:' . $fdr_color . '">' . $value[4] . '</td>
            								</tr>';
            						}
            					?>
            				</tbody>
            			</table>
                    </div>



				</div>
            </div>
		    <?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>
		</div>
	</div>
</body>
</html>