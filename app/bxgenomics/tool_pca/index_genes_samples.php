<?php
include_once("config.php");

?><!DOCTYPE html>
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
<div class="container-fluid">

    <?php $help_key = 'PCA Analysis'; include_once( dirname(__DIR__) . "/help_content.php"); ?>

	<div class="w-100 my-3">
		<a href="my_pca_results.php" class="mr-2">
			<i class="fas fa-caret-right"></i> Saved Results
		</a>
		<a href="index.php" class="mr-2">
			<i class="fas fa-caret-right"></i> PCA tool for uploaded data files
		</a>
	</div>

	<div class="w-100">

		<div class="row w-100">
			<div class="col-md-6">
				<?php include_once(dirname(__DIR__) . '/tool_save_lists/modal_gene.php'); ?>
				<div class="text-muted my-3">Note: Leave blank to perform analysis of <span class="text-success">all genes</span>.</div>
			</div>
			<div class="col-md-6">
				<?php include_once(dirname(__DIR__) . '/tool_save_lists/modal_sample.php'); ?>
				<div class="text-muted my-3">Note: You must enter <span class="text-success">one or more sample names</span>.</div>
			</div>
		</div>


		<div class="row w-100 mx-0">

			<?php

				$type = 'Sample';
				$pre_checked = array('DiseaseState', 'Tissue', 'Treatment');

				$list = $BXAF_CONFIG['TOOL_EXPORT_COLNAMES_ALL']['Sample'];
				sort($list);

				if (isset($_GET['project_id']) && intval($_GET['project_id']) >= 0) {
					$sql = "SELECT `" . implode("`,`", $list) . "` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES']}` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `_Projects_ID` = ?i";
					$sample_info = $BXAF_MODULE_CONN -> get_all($sql, intval($_GET['project_id']) );
					$list_count = array();
					foreach($sample_info as $row){ foreach($row as $k=>$v){ if($v != '' && $v != 'NA') $list_count[$k] += 1; } }
					$pre_checked = array_keys($list_count);
				}

				echo '<div class="row w-100 mt-3 mx-0">';
					echo '
						<p class="w-100 mb-1">
							<span class="font-weight-bold mr-2">' . $type . ' Attributes:</span>
							<span style="background-color:lightgreen; padding:5px;">
							( <span id="number_attributes_selected">' . count($pre_checked) . '</span> selected)
							</span>

							<a class="mx-2" href="javascript:void(0);" onclick="$(\'#div_all_options\').slideToggle()">
								<i class="fas fa-angle-double-right"></i>
								Show Attributes
							</a>
						</p>';

						echo '<div id="div_all_options" style="display:none;">';

							foreach ($list as $colname) {
								echo '
									<label class="mx-2">
										<input type="checkbox" category="' . $type . '" class="checkbox_check_individual" value="' . $colname . '" name="attributes_' . $type . '_' . $colname . '" ' . (in_array($colname, $pre_checked) ? "checked " : "") . '> ' . str_replace("_", " ", $colname) . '
									</label>';
							}

							echo "<a href='Javascript: void(0);' class='ml-3' onClick=\"$('.checkbox_check_individual').prop('checked', true ); $('#number_attributes_selected').html('all'); \" ><i class='fas fa-check'></i> Check All</a>";
							echo "<a href='Javascript: void(0);' class='ml-3' onClick=\"$('.checkbox_check_individual').prop('checked', false ); $('#number_attributes_selected').html('0'); \" ><i class='fas fa-times'></i> Check None</a>";

						echo '</div>';
					echo '</div>';

			?>
		</div>

		<button type="submit" class="btn btn-primary mt-3" id="btn_submit">
			<i class="fas fa-upload"></i> Submit
		</button>

		<div class="mt-3" id="debug"></div>

	</div>


</div>
</div>
<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>
</div>
</div>





<script>

$(document).ready(function() {


	$(document).on('change', '.checkbox_check_individual', function() {
		var curr = $(this);
		var category = curr.attr('category');
		var value = curr.attr('value');
		var check_option = false;
		if (curr.is(':checked')) {
			check_option = true;
		}

		var number_checked = 0;
		$('.checkbox_check_individual').each(function(i, e) {
			if ($(e).is(':checked')) {
				number_checked++;
			}
		});
		$('#number_attributes_selected').html(number_checked);
	});


  //-----------------------------------------------------------------------------
  // Submit
  //-----------------------------------------------------------------------------
  $(document).on('click', '#btn_submit', function() {
    var vm      = $(this);
    var Gene_List   = $('#Gene_List').val();
    var Sample_List = $('#Sample_List').val();
    var attr    = [];
    $('.checkbox_check_individual')
      .each(function(index, element) {
        if ($(element).is(':checked')) {
          attr.push($(element).attr('value'));
        }
      });
    vm
      .attr('disabled', '')
      .children(':first')
      .removeClass('fa-upload')
      .addClass('fa-spin fa-spinner');

    $.ajax({
      type: 'POST',
      url: 'exe_genes_samples.php?action=get_gene_sample_data',
      data: { "Gene_List": Gene_List, "Sample_List": Sample_List, "attr": attr },
      success: function(response) {
        vm
          .removeAttr('disabled')
          .children(':first')
          .addClass('fa-upload')
          .removeClass('fa-spin fa-spinner');

        var type = response.type;
        if (type == 'Error') {
          bootbox.alert(response.detail);
        } else {
          setInterval(function() {
            window.location = 'index_r_barchart.php?id=' + response.time + '<?php if (isset($_GET['project_id']) && intval($_GET['project_id']) > 0) echo "&project_id=" . intval($_GET['project_id']); ?>';
          }, 1000);
        }
      }
    });
  });



});

</script>

</body>

</html>