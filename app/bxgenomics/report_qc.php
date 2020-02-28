<?php

//To disable login requirement
//$BXAF_CONFIG_CUSTOM['PAGE_LOGIN_REQUIRED'] = false;

include_once(dirname(__FILE__) . "/config/config.php");

$BXAF_CONFIG['PAGE_MENU_ITEMS'][] =  array(
    'Name'=>'In-Page Navigation',
    'Children'=>array(
        array(
            'Name' => 'Assign Reads to Genes',
            'URL' => '#part1',
        ),
        array(
            'Name' => 'Number of Genes Detected',
            'URL' => '#part2',
        ),
        array(
            'Name' => 'Percentage Reads from Most Highly Expressed Genes',
            'URL' => '#part3',
        ),
        array(
            'Name' => 'Normalization and Boxplot of Gene Expression',
            'URL' => '#part4',
        ),
        array(
            'Name' => 'Grouping and Clustering of Samples',
            'URL' => '#part5',
        ),
        array(
            'Name' => 'Sample correlation',
            'URL' => '#part6',
        ),
        array(
            'Name' => 'Outlier Detection',
            'URL' => '#part7',
        ),
    ),
);


// e.g. http://yz.bxaf.com:8002/bxgenomics_v2.2/app/bxgenomics/report_qc.php?analysis=6_Cd8PPQJZa2EDV--Y4tlgdtSmdwrzaGClkYT9XAFFecQ

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

$sql = "SELECT * FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_EXPERIMENT'] . "` WHERE `ID` = " . $analysis_info['Experiment_ID'];
$experiment_info = $BXAF_MODULE_CONN -> get_row($sql);


$files_url = array();
$files_dir = array();
$target_dir = $analysis_dir . 'alignment/QC/';
$target_url = $analysis_url . 'alignment/QC/';
if(file_exists($target_dir)){
    $d = dir($target_dir);
    while (false !== ($f = $d->read())) {
       if(is_file($target_dir . $f)) {
           $files_url[$f] = $target_url . $f;
           $files_dir[$f] = $target_dir . $f;
       }
    }
    $d->close();
}
// echo "target_url<pre>" . print_r($files_url, true) . "</pre>";

?><!DOCTYPE html>
<html lang="en">
<head>
	<?php include_once($BXAF_CONFIG['BXAF_PAGE_HEADER']); ?>

	<link   href='css/report.css' rel='stylesheet' type='text/css'>

	<script type="text/javascript">
		$(document).ready(function(){

            $('.scroll').on('click', function(event) {
                if (this.hash !== "") {
                    event.preventDefault();
                    var hash = this.hash;
                    $('html, body').animate({
                        scrollTop: $(hash).offset().top - 50
                    }, 800, function() {
                        window.location.hash = hash;
                    });
                }
            });


		});

	</script>

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

					<h1 class="w-100 text-center">BxGenomics - RNA-Seq QC Report</h1>


<?php
    if($analysis_info['Data_Type'] == 'gene_counts'){ echo '<div class="w-100 my-5 text-danger">Since this analysis is started from gene counts, there is no QC Report available.</div>'; }
    else {
?>

					<!-- PART 1 -->
					<div class="row my-3" id="part1">
						<h3>1. Assign Reads to Genes</h3>

						<p class="w-100 my-1">The alignment bam files were compared against the gene annotation GFF file, and raw counts for each gene were generated using the <a href="http://bioinf.wehi.edu.au/featureCounts/" target="_blank">featureCounts</a> tool from subread. The graph below shows mapping and gene assignment summary. Click the graph to download the pdf version. You can also <a href="<?php echo $files_url['Mapping_Summary.csv'];?>">download the csv file that contains the numbers</a>. </p>

                        <ul class="my-2"><li class="w-100 my-1"><a href="<?php echo $files_url['Raw_Counts.csv'];?>" target="_blank" >Download the raw gene counts in CSV format</a>. This file lists the number of reads mapped to each gene.</li></ul>

						<a class="mx-auto my-4" href="<?php echo $files_url['Mapping_Summary.pdf'];?>"><img class="img-fluid" src="<?php echo $files_url['Mapping_Summary.png'];?>" /></a>

						<p class="w-100 my-1">It is normal to observe some variation in number of reads across samples. However, samples with extremely low number of reads may not be suitable for downstream analysis, and we recommend checking the additional QC metrics below to identify potential outliers to exclude from downstream analysis.</p>

					</div>


					<!-- PART 2 -->
					<div class="row my-3" id="part2">

						<h3>2. Number of Genes Detected</h3>

						<p class="w-100 my-1">Next, we performed additional QC at gene level. We first looked at number of genes detected. We count the number of genes that have at least 1, 2, 10, 50 or 100 counts. In generally, number of genes with 2 or more counts can be used as a rough estimate of how many genes are expressed. Genes with only 1 read could be noise. In addition, the number of genes with 10 or more reads is a good indicator of how many genes have enough reads for downstream statistical analysis. Click the graph to download a pdf version, you can also download a csv file <a href="<?php echo $files_url['Expressed_Genes.csv'];?>" download>containing the numbers</a>. </p><br />

						<a class="mx-auto my-4" href="<?php echo $files_url['Expressed_Genes.pdf'];?>"><img class="img-fluid" src="<?php echo $files_url['Expressed_Genes.png'];?>" /></a>

						<p class="w-100 my-1">We also try to detect outliers from this step. Any samples that show very small number of genes with 10 or more reads are potential outliers. The cutoff we used is 1/2 of the median across all samples.</p>
					</div>


					<!-- PART 3 -->
					<div class="row my-3" id="part3">

						<h3>3. Percentage Reads from Most Highly Expressed Genes</h3>

						<div class="row my-3">
							<div class="col-md-7">
								<p class="w-100 my-1">We also look at the percentage of reads belonging to the top genes. Basically we rank the genes by read counts, and compute the percentage of reads belonging to the top genes (up to top 100). </p>
								<p class="w-100 my-1">If majority of the reads come from top genes, then the sample probably has bottlenecking issues where a few genes were amplified many times by PCR during library preparation. </p>
								<p class="w-100 my-1">Most samples should have ~ 20% reads mapped to the top 100 genes.</p>
								<p class="w-100 my-1">If the top 100 genes account for more than 35% of all reads, we consider this sample as a potential outlier.</p>

								<p class="w-100 my-1">Click the graph to download a pdf version. You can also download a csv file <a href="<?php echo $files_url['Top_Genes.csv']; ?>">download the csv file that contains the numbers</a>. </p>
							</div>

							<div class="col-md-5">
								<a href="<?php echo $files_url['Top_Genes.pdf']; ?>"><img class="img-fluid" src="<?php echo $files_url['Top_Genes.png']; ?>" /></a>
							</div>
						</div>
						<br />
					</div>


					<!-- PART 4 -->
					<div class="row" id="part4">

						<h3>4. Normalization and Boxplot of Gene Expression</h3>

                        <?php

                        $info = file_get_contents(str_replace($analysis_url, $analysis_dir, $files_url["QC_info.txt"]) );
                        $rows = explode("\n", file_get_contents(str_replace($analysis_url, $analysis_dir, $files_url["QC_info.txt"]) ) );
                        $info_array = array();
                        foreach($rows as $row){
                            list($k, $v) = explode("\t", $row);
                            if($k != '') $info_array[$k] = $v;
                        }

                        ?>

						<div class="w-100 my-1" style="margin-left:0px;">The raw counts data were further processed by the following steps: </div>

						<div class="row my-1">
							<div class="col">
								<strong>a)</strong> Remove genes that were not expressed. If a gene has counts per million (CPM) value >=1 in at least two of the samples, we consider it expressed in the experiment and include it for downstream QC analysis.
								<strong>From <?php echo $info_array['Total Genes']; ?> total genes, <?php echo $info_array['Selected Genes']; ?> genes are selected as expressed and used in downstream QC analysis.</strong>
							</div>
						</div>

						<div class="row my-1">
							<div class="col">
								<p class="w-100 my-1">
									<strong>b)</strong> The <a href="https://doi.org/10.1186/gb-2010-11-3-r25" target="_blank">TMM normalization method</a> was used to scale samples to remove differences in the composition of the RNA population between samples. It is performed with the <a href="http://www.bioconductor.org/packages/release/bioc/html/edgeR.html" target="_blank">edgeR package</a>. The normalization factors for all samples are listed below. You can <a href="<?php echo $files_url["Library.Size.Normalization.csv"]; ?>">download the csv file</a>.
								</p>

								<p class="w-100 my-1">
									At this step, we also try to identity outliers that have extreme normalization factors (&gt;1.5 or &lt;0.66). Note sometimes samples with large biological differences can have extreme normalization factors.
								</p>

							</div>

							<div class="col">
								<?php
                                    $library_array = array();
                                    if(file_exists($files_dir["Library.Size.Normalization.csv"])){
                                        $file = fopen($files_dir["Library.Size.Normalization.csv"], "r");
    									while(! feof($file)) {
    										$library_array[] = fgetcsv($file);
    									}
    									fclose($file);
                                    }
								?>
								<div class="w-100" style=" font-size: 0.8rem;">
									<table class="table table-bordered table-striped table-hover my-3">
										<thead>
											<tr class="table-info">
												<th>Name</th>
												<th>group</th>
												<th>lib.size</th>
												<th>norm.factors</th>
											</tr>
										</thead>
										<tbody>
											<?php foreach ($library_array as $key=>$value) {  ?>
												<?php if (intval($key)!=0 && $value!='') { ?>
													<tr>
														<td><?php echo $value[0]; ?></td>
														<td><?php echo $value[1]; ?></td>
														<td><?php echo $value[2]; ?></td>
														<td><?php echo sprintf("%.3f", $value[3]); ?></td>
													</tr>
												<?php } ?>
											<?php } ?>
										</tbody>
									</table>
								</div>

							</div>
						</div>

						<div class="row my-1">

							<div class="col">
								<p class="w-100 my-1"><strong>c)</strong> The normalized gene counts were transformed to log2 scale using <a href="https://doi.org/10.1186/gb-2014-15-2-r29" target="_blank">voom method</a> from the <a href="http://www.bioconductor.org/packages/release/bioc/html/limma.html" target="_blank">R Limma package</a>. We created boxplot for each sample to summarize gene expression. </p>

								<p class="w-100 my-1">Since this is normalized data, most samples should look similar. Samples with high or low distribution may be outliers (or have large biological differences).</p>

							</div>

						</div>


						<a class="mx-auto my-3" href="<?php echo $files_url['logCPM_BoxPlot.pdf']; ?>"><img class="img-fluid" src="<?php echo $files_url['logCPM_BoxPlot.png']; ?>"></a>

					</div>


					<!-- PART 5 -->
					<div class="row my-3" id="part5">

						<h3>5. Grouping and Clustering of Samples</h3>

						<div class="row my-1">
							<div class="col">
								<p class="w-100 my-1">
									<strong>a)</strong> We first create multidimensional plot to view sample relationships. This is done using <a href="http://www.bioconductor.org/packages/release/bioc/html/limma.html" target="_blank">R Limma package</a>.
								</p>

								<p class="w-100 my-1">
									Here biological replicates should cluster together, and difference conditions ideally should separate from each other.
								</p>

							</div>

							<div class="col">
								<a class="mx-auto my-3" href="<?php echo $files_url['norm_MDSplot.pdf']; ?>"><img class="img-fluid" src="<?php echo $files_url['norm_MDSplot.png']; ?>"></a>
							</div>
						</div>


						<div class="row my-1">
							<div class="col">
								<p class="w-100 my-1"><strong>b)</strong> Very often hierarchical clustering can give better indication of the sample and gene relationships. We used <a href="http://www.bioconductor.org/packages/release/bioc/html/made4.html" target="_blank">made4 package</a> from R to cluster samples and draw a heatmap.</p>

								<p class="w-100 my-1">We selected genes that have variable expression across samples to make the heatmap. These variable genes were chosen based on standard deviation (SD) of expression values larger than 30% of the mean expression values (Mean). If there are more than >5000 variable genes, we first remove genes with mean logCPM<1, then rank genes by SD/Mean to get the top 5000 genes. </p>

								<p class="w-100 my-3"><strong>The heatmap is created from <?php echo $info_array['Genes in heatmap']; ?> variable genes. </strong></p>

								<p class="w-100 my-1">In the heatmap above, we selected genes that changed across samples (normally by SD/mean > 0.3), and plotted the relatively gene expression levels (blue is low, red is high). Gene names are not shown due to large number of genes used to create the heatmap. Both genes and samples are clustered in the heatmap. Normally biological replicates should cluster together, and ideally there should be up- or down- regulated genes between different conditions.</p>

								<p class="w-100 my-1">Heatmap can be used to detect overall patterns, as well as outlier samples. </p>

							</div>

							<div class="col">
								<a class="mx-auto my-3" href="<?php echo $files_url['Overall_Heatmap.pdf']; ?>"><img class="img-fluid" src="<?php echo $files_url['Overall_Heatmap.png']; ?>"></a>
							</div>
						</div>

					</div>


					<!-- PART 6 -->
					<div class="row my-3" id="part6">

						<h3>6. Sample correlation</h3>

						<p class="w-100 my-1">We also created scatter plots for the correlation between sample pairs. If there are many samples, you may need to download the graph and view it at full size. Again, the idea here is that biological replicates should look similar in the scatter plot, and should have high correlation values.</p>

						<a class="mx-auto my-3" href="<?php echo $files_url['Correlation.png']; ?>"><img class="img-fluid" src="<?php echo $files_url['Correlation.png']; ?>"></a>

					</div>


					<!-- PART 7 -->
					<div class="row my-3" id="part7">

						<h3>7. Outlier Detection</h3>

						<p class="w-100 my-1">Please note automatic outlier detection will not be very accruate, especially when true biological differences are large. We encourage researchers to use additional information to make the final judgment. A few things to consider include: do biological replicates match each other? How do different experimental conditions separate in the heatmap? Are there potential biological conditions (e.g. strong overexpression of certain genes) that will cause extreme RNA composition? </p>

						<p class="w-100 my-1">We tried to use the number of genes detected, normalization factor and % reads from top gene to identify outliers. The results are listed below (or you can download <a href="<?php echo $files_url['Outlier_Detection.csv']; ?>" download>csv file</a>). In the "rate" column, * indicates potential outliers. </p>


						<?php
                            $library_array = array();
                            if(file_exists($files_dir["Outlier_Detection.csv"])){
                                $file = fopen($files_dir["Outlier_Detection.csv"], "r");
                                while(! feof($file)) {
                                    $library_array[] = fgetcsv($file);
                                }
                                fclose($file);
                            }
						?>
						<div class="w-100" style="font-size: 0.8rem;">
							<table class="table table-bordered table-striped table-hover my-3">
								<thead>
									<tr class="table-info">
										<th>Name</th>
										<th>norm factor</th>
										<th>norm factor rate</th>
										<th>gene count10</th>
										<th>gene count10 rate</th>
										<th>Top100</th>
										<th>Top100 rate</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($library_array as $key=>$value) {  ?>
										<?php if (intval($key)!=0 && $value!='') { ?>
											<tr>
												<td><?php echo $value[0]; ?></td>
												<td><?php echo sprintf("%.3f", $value[1]); ?></td>
												<td><?php echo $value[2]; ?></td>
												<td><?php echo $value[3]; ?></td>
												<td><?php echo $value[4]; ?></td>
												<td><?php echo sprintf("%.3f", $value[5]); ?></td>
												<td><?php echo $value[6] == '*' ? '<i class="fas fa-star text-danger"></i>' : ""; ?></td>
											</tr>
										<?php } ?>
									<?php } ?>
								</tbody>
							</table>
						</div>


						<p class="w-100 my-3 text-danger">Out of the <?php echo $info_array['Number of Samples']; ?> samples, <?php echo $info_array['Number of Outliers']; ?> <?php echo ($info_array['Number of Outliers'] <= 1) ? 'is' : 'are'; ?> considered as potential outlier.</p>

						<?php if (intval($info_array['Number of Samples']) > 0 && file_exists($files_dir["norm_MDSplot_No_Outliers.png"] ) && file_exists($files_dir["Overall_Heatmap_No_Outliers.png"] ) ) { ?>

							<p class="w-100 my-1">We have also created the MDS plot and Heatmap without outlier samples. Please see below.</p>

							<div class="row">
								<div class="col">
									<a class="mx-auto my-3" href="<?php echo $files_url["norm_MDSplot_No_Outliers.pdf"]; ?>"><img class="img-fluid" src="<?php echo $files_url['norm_MDSplot_No_Outliers.png']; ?>"></a>
								</div>
								<div class="col">
									<a class="mx-auto my-3" href="<?php echo $files_url['Overall_Heatmap_No_Outliers.pdf']; ?>"><img class="img-fluid" src="<?php echo $files_url['Overall_Heatmap_No_Outliers.png']; ?>"></a>
								</div>
							</div>

						<?php } ?>

					</div>

<?php } ?>

				</div>
            </div>
		    <?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>
		</div>
	</div>
</body>
</html>