<?php
include_once("config.php");

if (isset($_GET['action']) && $_GET['action'] == 'save_my_list') {

    $uniqueID = md5(microtime(true));
    $_SESSION['SAVED_LIST'][$uniqueID] = explode(",", $_POST['list_ids']);

    echo $uniqueID;

    exit();
}


$analysis_files = '';
if (isset($_GET['analysis'])) {
    $current_analysis = trim($_GET['analysis']);
    $analysis_id = intval(array_shift(explode("_", $current_analysis)));

    $comparisons = array();
    if($analysis_id > 0 && isset($_GET['comp']) && $_GET['comp'] != ''){
        $comparisons = explode(",", trim($_GET['comp']));
    }
    else if($analysis_id > 0){
        $sql = "SELECT `Comparisons` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_ANALYSIS']}` WHERE `ID`=" . $analysis_id;
        $comparisons = unserialize($BXAF_MODULE_CONN -> get_one($sql));
    }
    foreach($comparisons as $c){
        $file = $BXAF_CONFIG['ANALYSIS_DIR'] . "{$current_analysis}/alignment/DEG/{$c}/Overview/{$c}_alldata.csv";
        if(file_exists($file)) $analysis_files[$c] = bxaf_encrypt($file, $BXAF_CONFIG['BXAF_KEY']);
    }
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include_once($BXAF_CONFIG['BXAF_PAGE_HEADER']); ?>

    <script type="text/javascript" src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/jquery/jquery.form.min.js"> </script>

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

    <?php $help_key = 'Significantly Changed Genes'; include_once( dirname(__DIR__) . "/help_content.php"); ?>

    <div class="w-100 my-3">
        <form class="my-3" id="form_show_pathway" enctype="multipart/form-data" method="post" style="max-width: 70rem;">

            <?php include_once(dirname(__DIR__) . '/tool_save_lists/modal_comparison.php'); ?>

            <div class="form-group w-100 my-3 ">
                <label class="font-weight-bold">Or, upload your comparison files:</label>
                <input type="file" class="" id="comparison_file" name="comparison_file" onchange="$('#Comparison_List').val('');">
                <a href="files/demo.csv"> <i class="fas fa-angle-double-right"></i> Demo Data </a>
            </div>

            <div class="w-100 form-inline mt-4">
                <label class="form-check-label font-weight-bold mr-3">Display Options: </label>

                <input class="form-check-input mr-2" type="checkbox" name="Display_Options[]" value="Log2FoldChange" checked>
                <label class="form-check-label mr-2">Log2FC</label>

                <input class="form-check-input mr-2" type="checkbox" name="Display_Options[]" value="PValue" checked>
                <label class="form-check-label mr-2">P.Value</label>

                <input class="form-check-input mr-2" type="checkbox" name="Display_Options[]" value="AdjustedPValue" checked>
                <label class="form-check-label mr-2">FDR</label>
            </div>

            <div class="w-100 form-inline my-3">
                <label class="form-check-label font-weight-bold mr-3">Fold Change Cutoff: </label>
                <select class="custom-select mr-2" id="fc_cutoff" name="fc_cutoff" style="width:10rem;">
                    <option value="2">2</option>
                    <option value="4">4</option>
                    <option value="8">8</option>
                    <option value="">Enter Value</option>
                </select>
                <input class="form-control mr-2" id="fc_custom" name="fc_custom" placeholder="Custom Cutoff" value="" style="width:10rem; display: none;">

                <select class="custom-select mr-2" id="fc_direction" name="fc_direction" style="width:20rem;">
                    <option value="Up">Up-regulated Only</option>
                    <option value="Down">Down-regulated Only</option>
                    <option value="" selected>Both Up- and Down-regulated</option>
                </select>

            </div>

            <div class="w-100 form-inline my-2">
                <label class="form-check-label font-weight-bold mr-3">Statistic Cutoff: </label>
                <select class="custom-select mr-3" name="statistic_field" name="statistic_field" style="width: 8rem;">
                    <option value="PValue">P.Value</option>
                    <option value="AdjustedPValue" selected>FDR</option>
                </select>
                <label class="font-weight-bold mr-3" style="font-size: 26px;"> &le; </label>
                <select class="custom-select mr-2" id="statistic_cutoff" name="statistic_cutoff" style="width:8em;">
                  <option value="0.05" selected>0.05</option>
                  <option value="0.01">0.01</option>
                  <option value="0.001">0.001</option>
                  <option value="">Enter Value</option>
                </select>
                <input class="form-control mr-2" id="statistic_custom" name="statistic_custom" placeholder="Custom Cutoff" style="width:10rem; display: none;">

            </div>

            <div class="w-100 form-inline mt-4">
                <label class="form-check-label font-weight-bold mr-3">List Genes: </label>

                <input class="form-check-input mr-2" type="radio" name="List_Genes" value="Common" checked>
                <label class="form-check-label mr-2">Common Genes from All Comparisons</label>

                <input class="form-check-input mr-2" type="radio" name="List_Genes" value="Any">
                <label class="form-check-label mr-2">Genes from Any Comparisons</label>

            </div>

            <div class="w-100 my-3">
                <button id="btn_submit" type='submit' class="btn btn-primary"> Submit </button>
                <label class="mx-2 hidden" id="btn_busy"><i class="fas fa-pulse fa-spinner"></i></label>
                <a class="mx-3" href="<?php echo $_SERVER['PHP_SELF']; ?>"><i class="fas fa-sync"></i> Start Over</a>
            </div>
        </form>

        <div class="w-100 my-5">
            <div id="div_results" class="w-100"></div>
            <div id="div_debug" class="w-100 my-3"></div>
        </div>

    </div>


</div>
</div>
<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>
</div>
</div>




<script>

    $(document).ready(function() {

        $(document).on('blur', '#Comparison_List', function() {
    		$('#comparison_file').val('');
    	});

        $(document).on('change', '#fc_cutoff, #statistic_cutoff', function() {
            if( $(this).val() == ''){
                $(this).next().show().focus();
            }
            else {
                $(this).next().hide();
            }
    	});


        // // Check/Uncheck All
        $(document).on('change', '.bxaf_checkbox', function() {
            if($(this).hasClass('bxaf_checkbox_all')){
                $('.bxaf_checkbox_one').prop ('checked', $(this).is(':checked') );
            }
            else if( $(this).hasClass('bxaf_checkbox_one') ){
                var checked = true;
                $('.bxaf_checkbox_one').each(function(index, element) {
                    if (! element.checked ) checked = false;
                });
                $('.bxaf_checkbox_all').prop ('checked', checked);
            }
        });

        // Save session list
        $(document).on('click', '.btn_gene_actions', function() {

            var rowid = '';
            $('.bxaf_checkbox_one').each(function(index, element) {
                if ( element.checked ) {
                    if(rowid == '') rowid = $(element).attr('rowid');
                    else rowid += ',' + $(element).attr('rowid');
                }
            });
            if (rowid == ''){
                rowid = $('#input_all_gene_ids').val();
            }

            if (rowid != ''){
                var new_url = $(this).attr('action_type') + '&time=';

        		$.ajax({
        			type: 'POST',
        			url: '<?php echo $_SERVER['PHP_SELF']; ?>?action=save_my_list',
        			data: { 'list_ids': rowid },
        			success: function(res) {
                        window.open( new_url + res);
        			}
        		});
            }

        });

        // Save session list
        $(document).on('click', '.btn_comparison_actions', function() {
            window.open( $(this).attr('action_type') + '&comparison_ids=' + $('#input_all_comparison_ids').val() );
        });


        var options = {
            url: 'exe.php?action=show_changed_genes',
            type: 'post',
            beforeSubmit: function(formData, jqForm, options) {
                $('#btn_busy').removeClass('hidden');
                return true;
            },
            success: function(response){
                $('#btn_busy').addClass('hidden');

                $('#div_results').html(response);

                $('#resultTable').DataTable({"pageLength": 10, "lengthMenu": [[10, 100, 500, 1000], [10, 100, 500, 1000]], "dom": 'Blifrtip', "buttons": ['colvis','copy','csv'], "order": [[ 1, 'asc' ]], "columnDefs": [ { "targets": 0, "orderable": false } ] });

                return true;
            }
        };
        $('#form_show_pathway').ajaxForm(options);

    }); // End of $(document).ready(function() {

</script>

</body>
</html>