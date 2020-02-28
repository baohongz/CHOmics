<?php
include_once("config.php");

if (isset($_GET['action']) && $_GET['action'] == 'save_list') {

    header('Content-Type: application/json');
    $OUTPUT       = array('type' => 'Error');

    $SPECIES = $_POST['species'];
    $CATEGORY     = $_POST['category'];

    $LIST_NAME    = trim($_POST['list_name']);

    if ($LIST_NAME == '') {
        $OUTPUT['detail'] = 'Please enter list name.';
        echo json_encode($OUTPUT);
        exit();
    }

    $sql = "SELECT `ID` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_USERSAVEDLISTS']}` WHERE " . $BXAF_CONFIG['QUERY_DEFAULT_FILTER'] . " AND `Category`=?s AND `Name`=?s AND `Species`=?s";
    $existing_id = $BXAF_MODULE_CONN -> get_one($sql, $CATEGORY, $LIST_NAME, $SPECIES);

    if ($existing_id > 0) {
        $OUTPUT['detail'] = 'The name has been used. Please use another name.';
        echo json_encode($OUTPUT);
        exit();
    }

    $id_names = category_text_to_idnames($_POST['content_name'], 'name', $CATEGORY, $SPECIES);

    if (!is_array($id_names) || count($id_names) <= 0) {
        $OUTPUT['detail'] = 'Please enter a few names.';
        echo json_encode($OUTPUT);
        exit();
    }

    // Save to Database
    $info = array(
        'Name'         => $LIST_NAME,
        'Species'      => $SPECIES,
        'Category'     => $CATEGORY,
        'Table'        => $CATEGORY,
        'Items'        => serialize(array_keys($id_names)),
        'Count'        => count($id_names),
        'Notes'        => trim($_POST['description']),

        '_Owner_ID'    => $BXAF_CONFIG['BXAF_USER_CONTACT_ID'],
        'Time_Created' => date("Y-m-d H:i:s")
    );

    $list_id = $BXAF_MODULE_CONN -> insert($BXAF_CONFIG['TBL_BXGENOMICS_USERSAVEDLISTS'], $info);

    //--------------------------------------------------------------------
    // Output
    $OUTPUT['type']     = 'Success';
    $OUTPUT['list_id']  = $list_id;
    $OUTPUT['count']    = count($id_names);
    $OUTPUT['category'] = strtolower($CATEGORY);

    echo json_encode($OUTPUT);
    exit();
}




if (isset($_GET['action']) && $_GET['action'] == 'delete_list') {

    $ROWID = intval($_POST['rowid']);
    if ($ROWID == 0) exit();
    $info = array('bxafStatus' => 9);
    $BXAF_MODULE_CONN -> update($BXAF_CONFIG['TBL_BXGENOMICS_USERSAVEDLISTS'], $info, "`ID`={$ROWID}");


    exit();
}



//********************************************************************************************
// Save SESSION List
//********************************************************************************************
if (isset($_GET['action']) && trim($_GET['action']) == 'save_session_list') {

    $uniqueID = md5(microtime(true));
    $_SESSION['SAVED_LIST'][$uniqueID] = explode(",", $_POST['list']);

    $type_list = array('comparison', 'gene', 'sample', 'project');
    $CATEGORY = 'Comparison';
    if (isset($_GET['category']) && in_array(strtolower($_GET['category']), $type_list)) {
        $CATEGORY = ucfirst(strtolower($_GET['category']));
    }

    echo "{$BXAF_CONFIG['BXAF_APP_URL']}bxgenomics/tool_save_lists/new_list.php?Category={$CATEGORY}&time={$uniqueID}";

    exit();
}


?>