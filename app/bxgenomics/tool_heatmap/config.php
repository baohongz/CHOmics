<?php

include_once('../config/config.php');

$BXAF_CONFIG['CURRENT_SYSTEM_CACHE_DIR'] = $BXAF_CONFIG['BXAF_DIR'] . "app_data/cache/tool_heatmap/{$BXAF_CONFIG['BXAF_USER_CONTACT_ID']}/";
$BXAF_CONFIG['CURRENT_SYSTEM_CACHE_URL'] = $BXAF_CONFIG['BXAF_URL'] . "app_data/cache/tool_heatmap/{$BXAF_CONFIG['BXAF_USER_CONTACT_ID']}/";
if (!is_dir($BXAF_CONFIG['CURRENT_SYSTEM_CACHE_DIR'])) mkdir($BXAF_CONFIG['CURRENT_SYSTEM_CACHE_DIR'], 0755, true);



$BXAF_CONFIG['TOOL_EXPORT_COLNAMES_SHORT'] = array(
  'Comparison' => array(
    'Name', 'ComparisonCategory', 'ComparisonContrast', 'Case_DiseaseState', 'Case_Tissue',
    'Case_CellType'
  ),
  'Gene' => array(
    'GeneName', 'EntrezID', 'Ensembl', 'Unigene'
  ),
  'Sample' => array(
    'Name', 'CellType', 'DiseaseCategory', 'DiseaseState', 'Tissue'
  ),
);


$BXAF_CONFIG['TOOL_EXPORT_COLNAMES_ALL'] = $BXAF_CONFIG['TBL_BXGENOMICS_FIELDS'];





if (!function_exists('stats_standard_deviation')) {
    /**
     * This user-land implementation follows the implementation quite strictly;
     * it does not attempt to improve the code or algorithm in any way. It will
     * raise a warning if you have fewer than 2 values in your array, just like
     * the extension does (although as an E_USER_WARNING, not E_WARNING).
     *
     * @param array $a
     * @param bool $sample [optional] Defaults to false
     * @return float|bool The standard deviation or false on error.
     */
    function stats_standard_deviation(array $a, $sample = false) {
        $n = count($a);
        if ($n === 0) {
            trigger_error("The array has zero elements", E_USER_WARNING);
            return false;
        }
        if ($sample && $n === 1) {
            trigger_error("The array has only 1 element", E_USER_WARNING);
            return false;
        }
        $mean = array_sum($a) / $n;
        $carry = 0.0;
        foreach ($a as $val) {
            $d = ((double) $val) - $mean;
            $carry += $d * $d;
        };
        if ($sample) {
           --$n;
        }
        return sqrt($carry / $n);
    }
}




function calculateStdev($array){
	$sum 	= array_sum($array);
	$count 	= count($array);

	if ($count > 0){
		$mean	= $sum / $count;

		foreach($array as $tempKey => $tempValue) {
			$devs[] = pow($tempValue - $mean, 2);
		}

		return sqrt(array_sum($devs) / $count);

	} else {
		return 0;
	}
}

function calculateMean($array){
	$sum 	= array_sum($array);
	$count 	= count($array);

	if ($count > 0){
		$mean	= $sum / $count;
	} else {
		$mean	= 0;
	}

	return $mean;
}

function calculateZScore($number, $mean, $stdev){

	$number = floatval($number);
	$mean 	= floatval($mean);
	$stdev 	= floatval($stdev);

	if ($stdev == 0){
		return 0;
	} else {
		return ($number - $mean)/$stdev;
	}

}

?>