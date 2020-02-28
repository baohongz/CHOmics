<?php

//System Config
$BXAF_CONFIG_CUSTOM['PAGE_LOGIN_REQUIRED']	= true;
include_once(dirname(dirname(__FILE__)) . "/bxaf_lite/config.php");


$BXAF_CONFIG['BXGENOMICS_TOOLS'] = array(
    'Help' => '/'. $BXAF_CONFIG['BXAF_APP_SUBDIR'] . 'bxgenomics/help.php',

	'Import Project Data' => '/'. $BXAF_CONFIG['BXAF_APP_SUBDIR'] . 'bxgenomics/tool_import/index.php',

	'Gene Expression Plot' => '/'. $BXAF_CONFIG['BXAF_APP_SUBDIR'] . 'bxgenomics/tool_gene_expression_plot/index.php',
	'Heatmap' => '/'. $BXAF_CONFIG['BXAF_APP_SUBDIR'] . 'bxgenomics/tool_heatmap/index.php',
	'Correlation Tool' => '/'. $BXAF_CONFIG['BXAF_APP_SUBDIR'] . 'bxgenomics/tool_correlation/index.php',
	'PCA Analysis' => '/'. $BXAF_CONFIG['BXAF_APP_SUBDIR'] . 'bxgenomics/tool_pca/index_genes_samples.php',
	'Export Expression Data' => '/'. $BXAF_CONFIG['BXAF_APP_SUBDIR'] . 'bxgenomics/tool_export/genes_samples.php',

	'Volcano Plot' => '/'. $BXAF_CONFIG['BXAF_APP_SUBDIR'] . 'bxgenomics/tool_volcano_plot/index.php',
	'Bubble Plot' => '/'. $BXAF_CONFIG['BXAF_APP_SUBDIR'] . 'bxgenomics/tool_bubble_plot/index.php',
    'Bubble Plot Multiple' => '/'. $BXAF_CONFIG['BXAF_APP_SUBDIR'] . 'bxgenomics/tool_bubble_plot/multiple.php',
	'Significantly Changed Genes' => '/'. $BXAF_CONFIG['BXAF_APP_SUBDIR'] . 'bxgenomics/tool_pathway/changed_genes.php',
	'Pathway Heatmap' => '/'. $BXAF_CONFIG['BXAF_APP_SUBDIR'] . 'bxgenomics/tool_pathway_heatmap/index.php',
	'Export Comparison Data' => '/'. $BXAF_CONFIG['BXAF_APP_SUBDIR'] . 'bxgenomics/tool_export/genes_comparisons.php',
	'KEGG Pathway View' => '/'. $BXAF_CONFIG['BXAF_APP_SUBDIR'] . 'bxgenomics/tool_pathway/kegg.php',
	'Reactome Pathway View' => '/'. $BXAF_CONFIG['BXAF_APP_SUBDIR'] . 'bxgenomics/tool_pathway/reactome.php',
	'WikiPathway View' => '/'. $BXAF_CONFIG['BXAF_APP_SUBDIR'] . 'bxgenomics/tool_pathway/index.php',

	'My Saved Lists' => '/'. $BXAF_CONFIG['BXAF_APP_SUBDIR'] . 'bxgenomics/tool_save_lists/my_lists.php',
	'Functional Enrichment' => '/'. $BXAF_CONFIG['BXAF_APP_SUBDIR'] . 'bxgenomics/tool_functional_enrichment/index.php',
	'Overlap and Venn Diagrams' => '/'. $BXAF_CONFIG['BXAF_APP_SUBDIR'] . 'bxgenomics/tool_venn/overlap.php',
	'Search Functional Gene Lists' => '/'. $BXAF_CONFIG['BXAF_APP_SUBDIR'] . 'bxgenomics/tool_save_lists/gene_list.php',
	'Compare Gene Lists' => '/'. $BXAF_CONFIG['BXAF_APP_SUBDIR'] . 'bxgenomics/tool_save_lists/gene_list_compare.php',
	'Meta Analysis' => '/'. $BXAF_CONFIG['BXAF_APP_SUBDIR'] . 'bxgenomics/tool_meta_analysis/index.php',

    'Platforms' => '/'. $BXAF_CONFIG['BXAF_APP_SUBDIR'] . 'bxgenomics/tool_import/platforms.php',
);






// Top menu settings
$BXAF_CONFIG['PAGE_MENU_ITEMS'] =  array(
    array(
        'Name'=>'Toolbox',
        'Children'=>array(
            array(
                'Name' => 'Import Project Data',
                'URL' => $BXAF_CONFIG['BXGENOMICS_TOOLS']['Import Project Data'],
            ),
            array(
                'Class'=>'divider',
            ),
            array(
                'Name'=>'Gene Expression Analysis',
                'Class'=>'dropdown-header',
            ),
            array(
                'Name' => 'Gene Expression Plot',
                'URL' => $BXAF_CONFIG['BXGENOMICS_TOOLS']['Gene Expression Plot'],
            ),
            array(
                'Name' => 'Heatmap',
                'URL' => $BXAF_CONFIG['BXGENOMICS_TOOLS']['Heatmap'],
            ),
            array(
                'Name' => 'Correlation Tool',
                'URL' => $BXAF_CONFIG['BXGENOMICS_TOOLS']['Correlation Tool'],
            ),
            array(
                'Name' => 'PCA Analysis',
                'URL' => $BXAF_CONFIG['BXGENOMICS_TOOLS']['PCA Analysis'],
            ),
            array(
                'Name' => 'Export Expression Data',
                'URL' => $BXAF_CONFIG['BXGENOMICS_TOOLS']['Export Expression Data'],
            ),


            array(
                'Class'=>'divider',
            ),
            array(
                'Name'=>'Comparison-based Analysis',
                'Class'=>'dropdown-header',
            ),
            array(
                'Name' => 'Volcano Plot',
                'URL' => $BXAF_CONFIG['BXGENOMICS_TOOLS']['Volcano Plot'],
            ),
            array(
                'Name' => 'Bubble Plot',
                'URL' => $BXAF_CONFIG['BXGENOMICS_TOOLS']['Bubble Plot'],
            ),
            array(
                'Name' => 'Significantly Changed Genes',
                'URL' => $BXAF_CONFIG['BXGENOMICS_TOOLS']['Significantly Changed Genes'],
            ),
            array(
                'Name' => 'Pathway Heatmap',
                'URL' => $BXAF_CONFIG['BXGENOMICS_TOOLS']['Pathway Heatmap'],
            ),
            array(
                'Name' => 'Export Comparison Data',
                'URL' => $BXAF_CONFIG['BXGENOMICS_TOOLS']['Export Comparison Data'],
            ),


            array(
                'Class'=>'divider',
            ),
            array(
                'Name' => 'Pathway Visualization',
                'Class'=>'dropdown-header',
            ),
            array(
                'Name' => 'KEGG Pathway View',
                'URL' => $BXAF_CONFIG['BXGENOMICS_TOOLS']['KEGG Pathway View'],
            ),
            array(
                'Name' => 'Reactome Pathway View',
                'URL' => $BXAF_CONFIG['BXGENOMICS_TOOLS']['Reactome Pathway View'],
            ),
            array(
                'Name' => 'WikiPathway View',
                'URL' => $BXAF_CONFIG['BXGENOMICS_TOOLS']['WikiPathway View'],
            ),


            array(
                'Class'=>'divider',
            ),
            array(
                'Name' => 'Other Tools',
                'Class'=>'dropdown-header',
            ),
            array(
                'Name' => 'My Saved Lists',
                'URL' => $BXAF_CONFIG['BXGENOMICS_TOOLS']['My Saved Lists'],
            ),
            array(
                'Name' => 'Functional Enrichment',
                'URL' => $BXAF_CONFIG['BXGENOMICS_TOOLS']['Functional Enrichment'],
            ),
            array(
                'Name' => 'Overlap and Venn Diagrams',
                'URL' => $BXAF_CONFIG['BXGENOMICS_TOOLS']['Overlap and Venn Diagrams'],
            ),
            array(
                'Name' => 'Search Functional Gene Lists',
                'URL' => $BXAF_CONFIG['BXGENOMICS_TOOLS']['Search Functional Gene Lists'],
            ),
            array(
                'Name' => 'Compare Gene Lists',
                'URL' => $BXAF_CONFIG['BXGENOMICS_TOOLS']['Compare Gene Lists'],
            ),
            array(
                'Name' => 'Meta Analysis',
                'URL' => $BXAF_CONFIG['BXGENOMICS_TOOLS']['Meta Analysis'],
            ),
            array(
                'Name' => 'Manage Platforms',
                'URL' => $BXAF_CONFIG['BXGENOMICS_TOOLS']['Platforms'],
            ),
        ),
    ),
);



if($_SESSION['BXAF_ADVANCED_USER']){
    $BXAF_CONFIG['PAGE_MENU_ITEMS'][] = array(
        'Name'=>'My Analyses',
        'Children'=>array(
            array(
                'Name' => 'Experiments',
                'URL' => '/'. $BXAF_CONFIG['BXAF_APP_SUBDIR'] . 'bxgenomics/experiments.php',
            ),
            array(
                'Name' => 'Samples',
                'URL' => '/'. $BXAF_CONFIG['BXAF_APP_SUBDIR'] . 'bxgenomics/samples.php',
            ),
            array(
                'Name' => 'Analysis',
                'URL' => '/'. $BXAF_CONFIG['BXAF_APP_SUBDIR'] . 'bxgenomics/analysis_all.php',
            ),
        )
    );

    $BXAF_CONFIG['PAGE_MENU_ITEMS'][] = array(
        'Name'=>'Admin',
        'Children'=>array(
            array(
                'Name' => 'Private Files',
                'URL' => '/'. $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR'] . 'bxfiles/',
            ),
            array(
                'Name' => 'Shared Files',
                'URL' => '/'. $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR'] . 'bxfiles_shared/',
            ),
            array(
                'Name' => 'Platforms',
                'URL' => '/'. $BXAF_CONFIG['BXAF_APP_SUBDIR'] . 'bxgenomics/tool_import/platforms.php',
            ),
        ),
    );

}


?>