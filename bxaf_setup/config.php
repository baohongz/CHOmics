<?php

//*****************************************************************************************
// This file contains the customer environment information.
// During the deployment/patching, this file should be excluded and not to be overwritten.
// Please try to keep the content of this file as few as possible, so that the customer
// do not need to modify it during the patching / update.
//*****************************************************************************************



// Please edit with real email account info to enable sending emails
$BXAF_CONFIG_CUSTOM['EMAIL_METHOD'] = "smtp";
$BXAF_CONFIG_CUSTOM['EMAIL_SMTP_SERVER'] 		= 'my smtp server';
$BXAF_CONFIG_CUSTOM['EMAIL_SMTP_AUTH'] 		= TRUE;
$BXAF_CONFIG_CUSTOM['EMAIL_SMTP_PORT'] 		= 465;
$BXAF_CONFIG_CUSTOM['EMAIL_SMTP_SECURITY'] 	= 'SSL';
$BXAF_CONFIG_CUSTOM['EMAIL_SMTP_USER'] 		= "my smtp account name";
$BXAF_CONFIG_CUSTOM['EMAIL_SMTP_PASSWORD'] 	= 'my smtp account password';


// Web page after login in or sign up
$BXAF_CONFIG_CUSTOM['BXAF_LOGIN_SUCCESS'] = '/chomics/app/bxgenomics/index.php';

// Please create and set up corresponding MySQL database and accounts
$BXAF_CONFIG_CUSTOM['BXAF_DB_DRIVER'] 		= 'mysql';
$BXAF_CONFIG_CUSTOM['BXAF_DB_SERVER'] 		= 'localhost';
$BXAF_CONFIG_CUSTOM['BXAF_DB_NAME'] 		= 'chomics';
$BXAF_CONFIG_CUSTOM['BXAF_DB_USER'] 		= 'chomics';
$BXAF_CONFIG_CUSTOM['BXAF_DB_PASSWORD'] 	= 'CHOMICS@2020';



$BXAF_CONFIG_CUSTOM['BXAF_PAGE_APP_NAME'] 	= 'CHOmics (v1)';
$BXAF_CONFIG_CUSTOM['BXAF_PAGE_TITLE'] 		= 'CHOmics (v1)';
$BXAF_CONFIG_CUSTOM['BXAF_PAGE_AUTHOR']		= 'BioInfoRx, Inc.';

$BXAF_CONFIG_CUSTOM['BXAF_PAGE_FOOTER']         = '';
$BXAF_CONFIG_CUSTOM['BXAF_PAGE_LEFT']           = '';
$BXAF_CONFIG_CUSTOM['BXAF_PAGE_CSS_RIGHT']      = 'w-100 d-flex align-content-between flex-wrap';
$BXAF_CONFIG_CUSTOM['BXAF_PAGE_SPLIT']          = false;



$BXAF_CONFIG_CUSTOM['BXAF_ADMIN_PASSWORD'] 	= 'CHOMICS@2020';

$BXAF_CONFIG_CUSTOM['BXAF_KEY'] = "chomics";


// For BxFiles - Private file management
$BXAF_CONFIG_CUSTOM['BXAF_BXFILES_SUBDIR_PRIVATE']     = "chomics/app_data/files_private/";
// Force readonly to private files, by default, private files are not readonly
// $BXAF_CONFIG_CUSTOM['BXFILES_PRIVATE_READONLY'] = false;

// For BxFiles_shared - Shared file management
$BXAF_CONFIG_CUSTOM['BXAF_BXFILES_SUBDIR_SHARED']      = "chomics/app_data/files_public/";
// Force shared files be readonly to normal users, but admin has full power to manipulate files. By default, shared folders are not readonly
$BXAF_CONFIG_CUSTOM['BXFILES_SHARED_READONLY'] = true;




// Please install all required programs on your server!
$BXAF_CONFIG_CUSTOM['BIN_fastqc']                 = '/public/programs/fastqc/latest/fastqc';
$BXAF_CONFIG_CUSTOM['BIN_samtools']               = '/public/programs/samtools/latest/samtools';
$BXAF_CONFIG_CUSTOM['BIN_subjunc']                = '/public/programs/subread/latest/bin/subjunc';
$BXAF_CONFIG_CUSTOM['BIN_featureCounts']          = '/public/programs/subread/latest/bin/featureCounts';
$BXAF_CONFIG_CUSTOM['BIN_gsea']                   = '/public/programs/gsea/gsea-3.0.jar';

$BXAF_CONFIG_CUSTOM['DIR_homer']                  = '/public/programs/homer/bin/';

$BXAF_CONFIG_CUSTOM['SORT_BIN']         = '/usr/bin/sort';
$BXAF_CONFIG_CUSTOM['CAT_BIN']          = '/usr/bin/cat';
$BXAF_CONFIG_CUSTOM['TAIL_BIN']         = '/usr/bin/tail';
$BXAF_CONFIG_CUSTOM['RSCRIPT_BIN']      = '/usr/bin/Rscript';
$BXAF_CONFIG_CUSTOM['TABIX_BIN']        = '/public/programs/tabix/latest/tabix';
$BXAF_CONFIG_CUSTOM['BGZIP_BIN']        = '/public/programs/tabix/latest/bgzip';

$BXAF_CONFIG_CUSTOM['SCRIPT_QC_DIR']    = '/public/scripts/R/Gene_CountQC.R';

$BXAF_CONFIG_CUSTOM['PROCESS_NUMBER_ALLOWED'] = 2;
$BXAF_CONFIG_CUSTOM['PROCESS_THREAD_ALLOWED'] = 6;


?>