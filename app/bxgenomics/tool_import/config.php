<?php
include_once(dirname(__DIR__) . '/config/config.php');

function get_name_and_type ($colname, $type = 'type'){
    $column_alias = array(
        'Log2FoldChange'=>array('log2foldchange','logfc','log2fc','logfoldchange', 'log fc', 'log2 foldchange', 'log foldchange', 'log fold change'),
        'AdjustedPValue'=>array('adjustedpvalue','adj.p.val','fdr','adj.p.value','adj p val', 'adj p value', 'adjusted p value', 'adjusted p val'),
        'PValue'=>array('pvalue','p.value','p.val','p value', 'pval', 'p val')
    );
    foreach($column_alias as $k=>$vs){
        foreach($vs as $v){
            if(preg_match("/{$v}$/i", $colname) ) {
                if($type == 'type') return $k;
                else return trim(rtrim(preg_replace("/\s*$v$/i", "", $colname), "\.\-\_"));
            }
        }
    }
    return '';
}

?>