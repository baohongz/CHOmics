<?php

$BXAF_CONFIG_CUSTOM['PAGE_LOGIN_REQUIRED']	= true;
include_once(__DIR__ . "/config/config.php");

// echo "<pre>" . print_r($_SESSION, true) . "</pre>";

$default_species = 'Human';
if(isset($_GET['species']) && in_array(strtolower($_GET['species']), array('human', 'mouse') ) ){
	$default_species = ucfirst(strtolower($_GET['species']));
}


// Check exists
$sql = "SELECT `ID` FROM ?n WHERE `bxafStatus` < 5 AND `Category`= 'Default Species' AND `_Owner_ID` = ?i";
$record_id = $BXAF_MODULE_CONN -> get_one($sql, $BXAF_CONFIG['TBL_BXGENOMICS_USERPREFERENCE'], $BXAF_CONFIG['BXAF_USER_CONTACT_ID'] );

// Update record
if ($record_id != '') {
	$info = array(
		'_Owner_ID'   => $BXAF_CONFIG['BXAF_USER_CONTACT_ID'],
		'Category'    => 'Default Species',
		'Detail'      => $default_species
	);
	$BXAF_MODULE_CONN -> update($BXAF_CONFIG['TBL_BXGENOMICS_USERPREFERENCE'], $info, "`ID`=" . intval($record_id) );
}
// Create new record
else {
	$info = array(
		'_Owner_ID'   => $BXAF_CONFIG['BXAF_USER_CONTACT_ID'],
		'Category'    => 'Default Species',
		'Detail'      => $default_species,
		'bxafStatus'      => 0
	);
	$BXAF_MODULE_CONN -> insert($BXAF_CONFIG['TBL_BXGENOMICS_USERPREFERENCE'], $info);
}

$_SESSION['SPECIES_DEFAULT'] = $default_species;
header("Location: " . $_SERVER['HTTP_REFERER']);
?>