#!/usr/bin/env Rscript
suppressPackageStartupMessages(library(optparse))
suppressPackageStartupMessages(library(stringr))
suppressPackageStartupMessages(library(limma))
suppressPackageStartupMessages(library(edgeR))
suppressPackageStartupMessages(library(made4))
suppressPackageStartupMessages(library(genefilter))
library(RColorBrewer)

#updated Mar 22 2018, replace counts with count_matrix in    cat("Total Genes\t", nrow(count_matrix), "\nSelected Genes\t", nrow(x), "\n")
#updated Aug 27 2017, max 5K genes in overlap, if >1000 DEGs, select up to 1000 for each direction (also affect homer list)
#Updated Mar 17 2016, when fewer than 2 DEG, use top 100 to generate summary heatmap, so auto report will work properly. This also affected GO Enrichment list.
#Updated Mar 17 2016, sort text to make sure colors are ordered.  color=color3[1, sort(unique(change)) ] 
#Updated Mar 11 2016: average logFC for GSEA, remove duplicates for up  and down list 

#===============================================================================#
#           performs a pairwise DEG analysis with limma                         #
#===============================================================================#

pairwise_limma_runner_GTF = function(count_matrix, pheno_mapper, compare, output_loc, annot, rpkm_sub, Filter=F, TMM=T) {
   # sanity checking that our subsetting worked right
   if (!all(colnames(count_matrix) == pheno_mapper$Sample)){    
     stop("Sample names don't line up with count matrix... Aborting")    
   }
 
  # create output directory
  dir.create(file.path(output_loc), showWarnings = FALSE)
  outdir1=paste(output_loc, "DEG_Analysis/", sep="")
  dir.create(file.path(outdir1), showWarnings = FALSE)
  outdir2=paste(output_loc, "Downstream/", sep="")
  dir.create(file.path(outdir2), showWarnings = FALSE)
  outdir3=paste(output_loc, "Top100Genes/", sep="")
  dir.create(file.path(outdir3), showWarnings = FALSE)
  outdir4=paste(output_loc, "Overview/", sep="")
  dir.create(file.path(outdir4), showWarnings = FALSE)
  
  name=str_c(compare[1], ".vs.", compare[2])
  fileLog=paste(outdir4, name, '_Log.txt', sep="")

  cat("Comparison\t", name, "\nRemove_NoExp_Genes\t", Filter, "\nTMM_normalization\t", TMM, "\n", file=fileLog)
  cat("\nNow working on comparison", str_c(compare[1], "-", compare[2]), "\n")
  

  # Filter out low count genes... Optional, as pre-process already done it
  if (Filter) {
	   isexpr = rowSums(cpm(count_matrix) > 1) >= 2  ## At least 2 samples with greater than 1 CPM
	   x = count_matrix[isexpr, ]
   } else {x = count_matrix }
   cat("Total_Genes\t", nrow(count_matrix), "\nSelected_Genes\t", nrow(x), "\n", file=fileLog, append=TRUE)
   cat("Total Genes\t", nrow(count_matrix), "\nSelected Genes\t", nrow(x), "\n")



  d2=DGEList(counts=x)

 if (TMM) {
 #TMM normalization
    d2=calcNormFactors(d2) 
    name=str_c(compare[1], ".vs.", compare[2])
    write.csv(d2$sample, paste(outdir4, name, '_Libary.Size.Normalization.csv', sep='')) 
  }
 
  #run limma
  celltype = factor(pheno_mapper$Phenotype)
  design = model.matrix(~ 0 + factor(celltype))
  colnames(design) = levels(factor(celltype))
  contrasts.matrix = makeContrasts( contrasts=str_c(compare[1], "-", compare[2]),  levels=design)
  
  # plot mean variance from Voom function
  pdf(paste(outdir4, "voom_mean_variance_plot.pdf", sep=""))
  y = voom(d2, design, plot=TRUE)
  dev.off()
  pdf(paste(outdir4, "MDS_plot.pdf", sep=""))
  plotMDS(y)
  dev.off()
  png(paste(outdir4, "MDS_plot.png", sep=""))
  plotMDS(y) # saved as picture
  dev.off()

  
  
  # use Limma topTable to get top genes
  fit = lmFit(y, design)
  fit2 = contrasts.fit(fit, contrasts.matrix)
  fit2 = eBayes(fit2)
  tt = topTable(fit2, resort.by="logFC", n=nrow(x))
  alldata=cbind(rpkm_sub[rownames(tt), ], tt[, c(2, 1, 4, 5)], annot[rownames(tt), ])
  write.csv(alldata, paste(outdir4, name, "_alldata.csv", sep=""))
#  write.table(alldata, paste(output_loc, name, "_alldata.txt", sep=""),sep="\t", quote=F ) #can use later if needed

#Count number of DEGs
DEGsum=matrix(, nrow=3, ncol=2)
rownames(DEGsum)=c('Up_regulated', 'Down_regulated', 'Total')
colnames(DEGsum)=c('FDR 0.05; 2 fold', 'P-value 0.01; 2 fold') 
DEGsum[1, 1]=nrow(tt[( (tt$logFC>=1) & (tt$adj.P.Val<=0.05)), ])
DEGsum[2, 1]=nrow(tt[( (tt$logFC<=-1) & (tt$adj.P.Val<=0.05)), ])
DEGsum[1, 2]=nrow(tt[( (tt$logFC>=1) & (tt$P.Value<=0.01)), ])
DEGsum[2, 2]=nrow(tt[( (tt$logFC<=-1) & (tt$P.Value<=0.01)), ])
DEGsum[3,]=DEGsum[1,]+DEGsum[2, ]
write.csv(DEGsum,  paste(outdir1, name, "_DEG_Summary.csv", sep=""))

#get change type to make vertical color bar for statistical values
changeAll=rep('Change0', nrow(tt))
changeAll[(abs(tt$logFC)>=1)  & (tt$P.Value<=0.01)]='Change1'
changeAll[(abs(tt$logFC)>=1) & (tt$adj.P.Val<=0.05)]='Change2'
tt$changeAll=as.character(changeAll)
color3=matrix(, ncol=3, nrow=1)
colnames(color3)=c('Change0', 'Change1', 'Change2')
color3[1, ]=brewer.pal(3, "OrRd")
 

#plot overview heatmap
dataSD=rowSds(y$E)
dataM=rowMeans(y$E)
dataS_M=dataSD/dataM
subdata=y$E[dataS_M>0.3, ]
#trim down number of genes in subdata if needed
if (nrow(subdata)>5000) {
	subdata=y$E[dataS_M>0.3 & dataM>=1, ]
	if (nrow(subdata)>5000) {
		diff=rowSds(subdata)/rowMeans(subdata)
		subdata=subdata[order(diff, decreasing=TRUE)[1:5000], ]
	}
}

cat ('subdata for heatplot size', dim(subdata), "\n")
cat ('Genes_in_overall_heatmap\t', nrow(subdata), "\n",file=fileLog, append=TRUE)
#figure out change, and color
change=tt[rownames(subdata), ncol(tt)]
color=color3[1, sort(unique(change)) ]

pdf(paste(outdir4, name, "_Overall_Heatmap.pdf", sep=""),width=8, height=10)
par(oma=c(2.5,0,0,0))  #outer margin where par(mar=c(bottom,left,top,right))
heatplot(subdata, labRow=" ", cexCol=1,classvec=change, classvecCol=color) #saved as picture #add key=FALSE if on color key is needed
dev.off()
png(paste(outdir4, name, "_Overall_Heatmap.png", sep=""),width=8*72, height=10*72)
par(oma=c(2.5,0,0,0))  #outer margin where par(mar=c(bottom,left,top,right))
heatplot(subdata, labRow=" ", cexCol=1,classvec=change, classvecCol=color) #saved as picture #add key=FALSE if on color key is needed
dev.off()



#plot DEG heatmap
DEG=tt[((abs(tt$logFC)>=1) & (tt$adj.P.Val<=0.05)), ]
if (nrow(DEG)>10) {
	  write.csv(alldata[rownames(DEG), ], paste(outdir1, name, "_DEG.csv", sep=""))
	  cat("DEG_Type\tFDR\nDEG_Number\t", nrow(DEG), "\n", file=fileLog, append=TRUE)
	
	#Plot summary
	toPlot=rownames(DEG)
	if (length(toPlot)>5000) {toPlot=sample(toPlot, 5000) }
	pdf(paste(outdir1, name, "_DEG_sum.pdf", sep=""),width=8, height=10)
	par(oma=c(2.5,0,0,0))  #outer margin where par(mar=c(bottom,left,top,right))
	heatplot(y$E[toPlot, ], labRow=" ", cexCol=1) #saved as picture #add key=FALSE if on color key is needed
	dev.off()
	png(paste(outdir1, name, "_DEG_sum.png", sep=""),width=8*72, height=10*72)
	par(oma=c(2.5,0,0,0))  #outer margin where par(mar=c(bottom,left,top,right))
	heatplot(y$E[toPlot, ], labRow=" ", cexCol=1) #saved as picture #add key=FALSE if on color key is needed
	dev.off()
	pdf(paste(outdir1, name, "_DEG_sum_g.pdf", sep=""),width=8, height=10)
	par(oma=c(2.5,0,0,0))  #outer margin where par(mar=c(bottom,left,top,right))
	heatplot(y$E[toPlot, ], dend="row", labRow=" ", cexCol=1) #saved as picture #add key=FALSE if on color key is needed
	dev.off()
	
	#plot details, up to top 1000 genes in each direction
	
	if (nrow(DEG)>1000) {
		DEG=DEG[order(DEG$adj.P.Val, decreasing=FALSE),]
		DEG_up=DEG[DEG$logFC>0, ]
		if (nrow(DEG_up)>1000 ) {DEG_up=DEG_up[1:1000, ] }
		DEG_down=DEG[DEG$logFC<0, ]
		if (nrow(DEG_down)>1000 ) {DEG_down=DEG_down[1:1000, ] }
		DEG=rbind(DEG_up, DEG_down)
	}
	graphHeight=ceiling(dim(DEG)[1]/100)*10  # decide height of graph, 70 genes for 10 inch
	pdf(paste(outdir1, name, "_DEG_details.pdf", sep=""), height=graphHeight, width=8) #adjust height, 70 per page (10 inc)
	par(oma=c(3.5,0,0,10))  #outer margin where par(mar=c(bottom,left,top,right))
	info=annot[rownames(DEG), ]
	heatplot(y$E[rownames(DEG), ], dend="both", labRow=paste(info[, 1], info[, 2]), cexCol=1,lhei=c(3,(graphHeight-3)) )
	dev.off()
	pdf(paste(outdir1, name, "_DEG_details_g.pdf", sep=""), height=graphHeight, width=8) #adjust height, 70 per page (10 inc)
	par(oma=c(3.5,0,0,10))  #outer margin where par(mar=c(bottom,left,top,right))
	info=annot[rownames(DEG), ]
	heatplot(y$E[rownames(DEG), ], dend="row", labRow=paste(info[, 1], info[, 2]), cexCol=1,lhei=c(3,(graphHeight-3)) )
	dev.off()
} else {
	
	DEG=tt[((abs(tt$logFC)>=1) & (tt$P.Value<=0.01)), ] #just use p value
	 write.csv(alldata[rownames(DEG), ], paste(outdir1, name, "_DEG_low.csv", sep=""))
	 cat("DEG_Type\tP-Value\nDEG_Number\t", nrow(DEG), "\n", file=fileLog, append=TRUE)

	#plot DEG when >10 DEGs; Otherwise use top 100 summary plot 
	if (nrow(DEG)<=10) {DEG=tt[order(abs(tt$logFC), decreasing=TRUE), ][1:100, ] }
	 
	#plot summary
	pdf(paste(outdir1, name, "_DEG_sum_low.pdf", sep=""),width=8, height=10)
	par(oma=c(2.5,0,0,0))  #outer margin where par(mar=c(bottom,left,top,right))
	heatplot(y$E[rownames(DEG), ], labRow=" ", cexCol=1) #saved as picture #add key=FALSE if on color key is needed
	dev.off()
	png(paste(outdir1, name, "_DEG_sum_low.png", sep=""),width=8*72, height=10*72)
	par(oma=c(2.5,0,0,0))  #outer margin where par(mar=c(bottom,left,top,right))
	heatplot(y$E[rownames(DEG), ], labRow=" ", cexCol=1) #saved as picture #add key=FALSE if on color key is needed
	dev.off()
	pdf(paste(outdir1, name, "_DEG_sum_low_g.pdf", sep=""),width=8, height=10)
	par(oma=c(2.5,0,0,0))  #outer margin where par(mar=c(bottom,left,top,right))
	heatplot(y$E[rownames(DEG), ], dend="row", labRow=" ", cexCol=1) #saved as picture #add key=FALSE if on color key is needed
	dev.off()

	#plot details, up to top 1000 genes
	if (nrow(DEG)>1000) {
		DEG=DEG[order(DEG$adj.P.Val, decreasing=FALSE),]
		DEG_up=DEG[DEG$logFC>0, ]
		if (nrow(DEG_up)>1000 ) {DEG_up=DEG_up[1:1000, ] }
		DEG_down=DEG[DEG$logFC<0, ]
		if (nrow(DEG_down)>1000 ) {DEG_down=DEG_down[1:1000, ] }
		DEG=rbind(DEG_up, DEG_down)
	}
	graphHeight=ceiling(dim(DEG)[1]/100)*10  # decide height of graph, 70 genes for 10 inch
	pdf(paste(outdir1, name, "_DEG_details_low.pdf", sep=""), height=graphHeight, width=8) #adjust height, 70 per page (10 inc)
	par(oma=c(3.5,0,0,10))  #outer margin where par(mar=c(bottom,left,top,right))
	info=annot[rownames(DEG), ]
	heatplot(y$E[rownames(DEG), ], dend="both", labRow=paste(info[, 1], info[, 2]), cexCol=1,lhei=c(3,(graphHeight-3)) )
	dev.off()
	pdf(paste(outdir1, name, "_DEG_details_low_g.pdf", sep=""), height=graphHeight, width=8) #adjust height, 70 per page (10 inc)
	par(oma=c(3.5,0,0,10))  #outer margin where par(mar=c(bottom,left,top,right))
	info=annot[rownames(DEG), ]
	heatplot(y$E[rownames(DEG), ], dend="row", labRow=paste(info[, 1], info[, 2]), cexCol=1,lhei=c(3,(graphHeight-3)) )
	dev.off()

	
}

#plot Top 100 gene by logFC heatmap
if (nrow(DEG)>99) {
	top100=DEG[order(abs(DEG$logFC), decreasing=TRUE), ][1:100, ]
	 cat("Top_100_genes_Type\tLogFC_FDR_Pvalue\n", file=fileLog, append=TRUE)

} else {
	top100=tt[order(abs(tt$logFC), decreasing=TRUE), ][1:100, ]
	 cat("Top_100_genes_Type\tLogFC_Only\n", file=fileLog, append=TRUE)
}
write.csv(alldata[rownames(top100), ], paste(outdir3, name, "_Top100.csv", sep=""))
graphHeight=15
change=tt[rownames(top100), ncol(tt)]
color=color3[1, sort(unique(change)) ]

pdf(paste(outdir3, name, "_Top100.pdf", sep=""), height=graphHeight, width=10) 
par(oma=c(3.5,0,0,10))  #outer margin where par(mar=c(bottom,left,top,right))
info=annot[rownames(top100), ]
mark=rep('-', 100)
mark[top100$P.Value<=0.01]='*'
mark[top100$adj.P.Val<=0.05]='**'
heatplot(y$E[rownames(top100), ], dend="both", labRow=paste(info[, 1], mark, info[, 2]), cexCol=1,lhei=c(3,(graphHeight-3)), classvec=change, classvecCol=color)
dev.off()

pdf(paste(outdir3, name, "_Top100_g.pdf", sep=""), height=graphHeight, width=10) 
par(oma=c(3.5,0,0,10))  #outer margin where par(mar=c(bottom,left,top,right))
heatplot(y$E[rownames(top100), ], dend="row", labRow=paste(info[, 1], mark, info[, 2]), cexCol=1,lhei=c(3,(graphHeight-3)), classvec=change, classvecCol=color)
dev.off()

#also top 50
if (nrow(DEG)>49) {
	top50=DEG[order(abs(DEG$logFC), decreasing=TRUE), ][1:50, ]
	 cat("Top_50_genes_Type\tLogFC+FDR_Pvalue\n", file=fileLog, append=TRUE)

} else {
	top50=tt[order(abs(tt$logFC), decreasing=TRUE), ][1:50, ]
	 cat("Top_50_genes_Type\tLogFC_Only\n", file=fileLog, append=TRUE)
}
write.csv(alldata[rownames(top50), ], paste(outdir3, name, "_Top50.csv", sep=""))
change=tt[rownames(top50), ncol(tt)]
color=color3[1, sort(unique(change)) ]

graphHeight=10
pdf(paste(outdir3, name, "_Top50.pdf", sep=""), height=graphHeight, width=10) 
par(oma=c(3.5,0,0,10))  #outer margin where par(mar=c(bottom,left,top,right))
info=annot[rownames(top50), ]
mark=rep('-', 50)
mark[top50$P.Value<=0.01]='*'
mark[top50$adj.P.Val<=0.05]='**'
heatplot(y$E[rownames(top50), ], dend="both", labRow=paste(info[, 1], mark, info[, 2]), cexCol=1,lhei=c(3,(graphHeight-3)), classvec=change, classvecCol=color)
dev.off()

png(paste(outdir3, name, "_Top50.png", sep=""), height=graphHeight*72, width=10*72) 
par(oma=c(3.5,0,0,10))  #outer margin where par(mar=c(bottom,left,top,right))
heatplot(y$E[rownames(top50), ], dend="both", labRow=paste(info[, 1], mark, info[, 2]), cexCol=1,lhei=c(3,(graphHeight-3)),classvec=change, classvecCol=color)
dev.off()


pdf(paste(outdir3, name, "_Top50_g.pdf", sep=""), height=graphHeight, width=10) 
par(oma=c(3.5,0,0,10))  #outer margin where par(mar=c(bottom,left,top,right))
heatplot(y$E[rownames(top50), ], dend="row", labRow=paste(info[, 1], mark, info[, 2]), cexCol=1,lhei=c(3,(graphHeight-3)),classvec=change, classvecCol=color)
dev.off()




#output for GSEA
gsea=cbind(annot[rownames(tt), 1], tt[, 1])
gsea[(gsea[, 1]==''), 1]='unknown' #gsea can not have empty values. NA is okay
#average logFC for duplicated names
rownames(gsea)=gsea[, 1]
gsea=gsea[, 2]
gsea=avereps(gsea)
Symbol=rownames(gsea)
logFC=gsea[,1]
gsea=cbind(Symbol, gsea)
colnames(gsea)=c('Symbol', 'logFC')
write.table(gsea, paste(outdir2, name, "_GSEA.rnk", sep=""), sep="\t", quote=F, row.names=F)

#output for hommer if there are more than 10 DEGs  (top 1000 genes were used)
if (nrow(DEG)>10) {
	info=annot[rownames(DEG), ]
	write.table(unique(info[(DEG$logFC>=1),1]), paste(outdir2, name, "_up_list.txt", sep=""), sep="\t", col.names=F, quote=F, row.names=F)
	write.table(unique(info[(DEG$logFC<=-1),1]), paste(outdir2, name, "_down_list.txt", sep=""), sep="\t", col.names=F, quote=F, row.names=F)
	cat("List_for_GO\tYes\n", file=fileLog, append=TRUE)

} else {cat("List_for_GO\tNo\n", file=fileLog, append=TRUE)}

}

