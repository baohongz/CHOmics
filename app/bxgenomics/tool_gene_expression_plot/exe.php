<?php

include_once('config.php');

$limit = 5000;

if (isset($_GET['action']) && $_GET['action'] == 'generate_filters') {

    $sample_indexnames = category_text_to_idnames($_POST['Sample_List'], 'name', 'Sample', $_SESSION['SPECIES_DEFAULT']);

    $field_name = $_POST['field_name'];

    $data_filter = array();
    $vals = json_decode( $_POST['data_filter'] );
    foreach($vals as $i=>$v){
        if($field_name == '_Platforms_ID') $data_filter[] = $v;
        else $data_filter[] = base64_decode($v);
    }

    $default_filter = $BXAF_CONFIG['QUERY_DEFAULT_FILTER'];
    if($_POST['data_type'] == 'public') $default_filter = " (`bxafStatus` < 5 AND (`_Owner_ID` IS NULL OR `_Owner_ID`='' OR `_Owner_ID`=0) ) ";
    else if($_POST['data_type'] == 'private') $default_filter = " (`bxafStatus` < 5 AND (`_Owner_ID` IS NOT NULL AND `_Owner_ID`={$BXAF_CONFIG['BXAF_USER_CONTACT_ID']} ) ) ";

    if($_POST['platform_type'] == 'NGS') $default_filter .= " AND `Platform_Type` = 'NGS' ";
    else if($_POST['platform_type'] == 'Array') $default_filter .= " AND `Platform_Type` = 'Array' ";

    $sql = "SELECT ?n, COUNT(*) FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES']}` WHERE {$default_filter} GROUP BY ?n ORDER BY COUNT(*) DESC";
    $category_counts = $BXAF_MODULE_CONN -> get_assoc($field_name, $sql, $field_name, $field_name);

    $sql = "SELECT ?n, COUNT(*) FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES']}` WHERE `ID` IN (?a) GROUP BY ?n ORDER BY COUNT(*) DESC";
    $category_counts2 = $BXAF_MODULE_CONN -> get_assoc($field_name, $sql, $field_name, array_keys($sample_indexnames), $field_name);


    if($field_name == '_Platforms_ID'){
        $sql = "SELECT `ID`, `Name` FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_PLATFORMS'] . "` WHERE `ID` IN (?a)";
        $platform_idnames = $BXAF_MODULE_CONN -> get_assoc('ID', $sql, array_keys($category_counts));
    }

    $content = '<div class="w-100" style="overflow-y:auto; max-height: 600px;"><table class="table table-bordered table-hover w-100" id="datatable_data_filter">
        <thead class="w-100">
            <tr class="w-100 table-info">
                <th><input type="checkbox" class="sample_check_all" /></th>
                <th>Value</th>
                <th>Occurence In All Samples</th>
                <th>Occurence In Selected Samples</th>
            </tr>
        </thead>
        <tbody class="w-100">';

    if(! is_array($category_counts) || count($category_counts) <= 0){
        $content .= "<tr><td colspan='3' class='text-danger'>No values found.</td></tr>";
    }
    else {
        if($field_name == '_Platforms_ID'){
            foreach($category_counts as $category => $count){
                $content .= "<tr>";
                $content .= "<td><input class='check_sample_filter' type='checkbox' value='" . $category . "' /></td>";
                $content .= "<td>" . $platform_idnames[$category] . "</td>";
                $content .= "<td>$count</td>";
                if(array_key_exists($category, $category_counts2)) $content .= "<td>" . $category_counts2[$category] . "</td>"; else $content .= "<td></td>";
                $content .= "</tr>";
            }
        }
        else {
            foreach($category_counts as $category => $count){
                $content .= "<tr>";
                $content .= "<td><input class='check_sample_filter' type='checkbox' value='" . base64_encode($category) . "' " . (in_array($category, $data_filter) ? "checked" : "") . " /></td>";
                $content .= "<td>" . htmlentities($category) . "</td>";
                $content .= "<td>$count</td>";
                if(array_key_exists($category, $category_counts2)) $content .= "<td>" . $category_counts2[$category] . "</td>"; else $content .= "<td></td>";
                $content .= "</tr>";
            }
        }
    }

    $content .= '</tbody>
    </table></div>';

    echo $content;

    exit();

}





if (isset($_GET['action']) && $_GET['action'] == 'generate_plot') {

    $species = $_SESSION['SPECIES_DEFAULT'];

    $gene_indexnames = category_text_to_idnames($_POST['Gene_List'], 'name', 'Gene', $species);

    // if (! is_array($gene_indexnames) || count($gene_indexnames) <= 0) {
    //     echo '<h4 class="text-danger">Error:</h4> No genes found. Please enter some gene names.';
    //     exit();
    // }
    $gene_indexes = array_keys($gene_indexnames);


    $SAMPLE_PLATFORM = '';
    $sample_indexnames = array();
    $sample_indexes = array();

    if(trim($_POST['Sample_List']) != ''){

        $sample_indexnames = category_text_to_idnames($_POST['Sample_List'], 'name', 'Sample', $species);

        if (is_array($sample_indexnames) && count($sample_indexnames) > 0){
            $sample_indexes = array_keys($sample_indexnames);

            $sql = "SELECT DISTINCT `Platform_Type` FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES'] . "` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `ID` IN (?a)";
            $platform_types = $BXAF_MODULE_CONN -> get_col($sql, $sample_indexes);

            if (! is_array($platform_types) || count($platform_types) > 1) {
                echo '<h4 class="text-danger">Error:</h4> Your samples are from different platforms. Please enter samples from either RNA-Seq or Microarray platform.';
                exit();
            }

            $SAMPLE_PLATFORM = array_shift($platform_types);

            if($_POST['platform_type'] != '' && $_POST['platform_type'] != $SAMPLE_PLATFORM){
                echo '<h4 class="text-danger">Error:</h4> Your selected platform type does not match the selected samples.';
                exit();
            }

        }
    }




    if($_POST['platform_type'] != '' && $SAMPLE_PLATFORM == ''){
        $SAMPLE_PLATFORM = $_POST['platform_type'];
    }

    $tabix_table = ($SAMPLE_PLATFORM == 'Array') ? 'GeneLevelExpression' : 'GeneFPKM';
    $tabix_colname = ($SAMPLE_PLATFORM == 'Array') ? 'Value' : ($_SESSION['View_NGS_in_TPM'] == 'TPM' ? 'TPM' : 'FPKM');


    ini_set('memory_limit','8G');

    $tabix_data = array();
    if ($_POST['data_type'] == 'private') {
        $tabix_data = tabix_search_bxgenomics($gene_indexes, $sample_indexes, $tabix_table, 'private');
    }
    else if ($_POST['data_type'] == 'public') {
        $tabix_data = tabix_search_bxgenomics($gene_indexes, $sample_indexes, $tabix_table, 'public');
    }
    else {
        $tabix_data = tabix_search_bxgenomics($gene_indexes, $sample_indexes, $tabix_table);
    }

    if (! is_array($tabix_data) || count($tabix_data) <= 0) {
        echo '<h4 class="text-danger">Error:</h4> No data retrieved from the database.';
        exit();
    }

    $gene_ids = array();
    foreach ($tabix_data as $t) $gene_ids[ $t['GeneIndex'] ] = '';
    $gene_indexnames = category_list_to_idnames(array_keys($gene_ids), 'id', 'gene', $species);
    $gene_indexes = array_keys($gene_indexnames);



    $data_gene_samples = array();
    $data_sample_genes = array();
    $GENE_IDS = array();
    $SAMPLE_IDS = array();
    foreach ($tabix_data as $row){
        $GENE_IDS[] = $row['GeneIndex'];
        $SAMPLE_IDS[] = $row['SampleIndex'];
        $data_gene_samples[ $row['GeneIndex'] ][ $row['SampleIndex'] ] = $row['Value'];
        $data_sample_genes[ $row['SampleIndex'] ][ $row['GeneIndex'] ] = $row['Value'];
    }
    $GENE_IDS = array_unique($GENE_IDS);
    $SAMPLE_IDS = array_unique($SAMPLE_IDS);



    $ATTRIBUTES = $_POST['attributes_Sample'];
    $SAMPLE_ATTRIBUTES = array();
    foreach($ATTRIBUTES as $attribute){
        if(isset($_POST['data_filters_' . $attribute]) && $_POST['data_filters_' . $attribute] != ''){
            $vals = json_decode( $_POST['data_filters_' . $attribute] );
            foreach($vals as $i=>$v){
                if($attribute == '_Platforms_ID') $vals[$i] = intval($v);
                else $vals[$i] = addslashes(base64_decode($v));
            }
            $SAMPLE_ATTRIBUTES[$attribute] = $vals;
        }
    }

    $sql = "SELECT * FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES'] . "` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `ID` IN (?a)";
    $SAMPLE_INFO = $BXAF_MODULE_CONN -> get_assoc('ID', $sql, $SAMPLE_IDS);


    foreach($SAMPLE_INFO as $id => $info){
        foreach($SAMPLE_ATTRIBUTES as $attribute=>$vals){
            if(is_array($vals) && count($vals) > 0 && ! in_array( addslashes($info[$attribute]), $vals ) ){
                unset($SAMPLE_INFO[$id]);
            }
        }
    }


    foreach ($tabix_data as $i=>$row){
        $GeneIndex = $row['GeneIndex'];
        $SampleIndex = $row['SampleIndex'];

        if(! array_key_exists($SampleIndex, $SAMPLE_INFO)){
            unset($tabix_data[$i]);
        }

    }
    $ALL_DATA_NUMBER = count($tabix_data);

    if(count($tabix_data) > $limit){
        shuffle($tabix_data);
        $tabix_data = array_slice($tabix_data, 0, $limit);
    }


    $final_gene_indexnames = array();
    $final_sample_indexnames = array();
    foreach ($tabix_data as $i=>$row){
        $GeneIndex = $row['GeneIndex'];
        $SampleIndex = $row['SampleIndex'];

        $final_gene_indexnames[$GeneIndex] = $gene_indexnames[$GeneIndex];
        $final_sample_indexnames[$SampleIndex] = $SAMPLE_INFO[$SampleIndex]['Name'];
    }




    $plot_title = "Log2(" . $tabix_colname . " + 0.5)";
    if(count($final_gene_indexnames) == 1) $plot_title .= " of " . current($final_gene_indexnames);


    $x = array();
    $y["smps"] = array();
    $y["data"] = array();
    $found_sample_gene = array();
    foreach ($tabix_data as $row){
        $GeneIndex = $row['GeneIndex'];
        $SampleIndex = $row['SampleIndex'];

        if(array_key_exists("$GeneIndex:$SampleIndex", $found_sample_gene)) continue;
        else $found_sample_gene["$GeneIndex:$SampleIndex"] = 1;

        if(count($final_gene_indexnames) > 1) $x['Gene Symbol'][] = "'" . addslashes($final_gene_indexnames[$GeneIndex]) . "'";
        foreach($ATTRIBUTES as $attribute){
            $x[$attribute][] = "'" . addslashes($SAMPLE_INFO[$SampleIndex][$attribute]) . "'";
        }
        $x["Samples"][] = "'" . addslashes($SAMPLE_INFO[$SampleIndex]['Name']) . "'";
        $y["smps"][] = "'" . addslashes($SAMPLE_INFO[$SampleIndex]['Name']) . '_' . count($y["smps"]) . "'";

        if($row['Value'] < 0) $row['Value'] = 0;
        $Value = log(floatval($row['Value']) + 0.5, 2);
        $y["data"][] = $Value;
    }


    $height = 900;
    $width	= 1000;

    $smpLabelScaleFontFactor = 0.7;
    $varLabelScaleFontFactor = 0.7;

    if (count($GENE_IDS) <= 30){
        $height = 900;
        $smpLabelScaleFontFactor = 0.7;
    }
    elseif (count($GENE_IDS) <= 60){
        $height = 1200;
        $smpLabelScaleFontFactor = 0.5;
    }
    else {
        $height = 1600;
        $smpLabelScaleFontFactor = 0.3;
    }






    $raw_data = array();
    $i = 0;
    $raw_data[$i] = array();
    $raw_data[$i][] = 'Samples';
    foreach($final_sample_indexnames as $sample_index => $sample_name){
        $raw_data[$i][] = $sample_name;
    }
    foreach ($ATTRIBUTES as $attr) {
        $i++;
        $raw_data[$i] = array();
        $raw_data[$i][] = $attr;
        foreach($final_sample_indexnames as $sample_index => $sample_name){
            $raw_data[$i][] = $SAMPLE_INFO[$sample_index][$attr];
        }
    }
    foreach ($final_gene_indexnames as $gene_index=>$gene_name) {
        $i++;
        $raw_data[$i] = array();
        $raw_data[$i][] = $gene_name;
        foreach($final_sample_indexnames as $sample_index => $sample_name){
            $raw_data[$i][] = $data_gene_samples[$gene_index][$sample_index];
        }
    }

    $data_file_unique = microtime(true);
    $data_file_dir = $BXAF_CONFIG['CURRENT_SYSTEM_CACHE_DIR'] . $data_file_unique;
    $data_file_url = $BXAF_CONFIG['CURRENT_SYSTEM_CACHE_URL'] . $data_file_unique;
    if (!is_dir($data_file_dir)) mkdir($data_file_dir, 0755, true);

    $raw_data_file = $data_file_dir . '/raw_data.csv';
    $fp = fopen($raw_data_file, 'w');
    foreach($raw_data as $i=>$row){
        fputcsv($fp, $row);
    }
    fclose($fp);








    $output_contents = '';
    $output_contents .= '<h3 class="my-3">Summary of Data</h3>';
    if(count($tabix_data) == $limit){
        echo "<div class='my-3 text-danger table-warning p-3'>Warning: $ALL_DATA_NUMBER records found, only first $limit records are used.</div>";
    }

    $sample_names = array_values($final_sample_indexnames);
    $output_contents .= '<div class="my-3">';
        $output_contents .= '<ul>';
            if(count($final_gene_indexnames) == 0){
                $output_contents .= '<li><strong><span class="text-success">No genes found. </span></strong></li>';
            }
            else if(count($final_gene_indexnames) == 1){
                $output_contents .= '<li><strong><span class="text-success">1 gene found: </span></strong> ' . implode(", ", array_values($final_gene_indexnames)) . '</li>';
            }
            else if(count($final_gene_indexnames) > 1 && count($final_gene_indexnames) < 100){
                $output_contents .= '<li><strong><span class="text-success">' . count($final_gene_indexnames) . ' genes found: </span></strong> ' . implode(", ", array_values($final_gene_indexnames)) . '</li>';
            }
            else {
                $output_contents .= '<li><strong><span class="text-success">' . count($final_gene_indexnames) . ' genes found. </span></strong></li>';
            }

            if(count($sample_names) == 0){
                $output_contents .= '<li><strong><span class="text-success">No samples found. </span></strong></li>';
            }
            else if(count($sample_names) == 1){
                $output_contents .= '<li><strong><span class="text-success">1 sample found: </span></strong> ' . implode(", ", $sample_names) . '</li>';
            }
            else if(count($sample_names) > 1 && count($sample_names) < 100){
                $output_contents .= '<li><strong><span class="text-success">' . count($sample_names) . ' samples found: </span></strong> ' . implode(", ", $sample_names) . '</li>';
            }
            else {
                $output_contents .= '<li><strong><span class="text-success">' . count($sample_names) . ' samples found. </span></strong></li>';
            }

        $output_contents .= '</ul>';
    $output_contents .= '</div>';





    if(count($final_gene_indexnames) > 0 && count($sample_names) > 0){

        $group_field = array_shift($ATTRIBUTES);
        $color_field = $group_field;
        if(count($final_gene_indexnames) == 1){
            $color_field = array_shift($ATTRIBUTES);
        }

        $output_contents .= "<div class='my-3'><h5>Download: <a href='{$data_file_url}/raw_data.csv' target='_blank'><i class='fas fa-download'></i> Raw Data File</a></h5></div>";

        $output_contents .= "<canvas class='plot_container my-3' id='plotSection' width='$width' height='$height' xresponsive='false' aspectRatio='1:1'></canvas>";
        $output_contents .= '<script type="text/javascript">';

            $output_contents .= '$(document).ready(function() {';

                $output_contents .= 'var plotObj = new CanvasXpress("plotSection", ';

    // data
                    $output_contents .= '{';

                        $output_contents .= '"x": {';

                            $x_contents = array();
                            foreach ($x as $k=>$vals) {
                                $x_contents[] = "'$k': [" . implode(",", $vals) . "]";
                            }
                            $output_contents .= implode(",\n", $x_contents);

                        $output_contents .= '},';
                        $output_contents .= "\n\n";

                        $output_contents .= '"y": {';

                            $output_contents .= "'vars': ['expression'],";
                            $output_contents .= '"smps":[' . implode(",", $y['smps']) . '],';
                            $output_contents .= '"data":[ [' . implode(",", $y['data']) . '] ]';

                        $output_contents .= '}';

                    $output_contents .= '},';
                    $output_contents .= "\n\n";


    // layout
                    $output_contents .= '{';
                        $output_contents .= '
                            "graphOrientation"          : "horizontal",
                            "graphType"                 : "Boxplot",
                            "jitter"                    : true,

                            "colorBy"                   : "' . $color_field . '",

                            "plotByVariable"            : true,
                            "showBoxplotOriginalData"   : true,
                            "smpLabelRotate"            : 0,

                            "legendBox"                 : true,
                            "showLegend"                : true,
                            "showShadow"                : false,

                            "title"                     : "' . $plot_title . '",
                            "axisTitleScaleFontFactor"  : 0.5,
                            "axisTickFontSize"          : 12,
                            "axisTickScaleFontFactor"   : 0.5,

                            "citation"                  : "",
                            "citationScaleFontFactor"   : 0.7,

                            "xAxisTitle"                : "",
                            "titleFontSize"             : 25,

                            "smpLabelScaleFontFactor"   : ' . $smpLabelScaleFontFactor . ',
                            "varLabelScaleFontFactor"   : ' . $varLabelScaleFontFactor . ',
                            "titleScaleFontFactor"      : 0.7,
                            "subtitleScaleFontFactor"   : 0.7,

                            "legendScaleFontFactor"     : 0.6,
                            "nodeScaleFontFactor"       : 0.7,
                            "sampleSeparationFactor"    : 0.7,
                            "variableSeparationFactor"  : 0.7,
                            "widthFactor"               : 0.7,
                            "printType"                 : "window"
                        ';
                    $output_contents .= '}';
                    $output_contents .= "\n\n";

                $output_contents .= ');';
                $output_contents .= "\n\n";


                $output_contents .= 'plotObj.sizes = plotObj.sizes.map(function(x) { return Number(x * 0.5).toFixed(1); });';
                $output_contents .= "\n\n";

                $output_contents .= 'CanvasXpress.stack["plotSection"]["config"]["sizes"] = plotObj.sizes.map(function(x) { return Number(x * 0.5).toFixed(1); });';
                $output_contents .= "\n\n";

                if(count($final_gene_indexnames) > 1) $output_contents .= 'plotObj.groupSamples(["Gene Symbol"]);';
                else $output_contents .= 'plotObj.groupSamples(["' . $group_field . '"]);';

                $output_contents .= "\n\n";

            $output_contents .= '});';
        $output_contents .= '</script>';
    }

    echo $output_contents;

    exit();
}

?>