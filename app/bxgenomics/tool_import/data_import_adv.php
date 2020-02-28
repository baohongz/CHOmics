<?php
include_once("config.php");

$upload_time = date('YmdHis');

$sql = "SELECT `ID`, `Species`, `Type`, `GEO_Accession`, `Name` FROM ?n WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `Species` = ?s ORDER BY `Type` DESC, `Name` ASC";
$platform_info = $BXAF_MODULE_CONN->get_assoc('ID', $sql, $BXAF_CONFIG['TBL_BXGENOMICS_PLATFORMS'], $_SESSION['SPECIES_DEFAULT']);

$sql = "SELECT `ID`, `Species`, `Name` FROM ?n WHERE `bxafStatus` < 5 AND `_Owner_ID` = {$BXAF_CONFIG['BXAF_USER_CONTACT_ID']} AND `Species` = ?s ORDER BY `Name` ASC";
$project_info = $BXAF_MODULE_CONN->get_assoc('ID', $sql, $BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS'], $_SESSION['SPECIES_DEFAULT']);

// "ID"=>"_Platforms_ID",
// "GEO_Accession"=>"Platform",
// "Type"=>"Platform_Type,
// "Name"=>"PlatformName",

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include_once($BXAF_CONFIG['BXAF_PAGE_HEADER']); ?>
    <script src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/jquery/jquery.form.min.js"></script>

    <link href='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/jquery_file_upload/css/jquery.fileupload.css' rel='stylesheet' type='text/css'>

    <script src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/jquery_file_upload/js/vendor/jquery.ui.widget.js"></script>
    <script src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/jquery_file_upload/js/jquery.iframe-transport.js"></script>
    <script src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/jquery_file_upload/js/jquery.fileupload.js"></script>
    <script src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/jquery_file_upload/js/jquery.fileupload-process.js"></script>
    <script src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/jquery_file_upload/js/jquery.fileupload-validate.js"></script>

    <style>
        .column_matches{
            min-width: 10rem;
        }
    </style>
</head>

<body>
<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_MENU'])) include_once($BXAF_CONFIG['BXAF_PAGE_MENU']); ?>
<div id="bxaf_page_wrapper" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_WRAPPER']; ?>">
<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_LEFT'])) include_once($BXAF_CONFIG['BXAF_PAGE_LEFT']); ?>
<div id="bxaf_page_right" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT']; ?>">
<div id="bxaf_page_right_content" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT_CONTENT']; ?>">
<div class="container-fluid">

    <?php $help_key = 'Import Project Data'; include_once( dirname(__DIR__) . "/help_content.php"); ?>

    <p><a href="index.php" class=""><i class="fas fa-question-circle"></i> Detailed Explanation of File Formats</a> <a href="data_import.php" class="ml-3"><i class="fas fa-angle-double-right"></i> Import Files with Fixed Formats</a></p>

    <div class="w-100 my-3">
        <div class="w-100">
            <div class="my-3">
                <span class="btn btn-success fileinput-button">
                    <i class="fas fa-plus"></i>
                    <span>Select a file ...</span>
                    <input id="fileupload" type="file" name="files[]" multiple >
                </span>
                <span class="text-danger ml-2">Note: The file should be in Comma Separated Values (CSV) or Tab Separated Values (TSV) format. The first row must contain column names.</span>
            </div>

            <div id="progress" class="my-3"></div>

            <!--
            <div id="progress" class="my-3 w-50 progress">
                <div class="progress-bar progress-bar-success"></div>
            </div>
             -->
            <div id="files" class="files my-3"></div>
        </div>

    </div>


    <form id="form_submit" class="hidden">

        <div class="w-100 my-3 text-muted" id="fileupload_results"></div>

        <div class="w-100 my-5" id="preview_results"></div>

        <div class="w-100 my-5" id="import_results"></div>

    </form>


    <div class="w-100" id="div_debug"></div>


</div>
</div>
<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>
</div>
</div>



<script>
    $(document).ready(function() {

        var url = 'data_import_adv_exe.php?action=file_upload&file_time=<?php echo $upload_time; ?>';

        $('#fileupload').fileupload({
            url: url,
            dataType: 'json',
            autoUpload: true,
            acceptFileTypes: /(\.|\/)(txt|csv|tsv)$/i,
            maxFileSize: 1024000000
        })
        .on('fileuploadadd', function (e, data) {

            data.context = $('<div/>').appendTo('#files');

            // $.each(data.files, function (index, file) {
            //
            //     var node = $('<p/>').append($('<span/>').text(file.name));
            //     if (!index) {
            //         node.append('<br>').append(uploadButton.clone(true).data(data));
            //     }
            //     node.appendTo(data.context);
            //
            // });

            $('#progress').html('');

            $('#form_submit').removeClass('hidden');

        })
        // .on('fileuploadprocessalways', function (e, data) {
        //
        //     var index = data.index,
        //         file = data.files[index],
        //         node = $(data.context.children()[index]);
        //     if (file.preview) {
        //         node
        //             .prepend('<br>')
        //             .prepend(file.preview);
        //     }
        //     if (file.error) {
        //         node
        //             .append('<br>')
        //             .append($('<span class="text-danger"/>').text(file.error));
        //     }
        //     if (index + 1 === data.files.length) {
        //         data.context.find('button')
        //             .text('Upload')
        //             .prop('disabled', !!data.files.error);
        //     }
        //
        // })
        .on('fileuploadprogressall', function (e, data) {
            var progress = parseInt(data.loaded / data.total * 100, 10);
            // $('#progress .progress-bar').css(
            //     'width',
            //     progress + '%'
            // );
            $('#progress').html( '<span class="text-danger">' + progress + '% uploaded:</span> ');
        })
        .on('fileuploaddone', function (e, data) {

            $('#progress').html('');

            // data.result is the return from server
            if(data.result.type == 'Error'){
                bootbox.alert(data.result.content);
            }
            else {
                $('#fileupload_results').append(data.result.content);
            }

            // var names = data.loaded;
            // for (var key in names) {
            //     if (names.hasOwnProperty(key)) {
            //         $('#fileupload_results').append(key + '<BR>');
            //     }
            // }

            // $.each(data.result.files, function (index, file) {
                // if (file.url) {
                //     var link = $('<a>')
                //         .attr('target', '_blank')
                //         .prop('href', file.url);
                //     $(data.context.children()[index])
                //         .wrap(link);
                //
                // } else if (file.error) {
                //     var error = $('<span class="text-danger"/>').text(file.error);
                //     $(data.context.children()[index])
                //         .append('<br>')
                //         .append(error);
                // }
            // });

        })
        .on('fileuploadfail', function (e, data) {
            $.each(data.files, function (index) {
                var error = $('<span class="text-danger"/>').text('File upload failed.');
                $(data.context.children()[index])
                    .append('<br>')
                    .append(error);
            });
        })
        .prop('disabled', !$.support.fileInput)
            .parent().addClass($.support.fileInput ? undefined : 'disabled');







        // $(document).on('change', 'input[name=file_type]', function() {
        //
        //     var file_type = $('input[name=file_type]:checked').val();
        //     if(file_type == 'Project'){
        //         $('.div_preset_values').removeClass('hidden');
        //         $('#div_set__Projects_ID').addClass('hidden');
        //     }
        //     else if(file_type == 'Sample' || file_type == 'Comparison'){
        //         $('.div_preset_values').removeClass('hidden');
        //         $('#div_set__Platforms_ID').addClass('hidden');
        //     }
        //     else {
        //         $('.div_preset_values').removeClass('hidden');
        //         $('.div_preset_values').addClass('hidden');
        //     }
        //
        //     $('#preview_results').html('');
        //     $('#btn-submit').addClass('hidden');
        //     $('#div_debug').html('');
        // });

        $(document).on('change', '.file_types', function() {

            var platform = $(this).parent().find('.file_platforms');
            var project = $(this).parent().find('.file_projects');

            if($(this).val() == 'Project'){
                if( platform.hasClass('hidden') ) platform.removeClass('hidden');
                if(! project.hasClass('hidden') ) project.addClass('hidden');
            }
            else if($(this).val() == 'Sample' || $(this).val() == 'Comparison'){
                if( project.hasClass('hidden') ) project.removeClass('hidden');
                if(! platform.hasClass('hidden') ) platform.addClass('hidden');
            }
            else {
                if(! project.hasClass('hidden') ) project.addClass('hidden');
                if(! platform.hasClass('hidden')) platform.addClass('hidden');
            }
        });


        $(document).on('click', '.btn-preview', function() {

            $('#preview_results').html('');
            $('#import_results').html('');

            var file_name = $(this).attr('file_name');

            var file_type = '';
            $('.file_types').each(function(index, element) {
                if ( $(element).attr('file_name') == file_name) {
                    file_type = $(element).val();
                }
            });

            var _Platforms_ID = '';
            $('.file_platforms').each(function(index, element) {
                if ( $(element).attr('file_name') == file_name) {
                    _Platforms_ID = $(element).val();
                }
            });
            var _Projects_ID = '';
            $('.file_projects').each(function(index, element) {
                if ( $(element).attr('file_name') == file_name) {
                    _Projects_ID = $(element).val();
                }
            });

            if(! file_type){
                bootbox.alert("<div class=''><h2 class='text-warning'><i class='fas fa-exclamation-triangle'></i> Warning</h2><hr /><div class='my-3 text-danger'>Please select the corresponding file type!</div></div>");
            }
            // else if(file_type == 'Project' && _Platforms_ID == ''){
            //     bootbox.alert("<div class=''><h2 class='text-warning'><i class='fas fa-exclamation-triangle'></i> Warning</h2><hr /><div class='my-3 text-danger'>To import projects, please set the corresponding platform!</div></div>");
            // }
            // else if((file_type == 'Sample' || file_type == 'Comparison') && _Projects_ID == ''){
            //     bootbox.alert("<div class=''><h2 class='text-warning'><i class='fas fa-exclamation-triangle'></i> Warning</h2><hr /><div class='my-3 text-danger'>To import samples and comparisons, you can put them in a project!</div></div>");
            // }
            else {
                $.ajax({
                    type: 'post',
                    url: 'data_import_adv_exe.php?action=preview_data',
                    data: { "file_name": file_name, "file_type": file_type, "_Platforms_ID": _Platforms_ID, "_Projects_ID": _Projects_ID, "file_time": '<?php echo $upload_time; ?>' },
                    success: function(res) {
                        $('#preview_results').html(res);
                        // $('#btn-submit').removeClass('hidden');
                    }
                });
            }

        });


        $(document).on('click', '.btn-remove', function() {

            var parent = $(this).parent().parent();
            var file_name = $(this).attr('file_name');

            $.ajax({
    			type: 'post',
    			url: 'data_import_adv_exe.php?action=remove_file',
                data: { "file_name": file_name, "file_time": '<?php echo $upload_time; ?>' },
    			success: function(res) {
    				parent.html('');
                    parent.addClass('hidden');

                    $('#preview_results').html('');
                    $('#import_results').html('');

    			}
    		});
        });


        var options = {
            type: 'post',
            url: 'data_import_adv_exe.php?action=import_data',
            beforeSubmit: function(formData, jqForm, options) {

                $('#btn-submit').children(':first')
                    .removeClass('fa-upload')
                    .addClass('fa-spin fa-spinner');

                return true;
            },
            success: function(response) {
                $('#btn-submit').children(':first')
                    .addClass('fa-upload')
                    .removeClass('fa-spin fa-spinner');

                // $('#import_results').html(response);

                if (response.type == 'Error') {
                    $('#import_results').html("<h2 class='text-danger'><i class='fas fa-exclamation-triangle'></i> Import Failed</h2><hr /><div class='my-3'>" + response.detail + "</div>");
                }
                else {
                    $('#preview_results').html('');
                    $('#import_results').html("<h2 class='text-success'><i class='fas fa-check-double'></i> Import Completed Successfully</h2><hr /><div class='my-3'>" + response.detail + "</div>");
                }

                return true;
            }
        };

        // bind form using 'ajaxForm'
        $('#form_submit').ajaxForm(options);
    });

</script>
</body>
</html>