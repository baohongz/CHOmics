<?php
include_once("config.php");

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
<div class="container-fluid">

    <?php $help_key = 'Export Expression Data'; include_once( dirname(__DIR__) . "/help_content.php"); ?>

    <div class="w-100 my-3">
        <a href="genes_comparisons.php">
            <i class="fas fa-angle-double-right"></i> Export Comparison Data
        </a>
    </div>



	<div class="w-100">

        <form class="w-100" id="form_export">

			<div class="row w-100">
				<div class="col-md-6">
					<?php include_once(dirname(__DIR__) . '/tool_save_lists/modal_gene.php'); ?>
                    <div class="text-muted my-3">Note: Leave empty to export expression data of <span class="text-success">all genes from selected samples</span>.</div>
				</div>
				<div class="col-md-6">
					<?php include_once(dirname(__DIR__) . '/tool_save_lists/modal_sample.php'); ?>
                    <div class="text-muted my-3">Note: You must enter <span class="text-success">one or more sample names</span>.</div>
				</div>
			</div>


	        <?php
	            $checked = array('GeneName', 'EntrezID');
	            $type = 'Gene';
	            echo '<div class="w-100 my-3">';
	                echo '
	                <p class="w-100 mb-1">
	                    <label class="font-weight-bold">' . $type . ' Attributes:</label>
	                    <span class="table-success mx-2 p-2">( <span id="span_number_attributes_' . $type . '">' . count($checked) . '</span> selected )</span>
	                    <a href="javascript:void(0);" onclick="if($(\'#div_attributes_' . $type . '\').hasClass(\'hidden\')) $(\'#div_attributes_' . $type . '\').removeClass(\'hidden\'); else $(\'#div_attributes_' . $type . '\').addClass(\'hidden\'); "> <i class="fas fa-angle-double-right"></i> Show Attributes </a>
	                </p>';

	                echo '<div id="div_attributes_' . $type . '" class="w-100 hidden">';
						$list = $BXAF_CONFIG['TOOL_EXPORT_COLNAMES_ALL'][$type];
						sort($list);
						foreach ($list as $colname) {
							$caption = str_replace('_', ' ', $colname);
	                        echo '<div class="form-check form-check-inline">
	                            <input class="form-check-input checkbox_attributes_' . $type . '" type="checkbox" value="' . $colname . '" name="attributes_' . $type . '[]"' . (in_array($colname, $checked) ? " checked " : "") . '>';
	                            echo '<label class="form-check-label">' . $caption . '</label>';
	                        echo '</div>';
	                    }

    					echo "<a href='Javascript: void(0);' class='ml-3' onClick=\"$('.checkbox_attributes_$type').prop('checked', true ); $('#span_number_attributes_$type').html('all'); \" ><i class='fas fa-angle-double-right'></i> Check All</a>";
    					echo "<a href='Javascript: void(0);' class='ml-3' onClick=\"$('.checkbox_attributes_$type').prop('checked', false ); $('#span_number_attributes_$type').html('0'); \" ><i class='fas fa-angle-double-right'></i> Check None</a>";

	                echo '</div>';
	            echo '</div>';
	        ?>


	        <?php

				$type = 'Sample';
				$pre_checked = array('DiseaseState', 'Tissue', 'CellType', 'Gender', 'Treatment');

				$list = $BXAF_CONFIG['TOOL_EXPORT_COLNAMES_ALL']['Sample'];
				sort($list);

				if (isset($_GET['project_id']) && intval($_GET['project_id']) >= 0) {
				    $sql = "SELECT `" . implode("`,`", $list) . "` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES']}` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `_Projects_ID` = ?i";
				    $sample_info = $BXAF_MODULE_CONN -> get_all($sql, intval($_GET['project_id']) );
					$list_count = array();
					foreach($sample_info as $row){ foreach($row as $k=>$v){ if($v != '' && $v != 'NA') $list_count[$k] += 1; } }
					$pre_checked = array_keys($list_count);
				}

	            echo '<div class="w-100 my-3">';
	                echo '
	                <p class="w-100 mb-1">
	                    <label class="font-weight-bold">' . $type . ' Attributes:</label>
	                    <span class="table-success mx-2 p-2">( <span id="span_number_attributes_' . $type . '">' . count($pre_checked) . '</span> selected )</span>
	                    <a href="javascript:void(0);" onclick="if($(\'#div_attributes_' . $type . '\').hasClass(\'hidden\')) $(\'#div_attributes_' . $type . '\').removeClass(\'hidden\'); else $(\'#div_attributes_' . $type . '\').addClass(\'hidden\'); "> <i class="fas fa-angle-double-right"></i> Show Attributes </a>
	                </p>';

	                echo '<div id="div_attributes_' . $type . '" class="w-100 hidden">';

	                    foreach ($list as $colname) {
							$caption = str_replace('_', ' ', $colname);
	                        echo '<div class="form-check form-check-inline">
							<input class="form-check-input checkbox_attributes_' . $type . '" type="checkbox" value="' . $colname . '" name="attributes_' . $type . '[]"' . (in_array($colname, $pre_checked) ? " checked " : "") . '>';
	                            echo '<label class="form-check-label">' . $caption . '</label>';
	                        echo '</div>';
	                    }

    					echo "<a href='Javascript: void(0);' class='ml-3' onClick=\"$('.checkbox_attributes_$type').prop('checked', true ); $('#span_number_attributes_$type').html('all'); \" ><i class='fas fa-check'></i> Check All</a>";
    					echo "<a href='Javascript: void(0);' class='ml-3' onClick=\"$('.checkbox_attributes_$type').prop('checked', false ); $('#span_number_attributes_$type').html('0'); \" ><i class='fas fa-times'></i> Check None</a>";


	                echo '</div>';
	            echo '</div>';


                echo '<div class="w-100 my-3">
    					<div class="form-check form-check-inline">
    						<label class="form-check-label font-weight-bold" for="">Platform Type:</label>
    					</div>
    					<div class="form-check form-check-inline">
    						<input class="form-check-input reset_chart" type="radio" name="platform_type" value="NGS" id="platform_type_rna_seq">
    						<label class="form-check-label" for="platform_type_rna_seq">RNA-Seq Only, ignore Microarray Samples</label>
    					</div>
    					<div class="form-check form-check-inline">
    						<input class="form-check-input reset_chart" type="radio" name="platform_type" value="Array" id="platform_type_array">
    						<label class="form-check-label" for="platform_type_array">Microarray Only, ignore RNA-Seq Samples</label>
    					</div>
						<div class="form-check form-check-inline">
    						<input class="form-check-input reset_chart" type="radio" name="platform_type" value="" id="platform_type_auto" checked>
    						<label class="form-check-label" for="platform_type_array">Automatic based on the entered samples.</label>
    					</div>
    				</div>
                ';

				// $checked = array('NGS');
				// $type = 'Platform';
				// echo '<div class="w-100 my-3 form-inline">';
				// 	echo '<label class="font-weight-bold mr-2">' . $type . ':</label> ';
				// 	$options = array('NGS', 'Array');
				// 	foreach ($options as $option) {
				// 		echo '<div class="form-check form-check-inline">';
				// 			echo '<input class="form-check-input" type="radio" value="' . $option . '" name="' . $type . '" ' . (in_array($option, $checked) ? " checked " : "") . '>';
				// 			echo '<label class="form-check-label">' . $option . '</label>';
				// 		echo '</div>';
				// 	}
				// echo '</div>';
	        ?>

			<button type="submit" class="btn btn-primary" id="btn_submit"> <i class="fas fa-upload"></i> Submit </button>

			<div class="my-3" id="div_results"></div>
			<div class="my-3" id="div_debug"></div>

		</form>
	</div>



</div>
</div>
<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>
</div>
</div>





<script>

$(document).ready(function() {

	// Update number selected
	$(document).on('change', '.checkbox_attributes_Gene', function() {
		var number = 0;
		$('.checkbox_attributes_Gene').each(function(i, e) {
			if ($(e).is(':checked')) number++;
		});
		$('#span_number_attributes_Gene').html(number);
	});

	// Update number selected
	$(document).on('change', '.checkbox_attributes_Sample', function() {
		var number = 0;
		$('.checkbox_attributes_Sample').each(function(i, e) {
			if ($(e).is(':checked')) number++;
		});
		$('#span_number_attributes_Sample').html(number);
	});


	var options = {
		url: 'exe.php?action=export_genes_samples',
		type: 'post',
		beforeSubmit: function(formData, jqForm, options) {
			// if($('#Gene_List').val() == ''){
			// 	bootbox.alert('<h4 class="text-danger">Error</h4><hr /><p>Please enter some genes first.</p>');
			// 	return false;
			// }
			if($('#Sample_List').val() == ''){
				bootbox.alert('<h4 class="text-danger">Error</h4><hr /><p>Please enter some samples first.</p>');
				return false;
			}

			$('#div_results').html('');

			$('#btn_submit')
				.attr('disabled', '')
				.children(':first')
				.removeClass('fa-upload')
				.addClass('fa-spin fa-spinner');

			return true;
		},
		success: function(response){

			$('#btn_submit')
				.removeAttr('disabled')
				.children(':first')
				.addClass('fa-upload')
				.removeClass('fa-spin fa-spinner');

			$('#div_results').html(response);

		}
	};
	$('#form_export').ajaxForm(options);

});

</script>

</body>
</html>