<?php


if (!function_exists('tabix_search_records_public')) {
    function tabix_search_records_public($primaryIndex, $secondaryIndex, $table, $outputFormat = ''){

    	global $BXAF_CONFIG;

        if(! is_array($BXAF_CONFIG['TABIX_INDEX'][ $_SESSION['SPECIES_DEFAULT'] ]) || count( $BXAF_CONFIG['TABIX_INDEX'][ $_SESSION['SPECIES_DEFAULT'] ] ) <= 0) return array();

    	$BXAF_TEMP = array();

    	if (is_array($primaryIndex)){
    		$primaryIndex = array_filter($primaryIndex, 'is_numeric');
    		$primaryIndex = array_unique($primaryIndex);
    	}
    	else $primaryIndex = array();

    	if (is_array($secondaryIndex)){
    		$secondaryIndex = array_filter($secondaryIndex, 'is_numeric');
    		$secondaryIndex = array_unique($secondaryIndex);
    	}
    	else $secondaryIndex = array();

    	if (count($primaryIndex) > 0){
    		$which = 'Primary';
    	}
    	else if (count($secondaryIndex) > 0){
    		$which = 'Secondary';
    	}

        if (count($primaryIndex) <= 0 && count($secondaryIndex) <= 0){
			return array();
		}
        $primaryIndex   = array_slice($primaryIndex,   0, $BXAF_CONFIG['TABIX_MAX_INPUT_ARRAY_SIZE']);
        $secondaryIndex = array_slice($secondaryIndex, 0, $BXAF_CONFIG['TABIX_MAX_INPUT_ARRAY_SIZE']);

    	natsort($primaryIndex);
    	natsort($secondaryIndex);

    	if ($table == 'GeneLevelExpression'){
    		if ($which == 'Primary'){
    			$indexFile 						= $BXAF_CONFIG['TABIX_INDEX'][ $_SESSION['SPECIES_DEFAULT'] ]['GeneLevelExpression'];
    		} else {
    			$indexFile 						= $BXAF_CONFIG['TABIX_INDEX'][ $_SESSION['SPECIES_DEFAULT'] ]['GeneLevelExpression-Sample'];
    		}

    		$BXAF_TEMP['columnOrder'] 			= array('SampleIndex', 'GeneIndex', 'Value');
    		$BXAF_TEMP['columnOrderPrintable'] 	= array('Sample Index', 'Gene Index', 'Value');
    	}
    	else if ($table == 'GeneFPKM'){

    		if ($which == 'Primary'){
    			$indexFile 						= $BXAF_CONFIG['TABIX_INDEX'][ $_SESSION['SPECIES_DEFAULT'] ]['GeneFPKM'];
    		} else {
    			$indexFile 						= $BXAF_CONFIG['TABIX_INDEX'][ $_SESSION['SPECIES_DEFAULT'] ]['GeneFPKM-Sample'];
    		}

    		$BXAF_TEMP['columnOrder']			= array('SampleIndex', 'GeneIndex', 'Value', 'Count');
    		$BXAF_TEMP['columnOrderPrintable'] 	= array('Sample Index', 'Gene Index', 'Value', 'Count');
    	}
    	else if ($table == 'ComparisonData'){

    		if ($which == 'Primary'){
    			$indexFile 						= $BXAF_CONFIG['TABIX_INDEX'][ $_SESSION['SPECIES_DEFAULT'] ]['ComparisonData'];
    		} else {
    			$indexFile 						= $BXAF_CONFIG['TABIX_INDEX'][ $_SESSION['SPECIES_DEFAULT'] ]['ComparisonData-Comparison'];
    		}

    		$BXAF_TEMP['columnOrder'] 			= array('ComparisonIndex', 'GeneIndex', 'Name', 'Log2FoldChange', 'PValue', 'AdjustedPValue', 'NumeratorValue', 'DenominatorValue');
    		$BXAF_TEMP['columnOrderPrintable'] 	= array('Comparison Index', 'Gene Index', 'Name', 'Log2 Fold Change', 'p-value', 'Adjusted p-value', 'Numerator Value', 'Denominator Value');
    	}
    	$BXAF_TEMP['columnOrderSize'] = sizeof($BXAF_TEMP['columnOrder']);


    	if (!is_file($indexFile)){
    		return array();
    	}

    	$path = "{$BXAF_CONFIG['TABIX_DIR']}" . 'cache/';
    	if (!is_dir($path)){
    		mkdir($path, 0777, true);
    	}

    	$filePrefix	= $path . microtime(true);
    	$fileInput 			= $filePrefix . '_input.txt';
    	$fileOutputTabix	= $filePrefix . '_output.tabix';
    	$fileOutputTxt 		= $filePrefix . '_output.txt';

    	if (!file_exists($fileOutputTabix) || filesize($fileOutputTabix) <= 0){

    		if (count($primaryIndex) > 0 && count($secondaryIndex) > 0){

    			$fp = fopen($fileInput, 'w');

    			foreach($primaryIndex as $tempKey1 => $currentPrimaryIndex){
    				foreach($secondaryIndex as $tempKey2 => $currentSecondaryIndex){
    					$currentSecondaryIndex++;
    					fwrite($fp, "{$currentPrimaryIndex}\t{$currentSecondaryIndex}\t{$currentSecondaryIndex}\n");
    				}
    			}
    			fclose($fp);

    			$cmd = "{$BXAF_CONFIG['TABIX_BIN']} {$indexFile} -R {$fileInput} > {$fileOutputTabix}";

    		} elseif (count($primaryIndex) > 0 && count($secondaryIndex) <= 0){

    			$cmd = "{$BXAF_CONFIG['TABIX_BIN']} {$indexFile} " . implode(' ', $primaryIndex) . " > {$fileOutputTabix}";

    		} elseif (count($primaryIndex) <= 0 && count($secondaryIndex) > 0){

    			$cmd = "{$BXAF_CONFIG['TABIX_BIN']} {$indexFile} " . implode(' ', $secondaryIndex) . " > {$fileOutputTabix}";
    		}

    		shell_exec($cmd);

    	}

    	if (!file_exists($fileOutputTabix) || filesize($fileOutputTabix) <= 0){
    		return array();
    	}

    	if (!file_exists($fileOutputTxt) || filesize($fileOutputTxt) <= 0){
    		$headerString = implode("\t", $BXAF_TEMP['columnOrderPrintable']);
    		$cmd = "echo '{$headerString}' | cat - {$fileOutputTabix} > {$fileOutputTxt}";
    		shell_exec($cmd);
    	}

        $results = array();
    	if ($outputFormat == 'GetArrayAssoc' || $outputFormat == ''){

    		if (($handle = fopen($fileOutputTabix, 'r')) !== FALSE) {
    			while (($data = fgetcsv($handle, 0, "\t")) !== FALSE) {
    				$results[] = array_combine($BXAF_TEMP['columnOrder'], $data);
    			}
    			fclose($handle);
    		}
    	}
    	else if ($outputFormat == 'GetArrayNumeric'){
    		if (($handle = fopen($fileOutputTabix, 'r')) !== FALSE) {
    			while (($data = fgetcsv($handle, 0, "\t")) !== FALSE) {
    				$results[] = $data;
    			}
    			fclose($handle);
    		}
    	}
    	else if ($outputFormat == 'Raw'){
    		$results = file_get_contents($fileOutputTabix);
    	}
    	else if ($outputFormat == 'Path'){
    		$results = $fileOutputTabix;
    	}

    	return $results;
    }
}


if (!function_exists('tabix_search_records_private')) {
	function tabix_search_records_private($primaryIndex, $secondaryIndex, $table, $outputFormat = '') {
		global $BXAF_CONFIG;

		if (is_array($primaryIndex)){
			$primaryIndex = array_filter($primaryIndex, 'is_numeric');
			$primaryIndex = array_unique($primaryIndex);
		}
		else $primaryIndex = array();

		if (is_array($secondaryIndex)){
			$secondaryIndex = array_filter($secondaryIndex, 'is_numeric');
			$secondaryIndex = array_unique($secondaryIndex);
		}
		else $secondaryIndex = array();

		if (count($primaryIndex) <= 0 && count($secondaryIndex) <= 0){
			return array();
		}
        $primaryIndex   = array_slice($primaryIndex,   0, $BXAF_CONFIG['TABIX_MAX_INPUT_ARRAY_SIZE']);
        $secondaryIndex = array_slice($secondaryIndex, 0, $BXAF_CONFIG['TABIX_MAX_INPUT_ARRAY_SIZE']);

		natsort($primaryIndex);
		natsort($secondaryIndex);


		$DIR_Tabix = $BXAF_CONFIG['TABIX_IMPORT_DIR'] . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'];

		$import_comparisons = array();
		$import_array_expression = array();
		$import_ngs_expression = array();
		if(file_exists($DIR_Tabix)){
			$d = dir($DIR_Tabix);
			while (false !== ($entry = $d->read())) {
			   if($entry != '.' && $entry != '..' && file_exists("$DIR_Tabix/$entry/comparison_data.txt.gz.tbi") && file_exists("$DIR_Tabix/$entry/comparison_data.txt.comparison.gz.tbi")){
				   $import_comparisons[$entry] = "$DIR_Tabix/$entry/";
			   }
			   if($entry != '.' && $entry != '..' && file_exists("$DIR_Tabix/$entry/array_expression_data.txt.gz.tbi") && file_exists("$DIR_Tabix/$entry/array_expression_data.txt.sample.gz.tbi")){
				   $import_array_expression[$entry] = "$DIR_Tabix/$entry/";
			   }
			   if($entry != '.' && $entry != '..' && file_exists("$DIR_Tabix/$entry/ngs_expression_data.txt.gz.tbi") && file_exists("$DIR_Tabix/$entry/ngs_expression_data.txt.sample.gz.tbi")){
				   $import_ngs_expression[$entry] = "$DIR_Tabix/$entry/";
			   }

			}
			$d->close();
		}
		ksort($import_comparisons);
		ksort($import_array_expression);
		ksort($import_ngs_expression);

		$indexFile = '';
		$tabix_files_list = array();
		if ($table == 'GeneLevelExpression'){
			if (count($primaryIndex) > 0) $indexFile = "array_expression_data.txt.gz";
			else $indexFile = "array_expression_data.txt.sample.gz";
			$tabix_files_list = $import_array_expression;
		}
		else if ($table == 'GeneFPKM'){
			if (count($primaryIndex) > 0) $indexFile = "ngs_expression_data.txt.gz";
			else $indexFile = "ngs_expression_data.txt.sample.gz";
			$tabix_files_list = $import_ngs_expression;
		}
		else if ($table == 'ComparisonData'){
			if (count($primaryIndex) > 0) $indexFile = "comparison_data.txt.gz";
			else $indexFile = "comparison_data.txt.comparison.gz";
			$tabix_files_list = $import_comparisons;
		}
		else {
			return array();
		}


		$TABIX_CACHE_DIR = "{$BXAF_CONFIG['TABIX_DIR']}" . 'cache/';
		if (!is_dir($TABIX_CACHE_DIR)){
			mkdir($TABIX_CACHE_DIR, 0777, true);
		}

		$filePrefix	= microtime(true);
		$fileInput 			= $TABIX_CACHE_DIR . $filePrefix . '_input.txt';
		$fileOutputTabix	= $TABIX_CACHE_DIR . $filePrefix . '_output.tabix';
		$fileOutputTxt 		= $TABIX_CACHE_DIR . $filePrefix . '_output.txt';

		if(file_exists($fileInput)) unlink($fileInput);
		if(file_exists($fileOutputTabix)) unlink($fileOutputTabix);
		if(file_exists($fileOutputTxt)) unlink($fileOutputTxt);

		foreach($tabix_files_list as $tabix_foldername=>$tabix_foldername_dir){

			if (count($primaryIndex) > 0 && count($secondaryIndex) > 0){

				$fp = fopen($fileInput, 'w');
				foreach($primaryIndex as $currentPrimaryIndex){
					foreach($secondaryIndex as $currentSecondaryIndex){
						$currentSecondaryIndex++;
						fwrite($fp, "{$currentPrimaryIndex}\t{$currentSecondaryIndex}\t{$currentSecondaryIndex}\n");
					}
				}
				fclose($fp);

				$cmd = "{$BXAF_CONFIG['TABIX_BIN']} {$tabix_foldername_dir}{$indexFile} -R {$fileInput} >> {$fileOutputTabix}";

			}
			else if (count($primaryIndex) > 0 && count($secondaryIndex) <= 0){

				$cmd = "{$BXAF_CONFIG['TABIX_BIN']} {$tabix_foldername_dir}{$indexFile} " . implode(' ', $primaryIndex) . " >> {$fileOutputTabix}";

			}
			else if (count($primaryIndex) <= 0 && count($secondaryIndex) > 0){

				$cmd = "{$BXAF_CONFIG['TABIX_BIN']} {$tabix_foldername_dir}{$indexFile} " . implode(' ', $secondaryIndex) . " >> {$fileOutputTabix}";
			}
			shell_exec($cmd);
		}

        $results = array();
	    if(file_exists($fileOutputTabix) && ($handle = fopen($fileOutputTabix, "r")) !== FALSE){
			$fp = fopen($fileOutputTxt, 'w');
            while (($row = fgetcsv($handle, 0, "\t")) !== FALSE) {
				if ($table == 'ComparisonData'){
					$data = array(
						'ComparisonIndex' => $row[1],
			            'GeneIndex' => $row[0],
			            'Name' => $row[2],
			            'Log2FoldChange' => floatval($row[4]),
			            'PValue' => floatval($row[5]),
			            'AdjustedPValue' => floatval($row[6]),
			            'NumeratorValue' => '',
			            'DenominatorValue' => ''
					);
				}
				else {
					$data = array(
						'SampleIndex' => $row[1],
			            'GeneIndex' => $row[0],
			            'Value' => $row[4]
					);
				}
				fputcsv($fp, $data, "\t");
				$results[] = $data;
			}
			fclose($fp);
		}

		return $results;

	}
}



if (!function_exists('tabix_search_records_public2')) {
    function tabix_search_records_public2($primaryIndex, $secondaryIndex, $table){

    	global $BXAF_CONFIG;

        if(! is_array($BXAF_CONFIG['TABIX_INDEX'][ $_SESSION['SPECIES_DEFAULT'] ]) || count( $BXAF_CONFIG['TABIX_INDEX'][ $_SESSION['SPECIES_DEFAULT'] ] ) <= 0) return array();


    	if (is_array($primaryIndex)){
    		$primaryIndex = array_filter($primaryIndex, 'is_numeric');
    		$primaryIndex = array_unique($primaryIndex);
    	}
    	else $primaryIndex = array();

    	if (is_array($secondaryIndex)){
    		$secondaryIndex = array_filter($secondaryIndex, 'is_numeric');
    		$secondaryIndex = array_unique($secondaryIndex);
    	}
    	else $secondaryIndex = array();


        $indexFile = '';
    	if (count($primaryIndex) > 0){
    		// $which = 'Primary';
            if ($table == 'GeneLevelExpression') $indexFile = $BXAF_CONFIG['TABIX_INDEX'][ $_SESSION['SPECIES_DEFAULT'] ]['GeneLevelExpression'];
            else if ($table == 'GeneFPKM') $indexFile = $BXAF_CONFIG['TABIX_INDEX'][ $_SESSION['SPECIES_DEFAULT'] ]['GeneFPKM'];
            else if ($table == 'ComparisonData') $indexFile = $BXAF_CONFIG['TABIX_INDEX'][ $_SESSION['SPECIES_DEFAULT'] ]['ComparisonData'];
            else return '';
    	}
    	else if (count($secondaryIndex) > 0){
    		// $which = 'Secondary';
            if ($table == 'GeneLevelExpression') $indexFile = $BXAF_CONFIG['TABIX_INDEX'][ $_SESSION['SPECIES_DEFAULT'] ]['GeneLevelExpression-Sample'];
            else if ($table == 'GeneFPKM') $indexFile = $BXAF_CONFIG['TABIX_INDEX'][ $_SESSION['SPECIES_DEFAULT'] ]['GeneFPKM-Sample'];
            else if ($table == 'ComparisonData') $indexFile = $BXAF_CONFIG['TABIX_INDEX'][ $_SESSION['SPECIES_DEFAULT'] ]['ComparisonData-Comparison'];
            else return '';
    	}
        else {
            return '';
        }

        $primaryIndex   = array_slice($primaryIndex,   0, $BXAF_CONFIG['TABIX_MAX_INPUT_ARRAY_SIZE']);
        $secondaryIndex = array_slice($secondaryIndex, 0, $BXAF_CONFIG['TABIX_MAX_INPUT_ARRAY_SIZE']);

    	natsort($primaryIndex);
    	natsort($secondaryIndex);


    	$path = "{$BXAF_CONFIG['TABIX_DIR']}" . 'cache/';
    	if (!is_dir($path)){
    		mkdir($path, 0777, true);
    	}

    	$filePrefix	        = $path . microtime(true);
    	$fileInput          = $filePrefix . '_input.txt';
    	$fileOutputTabix    = $filePrefix . '_output.tabix';

        if(file_exists($fileInput)) unlink($fileInput);
        if(file_exists($fileOutputTabix)) unlink($fileOutputTabix);

		if (count($primaryIndex) > 0 && count($secondaryIndex) > 0){
			$fp = fopen($fileInput, 'w');
			foreach($primaryIndex as $tempKey1 => $currentPrimaryIndex){
				foreach($secondaryIndex as $tempKey2 => $currentSecondaryIndex){
					$currentSecondaryIndex++;
					fwrite($fp, "{$currentPrimaryIndex}\t{$currentSecondaryIndex}\t{$currentSecondaryIndex}\n");
				}
			}
			fclose($fp);
			$cmd = "{$BXAF_CONFIG['TABIX_BIN']} {$indexFile} -R {$fileInput} >> {$fileOutputTabix}";
		}
        elseif (count($primaryIndex) > 0 && count($secondaryIndex) <= 0){
			$cmd = "{$BXAF_CONFIG['TABIX_BIN']} {$indexFile} " . implode(' ', $primaryIndex) . " >> {$fileOutputTabix}";
		}
        elseif (count($primaryIndex) <= 0 && count($secondaryIndex) > 0){
			$cmd = "{$BXAF_CONFIG['TABIX_BIN']} {$indexFile} " . implode(' ', $secondaryIndex) . " >> {$fileOutputTabix}";
		}
		shell_exec($cmd);

        return $fileOutputTabix;

    }
}



if (!function_exists('tabix_search_records_private2')) {
    function tabix_search_records_private2($primaryIndex, $secondaryIndex, $table){

		global $BXAF_CONFIG;

		if (is_array($primaryIndex)){
			$primaryIndex = array_filter($primaryIndex, 'is_numeric');
			$primaryIndex = array_unique($primaryIndex);
		}
		else $primaryIndex = array();

		if (is_array($secondaryIndex)){
			$secondaryIndex = array_filter($secondaryIndex, 'is_numeric');
			$secondaryIndex = array_unique($secondaryIndex);
		}
		else $secondaryIndex = array();

		if (count($primaryIndex) <= 0 && count($secondaryIndex) <= 0){
			return array();
		}
        $primaryIndex   = array_slice($primaryIndex,   0, $BXAF_CONFIG['TABIX_MAX_INPUT_ARRAY_SIZE']);
        $secondaryIndex = array_slice($secondaryIndex, 0, $BXAF_CONFIG['TABIX_MAX_INPUT_ARRAY_SIZE']);

		natsort($primaryIndex);
		natsort($secondaryIndex);


		$DIR_Tabix = $BXAF_CONFIG['TABIX_IMPORT_DIR'] . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'];

		$import_comparisons = array();
		$import_array_expression = array();
		$import_ngs_expression = array();
		if(file_exists($DIR_Tabix)){
			$d = dir($DIR_Tabix);
			while (false !== ($entry = $d->read())) {
			   if($entry != '.' && $entry != '..' && file_exists("$DIR_Tabix/$entry/comparison_data.txt.gz.tbi") && file_exists("$DIR_Tabix/$entry/comparison_data.txt.comparison.gz.tbi")){
				   $import_comparisons[$entry] = "$DIR_Tabix/$entry/";
			   }
			   if($entry != '.' && $entry != '..' && file_exists("$DIR_Tabix/$entry/array_expression_data.txt.gz.tbi") && file_exists("$DIR_Tabix/$entry/array_expression_data.txt.sample.gz.tbi")){
				   $import_array_expression[$entry] = "$DIR_Tabix/$entry/";
			   }
			   if($entry != '.' && $entry != '..' && file_exists("$DIR_Tabix/$entry/ngs_expression_data.txt.gz.tbi") && file_exists("$DIR_Tabix/$entry/ngs_expression_data.txt.sample.gz.tbi")){
				   $import_ngs_expression[$entry] = "$DIR_Tabix/$entry/";
			   }

			}
			$d->close();
		}
		ksort($import_comparisons);
		ksort($import_array_expression);
		ksort($import_ngs_expression);

		$indexFile = '';
		$tabix_files_list = array();
		if ($table == 'GeneLevelExpression'){
			if (count($primaryIndex) > 0) $indexFile = "array_expression_data.txt.gz";
			else $indexFile = "array_expression_data.txt.sample.gz";
			$tabix_files_list = $import_array_expression;
		}
		else if ($table == 'GeneFPKM'){
			if (count($primaryIndex) > 0) $indexFile = "ngs_expression_data.txt.gz";
			else $indexFile = "ngs_expression_data.txt.sample.gz";
			$tabix_files_list = $import_ngs_expression;
		}
		else if ($table == 'ComparisonData'){
			if (count($primaryIndex) > 0) $indexFile = "comparison_data.txt.gz";
			else $indexFile = "comparison_data.txt.comparison.gz";
			$tabix_files_list = $import_comparisons;
		}
		else {
			return array();
		}


		$TABIX_CACHE_DIR = "{$BXAF_CONFIG['TABIX_DIR']}" . 'cache/';
		if (!is_dir($TABIX_CACHE_DIR)){
			mkdir($TABIX_CACHE_DIR, 0777, true);
		}

		$filePrefix	= microtime(true);
		$fileInput 			= $TABIX_CACHE_DIR . $filePrefix . '_input.txt';
		$fileOutputTabix	= $TABIX_CACHE_DIR . $filePrefix . '_output.tabix';

		if(file_exists($fileInput)) unlink($fileInput);
		if(file_exists($fileOutputTabix)) unlink($fileOutputTabix);

        $sums_all = array();
		foreach($tabix_files_list as $tabix_foldername=>$tabix_foldername_dir){

			if (count($primaryIndex) > 0 && count($secondaryIndex) > 0){

				$fp = fopen($fileInput, 'w');
				foreach($primaryIndex as $currentPrimaryIndex){
					foreach($secondaryIndex as $currentSecondaryIndex){
						$currentSecondaryIndex++;
						fwrite($fp, "{$currentPrimaryIndex}\t{$currentSecondaryIndex}\t{$currentSecondaryIndex}\n");
					}
				}
				fclose($fp);

				$cmd = "{$BXAF_CONFIG['TABIX_BIN']} {$tabix_foldername_dir}{$indexFile} -R {$fileInput} >> {$fileOutputTabix}";

			}
			else if (count($primaryIndex) > 0 && count($secondaryIndex) <= 0){

				$cmd = "{$BXAF_CONFIG['TABIX_BIN']} {$tabix_foldername_dir}{$indexFile} " . implode(' ', $primaryIndex) . " >> {$fileOutputTabix}";

			}
			else if (count($primaryIndex) <= 0 && count($secondaryIndex) > 0){

				$cmd = "{$BXAF_CONFIG['TABIX_BIN']} {$tabix_foldername_dir}{$indexFile} " . implode(' ', $secondaryIndex) . " >> {$fileOutputTabix}";
			}
			shell_exec($cmd);




            // Create sum values for calculating FPKM to TPM
            if ($table == 'GeneFPKM' && isset($_SESSION['View_NGS_in_TPM']) && $_SESSION['View_NGS_in_TPM'] == 'TPM'){


                if(file_exists("{$tabix_foldername_dir}ngs_expression_data.sum")){
                    $sums = unserialize(file_get_contents("{$tabix_foldername_dir}ngs_expression_data.sum"));
                    foreach($sums as $k=>$v) $sums_all[$k] += $v;
                }
                else if(file_exists("{$tabix_foldername_dir}ngs_expression_data.txt") && ! file_exists("{$tabix_foldername_dir}ngs_expression_data.sum")){
                    if (($handle = fopen("{$tabix_foldername_dir}ngs_expression_data.txt", "r")) !== FALSE) {
                        $sums = array();
                        $head = fgetcsv($handle, 1000, "\t");
                        while (($data = fgetcsv($handle, 1000, "\t")) !== FALSE) {
                            if(is_array($data) && count($data) >= 5){
                                $sums[ $data[1] ] += $data[4];
                                $sums_all[ $data[1] ] += $data[4];
                            }
                        }
                        fclose($handle);

                        file_put_contents("{$tabix_foldername_dir}ngs_expression_data.sum", serialize($sums) );
                    }
                }
            }


		}

        // Convert value from FPKM to TPM
        if ($table == 'GeneFPKM' && isset($_SESSION['View_NGS_in_TPM']) && $_SESSION['View_NGS_in_TPM'] == 'TPM'){

            $handle_fpkm = fopen($fileOutputTabix, "r");
            $handle_tpm  = fopen("{$fileOutputTabix}.tpm", "w");

            if ($handle_fpkm !== FALSE && $handle_tpm !== FALSE) {

                while (($row = fgetcsv($handle_fpkm, 1000, "\t")) !== FALSE) {
                    if(is_array($row) && count($row) >= 5){
                        if($sums_all[ $row[1] ] > 0) $row[4] = 1e6 * $row[4] / $sums_all[ $row[1] ];
                        fputcsv($handle_tpm, $row, "\t");
                    }
                }

                fclose($handle_fpkm);
                fclose($handle_tpm);
            }

            $fileOutputTabix = "{$fileOutputTabix}.tpm";
        }

        return $fileOutputTabix;
	}
}



if (!function_exists('tabix_search_bxgenomics')) {
    function tabix_search_bxgenomics($primaryIndex, $secondaryIndex, $table, $types = ''){

		global $BXAF_CONFIG;

        if($types == 'public'){

            if(! is_array($BXAF_CONFIG['TABIX_INDEX'][ $_SESSION['SPECIES_DEFAULT'] ]) || count( $BXAF_CONFIG['TABIX_INDEX'][ $_SESSION['SPECIES_DEFAULT'] ] ) <= 0) return array();

            $tabix_file_public  =  tabix_search_records_public2($primaryIndex, $secondaryIndex, $table);
            $tabix_files = array('Public'=>$tabix_file_public);
        }
        else if($types == 'private'){
            $tabix_file_private = tabix_search_records_private2($primaryIndex, $secondaryIndex, $table);
            $tabix_files = array('Private'=>$tabix_file_private );
        }
        else {
            if(is_array($BXAF_CONFIG['TABIX_INDEX'][ $_SESSION['SPECIES_DEFAULT'] ]) && count( $BXAF_CONFIG['TABIX_INDEX'][ $_SESSION['SPECIES_DEFAULT'] ] ) > 0) $tabix_file_public  =  tabix_search_records_public2($primaryIndex, $secondaryIndex, $table);
            $tabix_file_private = tabix_search_records_private2($primaryIndex, $secondaryIndex, $table);
            $tabix_files = array('Public'=>$tabix_file_public , 'Private'=>$tabix_file_private );
        }

        $results = array();
        $n = 0;
        foreach($tabix_files as $type => $filename){

            if(! file_exists($filename) || filesize($filename) <= 0) continue;

            $k  = '';
            $k1 = '';
            $k2 = '';
            $k3 = '';
            if($type == 'Public'){

                $columnOrder = array();
            	if ($table == 'GeneLevelExpression') $columnOrder = array('SampleIndex', 'GeneIndex', 'Value');
            	else if ($table == 'GeneFPKM') $columnOrder = array('SampleIndex', 'GeneIndex', 'Value', 'Count');
            	else if ($table == 'ComparisonData') $columnOrder = array('ComparisonIndex', 'GeneIndex', 'Name', 'Log2FoldChange', 'PValue', 'AdjustedPValue', 'NumeratorValue', 'DenominatorValue');
                else return array();

                $columnOrder_flip = array_flip($columnOrder);
                $columnOrder_length = count($columnOrder);

                if($table == 'ComparisonData'){
                    $k1 = $columnOrder_flip['Log2FoldChange'];
                    $k2 = $columnOrder_flip['PValue'];
                    $k3 = $columnOrder_flip['AdjustedPValue'];
                }
                else {
                    $k = $columnOrder_flip['Value'];
                }
            }
            else if($type == 'Private'){

                $columnOrder = array();
            	if ($table == 'GeneLevelExpression') $columnOrder = array('SampleIndex', 'GeneIndex', 'Value');
            	else if ($table == 'GeneFPKM') $columnOrder = array('GeneIndex', 'SampleIndex', 'Name', 'SampleName', 'Value');
            	else if ($table == 'ComparisonData') $columnOrder = array('GeneIndex', 'ComparisonIndex', 'Name', 'ComparisonName', 'Log2FoldChange', 'PValue', 'AdjustedPValue');
                else return array();

                $columnOrder_flip = array_flip($columnOrder);
                $columnOrder_length = count($columnOrder);

                if($table == 'ComparisonData'){
                    $k1 = $columnOrder_flip['Log2FoldChange'];
                    $k2 = $columnOrder_flip['PValue'];
                    $k3 = $columnOrder_flip['AdjustedPValue'];
                }
                else {
                    $k = $columnOrder_flip['Value'];
                }
            }


            if ( ($handle = fopen($filename, 'r')) !== FALSE ) {

                if($table == 'ComparisonData'){
                    while (($row = fgets($handle)) !== FALSE) {

                        $row_exploded = array_pad( array_slice(explode("\t", trim($row)), 0, $columnOrder_length), $columnOrder_length, 0);

                        if(($row_exploded[$k1] == 0 || $row_exploded[$k1] == '.') && ($row_exploded[$k2] == 0 || $row_exploded[$k2] == '.') && ($row_exploded[$k3] == 0 || $row_exploded[$k3] == '.')) continue;

                        $results[] 	= array_combine($columnOrder, $row_exploded);

                        if($n++ >= $BXAF_CONFIG['TABIX_MAX_OUTPUT_ARRAY_SIZE']) break;
                    }
                }
                else {
                    while (($row = fgets($handle)) !== FALSE) {

                        $row_exploded = array_pad( array_slice(explode("\t", trim($row)), 0, $columnOrder_length), $columnOrder_length, 0);

                        if($row_exploded[$k] == 0 || $row_exploded[$k] == '.') continue;

                        $results[] 	= array_combine($columnOrder, $row_exploded);

                        if($n++ >= $BXAF_CONFIG['TABIX_MAX_OUTPUT_ARRAY_SIZE']) break;
                    }
                }

                fclose($handle);
            }

        }

        return $results;
    }
}


?>