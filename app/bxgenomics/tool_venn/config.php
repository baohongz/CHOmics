<?php
$BXAF_CONFIG_CUSTOM['PAGE_LOGIN_REQUIRED']	= false;

include_once(dirname(__DIR__) . '/config/config.php');

$BXAF_CONFIG['BXAF_VENN_DATA_DIR']	= $BXAF_CONFIG['BXAF_DIR'] . "app_data/cache/tool_venn/" . session_id() . "/";
$BXAF_CONFIG['BXAF_VENN_DATA_URL']	= $BXAF_CONFIG['BXAF_URL'] . "app_data/cache/tool_venn/" . session_id() . "/";

if(! file_exists($BXAF_CONFIG['BXAF_VENN_DATA_DIR'] ) ) {
    mkdir($BXAF_CONFIG['BXAF_VENN_DATA_DIR'], 0775, true);
}


?>