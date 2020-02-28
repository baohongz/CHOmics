<?php

$sql = "SELECT * FROM `tbl_bxgenomics_help` WHERE `bxafStatus` < 5 AND `File` = ?s ORDER BY `Version` DESC";
$current_help_info = $BXAF_MODULE_CONN->get_row($sql, $BXAF_CONFIG['BXGENOMICS_TOOLS'][ $help_key ] );
// echo "<pre>" . print_r($current_help_info, true) . "</pre>";

$help_title = $help_key;
if(is_array($current_help_info) && count($current_help_info) > 0) $help_title = $current_help_info['Title'];
echo "<h2 class='w-100'>" . $help_title . "</h2>";

if(is_array($current_help_info) && count($current_help_info) > 0 && array_key_exists('Short_Description', $current_help_info) && $current_help_info['Short_Description'] != ''){

	echo "<div id='div_help_summary' class='w-100 my-1'>" . $current_help_info['Short_Description'];

		if( ($current_help_info['Screenshot'] != '' && file_exists(__DIR__ . "/files/help/" . $current_help_info['Screenshot']) ) || $current_help_info['Detailed_Description'] != '') echo " <a target='_blank' href='"  . $BXAF_CONFIG['BXAF_APP_URL'] . "bxgenomics/help.php?key=" . urlencode($help_key) . "'><i class='fas fa-caret-right ml-2'></i> Read More</a>";

	echo "</div>";

	// echo "<div id='div_help_summary' class='w-100 my-1'>" . $current_help_info['Short_Description'];
	// 	if( ($current_help_info['Screenshot'] != '' && file_exists(__DIR__ . "/files/help/" . $current_help_info['Screenshot']) ) || $current_help_info['Detailed_Description'] != '') echo " <a href='Javascript: void(0);' class='div_help_toggle' target_id='div_help_read_more'><i class='fas fa-caret-right ml-2'></i> Read More</a>";
	// echo "</div>";

	// if( ($current_help_info['Screenshot'] != '' && file_exists(__DIR__ . "/files/help/" . $current_help_info['Screenshot']) ) || $current_help_info['Detailed_Description'] != '') {
	// 	echo "<div id='div_help_read_more' class='w-100 my-3 hidden'>";
	//
	// 		if($current_help_info['Screenshot'] != '' && file_exists(__DIR__ . "/files/help/" . $current_help_info['Screenshot'])) echo "<div id='' class='w-100 my-1 p-3'><img class='img-fluid' src='/" . $BXAF_CONFIG['BXAF_APP_SUBDIR'] . "bxgenomics/files/help/" . $current_help_info['Screenshot'] . "' /></div>";
	//
	// 		if( $current_help_info['Detailed_Description'] != '') echo "<div id='' class='w-100 my-1'>" . $current_help_info['Detailed_Description'] . "</div>";
	//
	// 	echo "</div>";
	// }
	//
	// echo "\n\n" . '
	// <script type="text/javascript">
	// 	$(document).ready(function(){
	//
	// 		$(document).on("click", ".div_help_toggle", function(){
	// 			var target = $(this).attr("target_id");
	// 			if($("#" + target).hasClass("hidden")){
	// 				$("#" + target).removeClass("hidden");
	// 			}
	// 			else{
	// 				$("#" + target).addClass("hidden");
	// 			}
	// 		});
	//
	// 	});
	// </script>' . "\n\n";

}

echo '<hr class="w-100 mb-3" />' . "\n\n";

?>