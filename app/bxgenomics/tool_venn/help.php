<?php

include_once(__DIR__ . '/config.php');



?><!DOCTYPE html>
<html lang="en">
<head>
<?php include_once($BXAF_CONFIG['BXAF_PAGE_HEADER']); ?>

<style>
.carousel{
	width: 1000px;
	border: 1px solid #CCC;
	border-radius: 10px;
	padding: 10px;
}
.carousel-indicators{
	bottom: -30px;
	padding: 10px;
	background-color: #AAA;
}
.carousel-caption{
	bottom: -80px;
	color: #000;
}
.carousel-inner{
	padding-bottom: 60px;
}
</style>

<script type="text/javascript">

	$(document).ready(function(){
		<?php
			if(isset($_GET['tab']) && $_GET['tab']!='') {
				echo "\$('#tab" . $_GET['tab'] . "').tab('show');\n";
			}
			else {
				echo "\$('#tab1').tab('show');\n";
			}
		?>
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


					<div class="my-3">
						<h1>
							Area-Proportional Venn Diagram Plotter and Editor
						</h1>

						<div class="my-3">
							Tip: Use this tool to generate area-proportional Venn Diagrams.
						</div>

						<div class="my-3 w-100">
							<a href="index.php" class="no_decoration btn btn-success btn-sm"><i class="fas fa-link"></i> Draw the Area-Proportional Venn Diagram directly</a>
							<a href="overlap.php" class="no_decoration btn btn-success btn-sm"><i class="fas fa-link"></i> Calculate overlap between lists and draw Venn Diagram</a>
						</div>

					</div>


					<div class="main_container">

						<div role="tabpanel" class="bd-example bd-example-tabs">

							<ul role="tablist" class="nav nav-tabs" id="myTab">
								<li class="nav-item"> <a role="tab" class="nav-link active" href="#tab1" data-toggle="tab" aria-controls="tab1"><i class="fas fa-question-circle"></i> Quick Guide</a> </li>
								<li class="nav-item"> <a role="tab" class="nav-link"        href="#tab2" data-toggle="tab" aria-controls="tab2"><i class="fas fa-group"></i> Publications</a> </li>
								<li class="nav-item"> <a role="tab" class="nav-link"        href="#tab3" data-toggle="tab" aria-controls="tab3"><i class="fas fa-desktop"></i> Screenshots</a> </li>
							</ul>

							<div class="tab-content mt-3 w-100" id="myTabContent">

								<div id="tab1" class="tab-pane active" role="tabpanel">
									<h3>Quick Guide</h3>
									<ol>
										<li>Enter a list of names you want to calculate the intersections. You can either enter the list of values in the text area field, or upload the dataset file.</li>
										<li>If you prefer to enter the list of values directly, please enter one name per row.</li>
										<li>If you prefer to upload a csv file, <strong>the file should contain several columns</strong>, and each row is separated by line breaks.</li>
										<li>Click <strong>Add more columns</strong> if you need to analyze more than three datasets.</li>
										<li>Click the <strong>Submit</strong> button for the result.</li>
									</ol>
									<div class="row w-100">
										<div class="col-md-5"> <img src="image/venn01.jpg" class="img-fluid"> </div>
										<div class="col-md-4"> <img src="image/venn02.png" class="img-fluid"> </div>
									</div>
								</div>

								<div id="tab2" class="tab-pane" role="tabpanel">
									<h3>Publications</h3>
									<p>Here are some real-world examples of research publications using this tool:</p>
									<table class="table table-bordered">
										<tbody>

											<tr>
												<td>
													<p><strong>Genomics/Microarray</strong></p>
													<p>Overlap between gene lists from different amplification conditions.</p>
												</td>
												<td><img src="image/brand01.png" class="img-fluid" width="250"></td>
												<td>
													<p>Kang Y, et al. <a href="http://genome.cshlp.org/content/21/6/925.long">Transcript amplification from single bacterium for transcriptome analysis</a>. Genome Res. 2011 Jun;21(6):925-35. PubMed PMID: 21536723</p>
												</td>
											</tr>

											<tr>
												<td>
													<p><strong>Genomics/Microarray</strong></p>
													<p>Compare two statistical methods to identify differentially expressed genes.</p>
												</td>
												<td><img src="image/brand02.png" class="img-fluid" width="250"></td>
												<td>
													<p>Mougeot JL, et al. <a href="http://www.biomedcentral.com/1755-8794/4/74">Microarray analysis of peripheral blood lymphocytes from ALS patients and the SAFE detection of the KEGG ALS pathway</a>. BMC Med Genomics. 2011 Oct 25;4:74. PubMed PMID: 22027401</p>
												</td>
											</tr>

											<tr>
												<td>
													<p><strong>Proteomics</strong></p>
													<p>Overlap of peptides/proteins from S.aureus that were detected under two conditions.</p>
												</td>
												<td><img src="image/brand03.png" class="img-fluid" width="250"></td>
												<td>
													<p>Miller M et al. <a href="http://pubs.acs.org/doi/abs/10.1021/pr200224x">Mapping of interactions between human macrophages and Staphylococcus aureus reveals an involvement of MAP kinase signaling in the host defense</a>. J Proteome Res. 2011 Sep 2;10(9):4018-32. PubMed PMID: 21736355.</p>
												</td>
											</tr>

											<tr>
												<td>
													<p><strong>System Biology</strong></p>
													<p>Compare human and mouse protein networks.</p>
												</td>
												<td><img src="image/brand04.png" class="img-fluid" width="250"></td>
												<td>
													<p>Feltes BC, et al. <a href="http://link.springer.com/article/10.1007%2Fs10522-011-9325-8">The developmental aging and origins of health and disease hypotheses explained by different protein networks. Biogerontology</a>. 2011 Aug;12(4):293-308. PubMed PMID: 21380541.</p>
												</td>
											</tr>

											<tr>
												<td>
													<p><strong>Medical Study</strong></p>
													<p>Overlap for attendance of different health services by African women.</p>
												</td>
												<td><img src="image/brand05.png" class="img-fluid" width="250"></td>
												<td>
													<p>Carlson M, et al. <a href="http://www.malariajournal.com/content/10/1/341">Who attends antenatal care and expanded programme on immunization services in Chad, Mali and Niger? The implications for insecticide-treated net delivery</a>. Malar J. 2011 Nov 13;10:341. PubMed PMID: 22078175.</p>
												</td>
											</tr>

											<tr>
												<td>
													<p><strong>Genomics/Microarray</strong></p>
													<p>Gene regulation in presence of hormone.</p>
												</td>
												<td><img src="image/brand06.png" class="img-fluid" width="250"></td>
												<td>
													<p>Galliher-Beckley AJ, et al. <a href="http://mcb.asm.org/content/31/23/4663.long">Ligand-independent phosphorylation of the glucocorticoid receptor integrates cellular stress pathways with nuclear receptor signaling</a>. Mol Cell Biol. 2011 Dec;31(23):4663-75. PubMed PMID: 21930780.</p>
												</td>
											</tr>

										</tbody>
									</table>
								</div>

								<div id="tab3" class="tab-pane" role="tabpanel">
									<h3>Here are some screen shots of the tool.</h3><br />
									<div class="text-muted mb-3">Tip: Click the image to change slide.</div>
									<div class="w-100">

										<div id="myCarousel" class="carousel slide" data-ride="carousel">
											<ol class="carousel-indicators">
												<li data-target="#myCarousel" data-slide-to="0" class="active"></li>
												<li data-target="#myCarousel" data-slide-to="1"></li>
												<li data-target="#myCarousel" data-slide-to="2"></li>
												<li data-target="#myCarousel" data-slide-to="3"></li>
												<li data-target="#myCarousel" data-slide-to="4"></li>
												<li data-target="#myCarousel" data-slide-to="5"></li>
												<li data-target="#myCarousel" data-slide-to="6"></li>
											</ol>
											<div class="carousel-inner" role="listbox">
												<div class="carousel-item active">
													<img src="image/screen01.jpg" alt="Result for Multiple Datasets">
													<div class="carousel-caption">
														<h3>Result for Multiple Datasets</h3>
												    </div>
												</div>
												<div class="carousel-item">
													<img src="image/screen02.jpg" alt="Freedom of Datasets Selection">
													<div class="carousel-caption">
														<h3>Freedom of Datasets Selection</h3>
												    </div>
												</div>
												<div class="carousel-item">
													<img src="image/screen03.jpg" alt="Pop-up Window of Detail Information">
													<div class="carousel-caption">
														<h3>Pop-up Window of Detail Information</h3>
												    </div>
												</div>
												<div class="carousel-item">
													<img src="image/screen04.jpg" alt="Results for Any Dataset Combination">
													<div class="carousel-caption">
														<h3>Results for Any Dataset Combination</h3>
												    </div>
												</div>
												<div class="carousel-item">
													<img src="image/screen05.jpg" alt="Option to Import Data From a File">
													<div class="carousel-caption">
														<h3>Option to Import Data From a File</h3>
												    </div>
												</div>
												<div class="carousel-item">
													<img src="image/screen06.jpg" alt="Simple Tool to Plot Venn Diagrams">
													<div class="carousel-caption">
														<h3>Simple Tool to Plot Venn Diagrams</h3>
												    </div>
												</div>

												<div class="carousel-item">
													<img src="image/screen07.jpg" alt="Interactive Graph to View Overlap By Hovering Mouse Point">
													<div class="carousel-caption">
														<h3>Interactive Graph to View Overlap By Hovering Mouse Point</h3>
												    </div>
												</div>

											</div>

											<a class="carousel-control-prev" href="#myCarousel" role="button" data-slide="prev">
												<span class="icon-prev" aria-hidden="true"></span>
												<span class="sr-only">Previous</span>
											</a>
											<a class="carousel-control-next" href="#myCarousel" role="button" data-slide="next">
												<span class="icon-next" aria-hidden="true"></span>
												<span class="sr-only">Next</span>
											</a>

										</div>

									</div>

								</div>
							</div>

						</div>

					</div>

				</div>
	        </div>
	        <?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>
	    </div>
	</div>

</body>
</html>