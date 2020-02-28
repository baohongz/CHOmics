<?php
include_once(__DIR__ . "/config.php");


$analysis_root = $BXAF_CONFIG['USER_FILES']['TOOL_FUNCTIONAL_ENRICHMENT'] . "analysis_results/";
if(! file_exists($analysis_root)) mkdir($analysis_root, 0777, true);

$homer_bin = $BXAF_CONFIG['DIR_homer'];



if(isset($_GET['action']) && $_GET['action'] == 'get_status'){
	$process_id = intval(file_get_contents("{$analysis_root}{$_GET['time']}/processes.txt"));

	echo file_exists("{$analysis_root}{$_GET['time']}/results/geneOntology.html")  ? 1 : 0;

	exit();
}

//Actions to start analysis
if(isset($_GET['action']) && $_GET['action'] == 'submit'){

	$list = preg_split("/[\s,]+/", trim($_POST['Gene_List']), NULL, PREG_SPLIT_NO_EMPTY);
	foreach($list as $i=>$s) if(trim($s) == '') unset($list[$i]);
	$list = array_unique($list);

	if(! is_array($list) || count($list) <= 0){
		echo 'Error: Please enter some gene names.';
		exit();
	}

	$analysis_dir = $analysis_root . $_POST['time'];

	if(! file_exists($analysis_dir . "/results")) mkdir($analysis_dir . "/results", 0777, true);


	file_put_contents("{$analysis_dir}/input_ids.txt", implode("\n", $list) );
	chmod("{$analysis_dir}/input_ids.txt", 0775);

	file_put_contents("{$analysis_dir}/species.txt", $_POST['Species'] );
	chmod("{$analysis_dir}/species.txt", 0775);

	if($_POST['radio_background'] == 'not_all' && $_POST['Background_Gene_List'] != ''){

		$list2 = preg_split("/[\s,]+/", trim($_POST['Background_Gene_List']), NULL, PREG_SPLIT_NO_EMPTY);
		foreach($list2 as $i=>$s) if(trim($s) == '') unset($list2[$i]);
		$list2 = array_unique($list2);

		if(is_array($list2) && count($list2) > 0){
			file_put_contents("{$analysis_dir}/input_ids_background.txt", implode("\n", $list2) );
		}
	}


	// Compose command
	$command_sh = "#!/usr/bin/bash\n\n";

	$command_sh .= "export PATH=\$PATH:" . $homer_bin . "\n\n";

	$command_sh .= "cd $analysis_dir \n";

		$command = "{$homer_bin}findGO.pl input_ids.txt " . strtolower($_POST['Species']) . " results ";
		if(file_exists("{$analysis_dir}/input_ids_background.txt")) $command .= " -bg input_ids_background.txt";
		$command .= " -cpu 2 >{$analysis_dir}/findGO_log.txt 2>&1";

	$command_sh .= $command . "\n\n";


	file_put_contents("{$analysis_dir}/command.script", $command_sh);
	shell_exec("chmod 777 {$analysis_dir}/command.script");

	bxaf_execute_in_background( "{$analysis_dir}/command.script" );

	sleep(3);

	exit();
}



$time = microtime(true);

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

    <?php $help_key = 'Functional Enrichment'; include_once( dirname(__DIR__) . "/help_content.php"); ?>

    <div class="w-100 my-3">
        <a href="report_deg.php?analysis=0">
            <i class="fas fa-angle-double-right"></i> Check Example Output
        </a>
    </div>


  	<div class="w-100">

      	<form method="post" enctype="multipart/form-data" name="form_submit" id="form_submit">

			<div class="my-3">
				<label class="font-weight-bold">Species: </label>
				<label class="mx-2"><input type="radio" name="Species" id="Species_Human" value="Human" <?php if($_SESSION['SPECIES_DEFAULT'] == 'Human') echo "checked"; ?>> Human</label>
				<label class="mx-2"><input type="radio" name="Species" id="Species_Mouse" value="Mouse" <?php if($_SESSION['SPECIES_DEFAULT'] == 'Mouse') echo "checked"; ?>> Mouse</label>
			</div>

			<div class="my-3">
				<?php include_once('../tool_save_lists/modal_gene.php'); ?>
			</div>

			<div class="my-3">
				<a href="javascript:void(0);" id="gene_list_info_btn" class="red_link" data-tootik="Gene List ID Info" data-tootik-conf="right info"><i class="fas fa-info-circle"></i> Help</a>
				<a href="javascript:void(0);" class="text-success" data-tootik="Use Demo Data" data-tootik-conf="right success" onClick="$('#Gene_List').val('GPR37\nMAGEA3\nIGFBP3\nRBPMS\nRBPMS\nTNFAIP6\nCXCL3\nKRT7\nELF3\nTGM2\nANO1\nGPR87\nPI3\nMAGEA6\nTFF2\nTGM2\nCEACAM6\nINPP4B\nTGFA\nATP1B1\nCEACAM5\nIL8\nATP1B1\nTHBS1\nTHBS1\nTHBS1\nSLPI\nPI3\nDST\nTNFRSF11B\nTNFRSF11B\nPCDH1\nSCG5\nCEACAM6\nIGFBP6\nTGM2');"><i class="fas fa-leaf"></i> Example</a>
			</div>

			<div class="my-3">
				<label class="font-weight-bold">Background Gene List: </label>
				<label class="mx-1"><input onClick="$('#Background_Gene_List').hide();" name="radio_background" type="radio" id="radio" value="all" checked> Whole Genome</label>
				<label class="mx-1"><input type="radio" name="radio_background" id="radio2" value="not_all" onClick="$('#Background_Gene_List').show();"> Upload  Gene List</label>
				<textarea autocomplete="off" style="display: none;" class="form-control" name="Background_Gene_List" id="Background_Gene_List"></textarea>
			</div>

			<div class="my-3">
				<button id='button_submit' class='btn btn-primary'><i class="fas fa-play"></i> Submit</button>
				<input type="hidden" name="time" value="<?php echo $time; ?>">
			</div>

			<div id="jumbotron_div" class="hidden">
				<h5 class="w-100 my-3">
					<i class="fas fa-check-square text-success" id="jumbotron_icon"></i>
					It's being processed!
					...
					<span class="text-success" value="0" id="processing_time" style="font-size: 1.3rem;">0 sec</span>
				</h5>
				<hr>
				<p class="lead">Typically, it will take less than a minute to finish the analysis. This page will be updated when the analysis is done.</p>
				<button class="btn btn-danger hidden" id='analysis_waiting'> <i class="fas fa-ban"></i> Running ... Please Wait</button>
			</div>

      	</form>
	</div>

	<div id="debug"></div>

</div>
</div>
<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>
</div>
</div>




<script type="text/javascript">

    $(document).ready(function(){

    	$('#Gene_List').focus();

    	$(document).on('click', '#gene_list_info_btn', function(){
    		bootbox.alert('<h4>Enter one ID per row.</h4><hr />ID can be: <ul><li>Gene symbol</li><li>Locus ID (gene ID)</li><li>RefSeq ID </li><li>Ensembl ID</li></ul>');
    	});


    	// Submit Form
    	var options = {
    		url: '<?php echo $_SERVER['PHP_SELF']; ?>?action=submit',
     		type: 'post',
            beforeSubmit: function(formData, jqForm, options) {

    			if($('#Gene_List').val() == ''){
    				bootbox.alert('Error: Please enter some gene names.', function(){
    					$('#Gene_List').focus();
    				});
    				return false;
    			}

				$('#analysis_waiting').removeClass('hidden');

    			$('#button_submit').addClass('hidden');
    			$('#jumbotron_div').removeClass('hidden');

    			setInterval(function(){
    				var processingTime = parseInt(parseInt($('#processing_time').attr('value')) + 1);
    				$('#processing_time').attr('value', processingTime);
    				$('#processing_time').html(processingTime + ' sec');
    			}, 1000);

    			return true;

    		},
            success: function(responseText, statusText){

    			if(responseText != ''){

    				$('#jumbotron_div').attr('hidden', '');

					bootbox.alert(responseText, function(){
    					$('#Gene_List').focus();
    				});
    			}
				else {
					setInterval(function(){
						$.ajax({
			    	  		type: 'get',
			    	  		url: '<?php echo $_SERVER['PHP_SELF']; ?>?action=get_status&time=<?php echo $time; ?>',
			    	  		success: function(responseText) {
								if(responseText == '1'){
									window.location = "<?php echo $BXAF_CONFIG['BXAF_URL']; ?>app/bxgenomics/tool_functional_enrichment/report_deg.php?analysis=<?php echo $time; ?>";
								}
			    	  		}
			    	  	});
	    			}, 2000);
    			}
    			return true;
    		}
        };
    	$('#form_submit').ajaxForm(options);
    });
</script>


</body>
</html>