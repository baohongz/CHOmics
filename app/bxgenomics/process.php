<?php

//To disable login requirement
//$BXAF_CONFIG_CUSTOM['PAGE_LOGIN_REQUIRED'] = false;

include_once(dirname(__FILE__) . "/config/config.php");


$sql = "SELECT * FROM `".$BXAF_CONFIG['TBL_BXGENOMICS_PROCESS']."` WHERE `_Owner_ID`='".$BXAF_CONFIG['BXAF_USER_CONTACT_ID']."' ORDER BY `bxafStatus`, `Start_Time` DESC LIMIT 100";
$data_my_processes = $BXAF_MODULE_CONN->get_all($sql);


$all_process_server = array();

$process = shell_exec("ps");

$total_info_array = explode("\n", $process);

for($i=0; $i<count($total_info_array); $i++){
	$row_info_array = explode(" ", trim($total_info_array[$i]));
	$process_temp = $row_info_array[0];
	if ($process_temp != 'PID' && trim($process_temp)!='' && intval($process_temp)!=0){
		$all_process_server[] = intval($process_temp);
	}
}

?><!DOCTYPE html>
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

						<h3 class="">System Processes</h3>

						<p class="w-100">Here are your recent 100 processes, related files will be removed if you terminate a process:</p>

						<div class="w-100">
							<table class="table table-bordered table-striped table-hover" id="myDataTable">
								<thead>
									<tr class="table-info">
										<th>Process ID</th>
										<th>Time Started</th>
										<th>Command</th>
										<th>Status</th>
										<th>Action</th>
									</tr>
								</thead>
								<tbody>
								<?php
									foreach($data_my_processes as $key => $value){
										echo '
											<tr>
												<td>'.$value['Process_ID'].'</td>
												<td>'.$value['Start_Time'].'</td>
												<td>'.$value['Command'].'</td>
												<td>' . ($value['bxafStatus'] == 9 ? '<span class="text-muted">Finished</span>' : '<span class="text-success">Ongoing</span>') . '</td>
												<td>' . ($value['bxafStatus'] == 9 ? '' : '<a rowid="'.$value['Process_ID'].'"  class="terminate_process text-danger" href="Javascript: void(0);"><i class="fas fa-times"></i> Terminate</a>') . '</td>
											</tr>
										';
									}
								?>
								</tbody>
							</table>

						</div>

						<input id="terminate_process_id" hidden>

						<div class="row">
							<div id='debug'></div>
						</div>
					</div>






<div class="modal fade" id="myModal_terminate_process" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">

		    <div class="modal-header">
				<h4 class="modal-title" id="myModalLabel">WARNING</h4>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
				    <span aria-hidden="true">&times;</span>
				    <span class="sr-only">Close</span>
				</button>
		    </div>

		  	<div class="modal-body" id="myModal_terminate_process_content">
				Are you sure you want to terminate the process?
		  	</div>

		  	<div class="modal-footer">
				<button type="button" class="btn btn-danger" id="confirm_terminate_process">TERMINATE</button>
				<button type="button" class="btn btn-secondary" data-dismiss="modal">CANCEL</button>
		  	</div>

		</div>
	</div>
</div>



<script type="text/javascript">

$(document).ready(function(){

	$("#myDataTable").DataTable({"pageLength": 100, "lengthMenu": [[10, 100, 500, 1000], [10, 100, 500, 1000]], dom: 'Blfrtip', buttons: ['colvis','copy','csv'] });

	$(document).on('click', '.terminate_process', function(){
		var process_id = $(this).attr('rowid');
		$('#myModal_terminate_process_content').html('Are you sure you want to terminate the process ' + process_id + '?');
		$('#terminate_process_id').val(process_id);
		$('#myModal_terminate_process').modal();
	})

	$(document).on('click', '#confirm_terminate_process', function(){
		var process_id = $('#terminate_process_id').val();
		$.ajax({
			method: 'POST',
			url: 'bxgenomics_exe.php?action=terminate_process',
			data: {"process_id": process_id},
			success: function(responseText){

				bootbox.alert('<h2><i class="fas fa-check-square text-success"></i> Message</h2><div class="lead p-3">The analysis process has been terminated.</div>', function(){ location.reload(true); });
			}
		});
	});

});


</script>






            </div>

		    <?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>

		</div>

	</div>

</body>
</html>