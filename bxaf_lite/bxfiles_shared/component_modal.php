<!-- Error Message -->
<div class="modal fade" id="modal_error_message">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title text-danger">Error</h4>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body" id="modal_error_message_content">

			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-sm btn-primary" data-dismiss="modal">OK</button>
			</div>
		</div>
	</div>
</div>




<!-- Confirm Create Folder -->
<div class="modal fade" id="modal_confirm_create_folder">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title">Create New Folder</h4>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				Please enter the folder name:
				<input class="form-control" id="new_folder_name">
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-sm btn-primary" data-dismiss="modal"><i class="fas fa-undo"></i> Cancel</button>
				<button type="button" class="btn btn-sm btn-success" id="confirm_create_folder_btn"><i class="fas fa-plus-square"></i> Create</button>
			</div>
		</div>
	</div>
</div>





<!-- Confirm Create File -->
<div class="modal fade" id="modal_confirm_create_file">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title">Create New File</h4>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				File name:
				<input class="form-control m-b" id="new_file_name">
				File Content:
				<textarea class="form-control" id="new_file_content"></textarea>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-sm btn-primary" data-dismiss="modal"><i class="fas fa-undo"></i> Cancel</button>
				<button type="button" class="btn btn-sm btn-success" id="confirm_create_file_btn"><i class="fas fa-plus-square"></i> Create</button>
			</div>
		</div>
	</div>
</div>




<!-- Edit Text File -->
<div class="modal fade" id="modal_edit_txt_file">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="edit_txt_file_name_in_header"></h4>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body" id="modal_edit_txt_file_content"></div>
			<div class="modal-footer">
				<button type="button" class="btn btn-sm btn-primary" data-dismiss="modal"><i class="fas fa-undo"></i> Cancel</button>
				<button type="button" class="btn btn-sm btn-success" id="confirm_edit_txt_file_btn"><i class="fas fa-save"></i> Save</button>
			</div>
		</div>
	</div>
</div>





<!-- Confirm Upload File -->
<div class="modal fade" id="modal_confirm_upload_file">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title">Upload Files</h4>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<form method="post" enctype="multipart/form-data" id="form_upload_file">
			<div class="modal-body">

				<input type="file" name="Files[]" multiple>
				<input name="current_dir" value="<?php echo bxaf_encrypt($CURRENT_DIR, $BXAF_ENCRYPTION_KEY); ?>" hidden>

			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-sm btn-primary" data-dismiss="modal"><i class="fas fa-undo"></i> Cancel</button>
				<button type="submit" class="btn btn-sm btn-success" id="confirm_upload_file_btn"><i class="fas fa-upload"></i> Upload</button>
			</div>
			</form>
		</div>
	</div>
</div>







<!-- Confirm Batch Upload -->
<div class="modal fade" id="modal_confirm_batch_upload">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title">Batch Upload</h4>
				<button type="button" class="close refresh_btn" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body p-a-lg">

				<form action="files_exe.php?action=batch_upload&f=<?php echo bxaf_encrypt($CURRENT_DIR, $BXAF_ENCRYPTION_KEY); ?>" class="dropzone text-center">
					<i class="fas fa-cloud-upload-alt fa-4x" style="color:#BDBEBE;"></i><br>
					<h3>Drag &amp; Drop a File</h3>
					<p>or select an option below</p>

					<div class="row m-t">
						<div class="col-md-4">&nbsp;</div>
						<div class="col-md-4">
							<a href="javascript: void(0);" class="btn btn-sm btn-success file_btn" id="upload_file_btn3">
								<i class="fas fa-upload fa-2x"></i><br>
								From Local
							</a>
						</div>
						<div class="col-md-4">&nbsp;</div>
					</div>
					<div class="dz-message" data-dz-message><span></span></div>
				</form>


			</div>
		</div>
	</div>
</div>







<!-- Confirm Edit Name -->
<div class="modal fade bd-example-modal-sm" id="modal_confirm_edit_name">
	<div class="modal-dialog modal-sm" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title">Rename</h4>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				Rename "<span class="text-success" id="confirm_edit_name_content_old_name"></span>" to:
				<input class="form-control" id="edit_name_input">
				<input class="form-control" id="edit_name_path" hidden>
				<input class="form-control" id="edit_name_old_name" hidden>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-sm btn-primary" data-dismiss="modal"><i class="fas fa-window-close"></i> Close</button>
				<button type="button" class="btn btn-sm btn-primary" id="confirm_edit_name_btn"><i class="fas fa-edit"></i> Save</button>
			</div>
		</div>
	</div>
</div>





<!-- Confirm Move File -->
<div class="modal fade" id="modal_confirm_move_file">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title move_copy_title">Move</h4>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<span class="move_copy_title">Move</span> to folder:
				<div class="row m-x-0" id="tree_select_folder_div">
					<div id="zTree_folder_checkbox_div"><ul id="tree_folder_checkbox" class="ztree"></ul></div>
				</div>
				<input id="move_file_dir" hidden>
				<input id="move_file_name" hidden>
				<input id="move_to_dir" hidden>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-sm btn-primary" data-dismiss="modal"><i class="fas fa-undo"></i> Cancel</button>
				<button type="button" class="btn btn-sm btn-primary confirm_copy_move_btn" action="move" id="confirm_move_file_btn" hidden><i class="fas fa-exchange-alt"></i> Move</button>
				<button type="button" class="btn btn-sm btn-success confirm_copy_move_btn" action="copy" id="confirm_copy_file_btn" hidden><i class="fas fa-copy"></i> Copy</button>
			</div>
		</div>
	</div>
</div>




<!-- Batch Download -->
<div class="modal fade" id="modal_download_message">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title ">Download Selected Folders and Files</h4>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body" id="modal_download_message_content">

			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-sm btn-primary" data-dismiss="modal">Done</button>
			</div>
		</div>
	</div>
</div>




<script>
var setting_checkbox = {
	check: {
		enable: true,
		chkStyle: "radio",
		radioType: "all" // 'all' or 'level'
	},
	data: {
		simpleData: {
			enable: true
		}
	},
	async: {
		enable: true,
		url:"get_nodes.php?type=checkbox_folder",
		autoParam:["id", "name=n", "level=lv", "path=path"],
//		otherParam:{"otherParam":""},
		dataFilter: filter
	},
	callback: {
		onCheck: myOnCheck
	}
}

// Customized check event
function myOnCheck(event, treeId, treeNode) {
	var folder_name = treeNode.name;
	var folder_path = treeNode.path;
	$('#move_to_dir').val(folder_path + '/' + treeNode.name);
};

var code;

function setCheck() {
	var type = $("#level").attr("checked")? "level":"all";
	setting_checkbox.check.radioType = type;
	showCode('setting_checkbox.check.radioType = "' + type + '";');
}
function showCode(str) {
	if (!code) code = $("#code");
	code.empty();
	code.append("<li>"+str+"</li>");
}





function filter(treeId, parentNode, childNodes) {
	if (!childNodes) return null;
	for (var i=0, l=childNodes.length; i<l; i++) {
		childNodes[i].name = childNodes[i].name.replace(/\.n/g, '.');
	}
	return childNodes;
}

$(document).ready(function(){
	$.fn.zTree.init($("#tree_folder_checkbox"), setting_checkbox);
	setCheck();
	$("#level").bind("change", setCheck);
	$("#all").bind("change", setCheck);
});
</script>