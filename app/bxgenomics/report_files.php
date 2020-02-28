<?php

//To disable login requirement
//$BXAF_CONFIG_CUSTOM['PAGE_LOGIN_REQUIRED'] = false;

include_once(dirname(__FILE__) . "/config/config.php");


if (!function_exists('format_size1')){
	function format_size1($size) {
	  $index = 0;
	  $units = array("B", "KB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB");
	  while((($size/1024) > 1) && ($index < 8)) {
		$size = $size/1024;
		$index++;
	  }
	  return sprintf("%01.2f %s", $size, $units[$index]);
	}
}

// e.g. http://yz.bxaf.com:8002/bxgenomics_v2.2/app/bxgenomics/report_fastqc.php?analysis=6_Cd8PPQJZa2EDV--Y4tlgdtSmdwrzaGClkYT9XAFFecQ

//echo "app/bxgenomics/files/<BR>" . bxaf_encrypt("app/bxgenomics/files/", $BXAF_CONFIG['BXAF_KEY']) . "<BR>"; // 26T07xbfHbth0bBWbcsCsXsp3mNoIVNfoVOPUxC8QyA

$title = "List of Files";
if (isset($_GET["title"]) && $_GET["title"] != "") {
	$title = $_GET["title"];
}

$subdir = '';
if (isset($_GET["d"]) && $_GET["d"] != "") {
	$subdir = bxaf_decrypt($_GET['d'], $BXAF_CONFIG['BXAF_KEY']);
}
else {
    die("Nothing found.");
}
$dir = $BXAF_CONFIG['BXAF_DIR'] . $subdir;

$files_name_url = array();
$files_name_encrypted = array();
$files_size = array();
if(! file_exists($dir) || ! is_dir($dir)){
    die("Nothing found.");
}
else if(strpos($dir, $BXAF_CONFIG['BXAF_DIR']) === 0){
    $d = dir($dir);
    while (false !== ($f = $d->read())) {
       if(is_file($dir . $f)) {
		   $files_name_url[$f] = $BXAF_CONFIG['BXAF_URL'] . $subdir . $f;
           $files_size[$f] = format_size1(filesize(realpath($dir . $f)));
           $files_name_encrypted[$f] = bxaf_encrypt(str_replace($BXAF_CONFIG['BXAF_DIR'], '', $dir) . $f, $BXAF_CONFIG['BXAF_KEY']);
       }
    }
    $d->close();
}
ksort($files_name_encrypted);

$is_bam_files = false;
if(preg_match("/ bam /", $title)) $is_bam_files = true;

?><!DOCTYPE html>
<html lang="en">
<head>
	<?php include_once($BXAF_CONFIG['BXAF_PAGE_HEADER']); ?>

	<link   href='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/datatables/datatables.min.css.php' rel='stylesheet' type='text/css'>
	<script src='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/datatables/datatables.min.js.php'></script>

	<link   href='css/report.css' rel='stylesheet' type='text/css'>

	<script type="text/javascript">
		$(document).ready(function(){
            $('#myDataTable').DataTable({ 'pageLength': 100, 'lengthMenu': [[10, 100, 500, 1000], [10, 100, 500, 1000]], dom: 'Blfrtip', buttons: ['colvis','copy','csv'] });
		});

	</script>

</head>
<body>
	<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_MENU'])) include_once($BXAF_CONFIG['BXAF_PAGE_MENU']); ?>

	<div id="bxaf_page_wrapper" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_WRAPPER']; ?>">

		<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_LEFT'])) include_once($BXAF_CONFIG['BXAF_PAGE_LEFT']); ?>

		<div id="bxaf_page_right" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT']; ?>">

			<div id="bxaf_page_right_content" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT_CONTENT']; ?>">



				<div class="container">

					<h1 class="w-100 text-center my-3">BxGenomics - <?php echo $title; ?></h1>

					<div class="w-100 my-3">
						<table id="myDataTable" class="table table-bordered table-striped table-hover w-100">
							<thead>
								<tr class="table-info">
                                    <th>No</th>
									<th>File Name</th>
									<th>File Size</th>
									<th>Type</th>
                                    <th>Download</th>
									<?php if($is_bam_files) echo "<th>URL</th>"; ?>
								</tr>
							</thead>
							<tbody>
								<?php
                                    $n = 0;
									foreach($files_name_encrypted as $f=>$enc){
                                        $n++;
								?>
									<tr>
                                        <td><?php echo $n; ?></td>
										<td><a href="download.php?f=<?php echo $enc; ?>"><?php echo $f; ?></a></td>
										<td><?php echo $files_size[$f]; ?></td>
										<td><?php echo array_pop( explode('.', $f)); ?></td>
                                        <td><a href="download.php?f=<?php echo $enc; ?>"><i class="fas fa-download"></i> Download</a></td>
										<?php if($is_bam_files) echo "<td style='font-family: monospace; font-size: 10px;'>" . wordwrap($files_name_url[$f], 80, "<BR>", true) . "</td>"; ?>
									</tr>
								<?php } ?>
							</tbody>
						</table>

						<?php if($is_bam_files) echo "<h3 class='my-3'>URL List of All bam Files</h3><textarea class='my-3 p-2' style='width: 100%; height: 200px; font-family: monospace; font-size: 10px;'>" . implode("\n", $files_name_url) . "</textarea>"; ?>
					</div>

				</div>



            </div>

		    <?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>

		</div>

	</div>

</body>
</html>