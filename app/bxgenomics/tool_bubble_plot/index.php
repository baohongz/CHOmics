<?php
include_once("config.php");

$gene_id = '';
$GENE_NAME_DEFAULT = '';
if (isset($_GET['gene_name']) && trim($_GET['gene_name']) != ''){
    $sql = "SELECT `ID` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_GENES']}` WHERE `GeneName` = ?s";
    $gene_id = $BXAF_MODULE_CONN -> get_one($sql, trim($_GET['gene_name']) );
    if($gene_id != '') $GENE_NAME_DEFAULT = trim($_GET['gene_name']);
}
else if (isset($_GET['id']) && trim($_GET['id']) != '' || isset($_GET['gene_id']) && trim($_GET['gene_id']) != '') {

    if (isset($_GET['id']) && trim($_GET['id']) != '') $gene_id = intval($_GET['id']);
    else $gene_id = intval($_GET['gene_id']);

    $sql = "SELECT `GeneName` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_GENES']}` WHERE `ID`= ?i";
    $GENE_NAME_DEFAULT = $BXAF_MODULE_CONN -> get_one($sql, $gene_id);
    if($GENE_NAME_DEFAULT == '') $gene_id = '';
}

?><!DOCTYPE html>
<html lang="en">
<head>
	<?php include_once($BXAF_CONFIG['BXAF_PAGE_HEADER']); ?>
	<script src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/jquery/jquery.form.min.js"></script>
	<script src="../library/plotly.min.js"></script>
</head>

<body>

<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_MENU'])) include_once($BXAF_CONFIG['BXAF_PAGE_MENU']); ?>
<div id="bxaf_page_wrapper" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_WRAPPER']; ?>">
<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_LEFT'])) include_once($BXAF_CONFIG['BXAF_PAGE_LEFT']); ?>
<div id="bxaf_page_right" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT']; ?>">
<div id="bxaf_page_right_content" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT_CONTENT']; ?>">
<div class="container-fluid">

    <?php $help_key = 'Bubble Plot'; include_once( dirname(__DIR__) . "/help_content.php"); ?>

    <div class="w-100 my-3">
        <a href="multiple.php">
            <i class="fas fa-angle-double-right"></i> Multiple genes .vs. multiple comparisons
        </a>
    </div>


	<div class="row mx-0 pl-3" id="first_form_div">

		<form class="w-100" id="form_bubble_plot" method="post">

			<div class="row">
				<div class="col-md-2 text-md-right text-muted">
					Gene Name:
				</div>
				<div class="col-md-10">
					<input name="gene_name" id="input_gene_name" class="form-control" style="width:20em;" value="<?php echo $GENE_NAME_DEFAULT; ?>" required>
					<span class="text-muted">Please enter the gene name, e.g., BRWD1-IT2</span>
				</div>
			</div>

			<div class="row mt-1">
				<div class="col-md-2 text-md-right text-muted">
					Y-axis Field:
				</div>
				<div class="col-md-10">
					<select class="custom-select" name="select_y_field" id="select_y_field" style="width:20em;">
					<?php
						foreach ($BXAF_CONFIG['BUBBLE_PLOT']['FIELDS'] as $field) {
							echo '<option value="' . $field . '"';
							echo ($field == 'Case_DiseaseState') ? ' selected' : '';
							echo '>' . $field . '</option>';
						}
					?>
					</select>
				</div>
			</div>

			<div class="row mt-1">
				<div class="col-md-2 text-md-right text-muted">
					Coloring Field:
				</div>
				<div class="col-md-10">
					<select class="custom-select" name="select_coloring_field" id="select_color_field" style="width:20em;">
					<?php
						foreach ($BXAF_CONFIG['BUBBLE_PLOT']['FIELDS'] as $field) {
							echo '<option value="' . $field . '"';
							echo ($field == 'Case_SampleSource') ? ' selected' : '';
							echo '>' . $field . '</option>';
						}
					?>
					</select>
				</div>
			</div>


			<div class="row mt-1">
				<div class="col-md-2 text-md-right text-muted">
					Comparison Type:
				</div>
				<div class="col-md-10">
					<select class="custom-select" name="select_comparison_type" id="select_comparison_type" style="width:20em;">
						<option value="all">All Comparisons</option>
						<option value="private">Only Private Comparisons</option>
						<option value="public">Only Public Comparisons</option>
					</select>
				</div>
			</div>


			<div class="row mt-3">
				<div class="col-md-2">&nbsp;</div>
				<div class="col-md-10">
					<button id="btn_submit" class="btn btn-outline-success"><i class="fas fa-chevron-right"></i> Next Step</button>
				</div>
			</div>

		</form>

	</div>




	<div class="row mx-0 pl-3" id="second_form_div" style="display:none;">
		<div class="col-md-12">
			<!-- <form id="form_bubble_plot_filter" method="post" enctype="multipart/form-data"
          action="display_chart.php"
          target="_blank"> -->
          <form id="form_bubble_plot_filter" method="post" enctype="multipart/form-data" target="_blank"></form>
		</div>
	</div>



    <div class="row mx-0 mt-4">
        <button class="btn btn-primary" style="display:none;" id="btn_modify_settings" onclick="$('#first_form_div, #second_form_div').slideToggle(300);">
            Modify Settings
        </button>
    </div>

    <div class="row mx-0 mt-4" id="chart_div"></div>
    <div class="row mx-0 mt-4" id="table_div"></div>

    <div id="debug"></div>


</div>
</div>
<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>
</div>
</div>




<script>
// Generate Chart
var options = {
	url: 'exe.php?action=bubble_pre_generate_chart',
		type: 'post',
	beforeSubmit: function(formData, jqForm, options) {
    // Loader
		$('body').prepend('<div class="loader loader-default is-active" data-text="Drawing..." style="margin-left:0px; margin-top:0px;"></div>');

		if ($('#file').val() == '') {
			message_alert('error', 'Please select a csv file.', '');
			return false;
		}
		$('#btn_submit').children(':first').removeClass('fa-chevron-circle-right').addClass('fa-spin fa-spinner');
		$('#btn_submit').attr('disabled', '');
		return true;
	},
	success: function(response){
		$('#btn_submit').children(':first').removeClass('fa-spin fa-spinner').addClass('fa-chevron-circle-right');
		$('#btn_submit').removeAttr('disabled');
    // $('#debug').html(response);


		if(response.substring(0, 5) == 'Error'){
			bootbox.alert(response);
      $('.loader').remove();
		} else {
			$('#form_bubble_plot_filter').html(response);
			//$('#first_form_div').slideUp(600);
			$('#second_form_div').slideDown(100);
			setTimeout(function() {
				$('#btn_submit_generate').trigger('click');
			}, 500);
		}
		return true;
	}
  };
$('#form_bubble_plot').ajaxForm(options);


// Can not make change to first section once submitted.
$(document).on('change', '#input_gene_name, #select_y_field, #select_color_field, #select_comparison_type', function() {
	$('#second_form_div').fadeOut(100);
});
</script>


</body>

</html>