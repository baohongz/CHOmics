<?php
include_once("config.php");

// Testing ...
// $names = array('GSE25401', 'GSE61260', 'GSE18897');
// $sql = "SELECT `ID`, `Name` FROM `".$BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS']."` WHERE `Name` IN (?a)";
// $existing_names = $BXAF_MODULE_CONN->GetAssoc($sql, $names);
// echo "$sql<pre>" . print_r($existing_names, true) . "</pre>"; exit();

// $url = "https://www.ncbi.nlm.nih.gov/geo/query/acc.cgi?acc=GPL570&targ=self&form=text&view=quick";
// // $content = file_get_contents($url);
// $content = file_get_contents($url, false, NULL, 0, 4096);
// echo "$url<pre>" . print_r($content, true) . "</pre>"; exit();

$TIME = time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<?php include_once($BXAF_CONFIG['BXAF_PAGE_HEADER']); ?>
	<script src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/jquery/jquery.form.min.js"></script>

</head>

<body>
    <!-------------------------------------------------------------------------------------------------->
    <!-- Page Header -->
    <!-------------------------------------------------------------------------------------------------->
    <?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_MENU'])) include_once($BXAF_CONFIG['BXAF_PAGE_MENU']); ?>
    <div id="bxaf_page_wrapper" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_WRAPPER']; ?>">
    <?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_LEFT'])) include_once($BXAF_CONFIG['BXAF_PAGE_LEFT']); ?>
    <div id="bxaf_page_right" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT']; ?>">
    <div id="bxaf_page_right_content" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT_CONTENT']; ?>">
    <!-------------------------------------------------------------------------------------------------->
    <!-------------------------------------------------------------------------------------------------->

    <h1 class="my-3">Fetch Platform Records from <a href="https://www.ncbi.nlm.nih.gov/geo/browse/?view=platforms" target="_blank">GEO</a></h1>

    <form id="form_submit">
        <input name="time" value="<?php echo $TIME; ?>" hidden />
<!-- 
		<label class="ml-2">
			<input type="radio" name="Species" value="Human" checked /> Human
		</label>
		<label class="ml-2">
			<input type="radio" name="Species" value="Mouse" /> Mouse
		</label>
		<label class="ml-2">
			<input type="radio" name="Species" value="Rat" /> Rat
		</label>
 -->
        <div class="my-2"><strong>Platform Accession Names</strong> (e.g., GPL24263, enter one name per row):</div>
        <textarea class="form-control" name="platform_names" style="height:200px;" required></textarea>

		<div class="my-2">
			<button type="submit" class="btn btn-primary mt-2" id="btn-submit">
	            <i class="fas fa-upload"></i> Submit
	        </button>
			<span class="text-muted">Note: this program may take long time to finish. Please paste only a few names at a time.</span>
		</div>

    </form>

    <div class="w-100" id="callback_info"></div>



    <!-------------------------------------------------------------------------------------------------->
    <!-- Page Footer -->
    <!-------------------------------------------------------------------------------------------------->
    </div>
    <?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>
    </div>
    </div>
    <!-------------------------------------------------------------------------------------------------->
    <!-------------------------------------------------------------------------------------------------->

<script>
    $(document).ready(function() {
        var options = {
            type: 'post',
            url: 'exe.php?action=fetch_platform_info',
            beforeSubmit: function(formData, jqForm, options) {
                $('#btn-submit').attr('disabled', '')
                    .children(':first')
                    .removeClass('fa-upload')
                    .addClass('fa-spin fa-spinner');

				$('#callback_info').html('');

				return true;
            },
            success: function(res) {

                $('#btn-submit').removeAttr('disabled')
                    .children(':first')
                    .addClass('fa-upload')
                    .removeClass('fa-spin fa-spinner');
                // console.log(res);
                if (typeof res == 'string' && res.substring(0, 5) == 'Error') {
                    bootbox.alert(res);
                } else {
                    callback_info(res);
                }
                return true;
            }
        };
        // bind form using 'ajaxForm'
        $('#form_submit').ajaxForm(options);

    });

    function callback_info(res) {
        // console.log(res);
		var content = '';

        var data = res.platforms_found;

		if(data.length > 0){
	        content = '<h4 class="mt-3 text-success">New Platforms Retrieved:</h4>';
	        content += '<table id="myDataTable" class="table table-bordered table-striped">';
	        content += '    <thead>';
	        content += '        <tr class="table-info">';
	        content += '            <th>GEO_Accession</th>';
	        content += '            <th>Title</th>';
	        content += '            <th>Species</th>';
	        content += '            <th>Type</th>';
	        content += '            <th>Distribution</th>';
	        content += '            <th>Manufacturer</th>';
	        content += '            <th>TaxID</th>';
	        content += '            <th>Submission_Date</th>';
	        content += '        </tr>';
	        content += '    </thead>';
	        content += '    <tbody>';

	        for (var i = 0; i < data.length; i++) {
	            content += '<tr>';
	            content += '    <td>' + data[i]['GEO_Accession'] + '</td>';
	            content += '    <td>' + data[i]['Title'] + '</td>';
	            content += '    <td>' + data[i]['Species'] + '</td>';
	            content += '    <td>' + data[i]['Type'] + '</td>';
	            content += '    <td>' + data[i]['Distribution'] + '</td>';
	            content += '    <td>' + data[i]['Manufacturer'] + '</td>';
	            content += '    <td>' + data[i]['TaxID'] + '</td>';
	            content += '    <td>' + data[i]['Submission_Date'] + '</td>';
	            content += '</tr>';
	        }

	        content += '    </tbody>';
	        content += '</table>';
		}

		data = res.platforms_not_found;
		if(data.length > 0){
			content += '<div class="my-3">';
			content += '<h4 class="my-3 text-danger">Platforms Not Found: </h4>';
			for (var i = 0; i < data.length; i++) {
				content += '<span class="mx-3">' + data[i] + '</span> ';
			}
			content += '</div>';
		}

		data = res.platforms_exist;
		if(data.length > 0){
			content += '<div class="my-3">';
			content += '<h4 class="my-3 text-warning">Platforms Already in the Database (Not Retrieved): </h4>';
			for (var i = 0; i < data.length; i++) {
				content += '<span class="mx-3">' + data[i] + '</span> ';
			}
			content += '</div>';
		}

        $('#callback_info').html(content);
    }
</script>
</body>
</html>