<?php
include_once(__DIR__ . "/config/config.php");

if(! $_SESSION['BXAF_ADVANCED_USER']){
	header("Location: index.php");
	exit();
}


$experiment_id = intval($_GET['id']);
$sql = "SELECT `Name` FROM `".$BXAF_CONFIG['TBL_BXGENOMICS_EXPERIMENT']."` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `ID` = ?i";
$experiment_name = $BXAF_MODULE_CONN -> get_one($sql, $experiment_id);

if($experiment_name == ''){
	header("Location: experiments.php");
	exit();
}

$sql = "SELECT * FROM `".$BXAF_CONFIG['TBL_BXGENOMICS_SAMPLE']."` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `Experiment_ID` = ?i";
$experiment_samples = $BXAF_MODULE_CONN -> get_assoc('ID', $sql, $experiment_id);

$experiment_sample_idnames = array();
foreach($experiment_samples as $sample_id=>$sample){
	$experiment_sample_idnames[ $sample_id ] = $sample['Name'];
}
asort($experiment_sample_idnames);

$sql = "SELECT * FROM `".$BXAF_CONFIG['TBL_BXGENOMICS_DATA']."` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `Sample_ID` IN (?a)";
$data_info = $BXAF_MODULE_CONN -> get_assoc('ID', $sql, array_keys($experiment_samples));
$experiment_datafiles = array();
foreach($data_info as $id=>$info){
	$experiment_datafiles[ $info['Sample_ID'] ][ $id ] = $info;
}

$sample_types = array(
	'PE' => 'fastq, Paired-end (PE)',
	'SE' => 'fastq, Single-end (SE)',
	'bam' => 'bam, sorted and indexed (.sorted.bam)',
	'gene_counts' => 'Gene counts (.txt)',
);






?><!DOCTYPE html>
<html lang="en">
<head>
	<?php include_once($BXAF_CONFIG['BXAF_PAGE_HEADER']); ?>
	<script src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/jquery/jquery.form.min.js"></script>
</head>
<body>
	<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_MENU'])) include_once($BXAF_CONFIG['BXAF_PAGE_MENU']); ?>
	<div id="bxaf_page_wrapper" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_WRAPPER']; ?>">
		<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_LEFT'])) include_once($BXAF_CONFIG['BXAF_PAGE_LEFT']); ?>
		<div id="bxaf_page_right" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT']; ?>">
			<div id="bxaf_page_right_content" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT_CONTENT']; ?>">
				<div class="container-fluid">





<h3 class="my-2"><i class="fas fa-chart-line"></i> Start new analysis in experiment <a class="" href="experiment.php?id=<?php echo $experiment_id; ?>"><?php echo $experiment_name; ?></a></h3>
<hr class="w-100 mb-5" />

<?php if(count($experiment_samples) <= 0) echo '<div class="text-danger my-3">No samples found.</div>';  ?>

<?php if(count($experiment_samples) > 0){  ?>

	<form id="form_new_analysis" role="form">
		<input type="hidden" name="Species" id="Species" value="<?php echo $_SESSION['SPECIES_DEFAULT']; ?>">

		<div class="form-inline my-3">
			<label class="font-weight-bold">* Analysis Name:</label>
			<input name="Name" id="Name" placeholder="Please enter meaningful analysis name here" class="form-control mx-2" style="width: 30rem;" required>
		</div>

		<div class="form-inline my-3">
			<label class="font-weight-bold">* Data Type: </label>
			<select name="Data_Type" id="Data_Type" class="custom-select mx-2">
				<?php
					foreach($sample_types as $k=>$v){
						echo '<option value="'. $k .'" ' . ($k == 'PE' ? "selected" : "") . '>'. $v .'</option>';
					}
				?>
			</select>
		</div>

		<div class="w-100 my-2 p-2" style="display: block; height: 350px; overflow-y: auto;">
			<table class="datatables table table-sm table-bordered table-hover mt-3">
				<thead>
					<tr class="table-info">
						<th>Sample Name</th>
						<th>Treatment</th>
						<th>Data Type</th>
						<th>Data Files</th>
					</tr>
				</thead>
				<tbody>
				<?php

					foreach($experiment_sample_idnames as $sample_id=>$sample_name){

						$sample = $experiment_samples[$sample_id];

						$data_files = array();
						foreach($experiment_datafiles[$sample_id] as $data_id=>$data_info){

							$classes = array();
							$classes[] = 'data_checkbox_one';
							$classes[] = 'class_' . $sample['Data_Type'];
							$classes[] = 'class_read_number_' . $data_info['Read_Number'];

							$data_files[ $data_info['Name'] ] = '<input type="checkbox" sampleid="' . $sample_id . '" class="data_checkbox_all ' . implode(" ", $classes) . '" name="data_ids[]" value="'. $data_id .'" ' . ($sample['Data_Type'] == 'PE' ? "checked" : "") . '> <label>' . $data_info['Name'] . '</label>';
						}
						ksort($data_files);

						echo '<tr>';
							echo '<td><a href="sample.php?id=' . $sample_id . '">' . $sample['Name'] . '</a></td>';
							echo '<td>' . $sample['Treatment_Name'] . '</td>';
							echo '<td>' . $sample['Data_Type'] . '</td>';
							echo '<td>' . implode("<BR>", $data_files ) . '</td>';

						echo '</tr>';
					}
				?>
				</tbody>
			</table>
		</div>


		<div class="form-group my-3">
			<label class="font-weight-bold">Description:</label>
			<div class="">
				<textarea name="Description" id="Description" class="form-control p-2" rows="5" cols="60"></textarea>
			</div>
		</div>

		<div class="w-100 my-3">
			<input name="experiment_id" value="<?php echo $experiment_id; ?>" hidden>
			<button type="submit" class="btn btn-primary mr-3 btn_submit"><i class="fas fa-chart-line"></i> Next Step</button>
			<i class="fas fa-spinner fa-pulse icon_busy" hidden></i>
		</div>

	</form>

<?php } // if(count($experiment_samples) > 0){  ?>






<!-- Datatype Warning Modal -->
<div class="modal fade" id="myModal_datatype_warning" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
		    <div class="modal-header">
				<h4 class="modal-title">Warning</h4>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
				    <span aria-hidden="true">&times;</span>
				    <span class="sr-only">Close</span>
				</button>
		    </div>

		  	<div class="modal-body">
				<div class="row mt-3" id="datatype_warning_text">
				</div>
		  	</div>

		  	<div class="modal-footer">
				<button type="button" class="btn btn-danger" id="confirm_start_analysis">Move On</button>
				<button type="button" class="btn btn-sucendary" data-dismiss="modal">Cancel</button>
		  	</div>

		</div>
	</div>
</div>







<script type="text/javascript">

$(document).ready(function(){

	$(document).on('change', '#Data_Type', function(){
		$('.data_checkbox_all').prop('checked', false);
		$('.data_checkbox_all').parent().parent().addClass('hidden');

		var value = 'class_' + $(this).val();
		$('.' + value ).parent().parent().removeClass('hidden');
		$('.' + value ).prop('checked', true);

	});

	var options = {
		url: 'bxgenomics_exe.php?action=new_analysis',
		type: 'post',
		beforeSubmit: function(formData, jqForm, options) {
			if($('#Name').val() == ''){
				bootbox.alert('<h2><i class="fas fa-exclamation-triangle text-warning"></i> Warning</h2><div class="lead p-3">Please enter an analysis name.</div>');
				return false;
			}
			return true;
		},
		success: function(responseText, statusText){
			if(bxaf_is_number(responseText)){
				window.location = 'analysis.php?id=' + responseText;
			}
			else {
				bootbox.alert('<h2><i class="fas fa-exclamation-triangle text-warning"></i> Warning</h2><div class="lead p-3">' + responseText + '</div>');
			}
		}
	};
	$('#form_new_analysis').ajaxForm(options);

	$('#Name').focus();

});


</script>




					<div id="div_debug"></div>
				</div>
            </div>
		    <?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>
		</div>
	</div>
</body>
</html>