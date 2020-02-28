<?php
$BXAF_CONFIG_CUSTOM['PAGE_LOGIN_REQUIRED']	= true;
include_once(dirname(__FILE__) . "/config/config.php");

if(! $_SESSION['BXAF_ADVANCED_USER']){
	header("Location: index.php");
	exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<?php include_once($BXAF_CONFIG['BXAF_PAGE_HEADER']); ?>

	<link href="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/bootstrap_plugin/bootstrap3-editable/css/bootstrap-editable.css" rel="stylesheet">
	<script src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/bootstrap_plugin/bootstrap3-editable/js/bootstrap-editable.min.js"></script>

	<script>

		$(document).ready(function(){

			$.fn.editable.defaults.mode = 'inline';

			$('.program_info').editable({
				type: 'text',
				pk: 1,
				url: 'bxgenomics_exe.php?action=admin_change_dir',
				title: 'Program Information',
				success: function(responseText){
				}
			});

			$('.file_info').editable({
				type: 'text',
				pk: 1,
				url: 'bxgenomics_exe.php?action=admin_change_dir_file',
				title: 'Change File Directory',
				success: function(responseText){
				}
			});

			$(document).on('click', '.div_category', function(){
				$(this).next().slideToggle(500);
			});

		});

	</script>

</head>
<body>
	<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_MENU'])) include_once($BXAF_CONFIG['BXAF_PAGE_MENU']); ?>
	<div id="bxaf_page_wrapper" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_WRAPPER']; ?>">
		<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_LEFT'])) include_once($BXAF_CONFIG['BXAF_PAGE_LEFT']); ?>
		<div id="bxaf_page_right" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT']; ?>">
			<div id="bxaf_page_right_content" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT_CONTENT']; ?>">
				<div class="container-fluid">

					<!-- System Programs & Files -->
					<div class="row">
						<h3 class="w-100">System Settings</h3>
						<p>This tool allows you to update program and file settings.</p>
					</div>

					<div class="row">

						<div class="d-flex w-100 m-2 p-2 table-success div_category">
							<div class="align-self-center btn btn-primary mx-2">Analysis Programs</div>
							<div class="align-self-center text-muted mx-2">Necessary Programs</div>
							<div class="align-self-center text-muted ml-auto mr-2">5 programs</div>
						</div>

						<div class="w-100 my-3" style="display: none;">
							<table class="table table-bordered table-hover">
								<tbody>
									<?php
									foreach($BXAF_CONFIG['PROGRAM_DIR'] as $key => $value){
										echo '
										<tr class="bg_white">
											<td class="row_title"><strong>'.$key.'</strong></td>
											<td><a href="#" class="program_info" data-type="text" data-pk="'.$key.'" data-title="Change Directory">'.$BXAF_CONFIG['PROGRAM_DIR'][$key].'</a></td>
										</tr>';
									}
									?>
								</tbody>
							</table>
						</div>

						<?php foreach($BXAF_CONFIG['NECESSARY_FILES'] as $key => $value){ ?>
							<div class="d-flex w-100 m-2 p-2 table-info div_category">
								<div class="align-self-center btn btn-success mx-2">Necessary Files</div>
				        		<div class="align-self-center text-muted mx-2"><?php echo $key; ?> Files</div>
								<div class="align-self-center text-muted ml-auto mr-2"><?php echo count($value); ?> files</div>
							</div>

							<div class="w-100 my-3" style="display: none;">
								<table class="table table-bordered table-hover">
									<tbody>
										<?php
											foreach($value as $id => $info){
												echo '
												<tr class="">
													<td class="row_title"><strong>'.$id.'</strong></td>
													<td><a href="#" class="file_info" data-type="text" data-pk="'. $key .', '. $id .'" data-title="Change Directory">'. $info .'</a></td>
												</tr>';
											}
										?>
									</tbody>
								</table>
							</div>
						<?php } ?>

					</div>


				</div>
			</div>
			<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>
		</div>
	</div>
</body>
</html>