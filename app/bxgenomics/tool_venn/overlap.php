<?php

include_once(__DIR__ . '/config.php');

$sql = "SELECT `Name`, `Count`, `Items` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_USERSAVEDLISTS']}` WHERE `Species` = '" . $_SESSION['SPECIES_DEFAULT'] . "' AND " . $BXAF_CONFIG['QUERY_DEFAULT_FILTER'] . " AND `Category` = 'Gene'";
$lists_gene = $BXAF_MODULE_CONN -> get_all($sql);


?><!DOCTYPE html>
<html lang="en">
<head>
	<?php include_once($BXAF_CONFIG['BXAF_PAGE_HEADER']); ?>

	<script src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/jquery/jquery.form.min.js"></script>

	<link href="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/jquery-ui/jquery-ui.min.css" rel="stylesheet">
	<script src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/jquery-ui/jquery-ui.min.js"></script>

	<script src="js/d3.js"></script>
	<script src="js/venn.js"></script>

	<link href="style.css" rel="stylesheet">



	<script type="text/javascript">


	$(document).ready(function(){

		$('.hidden_at_first').hide(0);
		$('.check_box_dataset').click();
		$('.check_box_dataset').parent().parent().parent().parent().addClass("medium_blue");


	    $( "#slider" ).slider({
	      value:500,
	      min: 300,
	      max: 2000,
	      step: 100,
	      slide: function( event, ui ) {
	        $( "#amount" ).val(ui.value );
	      }
	    });
	    $( "#amount" ).val( $( "#slider" ).slider( "value" ) );




		$("#add_dataset").click(function(){
			var m = $(".check_box_dataset").length;
			var original_selected = $("#dataset_selected").val();
			$("#dataset_container").append(
				'<div class="medium_blue dataset_div"> <div class="form-inline d-flex justify-content-start"><div class="pr-2"><label><input class="check_box_dataset" type="checkbox" name="check_select'+ m +'" id="check_select'+ m +'" recordid="'+ m +'" checked></label> </div>  <div class=""><input type="text" class="form-control block_left dataset_name" placeholder="Name of Dataset" name="name[]"></div> <div class="ml-auto"></div> </div> <div class="d-flex justify-content-center mb-3"><a href="javascript:void(0);" action_target="textarea'+m+'" class="btn_load_saved_lists"> <i class="fas fa-angle-double-right"></i> Load from saved lists</a></div> <textarea placeholder="List of Values" name="value[]" id="textarea'+ m +'"></textarea><br> </div>'
			);

			$("#dataset_selected").val(original_selected + ',' + m);

		})



		$(document).on("change", '.check_box_dataset', function(){
			var n = $(".check_box_dataset:checked").length;
			var recordid = $(this).attr("recordid");
			var original_selected = $("#dataset_selected").val();
			//alert(original_selected);
			if (n < 6){
				if( $(this).is( ":checked" ) ){
					$(this).parent().parent().parent().parent().addClass("medium_blue");
					$("#dataset_selected").val(original_selected + ',' + recordid);
				} else {
					$(this).parent().parent().parent().parent().removeClass("medium_blue");
					$("#dataset_selected").val(original_selected + ',' + recordid);
				}
			} else {
				$(this).prop('checked', false);
				bootbox.alert('Only 5 or fewer datasets can be selected at the same time.');

				if( $(this).is( ":checked" ) ){} else {
					$(this).parent().parent().parent().parent().removeClass("medium_blue");
					$("#dataset_selected").val(original_selected + ',' + recordid);
				}
			}
		});


		$(document).on("change", '#method_3', function(){

			if($("#method_3").attr('checked', 'checked')){

				//$(this).parent().parent().next().show(300);
				bootbox.dialog({
	                title: "Upload a csv file",
	                message: '<div class="w-100">  ' +
	                    '<form class="form-horizontal" enctype="multipart/form-data" role="form" id="form_upload"> ' +
                			'<input id="file" name="file" type="file" placeholder="Your name" class="mb-3 btn btn-success"> ' +
							'<label><input name="header" type="radio" placeholder="Your name" id="radio_header1" value="1" checked> My csv file contains headers as the first row.</label>' +
							'<label><input name="header" type="radio" placeholder="Your name" id="radio_header2" value="2"> My csv file doesn\'t contain headers.</label>' +
	                    '</form> </div>',
	                buttons: {
	                    success: {
	                        label: "Upload",
	                        className: "btn-primary",
	                        callback: function () {
								if($('#radio_header1').is(':checked')){var header = 1;}
								if($('#radio_header2').is(':checked')){var header = 2;}
								var data = new FormData();
								$.each($('#file')[0].files, function(i, file) {
									data.append('file-'+i, file);
								});
								jQuery.ajax({
									url: 'exe.php?action=upload&header=' + header,
									data: data,
									cache: false,
									contentType: false,
									processData: false,
									type: 'POST',
									success: function(responseText){
										if(responseText == 'notcsv'){bootbox.alert('The file you uploaded is not a csv file.');}
										else{
											$('#dataset_container').html(responseText);
											$('#dataset_selected').val($('#nrows_uploaded').val());
										}
									}
								});

	                        }
	                    }
	                }
	            });
			}
		})



		$('#example').click(function(){

			$input = '<div class="dataset_div medium_blue">' +
					 '<div class="form-inline d-flex justify-content-start">' +
					 '<div class="pr-2"><label><input class="check_box_dataset" type="checkbox" name="check_select0" id="check_select0" recordid="0" checked></label></div>' +
					 '<div class=""><input type="text" class="form-control block_left dataset_name" placeholder="Name of Dataset" name="name[]" value="A"></div>'+
					 '<div class="ml-auto"></div></div>' +

					 '<div class="d-flex justify-content-center mb-3"><a href="javascript:void(0);" action_target="value1" class="btn_load_saved_lists"> <i class="fas fa-angle-double-right"></i> Load from saved lists</a></div>' +

					 '<textarea placeholder="List of Values" name="value[]" id="value1">' +

					 'ABCB1\nADCY3\nADRBK1\nAP3D1\nAPH1B\nAPOL1\nBIRC5\nC13orf25\nC15orf20\nC18orf1\nC1orf34\nC4orf8\nC8ORFK29\n' +
					 'C8ORFK32\nCAMK2D\nCAST\nCBFA2T3\nCCPG1\nCCRL2\nCD3EAP\nCDC2L2\nCDC42BPA\nCEP250\nCEP68\nCFLAR\nCIAS1\nCLN3\n' + 'COL16A1\nCORO1A\nCRHR2\nDCAMKL2\nDGKZ\nDLC1\nDLG4\nDNM1L\nDNMT1\nDOCK8\nDSCAM\nEPB41\n' +
					 'EPHA7\nESR2\nET\nEVI5\nFAM21C\nFAS\nFHL2\nFN1\nGIT1\nGLYAT\nGOPC\nGPD2\nGRB7\nGRIN1\nGUCY1B3\nHD\nHDAC3\nHDAC5\n' +
					 'HELLS\nHIF3A\nIGSF1\nIRF3\nJAK1\nKCNC3\nKCNMA1\nKIAA0310\nKIAA0460\nKIF1B\nKIF5C\nKNDC1\nKRTAP19-2\nKRTAP5-8\n' +
					 'LDLR\nLILRA3\nLILRB1\nLILRB3\nLIMA1\nLOC653483\nMAP3K2\nMAP3K6\nMAPKBP1\nMARK2\nMEGF6\nMGAT5B\nMGC4172\nMKNK1\n' +
					 'MLLT6\nMMP23B\nMOP-1\nMR1\nNCR3\nNFYC\nOSBPL6\nOTUD1\nPACS2\nPAN3\nPCBP2\nPHC1\nPHLDB1\nPIGG\nPIK3R3\nPKLR\nPLEKHG5\nPLXNB2\nPMS2\n' +
					 'PMS2L2\nPTDSR\nPTK9\nRABGAP1L\nRALGDS\nRAP1GAP\nRAPGEF2\nRASAL1\nRBMS3\nRGS11\nRGS12\n' +
					 'RHAG\nRKHD1\nRNGTT\nRPS6KA3\nRRM2B\nRXRB\nRYR3\nSCN5A\nSCP2\nSDC3\nSGCE\nSH3BP2\nSLC15A1\nSLC43A2\nSLC4A4\nSMARCC2\n' +
					 'SNCAIP\nSPAG9\nSTK23\nSTK39\nSYNJ1\nSYNPO2L\nSYT15\nTAF2\nTBC1D12\nTEP1\nTGOLN2\nTHPO\nTMF1\nTNFRSF10C\nTNIK\nTP73L\n' +
					 'TRIO\nTSC1\nTTYH1\nTXNL4A\nUBE3A\nXYLB\nZNF589\nZNF646\nZNF747' +

					 '</textarea>' +
					 '</div>' +
					 '<div class="dataset_div medium_blue">' +
					 '<div class="form-inline d-flex justify-content-start">' +
					 '<div class="pr-2"><label><input class="check_box_dataset" type="checkbox" name="check_select1" id="check_select1" recordid="1" checked></label></div>' +
					 '<div class=""><input type="text" class="form-control block_left dataset_name" placeholder="Name of Dataset" name="name[]" value="B"></div>'+
					 '<div class="ml-auto"></div></div>' +

					 '<div class="d-flex justify-content-center mb-3"><a href="javascript:void(0);" action_target="value2" class="btn_load_saved_lists"> <i class="fas fa-angle-double-right"></i> Load from saved lists</a></div>' +

					 '<textarea placeholder="List of Values" name="value[]" id="value2">' +

					 'AAMP\nABCA4\nADAMTS6\nALPL\nANKRD28\nARNT\nATP10B\nBCKDHA\nBRUNOL4\nBZRAP1\nC6orf32\nCBFA2T3\nCEP68\nCLASP2\nCOL5A1\nCOPS2\nCUGBP1\nDRD2\nDYNC2H1\n' +
					 'ESR2\nFAM21C\nFLJ30092\nGOLGA4\nHADHSC\nHDAC5\nHSPH1\nKCNK2\nKIAA0676\nKIAA0690\nKIAA0701\nKIF1B\nKIF3A\nKIF5A\nKIR2DL4\nLAMA4\nLILRB1\nLOC387755\n' +
					 'LOC613212\nLOC653483\nLOH11CR2A\nLST1\nMAGI2\nMAP3K4\nMCF2L\nMEF2D\nMEGF6\nMGAT5B\nMOP-1\nMPDZ\nPER2\nPKLR\nPLD1\nPLEKHG5\nPMS2L2\nPPGB\nPRO1073\n' +
					 'PRPF3\nPTDSR\nRGS11\nRNGTT\nSATB1\nSDHC\nSNPH\nSNX9\nSPOCK1\nSS18L1\nSTAG1\nSYN3\nTBC1D12\nTLK1\nTNIK\nTP53BP1\nTP73L\nTPD52L1\nUSP52\nXYLB' +

					 '</textarea>' +
					 '</div>' +
					 '<div class="dataset_div medium_blue">' +
					 '<div class="form-inline d-flex justify-content-start">' +
					 '<div class="pr-2"><label><input class="check_box_dataset" type="checkbox" name="check_select2" id="check_select2" recordid="2" checked></label></div>' +
					 '<div class=""><input type="text" class="form-control block_left dataset_name" placeholder="Name of Dataset" name="name[]" value="C"></div>'+
					 '<div class="ml-auto"></div></div>' +

 					 '<div class="d-flex justify-content-center mb-3"><a href="javascript:void(0);" action_target="value3" class="btn_load_saved_lists"> <i class="fas fa-angle-double-right"></i> Load from saved lists</a></div>' +

					 '<textarea placeholder="List of Values" name="value[]" id="value3">' +

					 'ARTS-1\nATP10B\nBZRAP1\nC1orf34\nCBFA2T3\nCLASP2\nESR2\nFLJ30092\nGRB7\nOTUD3\nPCNXL2\nPDE4DIP\nPER2\nPKLR\nPLEKHG5\nPMS2L2\nRABGAP1L\nRAP1GAP\n' +
					 'RGS11\nSDC3\nSH3PXD2A\nSPAG9\nSS18L1\nTP53AP1\nTP73L\nUSP52\nXYLB\nZZEF1' +

					 '</textarea>' +
					 '</div>';

			$('#dataset_container').html($input);
			$("#dataset_selected").val(',0,1,2');

		})

		$(document).on("change", "#case_sensitive", function(){
			if($('#case_sensitive').is(':checked')){
				$('#remove_duplicated').prop('checked', true);
			}
		})

		$(document).on('click', '#reset_btn', function(){
			$('#div_debug').html('');

			bootbox.confirm('Are you sure you want to clear everything and start new analysis?', function(result){
				if(result){ window.location = 'overlap.php'; }
			});
		})

		var options = {
			url: 'exe.php?action=overlap&id=' + Math.random(),
			type: 'post',
			beforeSubmit: function(formData, jqForm, options){

				return true;
			},
			success: function(responseText, statusText){

				if(responseText == 'notcsv'){bootbox.alert('The file you uploaded is not a csv file.');}
				else if(responseText == 'samename'){bootbox.alert('The datasets should have different names.');}
				else if(responseText == 'empty'){bootbox.alert('You should have at least one non-empty dataset to get the diagram.');}
				else {
					$('#div_debug').hide().html(responseText).show(800);
					$('html,body').animate({
					  scrollTop: 820
					}, 1000);
				}
			}
		}
		$('#form_example').ajaxForm(options);


		$(document).on('click', '.content_detail',function(){
			var type = $(this).attr('type');
			var method = $(this).attr('method');
			var other = $(this).attr('other');
			var case0 = $(this).attr('case');
			var title = $(this).attr('title');
			$.ajax({
				method: 'POST',
				url: 'exe.php?action=get_content_detail&type=' + type + '&method=' + method + '&other=' + other + '&case=' + case0,
				success: function(responseText, statusText){
					bootbox.alert({
						title: title,
						message: responseText,
						callback: function(){}
					});
				}
			})
		})

		$(document).on('change', '.content_detail_radio', function(){
			if($('#content_detail0').is(":checked")){
				$('#content_detail1_div').addClass('hidden');
				$('#content_detail0_div').hide().removeClass('hidden').fadeIn(0);
			} else {
				$('#content_detail0_div').addClass('hidden');
				$('#content_detail1_div').hide().removeClass('hidden').fadeIn(0);
			}
		})
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

    <?php $help_key = 'Overlap and Venn Diagrams'; include_once( dirname(__DIR__) . "/help_content.php"); ?>

	<div class="w-100 my-3">
		<a href="help.php" class="mx-2" target="_blank"><i class="fas fa-info-circle"></i> Quick Guide</a>
		<a href="index.php" class="mx-2 btn btn-success btn-sm"><i class="fas fa-link"></i> Draw the Area-Proportional Venn Diagram directly</a>
	</div>

	<div class="w-100 my-3">

		<form enctype="multipart/form-data" id='form_example' role="form">

			<input type="text" name="dataset_selected" class="hidden" id="dataset_selected" value=",0,1,2">
			<input type="text" name="all_dataset_names" class="hidden" id="all_dataset_names" value="">

			<div class="form-inline">
				<strong>Input Options: </strong>
				<div class="checkbox checkbox_div"><label><input class="mx-2" type="radio" name="method" id="method_1" value="1" checked> Enter data in boxes below</label></div>
				<div class="checkbox checkbox_div"><label><input class="mx-2" type="radio" name="method" value="3" id="method_3"> Import from CSV file</label></div>
				<div class="hidden_at_first" style="margin-left:30px;"><input type="file" name="Files[]" id="exampleInputFile1"></div>
				<a href="javascript:void(0);" id="example" style="margin-left:20px;"> <i class="fas fa-arrow-circle-right"></i> Try With Example Data</a>
			</div>



			<div class="row no_margin" id="dataset_container">

				<div class="dataset_div">
					<div class="form-inline d-flex justify-content-start">
						<div class="pr-2">
							<label><input class="check_box_dataset" type="checkbox" name="check_select0" id="check_select0" recordid="0"></label>
						</div>
						<div class="">
							<input type="text" class="form-control block_left dataset_name" placeholder="Name of Dataset" name="name[]" value="A">
						</div>
						<div class="ml-auto"></div>
					</div>
					<div class="d-flex justify-content-center mb-3">
						<a href="javascript:void(0);" action_target="textarea_1" class="btn_load_saved_lists"> <i class="fas fa-angle-double-right"></i> Load from saved lists</a>
					</div>
					<textarea placeholder="List of Values" name="value[]" id="textarea_1"></textarea>
				</div>

				<div class="dataset_div">
					<div class="form-inline d-flex justify-content-start">
						<div class="pr-2">
							<label><input class="check_box_dataset" type="checkbox" name="check_select1" id="check_select1" recordid="1"></label>
						</div>
						<div class="">
							<input type="text" class="form-control block_left dataset_name" placeholder="Name of Dataset" name="name[]" value="B">
						</div>
						<div class="ml-auto"></div>
					</div>
					<div class="d-flex justify-content-center mb-3">
						<a href="javascript:void(0);" action_target="textarea_2" class="btn_load_saved_lists"> <i class="fas fa-angle-double-right"></i> Load from saved lists</a>
					</div>
					<textarea placeholder="List of Values" name="value[]" id="textarea_2"></textarea>
				</div>

				<div class="dataset_div">
					<div class="form-inline d-flex justify-content-start">
						<div class="pr-2">
							<label><input class="check_box_dataset" type="checkbox" name="check_select2" id="check_select2" recordid="2"></label>
						</div>
						<div class="">
							<input type="text" class="form-control block_left dataset_name" placeholder="Name of Dataset" name="name[]" value="C">
						</div>
						<div class="ml-auto"></div>
					</div>
					<div class="d-flex justify-content-center mb-3">
						<a href="javascript:void(0);" action_target="textarea_3" class="btn_load_saved_lists"> <i class="fas fa-angle-double-right"></i> Load from saved lists</a>
					</div>
					<textarea placeholder="List of Values" name="value[]" id="textarea_3"></textarea>
				</div>


			</div>

			<a href="javascript:void(0);" id="add_dataset"><i class="fas fa-plus fa-lg"></i> Add new dataset</a>

			<div class="w-100 mt-3">
				<label for="amount">Size of Venn Diagram (width and height in px):</label>
				<input type="text" id="amount" name="size" readonly style="background-color:black;">
			</div>

			<div class="block_left" id="slider"></div>

			<div class="form-inline w-100 my-5">
				<button type="submit" class="btn btn-primary">Submit</button>
				<button type="button" class="btn btn-secondary mx-2" id="reset_btn">Reset</button>
				<div class="checkbox"><label><input class="mx-2" type="checkbox" name="remove_duplicated" id="remove_duplicated" checked> Remove Duplicated Names</label></div>
				<div class="checkbox"><label><input class="mx-2" type="checkbox" name="case_sensitive" id="case_sensitive" checked> IDs are case sensitive</label></div>
			</div>

		</form>

		<div class="my-3 w-100" id="div_debug"></div>

	</div>

</div>
</div>
<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>
</div>
</div>



	<div class="modal" id="modal_saved_lists" tabindex="-1" role="dialog">
	  <div class="modal-dialog" role="document">
	    <div class="modal-content">
	      <div class="modal-header">
	        <h5 class="modal-title" id="modal-title">Saved Lists</h5>
	        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
	          <span aria-hidden="true">&times;</span>
	        </button>
	      </div>
	      <div class="modal-body">
			<input type="hidden" id="modal_action_target" value="" />

	        <p>Note: Click list name to select.</p>
	        <ul>
<?php
	foreach ($lists_gene as $record) {
		echo '<li>';
			echo '<a href="javascript:void(0);" class="btn_saved_list_name" content="' . implode("\n",  category_list_to_idnames(unserialize($record['Items']), 'id', 'Gene', $_SESSION['SPECIES_DEFAULT'])) . '">' . $record['Name'] . '</a>';
			echo '<span class="text-muted"> (Count: ' . $record['Count'] . ')</span>';
		echo '</li>';
	}
?>
			</ul>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

	<script type="text/javascript">
	    $(document).ready(function() {

			$(document).on('click', '.btn_load_saved_lists', function() {
				var target = $(this).attr('action_target');
				$('#modal_action_target').val( target );
				$('#modal_saved_lists').modal('show');
			});

			$(document).on('click', '.btn_saved_list_name', function() {
				var target = $('#modal_action_target').val();
				$('#' + target).val( $(this).attr('content') );
				$('#modal_saved_lists').modal('hide');
			});

	    });
    </script>

</body>
</html>