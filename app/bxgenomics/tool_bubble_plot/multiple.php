<?php
include_once("config.php");


if (isset($_GET['meta']) && $_GET['meta'] == 'true') {
	$gene_names_custom = $_SESSION['META_BUBBLE_PLOT_GENENAMES'];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<?php include_once($BXAF_CONFIG['BXAF_PAGE_HEADER']); ?>
	<script src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/jquery/jquery.form.min.js"></script>
	<script src="../library/plotly.min.js"></script>

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

	<?php $help_key = 'Bubble Plot Multiple'; include_once( dirname(__DIR__) . "/help_content.php"); ?>

	<div class="w-100 my-3">
        <a href="index.php">
            <i class="fas fa-angle-double-right"></i> Single Gene Plot
        </a>
    </div>



	<div class="row mx-0" id="first_form_div">

		<form class="w-100" id="form_bubble_plot" method="post">

			<div class="row my-4">
	            <div class="col-md-6">
	                <?php include_once(dirname(__DIR__) . '/tool_save_lists/modal_gene.php'); ?>
					<div class="text-muted my-3">Note: You must enter <span class="text-success">one or more gene names</span>.</div>
	            </div>
	            <div class="col-md-6">
	                <?php include_once(dirname(__DIR__) . '/tool_save_lists/modal_comparison.php'); ?>
					<div class="text-muted my-3">Note: You must enter <span class="text-success">one or more comparison names</span>.</div>
	            </div>
	        </div>


			<div class="row w-100 mx-0 mt-3">
				<a href="javascript:void(0);" onclick="$(this).next().slideToggle(300);">
				  <i class="fas fa-cogs"></i> Toggle Advanced Settings
				</a>
				<div class="alert alert-info w-100 mt-2 hidden">
				  <strong>Chart Height Scale Factor: </strong><br />
				  <input type="number" min="0.5" max="3" step="0.1" value="1"
				    class="form-control" style="width:100px;"
				    id="input_height_factor"
				    name="height_factor">
				  <strong>Chart Left Margin Scale Factor: </strong><br />
				  <input type="number" min="0.5" max="3" step="0.1" value="1"
				    class="form-control" style="width:100px;"
				    id="input_left_factor"
				    name="left_factor">
				  <strong>Show Columns in Table: </strong><br />
				  <label>
				    <input type="checkbox"
				      name="table_option_logfc" checked>
				    Log2FC
				  </label> &nbsp;
				  <label>
				    <input type="checkbox"
				      name="table_option_pval" checked>
				    P-Value
				  </label> &nbsp;
				  <label>
				    <input type="checkbox"
				      name="table_option_fdr" checked>
				    FDR
				  </label>
				</div>
			</div>



			<div class="row mt-2 ml-2">
				<button id="btn_submit" class="btn btn-primary">
					Submit
				</button>
			</div>

		</form>

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



	$(document).ready(function() {


		// ***************************************************************************
		// Generate Chart
		// ***************************************************************************
		var svg_index = $('#btn_save_svg').attr('index');
		var options = {
			url: 'exe.php?action=genes_comparisons_generate_chart',
			type: 'post',
			beforeSubmit: function(formData, jqForm, options) {

				svg_index = parseInt(svg_index) + 1;
				$('#container_btn_save_svg')
				.html('<button type="button" class="btn btn-warning mt-1 hidden btn_save_svg_'+svg_index+'" id="btn_save_svg" index="'+svg_index+'"><i class="fas fa-download"></i> Save SVG</button>');

				$('#chart_div').html('');

				$('#btn_submit').attr('disabled', '').children(':first').removeClass('fa-chevron-circle-right').addClass('fa-spin fa-spinner');

				return true;
			},
			success: function(response){
				$('#btn_submit').removeAttr('disabled').children(':first').removeClass('fa-spin fa-spinner').addClass('fa-chevron-circle-right');

				$('#btn_submit').removeAttr('disabled');

				// $('#chart_div').html(response);

	            if (response.type == 'Error') {
	                bootbox.alert(response.detail);
					return false;
	            }

					var data                = response.data;
					var layout              = response.layout;
					var settings            = response.settings;
					var gene_num            = response.Number.gene.length;
					var comparison_num      = response.Number.comparison.length;

			    	// Plotly.newPlot('chart_div', data, layout, settings);

					$('#btn_save_svg, #btn_export_gene_comp').show();
					Plotly
					  .newPlot('chart_div', data, layout, settings)
					  .then(function(gd){
					    $(document).on('click', '.btn_save_svg_' + svg_index, function() {
					      Plotly
					        .downloadImage(gd, {
											filename: 'bubblePlot',
											format:'svg',
											height: layout.height,
											width:1600
										})
					        .then(function(filename){
					            // console.log(filename);
					        });
					    });
					    $('.loader').remove();
					});




					var chartDiv            = document.getElementById('chart_div');

					chartDiv.on('plotly_click', function(data){
						var gene              = data.points[0].data.marker.gene[data.points[0].pointNumber];
						var gene_name         = data.points[0].data.marker.gene_name[data.points[0].pointNumber];
						var comparison        = data.points[0].data.marker.comparison[data.points[0].pointNumber];
						var comparison_name   = data.points[0].data.marker.comparison_name[data.points[0].pointNumber];
						var content           = '<h4>Marker Information</h4><hr />';
						content              += '<ul>';
						content              += '<li>Gene details: <a href="../tool_search/view.php?type=gene&id=' + gene + '" target="_blank">' + gene_name + '</a></li>';
						content              += '<li>Comparison details: <a href="../tool_search/view.php?type=comparison&id=' + comparison + '" target="_blank">' + comparison_name + '</a></li>';
						content              += '<ul>';
						//console.log(data.points[0]);
						bootbox.alert(content);
					});

					var num_content         = '<div class="alert alert-warning mt-1 mb-3">';
					num_content            += 'Number of genes appeared: ' + gene_num + ', ';
					num_content            += 'Number of comparisons appeared: ' + comparison_num + '. &nbsp;';
					num_content            += '<a href="files/' + response.userid + '/download.csv" download>';
					num_content            += '<i class="fas fa-angle-double-right"></i> Download Data</a>';
					num_content            += '</div>';

					$('#chart_div').prepend(num_content);

					$('#table_div').html(response.table);
					$('#datatable_' + response.time).DataTable({ 'pageLength': 100, 'lengthMenu': [[10, 100, 500, 1000], [10, 100, 500, 1000]], dom: 'Blfrtip', buttons: ['colvis','copy','csv'] });

					return true;

			}
		};
		$('#form_bubble_plot').ajaxForm(options);

	});

</script>

</body>
</html>