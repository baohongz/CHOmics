<?php
include_once("config.php");

?><!DOCTYPE html>
<html lang="en">
<head>
	<?php include_once($BXAF_CONFIG['BXAF_PAGE_HEADER']); ?>
	<script src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/jquery/jquery.form.min.js"></script>
</head>

<body>
    <?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_MENU'])) include_once($BXAF_CONFIG['BXAF_PAGE_MENU']); ?>
    <div id="bxaf_page_wrapper" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_WRAPPER']; ?>">
    <?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_LEFT'])) include_once($BXAF_CONFIG['BXAF_PAGE_LEFT']); ?>
    <div id="bxaf_page_right" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT']; ?>">
    <div id="bxaf_page_right_content" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT_CONTENT']; ?>">
    <div class="container-fluid">

    <?php $help_key = 'Import Project Data'; include_once( dirname(__DIR__) . "/help_content.php"); ?>

	<p><a href="index.php" class=""><i class="fas fa-question-circle"></i> Detailed Explanation of File Formats</a> <a href="data_import_adv.php" class="ml-3"><i class="fas fa-angle-double-right"></i> Import Files with Flexible Formats</a></p>

    <p class="text-danger">Note: You can upload one or more files from one or multiple projects. You DON'T have to upload all five files.</p>

    <hr />
    <form id="form_submit">

        <div class="my-3 w-100">
            <div class="form-row my-2">
                <div class="col text-right" style="max-width: 15rem;">
                    <label class="font-weight-bold">Projects: </label>
                </div>
                <div class="col" style="">
                    <input class="file_selection" type="file" class="" id="file_projects" name="file_projects">
                    <a class="ml-5" href='import/file_projects.csv'><i class="fas fa-caret-right" aria-hidden="true"></i> Example File</a>
                </div>
            </div>

            <div class="form-row my-2">
                <div class="col text-right" style="max-width: 15rem;">
                    <label class="font-weight-bold">Samples: </label>
                </div>
                <div class="col">
                    <input class="file_selection" type="file" class="" id="file_samples" name="file_samples">
                    <a class="ml-5" href='import/file_samples.csv'><i class="fas fa-caret-right" aria-hidden="true"></i> Example File</a>
                </div>
            </div>

            <div class="form-row my-2">
                <div class="col text-right" style="max-width: 15rem;">
                    <label class="font-weight-bold">Comparisons: </label>
                </div>
                <div class="col" style="">
                    <input class="file_selection" type="file" class="" id="file_comparisons" name="file_comparisons">
                    <a class="ml-5" href='import/file_comparisons.csv'><i class="fas fa-caret-right" aria-hidden="true"></i> Example File</a>
                </div>
            </div>

            <div class="form-row my-2">
                <div class="col text-right" style="max-width: 15rem;">
                    <label class="font-weight-bold">Sample Expression Data: </label>
                </div>
                <div class="col">
                    <input class="file_selection" type="file" class="" id="file_expression_data" name="file_expression_data">
                    <a class="ml-5" href='import/file_expression_data.csv'><i class="fas fa-caret-right" aria-hidden="true"></i> Example File 1</a>
					<a class="ml-3" href='import/file_expression_data1.csv'><i class="fas fa-caret-right" aria-hidden="true"></i> Example File 2</a>
                </div>
            </div>

            <div class="form-row my-2">
                <div class="col text-right" style="max-width: 15rem;">
                    <label class="font-weight-bold">Comparison Data: </label>
                </div>
                <div class="col">
                    <input class="file_selection" type="file" class="" id="file_comparison_data" name="file_comparison_data">
                    <a class="ml-5" href='import/file_comparison_data.csv'><i class="fas fa-caret-right" aria-hidden="true"></i> Example File 1</a>
					<a class="ml-3" href='import/file_comparison_data1.csv'><i class="fas fa-caret-right" aria-hidden="true"></i> Example File 2</a>
                </div>
            </div>

			<div class="form-row my-2">
                <div class="col text-right" style="max-width: 15rem;">
                    <label class="font-weight-bold">&nbsp; </label>
                </div>
                <div class="col form-check form-check-inline">
					<input class="form-check-input" type="checkbox" name="chk_update" id="chk_update" value="1">
					<label class="form-check-label" for="chk_update">Update if sample expression data or comparison data already imported</label>
                </div>
            </div>

            <div class="form-row my-2">
                <div class="col text-right" style="max-width: 15rem;">
                    <label class="font-weight-bold">&nbsp; </label>
                </div>
                <div class="col">
                    <button type="submit" class="btn btn-primary" id="btn-submit">
                        <i class="fas fa-upload"></i> Submit
                    </button>
					<a class="ml-2" href='Javascript: void(0);' onclick="$('.file_selection').val(''); $('#div_results').html('');"><i class="fas fa-times" aria-hidden="true"></i> Clear Selected Files</a>
                </div>
            </div>

            <div class="w-100 my-5">
                <div id="div_results"></div>
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
        var options = {
            type: 'post',
            url: 'exe.php?action=upload_data_info',
            beforeSubmit: function(formData, jqForm, options) {
                $('#btn-submit')
                    .children(':first')
                    .removeClass('fa-upload')
                    .addClass('fa-spin fa-spinner');
                return true;
            },
            success: function(response) {
                $('#btn-submit')
                    .children(':first')
                    .addClass('fa-upload')
                    .removeClass('fa-spin fa-spinner');

                if (response.type == 'Error') {
                    $('#div_results').html("<h2 class='text-danger'><i class='fas fa-exclamation-triangle'></i> Import Failed</h2><hr /><div class='my-3'>" + response.detail + "</div>");
                }
                else {
                    $('#div_results').html("<h2 class='text-success'><i class='fas fa-check-double'></i> Import Completed Successfully</h2><hr /><div class='my-3'>" + response.detail + "</div>");
                }

                return true;
            }
        };
        $('#form_submit').ajaxForm(options);
    });

</script>
</body>
</html>