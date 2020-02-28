<?php
$BXAF_CONFIG_CUSTOM['PAGE_LOGIN_REQUIRED']	= true;
include_once(dirname(__FILE__) . "/config/config.php");

if(! $_SESSION['BXAF_ADVANCED_USER']){
	header("Location: index.php");
	exit();
}



if (isset($_GET['action']) && $_GET['action'] == 'file_upload'){
    // echo "<pre>" . print_r($_POST, true) . "</pre>"; exit();

	$uploads_dir = __DIR__ . '/files/help';
	if(! file_exists($uploads_dir)){
		mkdir($uploads_dir, 0777, true);
	}

	$uploaded_files = array();
	foreach ($_FILES["files"]["error"] as $key => $error) {
	    if ($error == UPLOAD_ERR_OK) {
	        $tmp_name = $_FILES["files"]["tmp_name"][$key];
	        $name = basename($_FILES["files"]["name"][$key]);

			if(file_exists("$uploads_dir/$name")){

				$sql = "SELECT `ID` FROM `tbl_bxgenomics_help` WHERE `Screenshot` = ?s ORDER BY `Version` DESC";
		        $id = $BXAF_MODULE_CONN->get_one($sql, $name );

				rename("$uploads_dir/$name", "$uploads_dir/{$name}_{$id}" );
			}

	        if(move_uploaded_file($tmp_name, "$uploads_dir/$name")){
				$uploaded_files[] = $name;
			}
	    }
	}

	echo $uploaded_files[0];

    exit();
}

if (isset($_GET['action']) && $_GET['action'] == 'new_help'){
    // echo "<pre>" . print_r($_POST, true) . "</pre>"; exit();


	$message = '';

	if ($_POST['Tool'] == '' || ! array_key_exists($_POST['Tool'], $BXAF_CONFIG['BXGENOMICS_TOOLS'])){
		$message = "Please select the tool.";
	}
	if ($_POST['Short_Description'] == ''){
		$message = "The summary is required and it cannot be empty.";
	}

	if($message == ''){

		$info = array();

		$info['File'] = $BXAF_CONFIG['BXGENOMICS_TOOLS'][ $_POST['Tool'] ];
        $info['Title'] = $_POST['Title'] != '' ? $_POST['Title'] : $_POST['Tool'];
		$info['Short_Description']  = $_POST['Short_Description'];
		$info['Detailed_Description'] = $_POST['Detailed_Description'];
		$info['Screenshot']   = $_POST['Screenshot'];

		$sql = "SELECT MAX(`Version`) FROM `tbl_bxgenomics_help` WHERE `File` = ?s AND `bxafStatus` < 5";
        $version = 1 + intval( $BXAF_MODULE_CONN->get_one($sql, $info['File']) );

        $time = date('Y-m-d H:i:s');
		$info['Version']       = $version;
        $info['_Owner_ID']     = $BXAF_CONFIG['BXAF_USER_CONTACT_ID'];
        $info['Time_Added']    = $time;
        $info['Status_By']     = $BXAF_CONFIG['BXAF_USER_CONTACT_ID'];
        $info['Status_Time']   = $time;
		$info['bxafContact']   = $BXAF_CONFIG['BXAF_USER_CONTACT_ID'];
        $info['bxafStatus']    = 0;

		// if(isset($_POST['ID']) && intval($_POST['ID']) > 0){
		// 	$help_id = intval($_POST['ID']);
		// }
		// else {
		// 	$sql = "SELECT `ID` FROM `tbl_bxgenomics_help` WHERE `File` = ?s AND `bxafStatus` < 5 ORDER BY `Version` DESC";
	    //     $help_id = intval( $BXAF_MODULE_CONN->get_one($sql, $info['File']) );
		// }
		//
		//
		// if (intval($help_id) > 0){
		// 	$BXAF_MODULE_CONN->update('tbl_bxgenomics_help', $info, "`ID`=$help_id" );
		// 	// $message = "The help has been updated.";
		// }
		// else {
		// 	$help_id = $BXAF_MODULE_CONN->insert('tbl_bxgenomics_help', $info);
		// 	// $message = "The help has been saved.";
		// }

		$help_id = $BXAF_MODULE_CONN->insert('tbl_bxgenomics_help', $info);

// echo "<pre>" . print_r($_POST, true) . "</pre>";
// echo $BXAF_MODULE_CONN->last_query();

	}

	echo $message;

    exit();
}




$update = false;
$help_info = array();
if (isset($_GET['id']) && intval($_GET['id']) > 0){
    $sql = "SELECT * FROM `tbl_bxgenomics_help` WHERE `ID` = ?i";
	$help_info = $BXAF_MODULE_CONN->get_row($sql, intval($_GET['id']) );
    if(is_array($help_info) && count($help_info) > 0) $update = true;
}

$sql = "SELECT `File`, `ID` FROM `tbl_bxgenomics_help` WHERE `bxafStatus` < 5 ORDER BY `Version` ASC";
$help_ids = $BXAF_MODULE_CONN->get_assoc('File', $sql );
$BXAF_CONFIG['BXGENOMICS_TOOLS_FLIP'] = array_flip($BXAF_CONFIG['BXGENOMICS_TOOLS']);
// echo "<pre>" . print_r($help_info, true) . "</pre>";

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<?php include_once($BXAF_CONFIG['BXAF_PAGE_HEADER']); ?>
</head>
<body>
	<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_MENU'])) include_once($BXAF_CONFIG['BXAF_PAGE_MENU']); ?>
	<div id="bxaf_page_wrapper" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_WRAPPER']; ?>">
		<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_LEFT'])) include_once($BXAF_CONFIG['BXAF_PAGE_LEFT']); ?>
		<div id="bxaf_page_right" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT']; ?>">
			<div id="bxaf_page_right_content" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT_CONTENT']; ?>">



				<div class="container-fluid">

					<h1 class="w-100 my-3">Manage Help Contents</h1>
					<hr class="w-100" />

					<?php
						if(is_array($help_ids) && count($help_ids) > 0){
							echo "<div class='w-100 my-1'> <label class='font-weight-bold mr-2'>Update Help Contents: </label> ";
							foreach($help_ids as $file=>$id){
								echo "&bull; <a class='mr-2' href='" . $_SERVER['PHP_SELF'] . "?id=$id'>" . $BXAF_CONFIG['BXGENOMICS_TOOLS_FLIP'][$file] . "</a>";
							}
							echo '</div>';
							echo '<hr class="w-100" />';
						}
					?>

					<form id="form_new_help" enctype="multipart/form-data">

				        <div class="w-100 my-3 form-inline">
			                <label class="font-weight-bold">* Available Tool:</label>
							<select class="custom-select mx-3" id="Tool" name="Tool" onclick="">
								<option value=''>(Select a tool)</option>
									<?php
										foreach($BXAF_CONFIG['BXGENOMICS_TOOLS'] as $name=>$u){
											echo "<option value='$name' " . ( ($update && $u == $help_info['File']) ? "selected" : "") . ">$name ($u)</option>";
										}
									?>
							</select>
				        </div>

				        <div class="w-100 my-3 form-group">
			                <label class="font-weight-bold">* Summary:</label>
			                <textarea class='form-control' id="Short_Description" name='Short_Description' placeholder='* Short Summary (required)'  autofocus required><?php echo $update ? $help_info['Short_Description'] : ""; ?></textarea>
				        </div>

				        <div class="w-100 my-3 form-inline">
			                <label class="font-weight-bold mr-3">Page Title:</label>
			                <input type='text' class='form-control w-50' id='Title' name='Title' placeholder='Page Title' value='<?php echo $update ? $help_info['Title'] : ""; ?>'>
				        </div>

						<div class="w-100 my-3 form-inline">
							<label class="font-weight-bold mr-3">Screenshot:</label>
							<input type='hidden' id='Screenshot' name='Screenshot' value='<?php echo $update ? $help_info['Screenshot'] : ""; ?>'>

							<span id="label_Screenshot" class="font-weight-bold text-success mx-2"><?php echo $update ? $help_info['Screenshot'] : "(Not uploaded yet)"; ?></span>
							<span id="progress" class="ml-2 mr-5 text-muted"></span>

							<input id="fileupload" type="file" name="files[]">
							<script src="library/jquery.ui.widget.js"></script>
							<script src="library/jquery.iframe-transport.js"></script>
							<script src="library/jquery.fileupload.js"></script>
							<script>
								$(function () {
								    $('#fileupload').fileupload({
										url: '<?php echo $_SERVER['PHP_SELF']; ?>?action=file_upload',
										// dataType: 'json',
								        done: function (e, data) {
											$('#Screenshot').val(data.result);
											$('#label_Screenshot').html(data.result);
								        },
										progressall: function (e, data) {
											var progress = parseInt(data.loaded / data.total * 100, 10);
											$('#progress').html(progress + '% uploaded');
										}
								    });
								});
							</script>
				        </div>

				        <div class="w-100 my-3 form-group">
			                <label class="font-weight-bold">Detailed Help (with format):</label>
			                <textarea class='form-control summernote' id="Detailed_Description" name='Detailed_Description' placeholder='Details'><?php echo $update ? $help_info['Detailed_Description'] : ""; ?></textarea>
				        </div>

				        <div class="w-100 my-3 form-group">
				            <?php if($update) echo "<input type='hidden' id='ID' name='ID' value='" . $help_info['ID'] . "'>"; ?>
				            <button type='submit' class='btn btn-success'><i class='fas fa-save'></i> <?php echo $update ? "Update" : "Save"; ?></button>
				            <button type='reset' class='btn btn-outline-success mx-2'><i class='fas fa-undo-alt'></i> Clear</button>
				        </div>

				    </form>

					<div id="div_debug"></div>

				</div>


            </div>
		    <?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>
		</div>
	</div>



<script src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/jquery/jquery.form.min.js"></script>
<link  href="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/summernote/summernote-bs4.css.php" rel="stylesheet">
<script src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/summernote/summernote-bs4.min.js.php"></script>

<script>
	$(document).ready(function() {
		$('.summernote').summernote({
            // placeholder: 'Event details with formats (optional)',
            tabsize: 2,
            height: 250,
            // fontNames: ['Arial', 'Arial Black', 'Comic Sans MS', 'Courier New'],
            toolbar: [
                ['font', ['fontname', 'fontsize', 'strikethrough', 'superscript', 'subscript']],
                ['style', ['bold', 'italic', 'underline', 'clear', 'color']],
                ['para', ['style', 'ul', 'ol', 'paragraph', 'height']],
                ['media', ['picture', 'link', 'video', 'table', 'hr']],
                ['misc', ['fullscreen', 'codeview', 'undo', 'redo', 'help']]
            ]
        });


		var options_help = {
			url: '<?php echo $_SERVER['PHP_SELF']; ?>?action=new_help',
			type: 'post',
			beforeSubmit: function(formData, jqForm, options) {
	            if($('#Tool').val() == ''){
	                bootbox.alert('<h2><i class="fas fa-exclamation-triangle text-warning"></i> Warning</h2><div class="lead p-3">Please select a tool.</div>');
	                return false;
	            }
				return true;
			},
			success: function(response){
				// bootbox.alert(response);

	            if (response != '') {
	                bootbox.alert(response);
	            }
	            else {
	                bootbox.alert('The help content has been saved.', function() {
	                    window.location = '<?php echo basename($_SERVER['PHP_SELF']); ?>';
	                });
	            }
				return true;
			}
		};
		$('#form_new_help').ajaxForm(options_help);
	});
</script>

</body>
</html>