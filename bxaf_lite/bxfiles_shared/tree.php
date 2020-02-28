<?php

include_once(dirname(__FILE__) . "/config.php");

$VIEW_TYPE = 'tree';
if(isset($_GET['view']) && $_GET['view'] == 'table'){
	$VIEW_TYPE = 'table';
}

$current_dir_encrypted = '';
$CURRENT_DIR = '';

if($VIEW_TYPE == 'table'){
	if($VIEW_TYPE == 'table' && isset($_GET['f']) && trim($_GET['f']) != ''){
		$current_dir_encrypted = $_GET['f'];
		$CURRENT_DIR = rtrim(bxaf_decrypt($current_dir_encrypted, $BXAF_ENCRYPTION_KEY), '/');
	}
	else {
		$CURRENT_DIR = rtrim($DESTINATION_SUBFOLDER_DIR, '/');
		$current_dir_encrypted = bxaf_encrypt($CURRENT_DIR, $BXAF_ENCRYPTION_KEY);
	}
}

?><!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="Description" content="<?php echo $BXAF_CONFIG['BXAF_PAGE_DESCRIPTION']; ?>">
	<meta name="Keywords" content="<?php echo $BXAF_CONFIG['BXAF_PAGE_KEYWORDS']; ?>">
	<meta name="author" content="<?php echo $BXAF_CONFIG['BXAF_PAGE_AUTHOR'];  ?>">
	<title><?php echo $BXAF_CONFIG['BXAF_PAGE_TITLE']; ?></title>

	<script src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/jquery/jquery.min.js"></script>
	
	<link   href='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/fontawesome/css/all.min.css' rel='stylesheet' type='text/css'>

	<link href="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<script src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/bootstrap/js/bootstrap.min.js"></script>

	<link rel="stylesheet" href="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/ztree/css/zTreeStyle/zTreeStyle.css" type="text/css">
	<script type="text/javascript" src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/ztree/js/jquery.ztree.core.js"></script>
	<script type="text/javascript" src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/ztree/js/jquery.ztree.excheck.js"></script>
	<script type="text/javascript" src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/ztree/js/jquery.ztree.exedit.js"></script>

	<link   href='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>css/page.css' rel='stylesheet' type='text/css'>
	<script src='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>js/page.js'></script>

	<script type="text/javascript">
		var setting = {
			async: {
				enable: true,
				url:"get_nodes.php?type=folder",
				autoParam:["id", "name=n", "level=lv", "path=path"],
		//		otherParam:{"otherParam":""},
				dataFilter: filter
			}
		};

		function filter(treeId, parentNode, childNodes) {
			if (!childNodes) return null;
			for (var i=0, l=childNodes.length; i<l; i++) {
				childNodes[i].name = childNodes[i].name.replace(/\.n/g, '.');
			}
			return childNodes;
		}

		$(document).ready(function(){
			$.fn.zTree.init($("#treeFolders"), setting);
		});
	</script>

</head>

<body>
	<?php include_once(dirname(dirname(__FILE__)) . '/page_menu.php'); ?>


	<div class="container-fluid">
		<div class="row p-2">
			<h3 class='text-warning'>
				Folders
				<a href="folder.php?f=<?php echo $current_dir_encrypted; ?>" class="ml-3 font-weight-normal" style="font-size:1rem;"><i class="fas fa-angle-double-right"></i> Manipulate Files</a>
			</h3>
		</div>

		<div class="row p-2">
			<ul id="treeFolders" class="ztree"></ul>
		</div>

	</div>


</body>
</html>