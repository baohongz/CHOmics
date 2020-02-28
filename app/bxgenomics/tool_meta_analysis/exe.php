<?php
include_once('config.php');



if (isset($_GET['action']) && trim($_GET['action']) == 'save_column') {

    $TIME = $_GET['time'];

    if(isset($_SESSION['META_RESULTS']) && is_array($_SESSION['META_RESULTS']) && array_key_exists($TIME, $_SESSION['META_RESULTS']) && is_array($_SESSION['META_RESULTS'][$TIME]) ){

        if( $_GET['type'] == 1) $_SESSION['META_RESULTS'][$TIME][ $_GET['col'] ] = 1;
        else if(array_key_exists($_GET['col'], $_SESSION['META_RESULTS'][$TIME]) && $_GET['type'] == 0) unset($_SESSION['META_RESULTS'][$TIME][ $_GET['col'] ]);
    }

    exit();
}


//********************************************************************************************
// Save Meta Analysis
//********************************************************************************************
if (isset($_GET['action']) && trim($_GET['action']) == 'save_result') {

    $info = array(
        '_Owner_ID'    => $BXAF_CONFIG['BXAF_USER_CONTACT_ID'],
        'Title'       => trim($_POST['title']),
        'Type'        => 'Meta',
        'Description' => trim($_POST['description']),
        'Time'        => $_POST['time']
    );
    $BXAF_MODULE_CONN -> insert($BXAF_CONFIG['TBL_BXGENOMICS_USERSAVEDRESULTS'], $info);

    exit();
}




//********************************************************************************************
// Delete Saved Result
//********************************************************************************************
if (isset($_GET['action']) && trim($_GET['action']) == 'delete_result') {
    $ROWID = bxaf_decrypt($_POST['rowid'], $BXAF_CONFIG['BXAF_KEY']);
    $info  = array('bxafStatus' => 9);
    $BXAF_MODULE_CONN -> update($BXAF_CONFIG['TBL_BXGENOMICS_USERSAVEDRESULTS'], $info, "`ID`=" . $ROWID);
    exit();
}



//********************************************************************************************
// Submit Data
//********************************************************************************************

if (isset($_GET['action']) && trim($_GET['action']) == 'submit_data') {
    // echo "<pre>" . print_r($_POST, true) . "</pre>"; exit();

    header('Content-Type: application/json');
    $OUTPUT['type'] = 'Error';


    $gene_idnames   = category_text_to_idnames($_POST['Gene_List'], 'name', 'gene', $_SESSION['SPECIES_DEFAULT']);
    $comparison_indexnames = category_text_to_idnames($_POST['Comparison_List'], 'name', 'comparison', $_SESSION['SPECIES_DEFAULT']);

    if (! is_array($comparison_indexnames) || count($comparison_indexnames) <= 0) {
        $OUTPUT['detail'] = 'No comparisons found. Please enter at least one comparison name to continue.' ;
        echo json_encode($OUTPUT);
        exit();
    }

    $GENE_INDEXES = array_keys($gene_idnames);
    $COMPARISON_INDEXES = array_keys($comparison_indexnames);


	ini_set('memory_limit','8G');
	$tabix_results = tabix_search_bxgenomics($GENE_INDEXES, $COMPARISON_INDEXES, 'ComparisonData');

    // $OUTPUT['detail'] = "<pre>" . print_r($GENE_INDEXES, true) . print_r($COMPARISON_INDEXES, true) . count($tabix_results) . print_r(array_slice($tabix_results, 0, 5, true), true) . "</pre>";
    // echo json_encode($OUTPUT);
    // exit();


    $gene_ids = array();
    foreach ($tabix_results as $t) $gene_ids[] = $t['GeneIndex'];
    $gene_idnames = category_list_to_idnames($gene_ids, 'id', 'gene', $_SESSION['SPECIES_DEFAULT']);
    $GENE_INDEXES = array_keys($gene_idnames);

    $sql = "SELECT * FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_GENES']}` WHERE `Species` = '" . $_SESSION['SPECIES_DEFAULT'] . "' AND `ID` IN (?a)";
    $gene_info = $BXAF_MODULE_CONN -> get_assoc('ID', $sql, $GENE_INDEXES);

    // Generate Files
    $TIME = time();
    $dir = "{$BXAF_CONFIG['USER_FILES']['TOOL_META_ANALYSIS']}{$BXAF_CONFIG['BXAF_USER_CONTACT_ID']}/{$TIME}";
    if (! is_dir($dir)) mkdir($dir, 0755, true);


    $handle = fopen("{$dir}/Comparison_Info.csv","w");
    fputcsv($handle, array('Comparison Index', 'Comparison ID'));
    foreach ($comparison_indexnames as $comp_index => $comp_id) {
        fputcsv($handle, array($comp_index, $comp_id));
    }
    fclose($handle);


    $header = array_merge( array('ID'), $_POST['attributes_Gene'] );

    $handle = fopen("{$dir}/Gene_Info.csv","w");
    fputcsv($handle, $header);
    foreach ($GENE_INDEXES as $gene_index) {
        $row = array($gene_index);
        foreach ($_POST['attributes_Gene'] as $colname) {
            $row[] = $gene_info[$gene_index][$colname];
        }
        fputcsv($handle, $row);
    }
    fclose($handle);


    $file = "{$dir}/Genes_Comparisons_Raw.txt";
    $handle = fopen($file, 'w');
    foreach($tabix_results as $row){
        fputcsv($handle, array($row['ComparisonIndex'], $row['GeneIndex'], $row['Name'], $row['Log2FoldChange'], $row['PValue'], $row['AdjustedPValue']), "\t");
    }
    fclose($handle);

    // $OUTPUT['detail'] = "" . count($tabix_results) . "<pre>" . print_r(array_slice($tabix_results, 0, 100, true), true) . "</pre>";
    // echo json_encode($OUTPUT);
    // exit();

    //-------------------------------------------------------------------------------------------
    // Run R Script

    $miss_tol = floatval($_POST['miss_tol']);
    $logFC_cutoff = floatval($_POST['logFC_cutoff']);
    $sig_cutoff = floatval($_POST['sig_cutoff']);
    $sig_type = $_POST['sig_type'];

    // $OUTPUT['detail'] = "$dir: $miss_tol: $logFC_cutoff: $sig_cutoff: $sig_type: <pre>" . print_r(array_slice($tabix_results, 0, 100, true), true) . "</pre>";
    // echo json_encode($OUTPUT);
    // exit();

    $RCODE  = <<<RECODE_END

    library(MetaDE)
    library(data.table)
    library(dplyr)
    library(RankProd)
    library(stringr)

    setwd('{$dir}')

    options(stringsAsFactors=F)
    miss.tol = {$miss_tol}
    logFC_cutoff={$logFC_cutoff}
    sig_type="{$sig_type}"  # FDR or P-value
    sig_cutoff={$sig_cutoff}

    #read raw data
    system.time(raw_data<-fread("Genes_Comparisons_Raw.txt", sep="\\t", stringsAsFactors=FALSE, select=1:6, fill=T))

    raw_data=raw_data[, V4:=as.numeric(V4) ]  # Log2FoldChange
    raw_data=raw_data[, V5:=as.numeric(V5) ]  # PValue
    raw_data=raw_data[, V6:=as.numeric(V6) ]  # AdjustedPValue

    #rank by p-value (smaller first)
    raw_data=raw_data[order(V5), ]

    #remove duplicate gene names (the one with the smallest p-value will be kept)
    P1=paste(raw_data\$V1, raw_data\$V2, sep="_")
    sel=!duplicated(P1)
    cat("Remove duplicate: keep", sum(sel), "out of", length(P1), "unique data points\\n")
    raw_data=raw_data[sel, ]

    #now cast for FDR, p-value and logFC matrix
    system.time(logFC<-dcast(raw_data, V2~V1, value.var="V4") )
    system.time(pval<-dcast(raw_data, V2~V1, value.var="V5") )
    system.time(FDR<-dcast(raw_data, V2~V1, value.var="V6") )


    #compute simple count statistics
    Ncomp=ncol(logFC)
    N.data.points=rowSums(!is.na(logFC[, 2:Ncomp]))
    if (sig_type=="FDR") {
    	up_sel=(logFC[, 2:Ncomp]>=logFC_cutoff & FDR[, 2:Ncomp]<=sig_cutoff)
    	N_Up=rowSums(up_sel, na.rm=T)
    	down_sel=(logFC[, 2:Ncomp]<=(-logFC_cutoff) & FDR[, 2:Ncomp]<=sig_cutoff)
    	N_Down=rowSums(down_sel, na.rm=T)
    } else {
     	up_sel=(logFC[, 2:Ncomp]>=logFC_cutoff & pval[, 2:Ncomp]<=sig_cutoff)
     	N_Up=rowSums(up_sel, na.rm=T)
     	down_sel=(logFC[, 2:Ncomp]<=(-logFC_cutoff) & pval[, 2:Ncomp]<=sig_cutoff)
     	N_Down=rowSums(down_sel, na.rm=T)
    }
    Meta_out=data.table(GeneIndex=logFC\$V2, N.data.points, Up.Per=100*N_Up/N.data.points, Down.Per=100*N_Down/N.data.points)


    ###Consider use RankProducts on logFC only
    logFCdata=data.matrix(logFC[, 2:Ncomp]); rownames(logFCdata)=logFC\$V2
    #remove rows with all NAs
    logFCdata=logFCdata[rowSums(!is.na(logFCdata))>0, ]
    cl=rep(1, ncol(logFCdata))
    genes=rownames(logFCdata)
    RP.out<- RankProducts(logFCdata, cl, logged=T, na.rm=T,  plot=F, rand=123, MinNumOfValidPairs=2,gene.names=genes)
    outdata=topGene(RP.out,num.gene=nrow(logFCdata), logged=T, gene.names=genes )
    RP1=outdata[[1]]; RP2=outdata[[2]]  #here Up and Down are inverted as the two classes
    colnames(RP1)=str_c("Down_", c("gindex", "RP", "FC", "PFP", "P.Val") )
    colnames(RP2)=str_c("Up_", c("gindex", "RP", "FC", "PFP", "P.Val")  )

    RP<-data.frame(RP1)%>%mutate(GeneIndex=rownames(RP1)) %>%left_join(data.frame(RP2), by=c("Down_gindex"="Up_gindex") )

    RP<-RP%>%mutate(RP_logFC=log2(Up_FC), RP_Pval=ifelse(RP_logFC>0, Up_P.Val, Down_P.Val),
     RP_FDR=ifelse(RP_logFC>0, Up_PFP, Down_PFP), RankProd=ifelse(RP_logFC>0, Up_RP, Down_RP))

    RP<-RP%>%select(GeneIndex, RankProd,RP_logFC,RP_Pval,RP_FDR)
    RP\$GeneIndex=as.numeric(RP\$GeneIndex)
    RP<-RP%>%mutate(RP_logFC=round(RP_logFC*100)/100)
    Meta_out<-Meta_out%>%left_join(RP)


    #combined pvalues
    p_test=pval[, 2:Ncomp]
    rownames(p_test)=pval\$V2
    DE_test=list(p=p_test, bp=NULL)
    Meta_out\$Combined_Pval_Fisher=MetaDE.pvalue(DE_test, meta.method="Fisher",miss.tol=miss.tol )\$meta.analysis\$pval
    Meta_out\$Combined_Pval_maxP=MetaDE.pvalue(DE_test, meta.method="maxP",miss.tol=miss.tol )\$meta.analysis\$pval

    ##Added new lines
    Meta_out\$Combined_FDR_Fisher=MetaDE.pvalue(DE_test, meta.method="Fisher",miss.tol=miss.tol )\$meta.analysis\$FDR
    Meta_out\$Combined_FDR_maxP=MetaDE.pvalue(DE_test, meta.method="maxP",miss.tol=miss.tol )\$meta.analysis\$FDR


    #head(Meta_out[order(Combined_Pval_maxP), ]) #review

    #now combine data
    geneInfo=read.csv("Gene_Info.csv", row.names=1)
    sel=match(Meta_out\$GeneIndex, rownames(geneInfo) )
    #Meta_out=cbind(Meta_out, geneInfo[sel, ])
    Meta_out=cbind(geneInfo[sel, 1:ncol(geneInfo), drop=F], Meta_out)

    compInfo=read.csv("Comparison_Info.csv", row.names=1)
    comp.names=compInfo[colnames(logFC)[2:Ncomp], 1]
    colnames(logFC)[2:Ncomp]=paste(comp.names, "logFC", sep= "_")
    colnames(pval)[2:Ncomp]=paste(comp.names, "Pval", sep="_")
    colnames(FDR)[2:Ncomp]=paste(comp.names, "FDR", sep="_")
    comp.data=cbind(logFC[, 2:Ncomp], pval[, 2:Ncomp], FDR[, 2:Ncomp])
    #set new order logFC
    a=NULL
    for (i in 1:(Ncomp-1) ) {	a=c(a, i, i+Ncomp-1, i+2*(Ncomp-1) ) }
    comp.data=setcolorder(comp.data, a)
    Meta_out=cbind(Meta_out, comp.data)
    #fwrite(Meta_out, "Meta_output_RankProd.csv", row.names=F)
    fwrite(Meta_out, "Meta_output.csv", row.names=F)

RECODE_END;


    file_put_contents("{$dir}/command.R", $RCODE);
    chmod("{$dir}/command.R", 0777);
    chdir($dir);

    exec("Rscript command.R > outputFile.Rout 2>&1");
    if (!file_exists("{$dir}/Meta_output.csv")) {
        $error = file_get_contents("{$dir}/outputFile.Rout");
        $error = substr($error, strpos($error, 'Error'));
        $OUTPUT['detail'] = '<h3 class="text-danger"><i class="fas fa-exclamation-triangle"></i> Error: no results found. Check Rscript output for details:</h3><hr><pre>' . $error . '</pre>';
        echo json_encode($OUTPUT);
        exit();
    }


    $meta_data = file_get_contents("{$dir}/Meta_output.csv");
    $meta_data = explode("\n", $meta_data);
    foreach ($meta_data as $key => $value) {
        $meta_data[$key] = explode(",", $value);
        if (!is_array($meta_data[$key]) || count($meta_data[$key]) <= 1) {
            unset($meta_data[$key]);
        }
    }

    $meta_data_header = $meta_data[0];
    $meta_data = array_slice($meta_data, 1);

    $OUTPUT['type']             = 'Success';
    $OUTPUT['time']             = $TIME;
    $OUTPUT['meta_data_header'] = $meta_data_header;
    $OUTPUT['meta_data']        = $meta_data;
    echo json_encode($OUTPUT);

    exit();
}



?>