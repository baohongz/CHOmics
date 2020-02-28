<?php
include_once("config.php");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include_once($BXAF_CONFIG['BXAF_PAGE_HEADER']); ?>

    <script type="text/javascript" src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/jquery/jquery.form.min.js"> </script>

    <link   href='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/datatables/datatables.min.css.php' rel='stylesheet' type='text/css'>
	<script src='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/datatables/datatables.min.js.php'></script>

    <script type="text/javascript" language="javascript" src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/js/natural.js"></script>

</head>
<body>

<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_MENU'])) include_once($BXAF_CONFIG['BXAF_PAGE_MENU']); ?>
<div id="bxaf_page_wrapper" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_WRAPPER']; ?>">
<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_LEFT'])) include_once($BXAF_CONFIG['BXAF_PAGE_LEFT']); ?>
<div id="bxaf_page_right" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT']; ?>">
<div id="bxaf_page_right_content" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT_CONTENT']; ?>">
<div class="container-fluid">

    <?php $help_key = 'KEGG Pathway View'; include_once( dirname(__DIR__) . "/help_content.php"); ?>

    <div class="w-100 my-3">
        <a href="<?php echo $_SERVER['PHP_SELF']; ?>"><i class="fas fa-sync"></i> Start Over</a>
        <span class="ml-5 mt-2 text-muted">Note: <span class="text-danger">*</span> denotes required fields.</span>
    </div>


    <div class="w-100">

        <form class="my-3" id="form_show_pathway" enctype="multipart/form-data" method="post">

            <div class="form-group">
                <span class="text-muted" id="text_pathway_name">(No Pathway Selected)</span>
                <a href="Javascript: void(0);" class="hidden ml-2" id="btn_download_pathway" target="_blank">
                  <i class="fas fa-download"></i> Download Pathway File
                </a>
            </div>
            <div class="form-group">
                <button class="btn btn-outline-success btn-sm" type="button" id="btn_select_pathway_show_modal">
                  <i class="fas fa-angle-double-right"></i> Select Pathway
                </button>
                <input class="hidden" name="KEGG_Identifier" value="" id="input_pathway">
            </div>


            <div class="w-100 my-2">
                <?php include_once(dirname(__DIR__) . '/tool_save_lists/modal_comparison.php'); ?>
            </div>

            <div class="form-group">
                <label class="font-weight-bold">Or, upload your comparison files:</label>
                <input type="file" class="" id="comparison_file" name="comparison_file" onchange="$('#Comparison_List').val('');">
                <a href="kegg/demo_logfc.csv"> <i class="fas fa-angle-double-right"></i> Demo Data </a>
            </div>

            <div class="form-group">
                <label class="font-weight-bold">Visualization:</label>
                <select class='custom-select' name='Visualization' id='Visualization'>
					<option value='1'>Gradient Blue-White-Red (-1,0,1)</option>
					<option value='2'>Gradient Blue-White-Red (-2,0,2)</option>
					<option value='3'>Gradient Blue-White-Red (-3,0,3)</option>
				</select>
            </div>

            <div class="w-100 form-check form-check-inline">
                <button id="btn_submit" type='submit' class="btn btn-primary"> Submit </button>

                <label class="form-check-label mx-2 hidden" id="btn_busy"><i class="fas fa-pulse fa-spinner"></i></label>

            </div>
        </form>

        <div class="w-100 my-3">
            <div id="div_results" class="w-100"></div>
            <div id="div_debug" class="w-100 my-3"></div>
        </div>

    </div>


</div>
</div>
<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>
</div>
</div>



<!-------------------------------------------------------------------------------------------------------->
<!-- Modal to Select Pathway -->
<!-------------------------------------------------------------------------------------------------------->
<div class="modal" id="modal_select_pathway" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title">Select Pathway</h4>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			</div>
			<div class="modal-body">
				<?php
					echo '
					<table class="table table-bordered" id="table_select_pathway">
						<thead class="table-success">
						<tr>
                            <th>Code</th>
							<th>Pathway Name</th>
							<th>Action</th>
						</tr>
						</thead>
						<tbody>';

                        foreach ($BXAF_CONFIG['KEGG_PATHWAY_LIST'] as $key => $value) {
							echo '
							<tr>
                                <td>' . $key . ' &nbsp;</td>
								<td>' . $value . ' &nbsp;</td>
								<td><a href="javascript:void(0);" class="btn_select_search_pathway" content="' . $key . '" displayed_name="' . $value . '"><i class="fas fa-angle-double-right"></i> Select</a></td>
							</tr>';
						}

					echo '
						</tbody>
					</table>
					';
				?>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
			</div>
		</div>
	</div>
</div>



<script>

    $(document).ready(function() {

        $(document).on('click', '#btn_select_pathway_show_modal', function() {
            $('#table_select_pathway').DataTable();
        	$('#modal_select_pathway').modal('show');
        });

        $(document).on('click', '.btn_select_search_pathway', function() {
            var content = $(this).attr('content');
            var displayed_name = $(this).attr('displayed_name');

            $('#text_pathway_name').text( displayed_name );
            $('#input_pathway').val(content);

            $('#modal_select_pathway').modal('hide');

            $('#btn_download_pathway').show().attr('href', 'kegg/xml_png/' + content + '.png');
        });


<?php

if(isset($_GET['pathway']) && $_GET['pathway'] != ''){
    $content = '';
    $displayed_name = '';
    foreach ($BXAF_CONFIG['KEGG_PATHWAY_LIST'] as $key => $value) {

        if(strtolower( $value ) == strtolower( $_GET['pathway'] )){

            echo "var content = '$key';
            var displayed_name = '$value';

            $('#text_pathway_name').text( displayed_name );
            $('#input_pathway').val(content);

            $('#modal_select_pathway').modal('hide');

            $('#btn_download_pathway').show().attr('href', 'kegg/xml_png/' + content + '.png');";

            break;
        }
    }
}

?>



        var options = {
            url: 'exe.php?action=show_kegg_diagram',
            type: 'post',
            beforeSubmit: function(formData, jqForm, options) {
                $('#btn_busy').removeClass('hidden');
                return true;
            },
            success: function(response){
                $('#btn_busy').addClass('hidden');

                $('#div_results').html(response);

                $('#resultTable').DataTable({
                    "dom": 'Blfrtip',
                    "buttons": ['colvis','copy','csv'],
                    'pageLength': 100, 'lengthMenu': [[10, 100, 500, 1000], [10, 100, 500, 1000]],
                    "order": [ [0, 'desc'] ],
                    "columnDefs": [ { type: 'natural', targets: 0 } ]
                });

                return true;
            }
        };
        $('#form_show_pathway').ajaxForm(options);

    }); // End of $(document).ready(function() {

</script>

</body>
</html>