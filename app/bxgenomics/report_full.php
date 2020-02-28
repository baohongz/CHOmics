<?php

//To disable login requirement
//$BXAF_CONFIG_CUSTOM['PAGE_LOGIN_REQUIRED'] = false;

include_once(dirname(__FILE__) . "/config/config.php");


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

$sql = "SELECT * FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_ANALYSIS'] . "` WHERE " . $BXAF_CONFIG['QUERY_ACTIVE_FILTER'] . " AND `ID` = $analysis_id";
$analysis_info = $BXAF_MODULE_CONN -> get_row($sql);

$all_comparisons = unserialize($analysis_info['Comparisons']);

$sql = "SELECT * FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_EXPERIMENT'] . "` WHERE " . $BXAF_CONFIG['QUERY_ACTIVE_FILTER'] . " AND `ID` = " . $analysis_info['Experiment_ID'];
$experiment_info = $BXAF_MODULE_CONN -> get_row($sql);

$sql = "SELECT `ID`, `Name`, `Description`, `Treatment_Name`, `Data_Type` FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_SAMPLE'] . "` WHERE " . $BXAF_CONFIG['QUERY_ACTIVE_FILTER'] . " AND `Experiment_ID` = " . $analysis_info['Experiment_ID'];
$sample_info = $BXAF_MODULE_CONN -> get_assoc('ID', $sql);




$index = max( array_keys( $BXAF_CONFIG['PAGE_MENU_ITEMS']) ) + 1;
$BXAF_CONFIG['PAGE_MENU_ITEMS'][ $index ] =  array(
    'Name'=>'In-Page Navigation',
    'Children' => array(
        array(
            'Name' => 'Project and Sample Description',
            'URL' => '#part1',
        ),
        array(
            'Name' => 'Gene Expression and Sample Grouping Overview',
            'URL' => '#part2',
        ),
        array(
            'Name' => 'Expression Data and Differentially Expressed Genes (DEGs)',
            'URL' => '#part3',
        ),
        array(
            'Name' => 'Functional Enrichment Analysis',
            'URL' => '#part4',
        ),
    ),
);

if($analysis_info['Data_Type'] != 'bam' && $analysis_info['Data_Type'] != 'gene_counts'){

    $BXAF_CONFIG['PAGE_MENU_ITEMS'][$index] ['Children'][] = array(
        'Name' => 'Raw Data and Alignment',
        'URL' => '#part5',
    );
    $BXAF_CONFIG['PAGE_MENU_ITEMS'][$index] ['Children'][] = array(
        'Name' => 'Technical Details and Methods',
        'URL' => '#part6',
    );

}

?><!DOCTYPE html>
<html lang="en">
<head>
	<?php include_once($BXAF_CONFIG['BXAF_PAGE_HEADER']); ?>
	<link   href='css/report.css' rel='stylesheet' type='text/css'>
	<script src="library/plotly.min.js"></script>

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

					</div>

					<hr class="w-100" />

					<h1 class="w-100 my-4 text-center">BxGenomics - RNA-Seq Data Analysis Report</h1>


        			<!-- PART 1 -->
        			<div class="row my-5" id='part1'>

        				<h3 class="w-100 mb-3">1. Project and Sample Description </h3>
        				<p class="w-100 my-1">The samples used in this project are listed below.</p>

        				<div class="w-100" style="border: 1px solid #999; font-size: 0.8rem; overflow-x: auto;">
        					<table class="table table-bordered table-striped table-hover my-0">
        						<thead>
        							<tr class="table-active">
        								<th>Sample Name</th>
        								<th>Treatment Name</th>
        								<th>Read Type</th>
        								<th>Description</th>
        							</tr>
        						</thead>
        						<tbody>
        						<?php
                                    $sample_ids = explode(",", $analysis_info['Samples']);
                                    foreach($sample_ids as $sample_id){
                                        if($_SESSION['BXAF_ADVANCED_USER']){
                                            echo '
            									<tr>
            										<td><a href="sample.php?id=' . $sample_id . '" target="_blank">' . $sample_info[$sample_id]['Name'] . '</a></td>
            										<td>' . $sample_info[$sample_id]['Treatment_Name'] . '</td>
            										<td>' . $sample_info[$sample_id]['Data_Type'] . '</td>
            										<td>' . $sample_info[$sample_id]['Description'] . '</td>
            									</tr>';
                                        }
                                        else {
                                            echo '
            									<tr>
            										<td>' . $sample_info[$sample_id]['Name'] . '</td>
            										<td>' . $sample_info[$sample_id]['Treatment_Name'] . '</td>
            										<td>' . $sample_info[$sample_id]['Data_Type'] . '</td>
            										<td>' . $sample_info[$sample_id]['Description'] . '</td>
            									</tr>';
                                        }
        							}
        						?>
        						</tbody>
        					</table>
        				</div>
        			</div>


        			<!-- PART 2 -->
        			<div class="row mt-5" id="part2">
        				<h3 class="w-100 mb-3">2. Gene Expression and Sample Grouping Overview </h3>

<?php if($analysis_info['Data_Type'] != 'gene_counts'){  ?>
        				<div class="row">
        					<div class="col-md-5 mb-2">
        						<p class="w-100 my-1">Hierarchical clustering can provide good indications of sample and gene relationships. In this overall heatmap, each column is a sample and each row represents the scaled expression values for one gene (blue is low, red is high).</p>
        						<p class="w-100 my-1"><a href="report_qc.php?analysis=<?php echo $analysis_id_encrypted; ?>#part5" target="_blank"><i class="fas fa-caret-right" aria-hidden="true"></i> View details of heatmap and sample grouping information</a></p>
        					</div>
        					<div class="col-md-7 mb-2">
        						<a href="<?php if(file_exists($analysis_dir . 'alignment/QC/Overall_Heatmap.pdf')) echo $analysis_url . 'alignment/QC/Overall_Heatmap.pdf'; ?>" target="_blank"><img class="img-fluid" src="<?php if(file_exists($analysis_dir . 'alignment/QC/Overall_Heatmap.png')) echo $analysis_url . 'alignment/QC/Overall_Heatmap.png'; ?>"></a>
        					</div>
        				</div>
<?php } else { echo '<div class="w-100 my-5 text-danger">Since this analysis is started from gene counts, there is no gene expression information.</div>'; }  ?>
        			</div>


        			<!-- PART 3 -->
        			<div class="row mt-5" id="part3">
        				<h3 class="w-100 mb-3">3. Expression Data and Differentially Expressed Genes (DEGs)</h3>

        				<?php
        					$DEG_process = array();
        					$f = $analysis_dir . 'alignment/DEG_Process.log';
        					if (($handle = fopen($f, "r")) !== FALSE) {
        						while (($row = fgetcsv($handle, 1000, "\t")) !== FALSE) {
        							if($row && is_array($row) && count($row) >= 2 && $row[0] != '') $DEG_process[$row[0]] = $row[1];
        					    }
        						fclose($handle);
        					}

        				?>
        				<p class="w-100 my-1">We first chose <span class="text-danger"><?php echo $DEG_process['Selected Genes']; ?></span> out of <span class="text-danger"><?php echo $DEG_process['Total Genes']; ?></span> annotated genes that are expressed in the experiment for downstream analysis. We also computed the expression values for each gene in reads per kilobase per million (RPKM) and displayed the RPKM values in the results. <a href="javascript:void(0);" onclick="$('#btn_view_detail_div').toggle('slow');"><i class="fas fa-caret-right" aria-hidden="true"></i> Read more</a></p>

        				<p class="w-100 my-1 font-italic text-muted hidden" id="btn_view_detail_div">A counts per million (CPM) cutoff of <?php echo sprintf("%.2f", $DEG_process['CPM cutoff for Expression']); ?> is computed based on the average read count of all samples (<?php echo sprintf("%.1f", intval($DEG_process['Mean Number of Reads'])/1000000); ?> million) so that this cpm cutoff roughly equals to 10 raw reads in this experiment. If a gene has CPM value ><?php echo sprintf("%.2f", $DEG_process['CPM cutoff for Expression']); ?> in at least two samples from the experiment, we include it for downstream analysis. From <?php echo $DEG_process['Total Genes']; ?> total genes, <?php echo $DEG_process['Selected Genes']; ?> genes passed this filter.</p>

        				<ul class="my-3">
        					<li><a href="<?php if(file_exists($analysis_dir . 'alignment/rpkm_annot.csv')) echo $analysis_url . 'alignment/rpkm_annot.csv';?>" target="_blank">Table listing RPKM values for all expressed genes in all samples</a> (Open with Excel)</li>
        				</ul>



        				<p class="w-100 my-1">Next we detected differentially expressed genes. See below for summary of each comparison, and click the view details links to view full report for each comparison and download data. <a href="javascript:void(0);" onclick="$('#btn_view_detail_div_2').toggle('slow');"><i class="fas fa-caret-right" aria-hidden="true"></i> Read more</a></p>

        				<p class="w-100 my-1 font-italic text-muted hidden" id="btn_view_detail_div_2"><i>The raw count data of the expressed genes was normalized for RNA composition using <a href="https://doi.org/10.1186/gb-2010-11-3-r25" target="_blank">TMM method</a> from <a href="http://www.bioconductor.org/packages/release/bioc/html/edgeR.html" target="_blank">EdgeR package</a>, then transformed to log2CPM values using <a href="https://doi.org/10.1186/gb-2014-15-2-r29" target="_blank">voom</a> method from the <a href="http://www.bioconductor.org/packages/release/bioc/html/limma.html" target="_blank">R Limma package</a>. Next linear model was built for each comparison using <a href="http://www.bioconductor.org/packages/release/bioc/html/limma.html" target="_blank">R Limma package</a>, and statistics for differential expression analysis were computed. The statistics values include log fold change (logFC), p-value, and false discovery rate (FDR, shown as adj.p.value in the table). The FDR values comes from p-values corrected for multiple hypothesis testing with Benjamini-Hochberg procedure. In most cases, FDR should be used instead of p-value to detect DEGs. To filter for differential expression, we normally recommend using standard cutoff (two fold change with FDR<=0.05), which can be expressed as logFC>=1 (up) or <=-1 (down), together with FDR<=0.05 in the data table. For comparisons that have 10 or less less genes that pass the standard cutoff, we switch to a low stringent cutoff (two fold change and p-value <=0.01) to report DEG results. This low cutoff can be expressed as logFC>=1 (up) or &lt;=-1 (down), together with P-value&lt;0.01 in the data table. The low stringent cutoff often produces more DEGs, but there might be more false positives identified. Researchers can also download the table listing all genes, and apply an appropriate cutoff based on the experiment. </i></p>

        				<?php
        					foreach($all_comparisons as $comparison){
                                $comp_link = "report_comparison.php?analysis=" . $analysis_id_encrypted . "&comp=" . $comparison;

                                $comp_picture = '';
                                if(file_exists($analysis_dir . "alignment/DEG/$comparison/DEG_Analysis/{$comparison}_DEG_sum_low.png")){
                                    $comp_picture = $analysis_url . "alignment/DEG/$comparison/DEG_Analysis/{$comparison}_DEG_sum_low.png";
                                }
                                else if(file_exists($analysis_dir . "alignment/DEG/$comparison/DEG_Analysis/{$comparison}_DEG_sum.png")){
                                    $comp_picture = $analysis_url . "alignment/DEG/$comparison/DEG_Analysis/{$comparison}_DEG_sum.png";
                                }
        				?>
        					<div class="row my-5">

        						<div class="col-md-8">
        							<h4>Comparison: <?php echo $comparison; ?></h4>
        							<p class="w-100 my-2"><a href="<?php echo $comp_link; ?>" target="_blank"><i class="fas fa-caret-right" aria-hidden="true"></i> View Details</a></p>

        							<?php
                                        $comp_summary_table = $analysis_dir . "alignment/DEG/$comparison/DEG_Analysis/{$comparison}_DEG_Summary.csv";
        								$file = fopen($comp_summary_table,"r") or die("Unable to open file!");
        								$library_array = array();
        								while(! feof($file)) $library_array[] = fgetcsv($file);
        								fclose($file);
        							?>
        							<div class="w-100">
        								<table class="table table-bordered table-striped table-hover my-0">
        									<thead>
        										<tr class="table-info">
        											<th>Cutoff</th>
        											<th>Standard (two fold and FDR 0.05)</th>
        											<th>Low Stringency (two fold and p-value 0.01)</th>
        										</tr>
        									</thead>
        									<tbody>
        										<?php foreach ($library_array as $id => $info) {
        												if (is_array($info) && trim($info[0])!='') {
        													if($library_array[3][1]>10){
        										?>
        											<tr>
        												<td><?php echo $info[0]; ?></td>
        												<td><?php echo $info[1]; ?><?php if(intval($id)==3){echo '*';}?></td>
        												<td><?php echo $info[2]; ?></td>
        											</tr>
        										<?php } else { ?>
        											<tr>
        												<td><?php echo $info[0]; ?></td>
        												<td><?php echo $info[1]; ?></td>
        												<td><?php echo $info[2]; ?><?php if(intval($id)==3){echo '*';}?></td>
        											</tr>
        										<?php } } } ?>
        									</tbody>
        								</table>
        							</div>
        							<p class="w-100 my-1">*These genes are reported in the tables for differentially expressed genes (DEGs) and shown in the DEG heat maps.</p>
        						</div>

        						<div class="col-md-4">
        							<a href="<?php echo $comp_link;?>" target="_blank"><img class="img-fluid" src="<?php echo $comp_picture;?>" style="max-width:400px;"></a>
        						</div>
        					</div>

        				<?php } ?>

        			</div>



        			<!-- PART 4 -->
        			<div class="row mt-5" id="part4">
        				<h3 class="w-100">4. Functional Enrichment Analysis</h3>

        				<div class="w-100 my-3">

        					<h4>Gene Set Enrichment Analysis</h4>

        					<p class="w-100 my-1">For each comparison, we performed GSEA analysis to identify enriched functional categories. If you are new to GSEA analysis, here is a guide on <a href="http://www.broadinstitute.org/gsea/doc/GSEAUserGuideTEXT.htm#_Interpreting_GSEA_Results" target="_blank">how to interpret GSEA results</a>.</p>

        					<p class="w-100 my-1">In the summary page below we provide two bar charts for each comparison, listing the top 10 enriched functional categories for up- and down- regulated genes respectively. Click the view details link for each comparison to view the full GSEA report. </p>

        					<ul>
        						<li><a href="<?php echo "report_gsea.php?analysis=" . $analysis_id_encrypted; ?>" target="_blank">GSEA Summary</a></li>
        					</ul>

        					<p class="w-100 my-1">In general, we prefer GSEA method over traditional GO enrichment method where enriched functions were searched in DEGs vs. all genes in the genome. This is because GSEA method does not rely on a arbitrary cutoff, and GSEA can be more sensitive for small changes that happen across a whole group of genes.</p>


        					<?php if (file_exists($analysis_dir . 'alignment/DEG/GO_Summary/')) { ?>
        						<h4 class="mt-3">Functional Enrichment using DEGs</h4>
        						<p class="w-100 my-1">We also searched for functional categories that are enriched in DEGs vs. genome. We did the analysis separately for up- and down- regulated DEGs. In the summary page below we provide six bar charts for each analysis, plotting the top 10 lists from Biological Process, Cellular Component , Molecular Function, KEGG Pathway, Molecular Signature, and Interpro Protein Domain. Click "View Details" link to view full list from all categories.</p>
        						<ul>
        							<li><a href="<?php echo "report_deg.php?analysis=" . $analysis_id_encrypted; ?>" target="_blank">Functional Enrichment Summary</a></li>
        						</ul>
        					<?php } ?>

        				</div>
        			</div>


<?php if($analysis_info['Data_Type'] != 'bam' && $analysis_info['Data_Type'] != 'gene_counts'){  ?>

        			<!-- PART 5 -->
        			<div class="row mt-5" id="part5">
        				<h3 class="w-100">5. Raw Data and Alignment</h3>

        				<div class="w-100 my-3">

        					<p class="w-100 my-1">The raw data (fastq format) is available here.</p>
        					<ul>
        						<li><a href="report_files.php?d=<?php echo bxaf_encrypt(str_replace($BXAF_CONFIG['BXAF_DIR'], '', $analysis_dir . 'raw_data/'), $BXAF_CONFIG['BXAF_KEY']) . '&title=' . urlencode("List of Raw fastq Files"); ?>" target="_blank">Raw fastq files</a></li>
        					</ul>

        					<p class="w-100 my-1">The alignment files (sorted and indexed) are available here. You can view the aligned reads in genome browsers that support bam file, e.g. <a href="https://www.broadinstitute.org/igv/" target="_blank">IGV</a>.</p>
        					<ul>
        						<li><p class="w-100 my-1"><a href="report_files.php?d=<?php echo bxaf_encrypt(str_replace($BXAF_CONFIG['BXAF_DIR'], '', $analysis_dir . 'alignment/Sorted_Bam/'), $BXAF_CONFIG['BXAF_KEY']) . '&title=' . urlencode("List of Alignment bam Files"); ?>" target="_blank">Alignment bam files</a></p></li>
        					</ul>

        				</div>
        			</div>


        			<!-- PART 6 -->
        			<div class="row mt-5" id="part6">
        				<h3 class="w-100">6. Technical Details and Methods</h3>

        				<div class="w-100 my-3">
        					<h4>Genome</h4>
        					<p class="w-100 my-1">
                            <?php
                                $sql = "SELECT `Detail` FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_SETTING'] . "` WHERE `Keyword`='Description' AND `Category`= ?s";
                                echo $BXAF_MODULE_CONN -> get_one($sql, array_shift(explode(' ', $analysis_info['Species'])) );
                            ?>
                            </p>

        					<p class="w-100 my-1">
        						<?php echo $BXAF_CONFIG['NECESSARY_FILES'][$_GET['species']]['Description']; ?>
        					</p>

        					<h4 class="mt-3">Sequence Data QC</h4>
        					<p class="w-100 my-1">The <a href="http://www.bioinformatics.babraham.ac.uk/projects/fastqc/" target="_blank">fastQC</a> program is used to verify raw data quality of the Illumina reads.</p>
        					<p class="w-100 my-1">The table below shows the basic statistics. <a href="<?php echo "report_fastqc.php?analysis=" . $analysis_id_encrypted; ?>" target="_blank"><i class="fas fa-caret-right" aria-hidden="true"></i> Review full report of fastQC</a></p>


        					<div class="w-100">

                            <?php

                                $QC_Statistics_All = array();
                                $QC_Summary_All = array();
                                $key_name = '';

                                $fastQC_files = bxaf_list_files_only($analysis_dir . 'fastQC/');

                                foreach($fastQC_files as $f){
                                    $f_name = array_pop( explode('/', trim($f) ) );
                                    if(preg_match("/_fastqc\.html$/", $f_name)){

                                        $key_name = str_replace("_fastqc.html", "", $f_name);

                                        $QC_Statistics_All[$key_name] = array();
                                        $QC_Statistics_All[$key_name]['url'] = $analysis_url . 'fastQC/' . $f_name;
                                        $QC_Statistics_All[$key_name]['dir'] = str_replace(".html", "", trim($f) );
                                    }
                                }


                                $fastqc_data_txt_key_columns = array('Filename', 'File type', 'Encoding', 'Total Sequences', 'Sequences flagged as poor quality', 'Sequence length', '%GC', '#Total Deduplicated Percentage');

                                foreach($QC_Statistics_All as $key_name => $row){

                                    if(file_exists( $row['dir'] . '/fastqc_data.txt' )){

                                        $handle = @fopen($row['dir'] . '/fastqc_data.txt', "r");
                                        if ($handle) {

                                            foreach($fastqc_data_txt_key_columns as $k){
                                                $QC_Statistics_All[$key_name][ $k ] = '';
                                            }

                                            while (($buffer = fgetcsv($handle, 0, "\t")) !== false) {
                                                if(! is_array($buffer) || count($buffer) <= 0) continue;
                                                if(in_array($buffer[0], $fastqc_data_txt_key_columns) ) $QC_Statistics_All[$key_name][ $buffer[0] ] = trim( $buffer[1] );
                                            }

                                            fclose($handle);
                                        }
                                    }

                                    if(file_exists( $row['dir'] . '/summary.txt' )){

                                        $handle = @fopen($row['dir'] . '/summary.txt', "r");
                                        if ($handle) {
                                            while (($cols = fgetcsv($handle, 0, "\t")) !== false) {
                                                if(count($cols) == 3){
                                                    $QC_Summary_All[ trim($cols[2]) ][ trim($cols[1]) ] = trim($cols[0]);
                                                }
                                            }
                                            fclose($handle);
                                        }

                                    }
                                }




                                $QC_Statistics_header = array(
                                    'Filename',
                                    // 'File type',
                                    // 'Encoding',
                                    'Total Sequences',
                                    'Sequences flagged as poor quality',
                                    'Sequence length',
                                    '%GC',
                                    '#Total Deduplicated Percentage',
                                );

                                echo '<table class="table table-bordered table-striped table-hover">';
                                echo '	<thead>';
                                echo '		<tr class="table-info">';
                                foreach($QC_Statistics_header as $name){
                                    echo "			<th>$name</th>";
                                }
                                echo '		</tr>';
                                echo '	</thead>';
                                echo '	<tbody>';
                                foreach($QC_Statistics_All as $key=>$val){
                                    echo '		<tr>';
                                    foreach($QC_Statistics_header as $name){
                                        if($name == 'Filename') echo "			<td><a href='" . $val['url'] . "' target='_blank'>" . $val[$name] . "</a></td>";
                                        else if($name == '#Total Deduplicated Percentage') echo "			<td>" . sprintf("%.1f", $val[$name]) . "%</td>";
                                        else echo "			<td>" . $val[$name] . "</td>";
                                    }
                                    echo '		</tr>';
                                }
                                echo '	</tbody>';
                                echo '</table>';

                            ?>

        					</div>


        					<h4 class="mt-4">Map Reads and QC at Gene Level</h4>

        					<ul>
        						<li><a href="<?php echo "report_qc.php?analysis=" . $analysis_id_encrypted; ?>" target="_blank">View full report of Mapping Summary and Gene Counts</a></li>
        					</ul>

        					<h5 class="mt-2">Analysis steps:</h5>

        					<ol>
        						<li>The raw sequence reads were mapped to the genome using Subjunc aligner from <a href="http://subread.sourceforge.net/" target="_blank">Subread</a>. The alignment bam files were compared against the gene annotation GFF file, and raw counts for each gene were generated using the <a href="http://bioinf.wehi.edu.au/featureCounts/" target="_blank">featureCounts</a> tool from subread.</li>
        						<li>Next, we performed additional QC at gene level, including number of genes detected, percentage of reads belonging to the top genes, normalization for RNA composition, and grouping and correlation between samples.</li>
        						<li>The raw counts data were normalized using <a href="https://doi.org/10.1186/gb-2014-15-2-r29" target="_blank">voom</a> method from the <a href="http://www.bioconductor.org/packages/release/bioc/html/limma.html" target="_blank">R Limma package</a>, then used for differential expression analysis. <a class="scroll" href="#part3"><i class="fas fa-caret-right" aria-hidden="true"></i> Review technical details</a>.</li>
        						<li>Based on expression changes, we further carried out analyses to detect enriched functions, see section 4 above for results.</li>
        					</ol>


        				</div>
        			</div>

<?php } // if($analysis_info['Data_Type'] != 'bam' && $analysis_info['Data_Type'] != 'gene_counts'){  ?>




				</div>
            </div>
		    <?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>
		</div>
	</div>
</body>
</html>