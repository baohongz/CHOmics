<?php
include_once("config.php");
$TIME = time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<?php include_once($BXAF_CONFIG['BXAF_PAGE_HEADER']); ?>
  <script src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/jquery/jquery.form.min.js"></script>
  <script src="../library/plotly.min.js"></script>
</head>

<body>
    <?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_MENU'])) include_once($BXAF_CONFIG['BXAF_PAGE_MENU']); ?>
    <div id="bxaf_page_wrapper" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_WRAPPER']; ?>">
    <?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_LEFT'])) include_once($BXAF_CONFIG['BXAF_PAGE_LEFT']); ?>
    <div id="bxaf_page_right" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT']; ?>">
    <div id="bxaf_page_right_content" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT_CONTENT']; ?>">


	    <?php $help_key = 'Import Project Data'; include_once( dirname(__DIR__) . "/help_content.php"); ?>

		<div class="my-3">
			This tool will import Projects, Samples, Comparision, Comparsion Data, and Expression Data into projects.
			<strong><a href="import/file_format_notes.xlsx">Check this file</a></strong> for file format details, or download example files: <BR />
			<a class="ml-2" href='import/file_projects.csv'><i class="fas fa-arrow-right"></i> Projects</a>
			<a class="ml-2" href='import/file_samples.csv'><i class="fas fa-arrow-right"></i> Samples</a>
			<a class="ml-2" href='import/file_comparisons.csv'><i class="fas fa-arrow-right"></i> Comparisons</a>
			<a class="ml-2" href='import/file_expression_data.csv'><i class="fas fa-arrow-right"></i> Sample Expression Data (Format 1)</a>
			<a class="ml-2" href='import/file_expression_data1.csv'><i class="fas fa-arrow-right"></i> Sample Expression Data (Format 2)</a>
			<a class="ml-2" href='import/file_comparison_data.csv'><i class="fas fa-arrow-right"></i> Comparison Data (Format 1)</a>
			<a class="ml-2" href='import/file_comparison_data1.csv'><i class="fas fa-arrow-right"></i> Comparison Data (Format 2)</a>
		</div>
	    <hr />

		<div>
			<div class="my-3">
				<a href="data_import.php" class="btn btn-success mr-3">Import Data Files with Fixed Formats</a>
				<a href="data_import_adv.php" class="btn btn-primary mr-3">Import Data Files with Flexible Formats</a>
			</div>
		</div>

	    <div id="div_help" class="w-100 my-5">
			<h3>
	            Tips for Importing Data Files with <span class="font-weight-bold table-warning px-2">Flexible Formats</span>
	        </h3>
	        <hr />
	        <div class="w-100 my-3 text-danger">
	            Note: All files should be in <strong>Comma Separated Values (CSV)</strong> or <strong>Tab Separated Values (TSV)</strong> format. <strong>The first row must contain column names</strong>, which do not have to be exact names of database table fields. Each file must have at least <strong>two columns</strong>.
	        </div>

			<div id='quick_guide' style="display: block;" class="card w-100 my-3">
		        <div class="card-body p-3">
		            <h3 class="card-title">Quick Guides</h3>
		            <ol>
		                <li><span class="font-weight-bold text-success">Upload files</span>: Select files from your local computer with "Add files ..." button or use your computer mouse to <span class="font-weight-bold">drag and drop files into your browser</span>. Note that, you can upload multiple files, but each file will be previewed and uploaded one by one.</li>
		                <li><span class="font-weight-bold text-success">Select file type and preview file</span>: To import Projects files, you can preset Species and Platform, unless you have corresponding columns (Species, _Platforms_ID, Platform, or PlatformName) in your uploaded file. To import Sample or Comparison files, you need to select a Project, unless you have corresponding columns (_Projects_ID or Project_Name) in your uploaded file.</li>
		                <li><span class="font-weight-bold text-success">Match data file columns with database fields</span>:
							<ul>
								<li>To import Projects, Samples, and Comparisons, besides matching columns with fields, you can also set a common value for a column. </li>
								<li>To import Expression Data, you need to specify which column contains GeneName, and the rest columns should match corresponding Sample Names. </li>
								<li>To import Comparison Data, you need to specify which columns match GeneName, Log2FoldChange, PValue, and AdjustedPValue. If you have a column matching ComparisonName, then select Matching Field "ComparisonName", otherwise, you need to enter Comparison Name for all columns matching Log2FoldChange, PValue, and AdjustedPValue. </li>
						</li>
		            </ol>
		        </div>
		    </div>



	        <h3 class="mt-5">
	            Tips for Importing Data Files with  <span class="font-weight-bold table-warning px-2">Fixed Formats</span>
	        </h3>
	        <hr />
	        <div class="w-100 my-3 text-danger">
				Note: All files should be in Comma Separated Values (CSV) format, <span class="table-warning px-2">the first row must contain column names, which have to be the exact names of database table fields</span>.
	        </div>

	        <div class="card w-100 my-3">
	            <div class="card-body p-3">
	                <h5 class="card-title">Project File (e.g., <a class="ml-2" href='import/file_projects.csv'>file_projects.csv</a>)</h5>
	                <h6 class="card-subtitle my-2 text-success">Required Fields</h6>
	                <p class="card-text"><strong>Name</strong>, <strong>Platform</strong> (GEO Accession Number)</p>
	                <h6 class="card-subtitle my-2 text-success">Recommended Fields</h6>
	                <p class="card-text"><strong>Name</strong>, <strong>Platform</strong>, Description, Disease</p>
	                <h6 class="card-subtitle my-2 text-success">Optional Fields</h6>
	                <p class="card-text">Accession, PubMed_ID, ExperimentType, ContactAddress, ContactOrganization, ContactName, ContactEmail, ContactPhone, ContactWebLink, Keywords, ReleaseDate, Design, StudyType, TherapeuticArea, Comment, Contributors, WebLink, PubMed, PubMed_Authors, Collection</p>
	            </div>
	        </div>

	        <div class="card w-100 my-3">
	            <div class="card-body p-3">
	                <h5 class="card-title">Sample File (e.g., <a class="ml-2" href='import/file_samples.csv'>file_samples.csv</a>)</h5>
	                <h6 class="card-subtitle my-2 text-success">Required Fields</h6>
	                <p class="card-text"><strong>Name</strong>, <strong>Project_Name</strong></p>
	                <h6 class="card-subtitle my-2 text-success">Recommended Fields</h6>
	                <p class="card-text">Name, Project_Name, Description, Tissue, DiseaseState, SampleSource, Gender</p>
	                <h6 class="card-subtitle my-2 text-success">Optional Fields</h6>
	                <p class="card-text">_Samples_ID, SampleIndex, CellType, DiseaseCategory, Ethnicity, Infection, Organism, Response, SamplePathology, SampleType, SamplingTime, Symptom, TissueCategory, Transfection, Treatment, Collection, Age, RIN_Number, RNASeq_Total_Read_Count, RNASeq_Mapping_Rate, RNASeq_Assignment_Rate, Flag_To_Remove, Flag_Remark, Uberon_ID, Uberon_Term</p>
	            </div>
	        </div>

	        <div class="card w-100 my-3">
	            <div class="card-body p-3">
	                <h5 class="card-title">Comparison File (e.g., <a class="ml-2" href='import/file_comparisons.csv'>file_comparisons.csv</a>)</h5>
	                <h6 class="card-subtitle my-2 text-success">Required Fields</h6>
					<p class="card-text"><strong>Name</strong>, <strong>Project_Name</strong>, <strong>Case_SampleIDs</strong>, <strong>Control_SampleIDs</strong></p>
	                <h6 class="card-subtitle my-2 text-success">Recommended Fields</h6>
	                <p class="card-text">Name, Project_Name, Case_SampleIDs, Control_SampleIDs, ComparisonCategory, ComparisonContrast, Case_DiseaseState, Case_Tissue, Case_Ethnicity, Case_Gender, Case_SamplePathology, Case_SampleSource</p>
	                <h6 class="card-subtitle my-2 text-success">Optional Fields</h6>
	                <p class="card-text">Case_CellType, Case_Treatment, Case_SubjectTreatment, Case_AgeCategory, ComparisonType, Control_DiseaseState, Control_Tissue, Control_CellType, Control_Ethnicity, Control_Gender, Control_SamplePathology, Control_SampleSource, Control_Treatment, Control_SubjectTreatment, Control_AgeCategory</p>
	            </div>
	        </div>

	        <div class="card w-100 my-3">
	            <div class="card-body p-3">
	                <h5 class="card-title">Expression Data File (e.g., <a class="ml-2" href='import/file_expression_data.csv'>file_expression_data.csv</a> or <a class="ml-2" href='import/file_expression_data1.csv'>file_expression_data1.csv</a>)</h5>
	                <h6 class="card-subtitle my-2 text-success">Required Fields</h6>
	                <p class="card-text">Format 1: <strong>GeneName</strong>, <strong>SampleName</strong>, <strong>Value</strong></p>
					<p class="card-text">Format 2: <strong>GeneName</strong>, <span class="font-italic text-muted">SampleName1, SampleName2, SampleName3, ...</span></p>
	            </div>
	        </div>

	        <div class="card w-100 my-3">
	            <div class="card-body p-3">
	                <h5 class="card-title">Comparison Data File (e.g., <a class="ml-2" href='import/file_comparison_data.csv'>file_comparison_data.csv</a> or <a class="ml-2" href='import/file_comparison_data1.csv'>file_comparison_data1.csv</a>)</h5>
	                <h6 class="card-subtitle my-2 text-success">Required Fields</h6>
					<p class="card-text">Format 1: <strong>GeneName</strong>, <strong>ComparisonName</strong>, <strong>Log2FoldChange</strong>, <strong>PValue</strong>, <strong>AdjustedPValue</strong></p>
	                <p class="card-text">Format 2: <strong>GeneName</strong>, <strong>ComparisonName-Log2FoldChange</strong>, <strong>ComparisonName-PValue</strong>, <strong>ComparisonName-AdjustedPValue</strong></p>
	        </div>

	    </div>





    </div>
    <?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>
    </div>
    </div>
</body>
</html>