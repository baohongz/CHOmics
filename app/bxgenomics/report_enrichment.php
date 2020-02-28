<?php

//To disable login requirement
//$BXAF_CONFIG_CUSTOM['PAGE_LOGIN_REQUIRED'] = false;

include_once(dirname(__FILE__) . "/config/config.php");


// e.g. http://yz.bxaf.com:8002/bxgenomics_v2.2/app/bxgenomics/report_gsea.php?analysis=6_Cd8PPQJZa2EDV--Y4tlgdtSmdwrzaGClkYT9XAFFecQ&comp=Control.vs.Drug.A

$analysis_id = 0;
$analysis_id_encrypted = '';
if (isset($_GET['analysis_id']) && intval($_GET['analysis_id']) > 0) {
  $analysis_id = intval($_GET['analysis_id']);
  $analysis_id_encrypted = $analysis_id . '_' . bxaf_encrypt($analysis_id, $BXAF_CONFIG['BXAF_KEY']);
}
else if (isset($_GET['analysis']) && trim($_GET['analysis']) != '') {
  $analysis_id_encrypted = trim($_GET['analysis']);
  $analysis_id = intval(array_shift(explode('_', $analysis_id_encrypted)));
}

$analysis_dir = $BXAF_CONFIG['ANALYSIS_DIR'] . $analysis_id_encrypted . "/";
$analysis_url = $BXAF_CONFIG['ANALYSIS_URL'] . $analysis_id_encrypted . "/";


if($analysis_id <= 0 || ! file_exists($analysis_dir) || ! is_dir($analysis_dir) || ! is_readable($analysis_dir)){
	header("Location: analysis_all.php");
}

$sql = "SELECT * FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_ANALYSIS'] . "` WHERE `ID` = $analysis_id";
$analysis_info = $BXAF_MODULE_CONN -> get_row($sql);
$all_comparisons = unserialize($analysis_info['Comparisons']);

$sql = "SELECT * FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_EXPERIMENT'] . "` WHERE `ID` = " . $analysis_info['Experiment_ID'];
$experiment_info = $BXAF_MODULE_CONN -> get_row($sql);


$current_comparison = '';
if (isset($_GET['comp']) && trim($_GET['comp']) != '') {
  $current_comparison = $_GET['comp'];
}
if(! in_array($current_comparison, $all_comparisons)) $current_comparison = current($all_comparisons);

$direction = 'Down';
if (isset($_GET['direction']) && trim($_GET['direction']) != '') {
    $direction = $_GET['direction'];
}

$report_dir = $analysis_dir . "alignment/DEG/$current_comparison/Downstream/GO_Analysis_$direction/";
$report_url = $analysis_url . "alignment/DEG/$current_comparison/Downstream/GO_Analysis_$direction/";

$go_analysis_files = array(
    array("biocyc.txt", "BIOCYC pathways", "Groups of proteins in the same pathways", "http://biocyc.org/", "BIOCYC"),
    array("biological_process.txt", "biological process", "Functional groupings of proteins", "http://www.geneontology.org", "Gene Ontology"),
    array("cellular_component.txt", "cellular component", "Protein localization", "http://www.geneontology.org", "Gene Ontology"),
    array("chromosome.txt", "chromosome location", "Genes with similar chromosome localization", "http://www.ncbi.nlm.nih.gov/gene", "NCBI Gene"),
    array("cosmic.txt", "COSMIC cancer mutations", "Genes mutated in similar cancers", "http://cancer.sanger.ac.uk/cancergenome/projects/cosmic/", "COSMIC"),
    array("gene3d.txt", "gene3d domains", "Proteins with similar domains and features", "http://cathwww.biochem.ucl.ac.uk:8080/Gene3D", "Gene3D"),
    array("gwas.txt", "GWAS genes", "Genes mutated in similar diseases", "http://www.genome.gov/26525384", "GWAS Catalog"),
    array("interactions.txt", "protein interactions", "Proteins interacting with a common protein (BIND, EcoCyc, HPRD", "http://www.ncbi.nlm.nih.gov/gene", "NCBI Gene"),
    array("interpro.txt", "interpro domains", "Proteins with similar domains and features", "http://www.ebi.ac.uk/interpro/", "Interpro"),
    array("kegg.txt", "KEGG pathways", "Groups of proteins in the same pathways", "http://www.genome.jp/kegg/pathway.html", "KEGG"),
    array("lipidmaps.txt", "Lipid Maps pathways", "Groups of proteins in the same lipid pathways", "http://www.ncbi.nlm.nih.gov/biosystems/", "Lipid Maps/Biosystems"),
    array("molecular_function.txt", "molecular function", "Mechanistic actions of proteins", "http://www.geneontology.org", "Gene Ontology"),
    array("msigdb.txt", "MSigDB lists", "Genes sets for pathways, factor/miRNA target predictions, expression patterns, etc.", "http://www.broadinstitute.org/gsea/msigdb/index.jsp", "MSigDB"),
    array("pathwayInteractionDB.txt", "Pathway Interaction DB", "Groups of proteins in the same pathways", "http://pid.nci.nih.gov/", "Pathway Interaction Database"),
    array("pfam.txt", "pfam domains", "Proteins with similar domains and features", "http://www.sanger.ac.uk/Software/Pfam/", "Pfam"),
    array("prints.txt", "prints domains", "Proteins with similar domains and features", "http://www.bioinf.manchester.ac.uk/dbbrowser/PRINTS/", "PRINTS"),
    array("prosite.txt", "prosite domains", "Proteins with similar domains and features", "http://ca.expasy.org/prosite/", "Prosite"),
    array("reactome.txt", "REACTOME pathways", "Groups of proteins in the same pathways", "http://www.reactome.org/PathwayBrowser/", "REACTOME"),
    array("smart.txt", "smart domains", "Proteins with similar domains and features", "http://smart.embl-heidelberg.de/", "SMART"),
    array("smpdb.txt", "SMPDB pathways", "Groups of proteins in the same pathways", "http://www.smpdb.ca/", "SMPDB"),
    array("wikipathways.txt", "WikiPathways", "Groups of proteins in the same pathways", "http://www.wikipathways.org/index.php/Special:BrowsePathwaysPage", "Wikipathways")
);

$report_filename = 'geneOntology.html';


?><!DOCTYPE html>
<html lang="en">
<head>
	<?php include_once($BXAF_CONFIG['BXAF_PAGE_HEADER']); ?>

    <link   href='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/datatables/datatables.min.css.php' rel='stylesheet' type='text/css'>
	<script src='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/datatables/datatables.min.js.php'></script>

	<script type="text/javascript">
		$(document).ready(function(){

            var table = $('#myDataTable').DataTable({ 'pageLength': 100, 'lengthMenu': [[10, 100, 500, 1000], [10, 100, 500, 1000]], dom: 'Blfrtip', buttons: ['colvis','copy','csv'] });

			$('.toggle-vis').on( 'click', function (e) {
		        var column = table.column( $(this).val() );
		        column.visible( ! column.visible() );
		    } );
            // hide GO ID by default
            table.column( 4 ).visible( false );
            // hide Common Genes by default
            table.column( 9 ).visible( false );
		});

	</script>

    <style>
        #myDataTable thead tr th{
            white-space: nowrap!important;
        }
    </style>

</head>
<body>
	<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_MENU'])) include_once($BXAF_CONFIG['BXAF_PAGE_MENU']); ?>

	<div id="bxaf_page_wrapper" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_WRAPPER']; ?>">

		<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_LEFT'])) include_once($BXAF_CONFIG['BXAF_PAGE_LEFT']); ?>

		<div id="bxaf_page_right" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT']; ?>">

			<div id="bxaf_page_right_content" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT_CONTENT']; ?>">



				<div class="container">

					<div class="d-flex flex-row mt-3">

						<p class="align-self-baseline">Experiment: </p>
						<p class="align-self-baseline ml-2 lead"><a href="experiment.php?id=<?php echo $experiment_info['ID']; ?>" class=""><?php echo $experiment_info['Name']; ?></a></p>

						<p class="align-self-baseline ml-5">Analysis: </p>
						<p class="align-self-baseline ml-2 lead"><a href="analysis.php?id=<?php echo $analysis_id; ?>" class=""><?php echo $analysis_info['Name']; ?></a></p>

                        <p class="align-self-baseline ml-5 lead"><a href="report_full.php?analysis=<?php echo $analysis_id_encrypted; ?>" class=""><i class="fas fa-flag"></i> Full Report</a></p>

					</div>

					<hr class='w-100' />

					<h1 class="w-100 my-4 text-center">Gene Ontology Enrichment Results</h1>

<?php
    $directions = array('Up', 'Down');

	echo "<div class='w-100 my-3'>Comparisions: ";
	foreach($all_comparisons as $comparison){
        foreach($directions as $direct){
            echo "<a href='report_enrichment.php?analysis=$analysis_id_encrypted&comp=$comparison&direction=$direct' class='mx-2 " . (($current_comparison == $comparison && $direct == $direction) ? "btn btn-success" : "") . "' title='$comparison - $direct Regulated'>$comparison <i class='fas fa-arrow-" . strtolower($direct) . "'></i></a>";
        }
        echo "<span class='ml-4'>&nbsp;</span>";
	}
	echo "</div>";

    echo "<hr class='w-100' />";

    echo "<div class='w-100 my-3 text-warning'>Text file version of complete results (i.e. open with Excel) <a class='mx-5' href='Javascript: void(0);' onClick='if(\$(\"#all_file_list\").hasClass(\"hidden\")) \$(\"#all_file_list\").removeClass(\"hidden\"); else \$(\"#all_file_list\").addClass(\"hidden\"); '><i class='fas fa-arrow-right'></i> Show/Hide</a></div>";
    echo "<ul id='all_file_list' class='my-3 hidden'>";
    foreach($go_analysis_files as $files){
        echo "<li><a href='" . $report_url . $files[0]. "'>" . $files[1]. "</a> " . $files[2]. " (<a href='" . $files[3]. "' target='_blank'>" . $files[4]. "</a>)</li>";

    }
    echo "</ul>";

    echo "<hr class='w-100' />";

    echo "<h3 class='w-100 my-4'>Enriched Categories</h3>";

    echo '<div class="my-3">
                <strong>Toggle columns:</strong>
                <input type="checkbox" checked class="toggle-vis mx-2" value="0">P-value
                <input type="checkbox" checked class="toggle-vis mx-2" value="1">ln(P)
                <input type="checkbox" checked class="toggle-vis mx-2" value="2">Term
                <input type="checkbox" checked class="toggle-vis mx-2" value="3">GO Tree
                <input type="checkbox" class="toggle-vis mx-2" value="4">GO ID
                <input type="checkbox" checked class="toggle-vis mx-2" value="5">#1 (Number of Genes in Term)
                <input type="checkbox" checked class="toggle-vis mx-2" value="6">#2 (Number of Target Genes in Term)
                <input type="checkbox" checked class="toggle-vis mx-2" value="7">#3 (Number of Total Genes)
                <input type="checkbox" checked class="toggle-vis mx-2" value="8">#4 (Number of Target Genes)
                <input type="checkbox" class="toggle-vis mx-2" value="9">Common Genes
            </div>';

    // echo "<div class='my-1 text-warning' style='font-size: 1rem;'>Note for columns: #1: Number of Genes in Term, #2: Number of Target Genes in Term, #3: Number of Total Genes, #4: Number of Target Genes</div>";

    $report_file_content = '';
    if(file_exists($report_dir . $report_filename)){

        $report_file_content = str_replace("\n", "", str_replace("\r", "", file_get_contents($report_dir . $report_filename)));

        $report_file_content = str_replace('</TABLE></BODY></HTML>', '', substr($report_file_content, strpos($report_file_content, '<TABLE border="1" cellpading="0" cellspacing="0">') + strlen('<TABLE border="1" cellpading="0" cellspacing="0">') ) );

        $report_file_content = str_replace('<TR><TH>P-value</TH><TD>ln(P)</TD><TD>Term</TD><TD>GO Tree</TD><TD>GO ID</TD><TD># of Genes in Term</TD><TD># of Target Genes in Term</TD><TD># of Total Genes</TD><TD># of Target Genes</TD><TD>Common Genes</TD></TR>', "", str_replace('_', ' ', str_replace(',', ', ', $report_file_content)));

        $content_array = explode("</TR><TR>", $report_file_content);
        $content_array = array_slice($content_array, 0, 500);

        $report_file_content = implode("</TR><TR>", $content_array);

        $report_file_content = "<div class='w-100 table-responsive'><table id='myDataTable' class='table tabl-sm'><thead><TR class='table-info'><TH>P-value</TH><TD>ln(P)</TD><TD>Term</TD><TD>GO Tree</TD><TD>GO ID</TD><TD title='Number of Genes in Term' class='text-danger'>#1</TD><TD class='text-danger' title='Number of Target Genes in Term'>#2</TD><TD class='text-danger' title='Number of Total Genes'>#3</TD><TD class='text-danger' title='Number of Target Genes'>#4</TD><TD>Common Genes</TD></TR></thead><tbody>$report_file_content</tbody></TABLE></div>";

        echo $report_file_content;

        echo "<div class='my-2 text-warning'>Column #1: Number of Genes in Term<BR>Column #2: Number of Target Genes in Term<BR>Column #3: Number of Total Genes<BR>Column #4: Number of Target Genes</div>";

    }

?>



				</div>



            </div>

		    <?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>

		</div>

	</div>

</body>
</html>