<?php

	//Start: Output the content to index.html
	ob_start();
	//End: Output the content to index.html

	if (isset($_GET['action']) && $_GET['action']!=''){
		$folder = urldecode($_GET['action']).'/';
	} else {$folder = ''; }

	if (isset($_GET['number']) && $_GET['number']!=''){
		$number = urldecode($_GET['number']).'/';
	} else {$number = urldecode($_GET['action']).'/'; }

	unset($number);

	$files_read = array(
						'Correlation.png'=>$folder.'Correlation.png',
						'Expressed_Genes.csv'=>$folder.'Expressed_Genes.csv',
						'Expressed_Genes.pdf'=>$folder.'Expressed_Genes.pdf',
						'Expressed_Genes.png'=>$folder.'Expressed_Genes.png',
						'gene_counts.txt'=>$folder.'gene_counts.txt',
						'gene_counts.txt.summary'=>$folder.'gene_counts.txt.summary',
						'Libary.Size.Normalization.csv'=>$number.'Libary.Size.Normalization.csv',
						'logCPM_BoxPlot.pdf'=>$folder.'logCPM_BoxPlot.pdf',
						'logCPM_BoxPlot.png'=>$folder.'logCPM_BoxPlot.png',
						'Mapping_Summary.csv'=>$folder.'Mapping_Summary.csv',
						'Mapping_Summary.pdf'=>$folder.'Mapping_Summary.pdf',
						'Mapping_Summary.png'=>$folder.'Mapping_Summary.png',
						'norm_MDSplot.pdf'=>$folder.'norm_MDSplot.pdf',
						'norm_MDSplot.png'=>$folder.'norm_MDSplot.png',
						'Outlier_Detection.csv'=>$number.'Outlier_Detection.csv',
						'Overall_Heatmap.pdf'=>$folder.'Overall_Heatmap.pdf',
						'Overall_Heatmap.png'=>$folder.'Overall_Heatmap.png',
						'Raw_Counts.csv'=>$folder.'Raw_Counts.csv',
						'Raw_counts_MDS.pdf'=>$folder.'Raw_counts_MDS.pdf',
						'Rplots.pdf'=>$folder.'Rplots.pdf',
						'Top_Genes.csv'=>$folder.'Top_Genes.csv',
						'Top_Genes.pdf'=>$folder.'Top_Genes.pdf',
						'Top_Genes.png'=>$folder.'Top_Genes.png',
						'voom_norm.pdf'=>$folder.'voom_norm.pdf',
						'QC_info.txt'=>$number.'QC_info.txt',
						'Overall_Heatmap_No_Outliers.png'=>$folder.'Overall_Heatmap_No_Outliers.png',
						'norm_MDSplot_No_Outliers.png'=>$folder.'norm_MDSplot_No_Outliers.png'
					   );
?>
<!doctype html>
<html>
<head>
<meta charset="UTF-8">
<title>RNA-Seq QC Report</title>

<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css">
<script src="https://code.jquery.com/jquery-2.1.4.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js"></script>


<!--Fonts-->
<link href='http://fonts.googleapis.com/css?family=Raleway:400' rel='stylesheet' type='text/css'>

<script>
	 $(function(){
		$(".dropdown").hover(
				function() {
					$('.dropdown-menu', this).stop( true, true ).fadeIn("fast");
					$(this).toggleClass('open');
					$('b', this).toggleClass("caret caret-up");
				},
				function() {
					$('.dropdown-menu', this).stop( true, true ).fadeOut("fast");
					$(this).toggleClass('open');
					$('b', this).toggleClass("caret caret-up");
				});
		});
</script>
<style>
.p_large {
	font-size:16px;
}
ol li{
	font-size:16px;
}
.no-margin{
	margin:0px;
}

.no-padding{
	padding:0px;
}
</style>
</head>

<body style="background-color:#DDDDDD;">
	<div class="container">

		<div class="row">
			<div class="col-md-10 col-md-offset-1">

				<nav class="navbar navbar-default navbar-fixed-top" role="navigation" style="background-color:black;">

					<div class="navbar-header" style="margin-left:10%;">
					  <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
						<span class="sr-only">Toggle navigation</span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
					  </button>
					  <a class="navbar-brand" href="http://bioinforx.com/" style="padding:5px;"><img class="img-responsive" src="http://bioinforx.com/lims/images_template/logo_bx.png" border="0"/ align="absmiddle" style="width:80%;"></a>
					</div>

					<div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
					  <ul class="nav navbar-nav">
						<li class="dropdown">
						  <a href="#" class="dropdown-toggle" data-toggle="dropdown" style="color:white;">Sections <i class="fas fa-chevron-down"></i></a>
						  <ul class="dropdown-menu" role="menu">
							<li><a href="#part1">1. Assign Reads to Genes</a></li>
							<li><a href="#part2">2. Number of Genes Detected</a></li>
							<li><a href="#part3">3. Percentage Reads from Most Highly Expressed Genes</a></li>
							<li><a href="#part4">4. Normalization and Boxplot of Gene Expression</a></li>
							<li><a href="#part5">5. Grouping and Clustering of Samples</a></li>
							<li><a href="#part6">6. Sample correlation</a></li>
							<li><a href="#part7">7. Outlier Detection (Experimental Feature)</a></li>
						  </ul>
						</li>
					  </ul>

					  <ul class="nav navbar-nav navbar-right" style="margin-right:10%;">
						<li class="pull-right"><a href="#part0" style="color:white;"><i class="fas fa-arrow-circle-up"></i> Back to top</a></li>
					  </ul>



					</div>

				</nav>
				<!--
					<nav class="navbar navbar-default">
						<div class="container-fluid">
						  <div class="navbar-header">
							<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
							  <span class="sr-only">Toggle navigation</span>
							  <span class="icon-bar"></span>
							  <span class="icon-bar"></span>
							  <span class="icon-bar"></span>
							</button>
							<a class="navbar-brand" href="#">Project name</a>
						  </div>
						  <div id="navbar" class="navbar-collapse collapse">
							<ul class="nav navbar-nav">
							  <li class="active"><a href="#">Home</a></li>
							  <li><a href="#">About</a></li>
							  <li><a href="#">Contact</a></li>
							  <li class="dropdown">
								<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">Dropdown <span class="caret"></span></a>
								<ul class="dropdown-menu" role="menu">
								  <li><a href="#">Action</a></li>
								  <li><a href="#">Another action</a></li>
								  <li><a href="#">Something else here</a></li>
								  <li class="divider"></li>
								  <li class="dropdown-header">Nav header</li>
								  <li><a href="#">Separated link</a></li>
								  <li><a href="#">One more separated link</a></li>
								</ul>
							  </li>
							</ul>
							<ul class="nav navbar-nav navbar-right">
							  <li class="active"><a href="./">Default <span class="sr-only">(current)</span></a></li>
							  <li><a href="../navbar-static-top/">Static top</a></li>
							  <li><a href="../navbar-fixed-top/">Fixed top</a></li>
							</ul>
						  </div>
						</div>
					  </nav>
					  -->
			</div>
		</div>


		<div class="row" id="part0">
			<br /><br /><br />
			<h1 style="text-align:center;font-family: 'Raleway', sans-serif;">RNA-Seq Data QC Analysis </h1>
		</div>


		<!-- PART 1 -->
		<div class="row" id="part1">
			<br /><br />
			<h3>1. Assign Reads to Genes</h3>
			<hr>
			<p class="p_large">The alignment bam files were compared against the gene annotation GFF file, and raw counts for each gene were generated using the <a href="http://bioinf.wehi.edu.au/featureCounts/" target="_blank">featureCounts</a> tool from subread. The graph below shows mapping and gene assignment summary. Click the graph to download a pdf version, you can also download a csv file <a href="<?php echo $files_read['Mapping_Summary.csv'];?>" download>containing the numbers</a>. </p><br />
			<div class="row">
				<a href="<?php echo $files_read['Mapping_Summary.pdf'];?>"><img src="<?php echo $files_read['Mapping_Summary.png'];?>" style="display:block;margin-left:auto;margin-right:auto;max-width:100%;"></a>
			</div>
			<div class="row">
				<br />
				<ul>
					<li class="p_large"><a href="<?php echo $files_read['Raw_Counts.csv']; ?>" download>The raw gene counts</a>: Open in Excel. This table lists the number of reads mapped to each gene.</li>
				</ul>
			</div>
			<p class="p_large">It is normal to observe some variation in number of reads across samples. However, samples with extremely low number of reads may not be suitable for downstream analysis, and we recommend checking the additional QC metrics below to identify potential outliers to exclude from downstream analysis.</p>
		</div>


		<!-- PART 2 -->
		<div class="row" id="part2">
			<br /><br />
			<h3>2. Number of Genes Detected</h3>
			<hr>
			<p class="p_large">Next, we performed additional QC at gene level. We first looked at number of genes detected. We count the number of genes that have at least 1, 2, 10, 50 or 100 counts. In generally, number of genes with 2 or more counts can be used as a rough estimate of how many genes are expressed. Genes with only 1 read could be noise. In addition, the number of genes with 10 or more reads is a good indicator of how many genes have enough reads for downstream statistical analysis. Click the graph to download a pdf version, you can also download a csv file <a href="<?php echo $files_read['Expressed_Genes.csv'];?>" download>containing the numbers</a>. </p><br />
			<div class="row">
				<a href="<?php echo $files_read['Expressed_Genes.pdf'];?>"><img src="<?php echo $files_read['Expressed_Genes.png'];?>" style="display:block;margin-left:auto;margin-right:auto;max-width:100%;"></a>
			</div>
			<br />
			<p class="p_large">We also try to detect outliers from this step. Any samples that show very small number of genes with 10 or more reads are potential outliers. The cutoff we used is 1/2 of the median across all samples.</p>
		</div>


		<!-- PART 3 -->
		<div class="row" id="part3">
			<br /><br />
			<h3>3. Percentage Reads from Most Highly Expressed Genes</h3>
			<hr>
			<div class="row">
				<div class="col-md-7">
					<p class="p_large">We also look at the percentage of reads belonging to the top genes. Basically we rank the genes by read counts, and compute the percentage of reads belonging to the top genes (up to top 100). </p>
					<p class="p_large">If majority of the reads come from top genes, then the sample probably has bottlenecking issues where a few genes were amplified many times by PCR during library preparation. </p>
					<p class="p_large">Most samples should have ~ 20% reads mapped to the top 100 genes.</p>
					<p class="p_large">If the top 100 genes account for more than 35% of all reads, we consider this sample as a potential outlier.</p>
					<p class="p_large">Click the graph to download a pdf version, you can also download a csv file <a href="<?php echo $files_read['Top_Genes.csv']; ?>" download>containing the numbers</a>. </p><br /><br />
				</div>
				<div class="col-md-5">

					<a href="<?php echo $files_read['Top_Genes.pdf']; ?>"><img src="<?php echo $files_read['Top_Genes.png']; ?>" style="display:block;margin-left:auto;margin-right:auto;width:100%;"></a>
				</div>
			</div>
			<br />
		</div>


		<!-- PART 4 -->
		<div class="row" id="part4">
			<br /><br />
			<h3>4. Normalization and Boxplot of Gene Expression</h3>
			<hr>
			<?php
				$myfile = fopen($files_read["QC_info.txt"], "r") or die("Unable to open file!");
				$info = fread($myfile,filesize($files_read["QC_info.txt"]));
				fclose($myfile);
				$info_array = explode(' ', $info);
			?>
			<p class="p_large" style="margin-left:0px;">The raw counts data were further processed by the following steps: </p><br />

			<div class="row no-margin">


					<div class="row no-margin">
						<div class="col-md-1 no-padding" style="margin-top:0;">
							<p class="p_large pull-right" style="margin-right:28%;"><b>1.</b></p> <br />
						</div>
						<div class="col-md-10 no-padding" style="margin-top:0; margin-left:-20px;">
							<p class="p_large">Remove genes that were not expressed. If a gene has counts per million (CPM) value >=1 in at least two of the samples, we consider it expressed in the experiment and include it for downstream QC analysis. <b>From <?php echo $info_array[2]; ?> total genes, <?php echo $info_array[5]; ?> genes are selected as expressed and used in downstream QC analysis.</b></p></li><br /><br />
						</div>
					</div>

					<div class="row no-margin" style="margin-top:0px;">
						<div class="col-md-1 no-padding" style="margin-top:-1.8%;">
							<p class="p_large pull-right" style="margin-right:28%;"><b>2.</b></p> <br />
						</div>

						<div class="col-md-4 no-padding" style="margin-top:-1.8%; margin-left:-20px;">
							<p class="p_large">The <a href="https://doi.org/10.1186/gb-2010-11-3-r25" target="_blank">TMM normalization method</a> was used to scale samples to remove differences in the composition of the RNA population between samples. It is done with the <a href="http://www.bioconductor.org/packages/release/bioc/html/edgeR.html" target="_blank">edgeR package</a>. The normalization factors for all samples are listed below. You can also download the <a href="Library.Size.Normalization.csv" download>csv file</a>. </p><br />
							<p class="p_large">At this step, we also try to identity outliers that have extreme normalization factors (>1.5 or <0.66). Note sometimes samples with large biological differences can have extreme normalization factors.</p> <br />
						</div>

						<div class="col-md-6" style="margin-top:-1.8%;">
							<?php
								$file = fopen($files_read["Libary.Size.Normalization.csv"],"r");
								$library_array = array();
								while(! feof($file)) {
										$library_array[] = fgetcsv($file);
									//print_r(fgetcsv($file));echo '<br>';
								}

								fclose($file);
								//print_r($library_array);
								//print_r($library_array[0]);
							?>
							<div class="col-md-12">
								<table class="table table-striped" style="font-size:16px;border: 1px solid black;margin-left:auto; margin-right:auto;">
									<thead style="border: 1px solid black;">
										<tr style="border: 1px solid black;">
											<th style="border-bottom:1px solid black;">Name</th>
											<th style="border-bottom:1px solid black;">group</th>
											<th style="border-bottom:1px solid black;">lib.size</th>
											<th style="border-bottom:1px solid black;">norm.factors</th>
										</tr>
									</thead>
									<tbody style="border: 1px solid black;">
										<?php foreach ($library_array as $key=>$value) {  ?>
											<?php if (intval($key)!=0 && $value!='') { ?>
												<tr style="border: 1px solid black;">
													<td><?php echo $value[0]; ?></td>
													<td><?php echo $value[1]; ?></td>
													<td><?php echo $value[2]; ?></td>
													<td><?php echo $value[3]; ?></td>
												</tr>
											<?php } ?>
										<?php } ?>
									</tbody>
								</table>
							</div>
						</div>
					</div>


					<div class="row no-margin">

						<div class="col-md-1 no-padding" style="margin-top:0;">
							<p class="p_large pull-right" style="margin-right:28%;"><b>3.</b></p> <br />
						</div>
						<div class="col-md-10 no-padding" style="margin-top:0; margin-left:-20px;">
							<p class="p_large">The normalized gene counts were transformed to log2 scale using <a href="http://genomebiology.com/2014/15/2/R29" target="_blank">voom method</a> from the <a href="http://www.bioconductor.org/packages/release/bioc/html/limma.html" target="_blank">R Limma package</a>. We created boxplot for each sample to summarize gene expression. </p><br />
							<a href="<?php echo $files_read['logCPM_BoxPlot.pdf']; ?>"><img src="<?php echo $files_read['logCPM_BoxPlot.png']; ?>" style="display:block;margin-left:auto;margin-right:auto;max-width:100%;"></a><br />
							<p class="p_large">Since this is normalized data, most samples should look similar. Samples with high or low distribution may be outliers (or have large biological differences).</p>
						</div>

					</div>


			</div>
			<br />
		</div>


		<!-- PART 5 -->
		<div class="row" id="part5">
			<br /><br />
			<h3>5. Grouping and Clustering of Samples</h3>
			<hr><br />

				<div class="row no-margin">


					<div class="row no-margin">
						<div class="col-md-1 no-padding" style="margin-top:0;">
							<p class="p_large pull-right" style="margin-right:28%;"><b>1.</b></p> <br />
						</div>
						<div class="col-md-5 no-padding" style="margin-top:0; margin-left:-20px;">
							<p class="p_large">We first create multidimensional plot to view sample relationships. This is done using <a href="http://www.bioconductor.org/packages/release/bioc/html/limma.html" target="_blank">R Limma package</a>.</p><br />
							<p class="p_large">Here biological replicates should cluster together, and difference conditions ideally should separate from each other. </p><br />
						</div>
						<div class="col-md-5 no-padding" style="margin-top:0;">
							<a href="<?php echo $files_read['norm_MDSplot.pdf']; ?>"><img src="<?php echo $files_read['norm_MDSplot.png']; ?>" style="display:block;margin-left:auto;margin-right:auto;max-width:100%;" class="img-responsive"></a>
						</div>
					</div>

					<div class="row no-margin" style="margin-top:50px;">
						<div class="col-md-1 no-padding" style="margin-top:0;">
							<p class="p_large pull-right" style="margin-right:28%;"><b>2.</b></p> <br />
						</div>
						<div class="col-md-5 no-padding" style="margin-top:0; margin-left:-20px;">
							<p class="p_large">Very often hierarchical clustering can give better indication of the sample and gene relationships. We used <a href="http://www.bioconductor.org/packages/release/bioc/html/made4.html" target="_blank">made4 package</a> from R to cluster samples and draw a heatmap.</p>
							<p class="p_large">We selected genes that have variable expression across samples to make the heatmap. These variable genes were chosen based on standard deviation (SD) of expression values larger than 30% of the mean expression values (Mean). If there are more than >5000 variable genes, we first remove genes with mean logCPM<1, then rank genes by SD/Mean to get the top 5000 genes. </p>
							<p class="p_large"><b>The heatmap is created from <?php echo $info_array[9]; ?> variable genes. </b></p>
							<p class="p_large">In the heatmap above, we selected genes that changed across samples (normally by SD/mean > 0.3), and plotted the relatively gene expression levels (blue is low, red is high). Gene names are not shown due to large number of genes used to create the heatmap. Both genes and samples are clustered in the heatmap. Normally biological replicates should cluster together, and ideally there should be up- or down- regulated genes between different conditions.</p>
							<p class="p_large">Heatmap can be used to detect overall patterns, as well as outlier samples. </p><br />
						</div>
						<div class="col-md-5 no-padding" style="margin-top:0;padding-left:15px;">
							<a href="<?php echo $files_read['Overall_Heatmap.pdf']; ?>"><img src="<?php echo $files_read['Overall_Heatmap.png']; ?>" style="display:block;margin-left:auto;margin-right:auto;margin-top:auto;margin-bottom:auto;max-width:100%;" class="img-responsive"></a>
						</div>
					</div>

				</div>


			<br />
		</div>


		<!-- PART 6 -->
		<div class="row" id="part6">
			<br /><br />
			<h3>6. Sample correlation</h3>
			<hr>
			<p class="p_large">We also created scatter plots for the correlation between sample pairs. If there are many samples, you may need to download the graph and view it at full size. Again, the idea here is that biological replicates should look similar in the scatter plot, and should have high correlation values.</p><br />
			<div class="row">
				<a href="<?php echo $files_read['Correlation.png']; ?>"><img src="<?php echo $files_read['Correlation.png']; ?>" style="display:block;margin-left:auto;margin-right:auto;max-width:100%;"></a>
			</div>
		</div>


		<!-- PART 7 -->
		<div class="row" style="margin-bottom:200px;" id="part7">
			<br /><br />
			<h3>7. Outlier Detection (Experimental Feature)</h3>
			<hr>
			<p class="p_large">Please note automatic outlier detection will not be very accruate, especially when true biological differences are large. We encourage researchers to use additional information to make the final judgment. A few things to consider include: do biological replicates match each other? How do different experimental conditions separate in the heatmap? Are there potential biological conditions (e.g. strong overexpression of certain genes) that will cause extreme RNA composition? </p>
			<p class="p_large">We tried to use the number of genes detected, normalization factor and % reads from top gene to identify outliers. The results are listed below (or you can download <a href="<?php echo $files_read['Outlier_Detection.csv']; ?>" download>csv file</a>). In the "rate" column, * indicates potential outliers. </p><br />
			<div class="row">
				<div class="col-md-12">
					<?php
						$file = fopen($files_read["Outlier_Detection.csv"],"r");
						$library_array = array();
						while(! feof($file)) {
								$library_array[] = fgetcsv($file);
							//print_r(fgetcsv($file));echo '<br>';
						}

						fclose($file);
						//print_r($library_array);
						//print_r($library_array[0]);
					?>
						<table class="table table-striped" style="font-size:16px;margin-left:auto; margin-right:auto;">
							<thead style="border: 1px solid black;">
								<tr style="border: 1px solid black;">
									<th style="border-bottom:1px solid black;">Name</th>
									<th style="border-bottom:1px solid black;">norm factor</th>
									<th style="border-bottom:1px solid black;">norm factor rate</th>
									<th style="border-bottom:1px solid black;">gene count10</th>
									<th style="border-bottom:1px solid black;">gene count10 rate</th>
									<th style="border-bottom:1px solid black;">Top100</th>
									<th style="border-bottom:1px solid black;">Top100 rate</th>
								</tr>
							</thead>
							<tbody style="border: 1px solid black;">
								<?php foreach ($library_array as $key=>$value) {  ?>
									<?php if (intval($key)!=0 && $value!='') { ?>
										<tr style="border: 1px solid black;">
											<td><?php echo $value[0]; ?></td>
											<td><?php echo sprintf("%.3f", $value[1]); ?></td>
											<td><?php echo $value[2]; ?></td>
											<td><?php echo $value[3]; ?></td>
											<td><?php echo $value[4]; ?></td>
											<td><?php echo sprintf("%.3f", $value[5]); ?></td>
											<td><?php echo $value[6]; ?></td>
										</tr>
									<?php } ?>
								<?php } ?>
							</tbody>
						</table>
				</div>
			</div>
			<br />
			<p class="p_large"><b>Out of the <?php echo $info_array[13]; ?> samples, <?php echo $info_array[17]; ?> <?php if($info_array[17]==1 || $info_array[17]==0){echo 'is';}else{echo 'are';}?> considered as potential outlier.  </b></p><br />
			<?php if (intval($info_array[17]) > 0) { ?>
				<p class="p_large">We also created the MDS plot and Heatmap without outlier samples. Please see below.</p><br />
				<div class="row">
					<div class="col-md-6">
						<a href="norm_MDSplot_No_Outliers.pdf"><img src="<?php echo $files_read['norm_MDSplot_No_Outliers.png']; ?>" style="display:block;margin-left:auto;margin-right:auto;max-width:100%;"></a>
					</div>
					<div class="col-md-6">
						<a href="Overall_Heatmap_No_Outliers.pdf"><img src="<?php echo $files_read['Overall_Heatmap_No_Outliers.png']; ?>" style="display:block;margin-left:auto;margin-right:auto;max-width:100%;"></a>
					</div>
				</div>
			<?php } ?>
		</div>








	</div>
</body>
</html>
<?php
	//Start: Output the content to index.html
	$output = ob_get_clean();
	file_put_contents('index.html', $output);

	echo $output;
	//End: Output the content to index.html

	//copy("http://144.92.92.126:8003/mike/others/RNA_auto/soybean/auto_report.php", "index01.html");
?>
