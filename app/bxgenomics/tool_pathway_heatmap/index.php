<?php
	include_once("config.php");

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

    <?php $help_key = 'Pathway Heatmap'; include_once( dirname(__DIR__) . "/help_content.php"); ?>


	<!-- Save SVG Index, to avoid downloading multiple svgs-->
	<input class="hidden" id="input_svg_index" value="1" />


	<form id="form_heatmap">

		<div class="row">
			<div class="col-md-6">

				<?php include_once(dirname(__DIR__) . '/tool_save_lists/modal_comparison.php'); ?>

			</div>
			<div class="col-md-6">

				<div class="form-inline my-3">

					<label class="font-weight-bold mr-2">Select Sets:</label>

					<select class="custom-select" id="select_set" name="set">
						<option value="PAGE List" selected>PAGE List</option>
						<option value="Biological Process">Biological Process</option>
						<option value="Cellular Component">Cellular Component</option>
						<option value="Molecular Function">Molecular Function</option>
						<option value="KEGG">KEGG</option>
						<option value="Molecular Signature">Molecular Signature</option>
						<option value="Interpro Protein Domain">Interpro Protein Domain</option>
						<option value="Wiki Pathway">Wiki Pathway</option>
						<option value="Reactome">Reactome</option>
					</select>
				</div>

				<div class="form-inline my-3">

					<label class="font-weight-bold mr-2">Show Type:</label>

					<input class="mr-2" type="radio" name="show_type" id="show_type_10" value="10">
					Top 10

					<input class="mx-2" type="radio" name="show_type" id="show_type_20" value="20" checked>
					Top 20

					<input class="mx-2" type="radio" name="show_type" id="show_type_50" value="50">
					Top 50

					<input class="mx-2" type="radio" name="show_type" id="show_type_100" value="100">
					Top 100

				</div>

				<div class="w-100 my-3">
					<a href="Javascript: void(0);" class="btn btn-outline-success" id="btn_get_pathway">Refresh Pathway & GeneSets</a>
					<span class="mx-2 hidden" id="btn_busy"><i class="fas fa-pulse fa-spinner"></i></span>
				</div>

			</div>
		</div>

		<div class="w-100">

	        <div id="container_step_2" class="hidden my-3">

				<strong>Pathway Names and GeneSets:</strong>
				<div class="row">
					<div class="col-md-6">
						<div class="w-100 my-1" id="name_genesets_up">Up-Regulated Genesets</div>
						<textarea class="form-control mt-2 mb-3" id="textarea_genesets_up"  name="textarea_genesets_up"  style="height:200px;"></textarea>
					</div>
					<div class="col-md-6 hidden" id="div_genesets_down">
						<div class="w-100 my-1">Down-Regulated Genesets</div>
						<textarea class="form-control mt-2 mb-3" id="textarea_genesets_down" name="textarea_genesets_down" style="height:200px;"></textarea>
					</div>
				</div>

				<button class="btn btn-primary" id="btn_submit"> <i class="fas fa-paint-brush"></i> Draw Heatmap </button>
	        </div>


	        <div id="chart_container" class="card chart_container mt-3 hidden" style="width:830px;">
	          <div class="card-header">
	            <span id="chart_title"></span>
	            <span id="container_btn_download_svg">
	          </div>
	          <div class="card-block">
	            <div id="div_heapmap"></div>
	          </div>
	        </div>
	        <div id="chart_container_2" class="card chart_container_2 mt-3 hidden" style="width:830px;">
	          <div class="card-header">
	            <span id="chart_title_2"></span>
	            <span id="container_btn_download_svg_2">
	          </div>
	          <div class="card-block">
	            <div id="div_heapmap_2"></div>
	          </div>
	        </div>

		</div>
	</form>


</div>
</div>
<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>
</div>
</div>



<script>


$(document).ready(function() {

	// ***************************************************************************
	// Select Set
	// ***************************************************************************
	function btn_get_pathway() {

		if ($('#Comparison_List').val() == '') {
			bootbox.alert('<h4><span class="text-danger">Error:</span> Please enter comparisons first.</h4>');
			return false;
		}
		else {

			$.ajax({
				type: 'post',
				url: 'exe.php?action=select_set',
				data: {
					"set": $('#select_set').val(),
					"show_type": $('input[name=show_type]:checked').val(),
					"comparisons": encodeURIComponent( $('#Comparison_List').val() )
				},
				beforeSend: function( xhr ) {
					$('#btn_busy').removeClass('hidden');
				},
				success: function(response, textStatus, jqXHR) {
					// console.log(response);
					$('#btn_busy').addClass('hidden');

					if (response.type && response.type == 'Error') {
						bootbox.alert('<h4><span class="text-danger">Error:</span> ' + response.detail + '</h4>');
					} else if (!response.data) {
						bootbox.alert('<h4><span class="text-danger">Error:</span> No comparison found.</h4>');
					} else {

						$('#container_step_2').slideDown();

						if ($('#select_set').val() == 'PAGE List') {
							var content = response.data.map(data => data).join('\n');
							$('#textarea_genesets_up').val(content);
							$('#name_genesets_up').html('Genesets');

							$('#textarea_genesets_down').val('');
							$('#div_genesets_down').hide();
						}
						else {
							var content_up   = response.data.Up.map(data => data).join('\n');
							var content_down = response.data.Down.map(data => data).join('\n');
							$('#textarea_genesets_up').val(content_up);
							$('#name_genesets_up').html('Up-Regulated Genesets');

							$('#textarea_genesets_down').val(content_down);
							$('#div_genesets_down').show();
						}

						$('#btn_submit').click();

					}
				}
			});
	    }
	}



	// ***************************************************************************
	// Hide Step 2 Div
	// ***************************************************************************
	$(document).on(
		'change',
		'#select_set, #show_type_10, #show_type_20, #show_type_50, #show_type_100',
		function() {

			$('#container_step_2, #chart_container, #chart_container_2').hide(0);

			btn_get_pathway();
		}
	);
	$(document).on('click', '#btn_get_pathway', function() {
		$('#container_step_2, #chart_container, #chart_container_2').hide(0);
		btn_get_pathway();
  	});

	// Generate heatmap automatically
	if ($('#Comparison_List').val() != '') {
		btn_get_pathway();
	}


	// ***************************************************************************
	// Draw Heatmap
	// ***************************************************************************
	var options = {
		url: 'exe.php?action=draw_heatmap',
		type: 'post',
		beforeSubmit: function(formData, jqForm, options) {

			$('#btn_submit')
			  .attr('disabled', '')
			  .children(':first')
			  .removeClass('fa-paint-brush')
			  .addClass('fa-spin fa-spinner');

			return true;
		},
		success: function(response){

			$('#btn_submit')
			.removeAttr('disabled')
			.children(':first')
			.removeClass('fa-spin fa-spinner')
			.addClass('fa-paint-brush');

			// console.log(response);

			if (response.type == 'Error') {
				bootbox.alert(response.detail);
				return true;
			}


			var data   = [ response.data ];
			var layout = response.layout;
			var svg_index = $('#input_svg_index').val();

			svg_index = parseInt(svg_index) + 1;
			$('#input_svg_index').val(svg_index);

			$('#container_btn_download_svg').html('<a href="javascript:void(0);" class="mx-3 btn_save_svg_'+svg_index+'" id="btn_save_svg" index="'+svg_index+'"><i class="fas fa-download"></i> Save SVG</a>');

			Plotly
			.newPlot('div_heapmap', data, layout)
			.then(function(gd){

			  $(document).on('click', '.btn_save_svg_' + svg_index, function() {
			    Plotly
			      .downloadImage(gd, {
								filename: 'heatMap',
								format:'svg',
								height:900,
								width:1600
							})
			      .then(function(filename){
			          // console.log(filename);
			      });
			  });
			  $('.loader').remove();

			});




			if ($('#select_set').val() != '') {

				$('#chart_container').slideDown();

				if ($('#select_set').val() == 'PAGE List') $('#chart_title').html('Genesets vs. Comparisons, log10(p-value)');
				else $('#chart_title').html('Up-Regulated Genesets vs. Comparisons, log10(p-value)');


				clickEvent = function(data){
					var name = data.points[0]; // Geneset Name
					var content = '<h3 class="">Data Actions</h3><div class="w-100 m-3">';
					content += '<a href="../tool_search/view.php?type=comparison&name=' + data.points[0].x + '" target="_blank"><i class="fas fa-caret-right"></i> ';
					content += 'View Comparison Detail';
					content += '</a><br />';
					content += '<a href="../tool_volcano_plot/index.php?comparison_name=' + data.points[0].x + '&type=custom&geneset=' + data.points[0].y + '" target="_blank"><i class="fas fa-caret-right"></i> ';
					content += 'View Data in Volcano Plot';
					content += '</a></div>';

			          // bootbox.alert(content);
			          bootbox.confirm({
			            message: content,
			            buttons: {
			              confirm: {
			                label: 'Close',
			                className: 'btn-secondary'
			              },
			              cancel: {
			                label: 'No',
			                className: 'btn-danger hidden'
			              }
			            },
			            callback: function (result) {
			              console.log('This was logged in the callback: ' + result);
			            }
			          });
		        }

		        document.getElementById('div_heapmap').on('plotly_click', clickEvent);
			}


			if ($('#select_set').val() != 'PAGE List') {

				var data_2   = [ response.data_2 ];
				var layout_2 = response.layout;

				Plotly
				.newPlot('div_heapmap_2', data_2, layout_2)
				.then(function(gd){

		            $(document).on('click', '.btn_save_svg_2_' + svg_index, function() {
		              Plotly
		                .downloadImage(gd, {
		      						filename: 'heatMap',
		      						format:'svg',
		      						height:900,
		      						width:1600
		      					})
		                .then(function(filename){
		                    console.log(filename);
		                });
		            });
		            $('.loader').remove();
				});

				$('#chart_container_2').slideDown();
				$('#container_btn_download_svg_2').html('<a href="javascript:void(0);" class="mx-3 btn_save_svg_2_'+svg_index+'" id="btn_save_svg_2" index="'+svg_index+'"><i class="fas fa-download"></i> Save SVG</a>');

				$('#chart_title_2').html('Down-Regulated Genesets vs. Comparisons, log10(p-value)');

		        document.getElementById('div_heapmap_2').on('plotly_click', clickEvent);
			}

			return true;
		}
	};

	$('#form_heatmap').ajaxForm(options);

});
</script>

</body>
</html>