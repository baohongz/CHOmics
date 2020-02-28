<?php
include_once("config.php");

$sql = "SELECT * FROM `".$BXAF_CONFIG['TBL_BXGENOMICS_PLATFORMS']."` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']}  ORDER BY `GEO_Accession`";
$platform_info = $BXAF_MODULE_CONN->get_all($sql);

?>
<!DOCTYPE html>
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

    <?php $help_key = 'Platforms'; include_once( dirname(__DIR__) . "/help_content.php"); ?>


    <div class="my-3"><a class="btn btn-sm btn-primary" href="platform_fetch.php"><i class='fas fa-download'></i> Fetch New Platforms from GEO</a> <a class='mx-2 btn btn-sm btn-success btn_add_platform' platform_id='0' href='Javascript: void(0);'><i class='fas fa-plus'></i> Add Platform Manually</a></div>

    <div class="w-100">

<?php

echo '<table id="myDataTable" class="table table-bordered table-striped">';
echo '    <thead>';
echo '        <tr class="table-info">';
echo '            <th>ID</th>';
echo '            <th>Accession</th>';
echo '            <th>Name</th>';
echo '            <th>Species</th>';
echo '            <th>Type</th>';
echo '            <th>Manufacturer</th>';
echo '            <th>Actions</th>';
echo '        </tr>';
echo '    </thead>';
echo '    <tbody>';

foreach($platform_info as $platform){
	echo '<tr>';
	echo "    <td>" . $platform['ID'] . '</td>';
	echo "    <td>" . $platform['GEO_Accession'] . '</td>';
	echo "    <td>" . $platform['Name'] . '</td>';
	echo "    <td>" . $platform['Species'] . '</td>';
	echo "    <td>" . $platform['Type'] . '</td>';
	echo "    <td>" . $platform['Manufacturer'] . '</td>';

	echo "    <td>";
	if($BXAF_CONFIG['BXAF_USER_CONTACT_ID'] == $platform['_Owner_ID']) echo "<a class='mx-2 btn_update_platform' platform_id='{$platform['ID']}' href='Javascript: void(0);'><i class='fas fa-edit'></i> Edit</a> <a class='mx-2 btn_delete_platform' platform_id='{$platform['ID']}' href='Javascript: void(0);'><i class='fas fa-times'></i> Delete</a> ";
	echo '</td>';

	echo '</tr>';
}

echo '    </tbody>';
echo '</table>';

?>

    </div>



    <div class="w-100" id="div_debug"></div>

</div>
</div>
<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>
</div>
</div>




<!-- Edit File Info Modal -->
<form id="form_update_info" role="form">
	<div class="modal" id="myModal_update_info">
		<div class="modal-dialog" role="document">
			<div class="modal-content">

			    <div class="modal-header">
	          		<h4 id="modal-title" class="modal-title">Update Platform Information</h4>
	  				<button type="button" class="close" data-dismiss="modal">
					    <span aria-hidden="true">&times;</span>
					    <span class="sr-only">Close</span>
	  				</button>
			    </div>

			  	<div class="modal-body" id="myModal_content">

					<input id="platform_id" name="ID" value="" hidden>

					<div class="row mt-2">
						<div class="col-md-3 text-right"><strong>Name: </strong></div>
						<div class="col-md-9">
							<input id="Name" name="Name" value="" class="form-control" required>
						</div>
					</div>
					<div class="row mt-2">
						<div class="col-md-3 text-right"><strong>Type: </strong></div>
						<div class="col-md-9">
							<input id="Type" name="Type" value="" class="form-control" placeholder="NGS or Array" required>
						</div>
					</div>
					<div class="row mt-2">
						<div class="col-md-3 text-right"><strong>Species: </strong></div>
						<div class="col-md-9">
							<label class="ml-2">
		                        <input type="radio" name="Species" value="Human" <?php if($_SESSION['SPECIES_DEFAULT'] == 'Human') echo "checked"; ?> /> Human
		                    </label>
		                    <label class="ml-2">
		                        <input type="radio" name="Species" value="Mouse" <?php if($_SESSION['SPECIES_DEFAULT'] == 'Mouse') echo "checked"; ?> /> Mouse
		                    </label>

						</div>
					</div>
					<div class="row mt-2">
						<div class="col-md-3 text-right"><strong>Accession: </strong></div>
						<div class="col-md-9">
							<input id="GEO_Accession" name="GEO_Accession" value="" placeholder="e.g., GPL12345" class="form-control" required>
						</div>
					</div>
					<div class="row mt-2">
						<div class="col-md-3 text-right"><strong>Manufacturer: </strong></div>
						<div class="col-md-9">
							<input id="Manufacturer" name="Manufacturer" value="" placeholder="Company Name" class="form-control">
						</div>
					</div>
			  	</div>

			  	<div class="modal-footer">
	  				<button type="submit" class="btn btn-primary">Save</button>
	  				<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
	  				<button type="reset" class="btn btn-secondary">Reset</button>
			  	</div>

			</div>
		</div>
	</div>
</form>




<script>

    $(document).ready(function() {

		$('#myDataTable').DataTable({"pageLength": 100, "lengthMenu": [[10, 100, 500, 1000], [10, 100, 500, 1000]], dom: 'Blfrtip', buttons: ['colvis','copy','csv'] });


		$(document).on('click', '.btn_add_platform', function(){

			$('#platform_id').val('0');
			$('#Name').val('');
			$('#Type').val('');
			$('input[name=Species][value=Human]').prop("checked",true);
			$('#GEO_Accession').val('');
			$('#Manufacturer').val('');

			$('#modal-title').text('Add Platform Manually');

			$('#myModal_update_info').modal();
		});


		$(document).on('click', '.btn_update_platform', function(){

			var platform_id = $(this).attr('platform_id');

			$('#platform_id').val(platform_id);

			$.ajax({
				type: 'POST',
				url: 'exe.php?action=get_platform_info&id=' + platform_id,
				success: function(data){

					if (typeof data == 'string' && data.substring(0, 5) == 'Error') {
	                    bootbox.alert(data);
	                } else {
						$('#Name').val(data['Name']);
						$('#Type').val(data['Type']);

						$('input[name=Species][value=' + data['Species'] + ']').prop("checked",true);

						$('#GEO_Accession').val(data['GEO_Accession']);
						$('#Manufacturer').val(data['Manufacturer']);
						$('#modal-title').text('Update Platform Information');
					}

					$('#myModal_update_info').modal();
				}
			});

		});


		$(document).on('click', '.btn_delete_platform', function(){

			var platform_id = $(this).attr('platform_id');

			bootbox.confirm({
				message: '<h2><i class="fas fa-exclamation-triangle text-warning"></i> Warning</h2><div class="lead p-3 text-danger">Are you sure you want to delete this record?</div>',
				callback: function (result) {
					if(result){

						$.ajax({
							type: 'get',
							url: 'exe.php?action=delete_platform_info&id=' + platform_id,
							success: function(res){
								if (typeof res == 'string' && res.substring(0, 5) == 'Error') {
				                    bootbox.alert(res);
				                } else {
				                    location.reload(true);
				                }
							}
						});
					}
				}
			});
		});


        var options = {
            type: 'post',
            url: 'exe.php?action=save_platform_info',
            beforeSubmit: function(formData, jqForm, options) {

				if(	$('#Name').val() == '' || $('#Type').val() == '' || $('#GEO_Accession').val() == '') {
					bootbox.alert("Name, Type, Species and GEO Accession are required.");
					return false;
				}
				$('#myModal_update_info').modal('hide');

				return true;
            },
            success: function(res) {

                if (typeof res == 'string' && res.substring(0, 5) == 'Error') {
                    bootbox.alert(res);
                } else {
                    location.reload(true);
                }
                return true;
            }
        };
        $('#form_update_info').ajaxForm(options);

    });


</script>
</body>
</html>