# usage: Process.Inhouse.Comparison.R comp_file species gmt_file mode
# human gmt file: /public/programs/gsea/msigdb.v5.2.symbols.gmt
# mouse gmt file: /public/programs/gsea/mouse/Mouse_GO_AllPathways_with_GO_iea_December_01_2016_symbol_Modified.gmt
# Example: Rscript Process.Internal.Comparison.R test.csv human /public/programs/gsea/msigdb.v5.2.symbols.gmt auto

debug = 1;

options(stringsAsFactors=F)
library(stringr)
library(limma)
library(piano)
flush.console()

args <- commandArgs(TRUE)

file=args[1]
species=args[2]
if ( is.na(match(species, c("mouse", "human")) )) {
	species = 'human'
}
gmt.file=args[3]
if (!grepl("gmt", gmt.file)) {
	gmt.file = '/public/programs/gsea/msigdb.v5.2.symbols.gmt'
}
mode=args[4] #Auto, FDR, PValue
if ( is.na(match(mode, c("FDR", "PValue")) )) {
	mode = 'auto'
}

#function to output DEG list. If more than 2000, just output top 2000
limit = 2000
out.DEG<-function(subdata, sel, file) {
	outdata=subdata[which(sel), ]
	outdata[,2]=abs(outdata[, 2])
	if (nrow(outdata)>limit) {
		outdata=outdata[order(outdata[,2], decreasing=T), ]
		outdata=outdata[1:limit, ]
	}
	write.table(unique(outdata[,1]),file, sep="\t", col.names=F, quote=F, row.names=F)
}

name=str_replace(file, ".csv", "")
GO.file=paste("FindGO_", name,  ".bash", sep='')

cat("#!/bin/bash\n\n", sep="", file=GO.file)
cat("export PATH=/public/programs/homer/bin:$PATH\n\n", sep="", file=GO.file, append=T)
cat("#Homer Command to run functional enrichment\n", sep="", file=GO.file, append=T)

subdata=read.csv(file)
if ( nrow(subdata)<=0)  {
	stop("No data loaded... Aborting")
}
if (colnames(subdata)[1]!='GeneName') { stop ("Fist column must be GeneName... Aborting")}
if (colnames(subdata)[2]!='Log2FoldChange') { stop ("Second column must be Log2FoldChange... Aborting")}
if (colnames(subdata)[3]!="PValue" & colnames(subdata)[3]!="P.value") { stop ("Third column must be PValue... Aborting")}
if (colnames(subdata)[4]!="AdjustedPValue" & colnames(subdata)[3]!="AdjustedP.value") { stop ("Third column must be AdjustedPValue... Aborting")}


#select DEG to output for functional enrichment in homer
if (mode=="FDR") {
	if(debug == 1) cat("Using 2 Fold Change and FDR 0.05 as cutoff.\n")
	sel_UP=subdata$Log2FoldChange>=1 & subdata$AdjustedPValue<=0.05
	sel_DOWN=subdata$Log2FoldChange<=(-1) & subdata$AdjustedPValue<=0.05
} else if (mode=="PValue")  {
	if(debug == 1) cat("Using 2 Fold Change and Pvalue 0.01 as cutoff.\n")
	sel_UP=subdata$Log2FoldChange>=1 & subdata$PValue<=0.01
	sel_DOWN=subdata$Log2FoldChange<=(-1) & subdata$PValue<=0.01
} else {  #Auto selection
	if(debug == 1) cat("Using automatic cutoff.\n")
	sel1u=subdata$Log2FoldChange>=1 & subdata$AdjustedPValue<=0.05
	sel1d=subdata$Log2FoldChange<=(-1) & subdata$AdjustedPValue<=0.05
	sel2u=subdata$Log2FoldChange>=1 & subdata$PValue<=0.01
	sel2d=subdata$Log2FoldChange<=(-1) & subdata$PValue<=0.01
	sel_Low1u=subdata$Log2FoldChange>=0.263 & subdata$AdjustedPValue<=0.25
	sel_Low1d=subdata$Log2FoldChange<=(-0.263) & subdata$AdjustedPValue<=0.25
	sel_Low2u=subdata$Log2FoldChange>=0.263 & subdata$PValue<=0.1
	sel_Low2d=subdata$Log2FoldChange<=(-0.263) & subdata$PValue<=0.1
	if (  sum(sel1u, na.rm=T)>=200) {sel=sel1u
	} else if  (  sum(sel2u, na.rm=T)>=200) {sel=sel2u
	} else if  (  sum(sel_Low1u, na.rm=T)>=200) {sel=sel_Low1u
	} else if (sum(sel_Low2u, na.rm=T)>=50) {sel=sel_Low2u
	}else {sel=sel_Low2u; sel[order(subdata$Log2FoldChange, decreasing=T)[1:50]]=T}
	sel_UP=sel


	if (  sum(sel1d, na.rm=T)>=200) {sel=sel1d
	} else if  (  sum(sel2d, na.rm=T)>=200) {sel=sel2d
	} else if  (  sum(sel_Low1d, na.rm=T)>=200) {sel=sel_Low1d
	} else if (sum(sel_Low2d, na.rm=T)>=50) {sel=sel_Low2d
	}else {sel=sel_Low2d; sel[order(subdata$Log2FoldChange, decreasing=F)[1:50]]=T}
   	sel_DOWN=sel
}



#up regulated genes
fout_up=str_c(name, "_up_genes.txt")
out.DEG(subdata, sel_UP, fout_up)
#down regulated genes
fout_down=str_c(name, "_down_genes.txt")
out.DEG(subdata, sel_DOWN, fout_down)

#now create homer command
cat("/public/programs/homer/bin/findGO.pl ", fout_up, " ", species, " ", name, "_GO_Analysis_Up -cpu 6\n", sep="", file=GO.file, append=T)
cat("/public/programs/homer/bin/findGO.pl ", fout_down, " ", species, " ", name, "_GO_Analysis_Down -cpu 6\n", sep="", file=GO.file, append=T)

if(debug == 1) cat("Now running findGO.pl command in background\n")
system(paste("chmod 775 ", GO.file) )
system(paste("nohup bash ", GO.file, ">", str_replace(GO.file,".bash", ".log"), " &"))


#now work on PAGE
myGscF=loadGSC(gmt.file)
#Run PAGE analysis
gseaO=subdata[, 1:2]
gseaO[(gseaO[, 1]==''), 1]='unknown' #gsea cannot have empty values. NA is okay
gseaO=gseaO[!is.na(gseaO[, 2]), ] #remove logFC values that are NAs
#average logFC for duplicated names
gsea=data.matrix(gseaO[, 2])
rownames(gsea)=gseaO[, 1]
myFC=avereps(gsea)
write.table(myFC, str_c(name, ".rnk"), sep="\t", quote=F, col.names=F)
cat("PAGE analysis on Comparison", name, "...\n")
system.time(gsaRes <- runGSA(myFC, geneSetStat="page", gsc=myGscF, gsSizeLim=c(10, 1000), nPerm=1000) ) # typically 2-3 minutes per sample
out=GSAsummaryTable(gsaRes)
#combined up and down list
out[out[, 3]<0, 4:5]=out[out[, 3]<0, 6:7]
out=out[, c(1:5, 8:9)]
colnames(out)[3:5]=c("Z Score", "p-value", "FDR")
page.outfile=str_c("PAGE_", name, ".csv")
write.csv(out, page.outfile, row.names=F)

