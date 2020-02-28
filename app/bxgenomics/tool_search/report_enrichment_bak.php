<?php

//To disable login requirement
//$BXAF_CONFIG_CUSTOM['PAGE_LOGIN_REQUIRED'] = false;

include_once(__DIR__ . "/config.php");

$comp_subdir = '';
if (isset($_GET['comp_subdir']) && trim($_GET['comp_subdir']) != '') {
  $comp_subdir = $_GET['comp_subdir'];
}

// $comp_subdir = "app_data/bxgenomics/page_output/$species/comp_$current_comparison/comp_{$current_comparison}_GO_Analysis_$direction/";

$report_dir = $BXAF_CONFIG['BXAF_DIR'] . $comp_subdir;
$report_url = $BXAF_CONFIG['BXAF_URL'] . $comp_subdir;
$report_filename = 'geneOntology.html';

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

?><!DOCTYPE html>
<html lang="en">
<head>
	<?php include_once($BXAF_CONFIG['BXAF_PAGE_HEADER']); ?>

    <link   href='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/datatables/datatables.min.css.php' rel='stylesheet' type='text/css'>
	<script src='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/datatables/datatables.min.js.php'></script>

	<script type="text/javascript">
		$(document).ready(function(){

            var table = $('#myDataTable').DataTable({"pageLength": 100, "lengthMenu": [[10, 100, 500, 1000], [10, 100, 500, 1000]], dom: 'Blfrtip', buttons: ['colvis','copy','csv'] });

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

</head>
<body>
	<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_MENU'])) include_once($BXAF_CONFIG['BXAF_PAGE_MENU']); ?>

	<div id="bxaf_page_wrapper" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_WRAPPER']; ?>">

		<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_LEFT'])) include_once($BXAF_CONFIG['BXAF_PAGE_LEFT']); ?>

		<div id="bxaf_page_right" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT']; ?>">

			<div id="bxaf_page_right_content" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT_CONTENT']; ?>">



				<div class="container-fluid">
					<div class="d-flex flex-row mt-3">
						<h1 class="align-self-baseline">Gene Ontology Enrichment Results</h1>
						<p class="align-self-baseline ml-5 lead"><a href="view.php?type=comparison&id=<?php echo $_GET['id']; ?>" class=""><i class='fas fa-undo'></i> Back to Comparison Details</a></p>
					</div>

					<hr class='w-100' />

<?php

    echo "<div class='w-100 my-3 text-warning'>Text file version of complete results (i.e. open with Excel) <a class='mx-5' href='Javascript: void(0);' onClick='if(\$(\"#all_file_list\").hasClass(\"hidden\")) \$(\"#all_file_list\").removeClass(\"hidden\"); else \$(\"#all_file_list\").addClass(\"hidden\"); '><i class='fas fa-arrow-right'></i> Show/Hide</a></div>";
    echo "<ul id='all_file_list' class='my-3 hidden'>";
    foreach($go_analysis_files as $files){
        if(file_exists($BXAF_CONFIG['BXAF_DIR'] . $comp_subdir . $files[0])) echo "<li><a href='" . $BXAF_CONFIG['BXAF_URL'] . $comp_subdir . $files[0] . "'>" . $files[1]. "</a> " . $files[2]. " (<a href='" . $files[3]. "' target='_blank'>" . $files[4]. "</a>)</li>";

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


    $report_file_content = '';
    if(file_exists($report_dir . $report_filename)){

        $report_file_content = str_replace("\n", "", str_replace("\r", "", file_get_contents($report_dir . $report_filename)));

        $report_file_content = str_replace('</TABLE></BODY></HTML>', '', substr($report_file_content, strpos($report_file_content, '<TABLE border="1" cellpading="0" cellspacing="0">') + strlen('<TABLE border="1" cellpading="0" cellspacing="0">') ) );

        $report_file_content = str_replace('<TR><TH>P-value</TH><TD>ln(P)</TD><TD>Term</TD><TD>GO Tree</TD><TD>GO ID</TD><TD># of Genes in Term</TD><TD># of Target Genes in Term</TD><TD># of Total Genes</TD><TD># of Target Genes</TD><TD>Common Genes</TD></TR>', "", str_replace('_', ' ', str_replace(',', ', ', $report_file_content)));

        $content_array = explode("</TR><TR>", $report_file_content);
        $content_array = array_slice($content_array, 0, 500);

        $report_file_content = implode("</TR><TR>", $content_array);

        $report_file_content = "<div class='w-100'><table id='myDataTable' class='table table-bordered table-striped w-100'><thead><tr class='table-info'><th>P-value</th><th>ln(P)</th><th>Term</th><th>GO Tree</th><th>GO ID</th><th title='Number of Genes in Term' class='text-danger'>#1</th><th class='text-danger' title='Number of Target Genes in Term'>#2</th><th class='text-danger' title='Number of Total Genes'>#3</th><th class='text-danger' title='Number of Target Genes'>#4</th><th>Common Genes</th></tr></thead><tbody>$report_file_content</tbody></TABLE></div>";

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