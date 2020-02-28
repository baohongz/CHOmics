<?php
$BXAF_CONFIG_CUSTOM['PAGE_LOGIN_REQUIRED']	= false;
include_once(dirname(__FILE__) . "/config/config.php");

$help_key = '';
if(isset($_GET['key']) && array_key_exists($_GET['key'], $BXAF_CONFIG['BXGENOMICS_TOOLS'])) $help_key = $_GET['key'];

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

					<?php

					if($help_key == ''){

						$sql = "SELECT `File`, `Title` FROM `tbl_bxgenomics_help` WHERE `bxafStatus` < 5 ORDER BY `File` ASC, `Version` ASC";
						$help_titles = $BXAF_MODULE_CONN->get_assoc('File', $sql );
						if(! is_array($help_titles)) $help_titles = array();

						echo "<h2 class='w-100'>" . $BXAF_CONFIG['BXAF_PAGE_APP_NAME'] . "</h2>";
						echo "<ul class='my-3'>";
							foreach($BXAF_CONFIG['BXGENOMICS_TOOLS'] as $k=>$v){
								$title =  array_key_exists($BXAF_CONFIG['BXGENOMICS_TOOLS'][ $k ], $help_titles) ? $help_titles[ $BXAF_CONFIG['BXGENOMICS_TOOLS'][ $k ] ] : $k;
								echo "<li class='my-2'><a href='help.php?key=" . urlencode($k) . "' class=''>Help: " . $title . "</a> <a target='_blank' href='" . $v . "' class='text-success ml-3'><i class='fas fa-caret-right'></i> Open Tool</a> </li>";
							}
						echo "</ul>";

						if($_SESSION['BXAF_ADVANCED_USER']){
							echo '<h3 class="my-3">List of Admin Tools:</h3>
							<ul class="my-3">
								<li><a href="help_test_procedure.php">Test Procedures</a></li>
								<li><a href="help_pathview_notes.php">Pathview Notes</a></li>
								<li><a href="help_webmaster.php">Webmaster: System Setup</a></li>
							</ul>';
						}
					}
					else {

						$sql = "SELECT * FROM `tbl_bxgenomics_help` WHERE `bxafStatus` < 5 AND `File` = ?s ORDER BY `Version` DESC";
						$current_help_info = $BXAF_MODULE_CONN->get_row($sql, $BXAF_CONFIG['BXGENOMICS_TOOLS'][ $help_key ] );
						// echo "<pre>" . print_r($current_help_info, true) . "</pre>";

						$help_title = $help_key;
						if(is_array($current_help_info) && count($current_help_info) > 0) $help_title = $current_help_info['Title'];
						echo "<h2 class='w-100'>" . $help_title . " <a href='" . $BXAF_CONFIG['BXGENOMICS_TOOLS'][ $help_key ] . "' class='ml-5 btn btn-primary' style='font-size: 1rem;'><i class='fas fa-caret-right ml-2'></i> Start Tool</a></h2>";

						if(is_array($current_help_info) && count($current_help_info) > 0 && array_key_exists('Short_Description', $current_help_info) && $current_help_info['Short_Description'] != ''){

							echo "<div id='div_help_summary' class='w-100 my-1'>" . $current_help_info['Short_Description'] . "</div>";

							if( ($current_help_info['Screenshot'] != '' && file_exists(__DIR__ . "/files/help/" . $current_help_info['Screenshot']) ) || $current_help_info['Detailed_Description'] != '') {

								echo "<div id='div_help_read_more' class='w-100 my-3'>";

									if($current_help_info['Screenshot'] != '' && file_exists(__DIR__ . "/files/help/" . $current_help_info['Screenshot'])) echo "<div id='' class='w-100 my-1 p-3'><img class='img-fluid' src='" . $BXAF_CONFIG['BXAF_APP_URL'] . "bxgenomics/files/help/" . $current_help_info['Screenshot'] . "' /></div>";

									if( $current_help_info['Detailed_Description'] != '') echo "<div id='' class='w-100 my-1'>" . $current_help_info['Detailed_Description'] . "</div>";

								echo "</div>";
							}

						}

					}

					?>

				</div>

            </div>

		    <?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>

		</div>

	</div>

</body>
</html>
