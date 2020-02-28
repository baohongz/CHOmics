<?php

include_once(dirname(__FILE__) . "/config.php");

$current_dir_encrypted = '';
$CURRENT_DIR = '';

if(isset($_GET['f']) && trim($_GET['f']) != ''){
	$current_dir_encrypted = $_GET['f'];
	$CURRENT_DIR = rtrim(bxaf_decrypt($current_dir_encrypted, $BXAF_ENCRYPTION_KEY), '/');
}
else {
	$CURRENT_DIR = rtrim($DESTINATION_SUBFOLDER_DIR, '/');
	$current_dir_encrypted = bxaf_encrypt($CURRENT_DIR, $BXAF_ENCRYPTION_KEY);
}

if(strpos($CURRENT_DIR, $DESTINATION_SUBFOLDER_DIR) !== 0){
	$CURRENT_DIR = $DESTINATION_SUBFOLDER_DIR;
}
if(! file_exists($CURRENT_DIR)){
	mkdir($CURRENT_DIR, 0777, true);
}

// echo $CURRENT_DIR . "<BR>" . $DESTINATION_SUBFOLDER_DIR . "<BR>";

?><!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="Description" content="<?php echo $BXAF_CONFIG['BXAF_PAGE_DESCRIPTION']; ?>">
	<meta name="Keywords" content="<?php echo $BXAF_CONFIG['BXAF_PAGE_KEYWORDS']; ?>">
	<meta name="author" content="<?php echo $BXAF_CONFIG['BXAF_PAGE_AUTHOR'];  ?>">
	<link rel="shortcut icon" href="<?php echo $BXAF_CONFIG['BXAF_SYSTEM_URL']; ?>favicon.ico">
	<title><?php echo $BXAF_CONFIG['BXAF_PAGE_TITLE']; ?></title>

	<script src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/jquery/jquery.min.js"></script>
	<script src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/jquery/jquery.form.min.js"></script>

	<link   href='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/fontawesome/css/all.min.css' rel='stylesheet' type='text/css'>

	<link href="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<script src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/bootstrap/js/bootstrap.min.js"></script>
	<script src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/jquery/bootbox.min.js"></script>

	<link rel="stylesheet" href="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/ztree/css/zTreeStyle/zTreeStyle.css" type="text/css">
	<script type="text/javascript" src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/ztree/js/jquery.ztree.core.js"></script>
	<script type="text/javascript" src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/ztree/js/jquery.ztree.excheck.js"></script>
	<script type="text/javascript" src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/ztree/js/jquery.ztree.exedit.js"></script>


	<script type="text/javascript" src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/jquery/jquery.textarea_tabby.min.js"></script>

	<link rel="stylesheet" href="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/dropzone/dropzone.css">
	<script src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/dropzone/dropzone.js"></script>

	<link   href='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/datatables/datatables_all_extensions.min.css' rel='stylesheet' type='text/css'>
	<script src='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/datatables/datatables_all_extensions.min.js'></script>

	<link   href='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>css/page.css' rel='stylesheet' type='text/css'>
	<script src='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>js/page.js'></script>


<style>
	.dropzone{
		text-align: center;
		width: 100% !important;
		background-color: #F3F4F5;
		color:#656C7F;
		border: 2px d28Aashed #0F4;
		border-radius: 5px;
	}

	.file_btn{
		text-align: center;
		width: 100%;
		font-weight: 300;
		padding-top: 1rem;
	}
	.dropzone .dz-message{
		margin: 0rem !important;
	}
	.dropzone a{
		cursor: pointer;
	}
	.fix_width_font{
		font-family: monospace;
		font-size: 0.8rem;
	}
	.fix_width_font br {
	   display: block;
	   margin: -5px 0;
	   content: "";
	}
</style>

<script type="text/javascript">
var setting = {
	async: {
		enable: true,
		url:"get_nodes.php",
		autoParam:["id", "name=n", "level=lv", "path=path"],
//		otherParam:{"otherParam":""},
		dataFilter: filter
	}
};

function filter(treeId, parentNode, childNodes) {
	if (!childNodes) return null;
	for (var i=0, l=childNodes.length; i<l; i++) {
		childNodes[i].name = childNodes[i].name.replace(/\.n/g, '.');
	}
	return childNodes;
}


$(document).ready(function(){

	$.fn.zTree.init($("#treeFolders"), setting);

	$('.dataTable').DataTable({
		dom: 'Blfrtip',
		buttons: [ 'colvis','csv','excel','pdf','print'],
		"aoColumnDefs": [
			{ "bSortable": false, "aTargets": [ 0, 5 ] }
		],
		"aaSorting": [],
		"order": [[ 1, "asc" ]]
	});


});



$(document).ready(function(){

	$(document).on('click', '#upload_file_btn3', function(){
		$('.dropzone').trigger('click');
	});
	$('#modal_confirm_batch_upload').on('hidden.bs.modal', function (e) {
		location.reload(true);
	})

	/******************************************************************
	 * Checkbox
	 ******************************************************************/
	$(document).on('change', '#select_all_checkbox', function(){
		if($('#select_all_checkbox').is(':checked')){
			$('.checkbox_row').prop('checked', true);
		} else {
			$('.checkbox_row').prop('checked', false);
		}
	});
	$(document).on('change', '.checkbox_row, #select_all_checkbox', function(){
		var active = false
		$('.checkbox_row').each(function(index, element){
			if($(element).is(':checked')){
				active = true;
			}
		});
		if(active == true){
			$('.checked_action_button').removeAttr('disabled');
		} else {
			$('.checked_action_button').attr('disabled', '');
		}
	})



	/******************************************************************
	 * Create New Folder
	 ******************************************************************/
	$(document).on('click', '#create_folder_btn', function(){
		$('#modal_confirm_create_folder').modal();
	});
	$(document).on('click', '#confirm_create_folder_btn', function(){
		var folder_name = encodeURIComponent($('#new_folder_name').val());
		var current_dir = encodeURIComponent('<?php echo substr($CURRENT_DIR, strlen($DESTINATION_SUBFOLDER_DIR)); ?>');
		$.ajax({
			method: 'POST',
			url: 'files_exe.php?action=create_folder',
			data: {folder_name: folder_name, current_dir: current_dir},
			success: function(responseText){
				if(responseText.substring(0, 6) == 'Failed' || responseText.substring(0, 5) == 'Error'){
					$('#modal_error_message_content').html(responseText);
					$('#modal_error_message').modal();
					$('#modal_confirm_create_folder').modal('hide');
				} else if(responseText == 1){
					location.reload(true);
				}
			}
		});
	});




	/******************************************************************
	 * Create New File
	 ******************************************************************/
	$(document).on('click', '#create_file_btn', function(){
		$('#modal_confirm_create_file').modal();
	});
	$(document).on('click', '#confirm_create_file_btn', function(){
		var file_name = encodeURIComponent($('#new_file_name').val());
		var file_content = encodeURIComponent($('#new_file_content').val());
		var current_dir = encodeURIComponent('<?php echo substr($CURRENT_DIR, strlen($DESTINATION_SUBFOLDER_DIR)); ?>');
		$.ajax({
			method: 'POST',
			url: 'files_exe.php?action=create_file',
			data: {file_name: file_name, file_content: file_content, current_dir: current_dir},
			success: function(responseText){
				if(responseText.substring(0, 6) == 'Failed' || responseText.substring(0, 5) == 'Error'){
					$('#modal_error_message_content').html(responseText);
					$('#modal_error_message').modal();
					$('#modal_confirm_create_file').modal('hide');
				} else if(responseText == 1){
					location.reload(true);
				}
			}
		});
	});



	/******************************************************************
	 * Edit Txt File
	 ******************************************************************/
	$(document).on('click', '.edit_txt_file_link', function(){
		var file_dir = $(this).attr('file_dir');
		var file_name = $(this).attr('filename');
		$.ajax({
			method: 'POST',
			url: 'files_exe.php?action=get_txt_file_info',
			data: {file_name: file_name, file_dir: file_dir},
			success: function(responseText){
				$('#edit_txt_file_name_in_header').html(file_name);
				$('#modal_edit_txt_file_content').html(responseText);

				$('#modal_edit_txt_file').modal();
				$('#modal_edit_txt_file').on('shown.bs.modal', function (e) {
					$('#edit_txt_file_content').tabby();
				});
			}
		});
	});
	$(document).on('click', '#confirm_edit_txt_file_btn', function(){
		var file_name = $('#edit_txt_file_name').val();
		var file_content = $('#edit_txt_file_content').val();
		var current_dir = $('#edit_txt_file_full_dir').val();
		$.ajax({
			method: 'POST',
			url: 'files_exe.php?action=edit_txt_file',
			data: {file_name: file_name, file_content: file_content, current_dir: current_dir},
			success: function(responseText){
				if(responseText.substring(0, 6) == 'Failed' || responseText.substring(0, 5) == 'Error'){
					$('#modal_error_message_content').html(responseText);
					$('#modal_error_message').modal();
					$('#modal_edit_txt_file').modal('hide');
				} else if(responseText == 1){
					location.reload(true);
				}
			}
		});
	});



	/******************************************************************
	 * Upload File
	 ******************************************************************/
	$(document).on('click', '#upload_file_btn', function(){
		$('#modal_confirm_upload_file').modal();
	});
	$(document).on('click', '#batch_upload_btn', function(){
		$('#modal_confirm_batch_upload').modal();
	});
	var options_upload_file = {
		url: 'files_exe.php?action=upload_files',
		type: 'post',
			beforeSubmit: function(formData, jqForm, options) {
				$('#confirm_upload_file_btn').attr('disabled', '');
				$('#confirm_upload_file_btn').children().first().removeClass('fa-upload').addClass('fa-spin fa-spinner');
		    	return true;
			},
			success: function(responseText, statusText){
				$('#confirm_upload_file_btn').removeAttr('disabled');
				$('#confirm_upload_file_btn').children().first().removeClass('fa-spin fa-spinner').addClass('fa-upload');
				if(responseText.substring(0, 6) == 'Failed' || responseText.substring(0, 5) == 'Error'){
					$('#modal_error_message_content').html(responseText);
					$('#modal_error_message').modal();
					$('#modal_confirm_upload_file').modal('hide');
				} else {
					location.reload(true);
				}
				return true;
			}
	};
	$('#form_upload_file').ajaxForm(options_upload_file);




	/******************************************************************
	 * Rename File
	 ******************************************************************/
	$(document).on('click', '.rename_link', function(){
		$('#confirm_edit_name_content_old_name').html($(this).attr('filename'));
		$('#edit_name_old_name').val($(this).attr('filename'));
		$('#edit_name_path').val($(this).attr('file_dir'));
		$('#modal_confirm_edit_name').modal();
	});
	$(document).on('click', '#confirm_edit_name_btn', function(){
		var new_name = encodeURIComponent($('#edit_name_input').val());
		var file_dir = encodeURIComponent($('#edit_name_path').val());
		var old_name = encodeURIComponent($('#edit_name_old_name').val());
		$.ajax({
			method: 'POST',
			url: 'files_exe.php?action=dirfile_rename',
			data: {new_name: new_name, file_dir: file_dir, old_name: old_name},
			success: function(responseText){
				if(responseText.substring(0, 6) == 'Failed' || responseText.substring(0, 5) == 'Error'){
					$('#modal_error_message_content').html(responseText);
					$('#modal_error_message').modal();
					$('#modal_confirm_edit_name').modal('hide');
				} else if(responseText == 1){
					location.reload(true);
				}
			}
		});
	});




	/******************************************************************
	 * Delete File
	 ******************************************************************/

	// Delete single file
	$(document).on('click', '.delete_link', function(){

		var file_type = $(this).attr('file_type');
		var file_name = $(this).attr('filename');
		var file_dir  = $(this).attr('file_dir');

		bootbox.confirm({
			message: '<h2><i class="fas fa-exclamation-triangle text-warning"></i> Warning</h2><div class="lead p-3 text-danger">Are you sure you want to delete ' + file_type + ' <strong>' + file_name + '</strong>?</div>',
			callback: function (result) {
				if(result){
					$.ajax({
						method: 'POST',
						url: 'files_exe.php?action=dirfile_delete',
						data: {file_dir: file_dir, file_name: file_name, file_type: 'single'},
						success: function(responseText){
							if(responseText.substring(0, 6) == 'Failed' || responseText.substring(0, 5) == 'Error'){
								bootbox.alert('<h2><i class="fas fa-exclamation-triangle text-danger"></i> Alert</h2><div class="lead p-3">' + responseText + '</div>');
							} else {
								location.reload(true);
							}
						}
					});
				}
			}
		});

	});

	// Delete checked files
	$(document).on('click', '#delete_checked_btn', function(){

		var file_dir  = $(this).attr('file_dir');

		var name_list = '';
		$('.checkbox_row').each(function(index, element){
			if($(element).is(':checked')){
				name_list += ':';
				name_list += $(element).attr('filename');
			}
		});

		bootbox.confirm({
			message: '<h2><i class="fas fa-exclamation-triangle text-warning"></i> Warning</h2><div class="lead p-3 text-danger">Are you sure you want to delete checked files/folders?</div>',
			callback: function (result) {
				if(result){
					$.ajax({
						method: 'POST',
						url: 'files_exe.php?action=dirfile_delete',
						data: {file_dir: file_dir, file_name: name_list, file_type: 'checked'},
						success: function(responseText){
							if(responseText.substring(0, 6) == 'Failed' || responseText.substring(0, 5) == 'Error'){
								bootbox.alert('<h2><i class="fas fa-exclamation-triangle text-danger"></i> Alert</h2><div class="lead p-3">' + responseText + '</div>');
							} else {
								location.reload(true);
							}
						}
					});
				}
			}
		});

	});




	/******************************************************************
	 * Move && Copy Checked Files
	 ******************************************************************/

	$(document).on('click', '#move_checked_btn', function(){
		var name_list = '';
		$('.checkbox_row').each(function(index, element){
			if($(element).is(':checked')){
				name_list += ':';
				name_list += $(element).attr('filename');
			}
		});
		$('#move_copy_title').html('Move');
		$('#move_file_dir').val($(this).attr('file_dir'));
		$('#move_file_name').val(name_list);
		$('#confirm_move_file_btn').removeAttr('hidden');
		$('#confirm_copy_file_btn').attr('hidden', '');
		$('#modal_confirm_move_file').modal();
	});

	$(document).on('click', '#copy_checked_btn', function(){
		var name_list = '';
		$('.checkbox_row').each(function(index, element){
			if($(element).is(':checked')){
				name_list += ':';
				name_list += $(element).attr('filename');
			}
		});
		$('.move_copy_title').html('Copy');
		$('#move_file_dir').val($(this).attr('file_dir'));
		$('#move_file_name').val(name_list);
		$('#confirm_copy_file_btn').removeAttr('hidden');
		$('#confirm_move_file_btn').attr('hidden', '');
		$('#modal_confirm_move_file').modal();
	});



	$(document).on('click', '.confirm_copy_move_btn', function(){
		var action = $(this).attr('action');
		var file_dir = $('#move_file_dir').val();
		var file_name = $('#move_file_name').val();
		var move_to_dir = $('#move_to_dir').val();
		$.ajax({
			method: 'POST',
			url: 'files_exe.php?action=dirfile_' + action,
			data: {file_dir: file_dir, file_name: file_name, move_to_dir: move_to_dir},
			success: function(responseText){
				if(responseText.substring(0, 6) == 'Failed' || responseText.substring(0, 5) == 'Error'){
					$('#modal_error_message_content').html(responseText);
					$('#modal_error_message').modal();
					$('#modal_confirm_move_file').modal('hide');
				} else {
					location.reload(true);
				}
			}
		});
	});



	$(document).on('click', '#duplicate_checked_btn', function(){
		var file_dir = $(this).attr('file_dir');
		var name_list = '';
		$('.checkbox_row').each(function(index, element){
			if($(element).is(':checked')){
				name_list += ':';
				name_list += $(element).attr('filename');
			}
		});

		$.ajax({
			method: 'POST',
			url: 'files_exe.php?action=dirfile_duplicate',
			data: {file_dir: file_dir, file_name: name_list},
			success: function(responseText){
				if(responseText.substring(0, 6) == 'Failed' || responseText.substring(0, 5) == 'Error'){
					$('#modal_error_message_content').html(responseText);
					$('#modal_error_message').modal();
				} else {
					location.reload(true);
				}
			}
		});
	});








	$(document).on('click', '#download_checked_btn', function(){

		var file_dir = $(this).attr('file_dir');

		var name_list = '';
		$('.checkbox_row:checked').each(function(index, element){
			name_list += ':';
			name_list += $(element).attr('filename');
		});

		if(name_list == ''){
			$('#modal_error_message_content').html("Please select some folders and files");
			$('#modal_error_message').modal();
		}
		else {

			$.ajax({
				method: 'POST',
				url: 'files_exe.php?action=dirfile_download',
				data: {file_dir: file_dir, file_name: encodeURIComponent(name_list)},
				success: function(responseText){
					if(responseText.substring(0, 6) == 'Failed' || responseText.substring(0, 5) == 'Error'){
						$('#modal_error_message_content').html(responseText);
						$('#modal_error_message').modal();
					} else {
						$('#modal_download_message_content').html(responseText);
						$('#modal_download_message').modal();

						$('#modal_download_message').on('hide.bs.modal', function (e) {

							var zipfile_name = $('#download_zipfile').attr('zipfile_name');

							$.ajax({
								method: 'POST',
								url: 'files_exe.php?action=dirfile_download_delete',
								data: {file_dir: file_dir, file_name: encodeURIComponent(zipfile_name)},
								success: function(responseText){
									location.reload(true);
								}
							});

						})
					}
				}
			});


		}

	});




});
</script>



</head>

<body>
	<?php include_once('../page_menu.php'); ?>



			<?php files_table_view($CURRENT_DIR); ?>


	<?php include_once('component_modal.php'); ?>

</body>
</html>