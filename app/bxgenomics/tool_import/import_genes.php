<?php
include_once("config.php");

if (isset($_GET['action']) && $_GET['action'] == 'save_list') {

    $fields = array("GeneName", "EntrezID", "Source", "Description", "Alias", "Ensembl", "Unigene", "Uniprot", "TranscriptNumber", "Strand", "Chromosome", "Start", "End", "ExonLength", "GeneID", "AccNum", "Biotype");

    $sql = "SELECT MAX(`ID`) FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_GENES'] . "` ";
    $new_id = 1 + $BXAF_MODULE_CONN -> get_one($sql);

	$sql = "SELECT MAX(`ID`) FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_GENES_INDEX'] . "` ";
    $new_index_id = 1 + $BXAF_MODULE_CONN -> get_one($sql);

    $_POST['gene_info'] = str_replace("\r", "\n", str_replace("\r\n", "\n", $_POST['gene_info']) );

	$gene_indexes = array();
	$gene_names = array();
	$gene_info_array = array();

	$gene_info = explode("\n", $_POST['gene_info']);
	$head = explode("\t", array_shift($gene_info));
	$head_flip = array_flip($head);
	foreach($gene_info as $i=>$row){
        if(trim($row) == '') continue;

		$row = explode("\t", $row);
		foreach($head as $j=>$h){
            if(! in_array($h, $fields)) continue;
			$gene_info_array[$i][$h] = $row[$j];
			$gene_names[$i] = $row[ $head_flip['GeneName'] ];
		}
		$gene_info_array[$i]['ID'] = $new_id;
		$gene_info_array[$i]['GeneIndex'] = $new_id;
        $gene_info_array[$i]['Species'] = $_POST['Species'];
		$new_id++;

		$gene_indexes[$i]['ID']        = $new_index_id;
		$gene_indexes[$i]['Species']   = $_POST['Species'];
		$gene_indexes[$i]['Name']      = $gene_info_array[$i]['GeneName'];
		$gene_indexes[$i]['GeneIndex'] = $gene_info_array[$i]['GeneIndex'];
		$gene_indexes[$i]['GeneName']  = $gene_info_array[$i]['GeneName'];
		$new_index_id++;

	}

    $id_names = category_list_to_idnames($gene_names, 'name', 'Gene', $_POST['Species']);

	if(is_array($id_names) && count($id_names) > 0){
		echo "Error: These genes are already in the system: " . implode(", ", $id_names);
		exit();
	}

	$inserted_genes = 0;
	foreach($gene_info_array as $info){
		$id = $BXAF_MODULE_CONN -> insert( $BXAF_CONFIG['TBL_BXGENOMICS_GENES'], $info);
		if( $id > 0) $inserted_genes++;
	}
	$inserted_indexes = 0;
	foreach($gene_indexes as $info){
		$id = $BXAF_MODULE_CONN -> insert( $BXAF_CONFIG['TBL_BXGENOMICS_GENES_INDEX'], $info);
		if( $id > 0) $inserted_indexes++;
	}

	echo "$inserted_genes genes are added: " . implode(", ", $gene_names);

	exit();

}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include_once($BXAF_CONFIG['BXAF_PAGE_HEADER']); ?>
    <script src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/jquery/jquery.form.min.js"></script>
	<script src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/jquery/jquery.textarea_tabby.min.js"></script>
</head>
<body>
	<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_MENU'])) include_once($BXAF_CONFIG['BXAF_PAGE_MENU']); ?>
	<div id="bxaf_page_wrapper" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_WRAPPER']; ?>">
		<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_LEFT'])) include_once($BXAF_CONFIG['BXAF_PAGE_LEFT']); ?>
		<div id="bxaf_page_right" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT']; ?>">
			<div id="bxaf_page_right_content" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT_CONTENT']; ?>">



		    <div class="container-fluid">

		        <h2 class="w-100">Import Custom Genes</h2>
		        <hr class="w-100 mb-3">

		        <div class="w-100">

		            <form class="w-100" id="form_save_list">

		                <div class="my-3">
		                  <div class="font-weight-bold">Species: </div>
		                  <div class="">
		                      <select id="Species" name="Species" class="custom-select" style="width: 12rem;">
								  <option value="Mouse">CHO</option>
		          			</select>
		                  </div>
		                </div>
		                <div class="my-3">
		                  <div class="font-weight-bold">
							  Gene List (One per row, <span class="text-danger">tab-delimited</span>).
						  </div>
						  <div class="font-weight-bold">
							  The first row must be field names. <span class="text-danger">GeneName</span> is required and must not exists in current system.
						  </div>
		                  <div class="">
		                    <textarea class="form-control" style="height:150px; width: 100%;" name="gene_info" id="gene_info" required><?php echo "GeneName\tEntrezID\tSource\tDescription\tAlias\tEnsembl\tUnigene\tUniprot\tTranscriptNumber\tStrand\tChromosome\tStart\tEnd\tExonLength\tGeneID\tAccNum\tBiotype\n"; ?></textarea>
		                  </div>
		                </div>
		                <div class="my-3">
		                  <div class="">
		                    <button class="btn btn-primary" id="btn_submit">  <i class="fas fa-save"></i> Save Genes </button>
		                  </div>
		                </div>

		            </form>

					<div id="div_debug"></div>

		        </div>

		    </div>


        </div>
        <?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>
    </div>
</div>


</body>


<script>

$(document).ready(function() {

	$('#gene_info').tabby();

    // Save List
    var options = {
        url: '<?php echo $_SERVER['PHP_SELF']; ?>?action=save_list',
        type: 'post',
        beforeSubmit: function(formData, jqForm, options) {
            $('#btn_submit').children(':first').removeClass('fa-floppy-o').addClass('fa-spin fa-spinner');
            $('#btn_submit').attr('disabled', '');
            return true;
        },
        success: function(response){
            $('#btn_submit').children(':first').removeClass('fa-spin fa-spinner').addClass('fa-floppy-o');
            $('#btn_submit').removeAttr('disabled');

			$('#div_debug').html(response);

            return true;
        }
    };
    $('#form_save_list').ajaxForm(options);

});

</script>

</html>