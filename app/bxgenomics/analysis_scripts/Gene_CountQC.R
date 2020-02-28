#!/usr/bin/env Rscript
# Script to perform QC Test of RNA-Seq raw counts data
options(stringsAsFactors=F)
suppressPackageStartupMessages(library(limma))
suppressPackageStartupMessages(library(edgeR))
suppressPackageStartupMessages(library(stringr))
suppressPackageStartupMessages(library(made4))
suppressPackageStartupMessages(library(genefilter))
args <- commandArgs(TRUE)
#args=c('gene_counts.txt') #if doing local testing
#args=c('gene_counts.txt', 'SampleInfo.txt')   #if doing local testing with sample info

file=args[1]
gene_counts=read.table(file, sep="\t", header=T, row.names=1)
cat ('Gene count file file dimension', dim(gene_counts), "\n")
#gene_counts=read.table("all-gene-counts.tsv", header=T, sep="\t",quote = "", comment.char = "") # use this to load all if there are issues 
counts=gene_counts[, 6:dim(gene_counts)[2]]
colnames(counts)=str_replace(colnames(counts), '.sorted.bam', '') #remove sorted.bam
colnames(counts)=str_replace(colnames(counts), '.bam', '') #remove bam
if (length(args)>1) {
	cat("Use",args[2], "to rename samples.\n")
	sampleinfo=read.table(args[2], sep="\t", header=F, row.names=1)
	colnames(counts)=sampleinfo[colnames(counts), 1]
}
counts=counts[,order(colnames(counts))] #sort name
write.csv(counts, "Raw_Counts.csv")

pdf('Raw_counts_MDS.pdf') #adjust height, 70 per page (10 inc)
plotMDS(counts) # may look weird, need to normalize
dev.off()

#select genes
cpms = cpm(counts)
keep = rowSums(cpms>1)>=2 
countsF=counts[keep, ]
d2=DGEList(counts=countsF)
d2=calcNormFactors(d2) 
write.csv(d2$sample, 'Library.Size.Normalization.csv') #can check later

file_qc='QC_info.txt'
cat("Total Genes\t", nrow(counts), "\nSelected Genes\t", nrow(countsF), "\n", file=file_qc)
cat("Total Genes\t", nrow(counts), "\nSelected Genes\t", nrow(countsF), "\n")
d2$sample


pdf('voom_norm.pdf')
y=voom(d2, plot=TRUE)
dev.off()
pdf('norm_MDSplot.pdf')
plotMDS(y) # saved as picture
dev.off()
png('norm_MDSplot.png')
plotMDS(y) # saved as picture
dev.off()

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

cat ("Genes in heatmap\t", nrow(subdata),"\n", file=file_qc, append=TRUE)

pdf('Overall_Heatmap.pdf',width=8, height=10)
par(oma=c(3.5,0,0,0))  #outer margin where par(mar=c(bottom,left,top,right))
heatplot(subdata, labRow="",cexCol=1) #saved as picture #add key=FALSE if on color key is needed
dev.off()
png('Overall_Heatmap.png',width=8*72, height=10*72)
par(oma=c(3.5,0,0,0))  #outer margin where par(mar=c(bottom,left,top,right))
heatplot(subdata, labRow="", cexCol=1) #saved as picture #add key=FALSE if on color key is needed
dev.off()


#RNA-Seq stat for expressed genes
expressed=matrix(, nrow=5, ncol=dim(counts)[2])
expressed[1, ]=colSums(counts>=100)
expressed[2, ]=colSums(counts>=50)
expressed[3, ]=colSums(counts>=10)
expressed[4, ]=colSums(counts>=2)
expressed[5, ]=colSums(counts>=1)
rownames(expressed)=c('>=100', '>=50', '>=10', '>=2', '>=1')
colnames(expressed)=colnames(counts)
write.csv(expressed, "Expressed_Genes.csv")
plotW=ceiling(ncol(counts)*0.4)
plotW=max(plotW, 8)
pdf('Expressed_Genes.pdf',  height=8, width=plotW)
par(oma=c(2.5,0,0,0))  #outer margin where par(mar=c(bottom,left,top,right))
barplot(expressed, beside=TRUE, legend=rownames(expressed), args.legend=list(title="Read Count", x="topleft", cex=1),
 cex.names=0.65, main="Number of Expressed Genes", xaxt="n", xlab="", ylim=c(0, max(expressed)*1.3) )
axis(1, at=((1:ncol(counts))*6-3), labels=colnames(counts), las=2, lwd=0)
dev.off()
png('Expressed_Genes.png',  height=8*72, width=plotW*72)
par(oma=c(2.5,0,0,0))  #outer margin where par(mar=c(bottom,left,top,right))
barplot(expressed, beside=TRUE, legend=rownames(expressed), args.legend=list(title="Read Count", x="topleft", cex=1),
 cex.names=0.65, main="Number of Expressed Genes", xaxt="n", xlab="", ylim=c(0, max(expressed)*1.3) )
axis(1, at=((1:ncol(counts))*6-3), labels=colnames(counts), las=2, lwd=0)
dev.off()

#stat for top 100 genes
PerTop=matrix(, nrow=100, ncol=dim(counts)[2])
colnames(PerTop)=colnames(counts)
for (i in 1:dim(counts)[2]) {
	exp=counts[, i]
	total=sum(exp)
	perE=sort(exp, decreasing=TRUE)/total*100
	cumper=0;
	for (j in 1:100 ) {
		cumper=cumper+perE[j]
		PerTop[j, i]=cumper
	}
}
 write.csv(PerTop, "Top_Genes.csv")
c10=rainbow(dim(counts)[2])
n=dim(counts)[2]
ncol=ceiling(sqrt(n))
ncol=min(4, ncol)
nrow=ceiling(n/ncol)
#plot a chart
pdf('Top_Genes.pdf',  height=nrow*2.5, width=10)
par(mfrow=c(nrow, ncol)) #three columns, six rows
for (i in 1:dim(counts)[2]) {
	plot(PerTop[,i], ylim=c(1,100), type="l", xlab=colnames(PerTop)[i],ylab="", col=c10[i], lwd=2)
}
dev.off()
png('Top_Genes.png',  height=nrow*2.5*72, width=10*72)
par(mfrow=c(nrow, ncol)) #three columns, six rows
for (i in 1:dim(counts)[2]) {
	plot(PerTop[,i], ylim=c(1,100), type="l", xlab=colnames(PerTop)[i],ylab="", col=c10[i], lwd=2)
}
dev.off()

###box plot of logCPM
plotW=ceiling(ncol(counts)*0.4)
plotW=max(plotW, 8)
pdf('logCPM_BoxPlot.pdf', width=plotW, height=8)
par(oma=c(2.5,0,0,0))  #outer margin where par(mar=c(bottom,left,top,right))
boxplot(y$E, xaxt="n", xlab="",main="Normalized Expression Values (logCPM from voom)")
axis(1, at=1:ncol(counts), labels=colnames(counts), las=2)
dev.off()
png('logCPM_BoxPlot.png', width=plotW*72, height=8*72)
par(oma=c(2.5,0,0,0))  #outer margin where par(mar=c(bottom,left,top,right))
boxplot(y$E, xaxt="n", xlab="",main="Normalized Expression Values (logCPM from voom)")
axis(1, at=1:ncol(counts), labels=colnames(counts), las=2)
dev.off()

##correlation plot
panel.cor <- function(x, y, digits = 3, cex.cor, ...)
{
  usr <- par("usr"); on.exit(par(usr))
  par(usr = c(0, 1, 0, 1))
  # correlation coefficient
  r <- cor(x, y)
  txt <- format(c(r, 0.123456789), digits = digits)[1]
  txt <- paste("r= ", txt, sep = "")
  text(0.5, 0.6, txt, cex=1.3)
 
  # p-value calculation
  p <- cor.test(x, y)$p.value
  txt2 <- format(c(p, 0.123456789), digits = digits)[1]
  txt2 <- paste("p= ", txt2, sep = "")
  if(p<0.01) txt2 <- paste("p= ", "<0.01", sep = "")
  text(0.5, 0.4, txt2,cex=1.3)
}
png_W=ncol(counts)*100
png('Correlation.png', width=png_W, height=png_W)
pairs(y$E, upper.panel = panel.cor)
dev.off()


#Get gene count summary into nice csv file and plot
#file="gene_counts.txt"
file1=paste(file, ".summary", sep="")
summary_table=read.table(file1, sep="\t", header=T, row.names=1)
colnames(summary_table)=str_replace(colnames(summary_table), '.sorted.bam', '') #remove sorted.bam
colnames(summary_table)=str_replace(colnames(summary_table), '.bam', '') #remove bam
if (length(args)>1) {
	colnames(summary_table)=sampleinfo[colnames(summary_table), 1]
}

summary_table=summary_table[, order(colnames(summary_table))] #order headers
Total_Reads=colSums(summary_table)
Percentage_Mapped=round((1-summary_table["Unassigned_Unmapped", ]/Total_Reads)*1000)/10
Percentage_Assigned=round((summary_table["Assigned", ]/Total_Reads)*1000)/10
summary_table_out=rbind(summary_table, Total_Reads,Percentage_Mapped,Percentage_Assigned)
n=nrow(summary_table)+1
rownames(summary_table_out)[n:(n+2)]=c('Total_Reads','Percentage_Mapped','Percentage_Assigned')
write.csv(summary_table_out, "Mapping_Summary.csv")
keep=(rowSums(summary_table)>0)
plottable=as.matrix(summary_table[keep, ])
library(RColorBrewer)
color=brewer.pal(nrow(plottable), "Set2")
ymax=max(colSums(plottable))*1.3
#save barplot position first
m=barplot(plottable, col=color,  main="Mapping Summary", ylab="Number of Reads",ylim=c(0, ymax), legend = rownames(plottable), xaxt="n", xlab="")
pdf("Mapping_Summary.pdf", width=plotW, height=8)
par(oma=c(2.5,0,0,0))  #outer margin where par(mar=c(bottom,left,top,right))
barplot(plottable, col=color,  main="Mapping Summary", ylab="Number of Reads",ylim=c(0, ymax), legend = rownames(plottable), xaxt="n", xlab="")
axis(1, at=m, labels=colnames(counts), las=2, lwd=0)
dev.off()
png("Mapping_Summary.png", width=plotW*72, height=8*72)
par(oma=c(2.5,0,0,0))  #outer margin where par(mar=c(bottom,left,top,right))
barplot(plottable, col=color,  main="Mapping Summary", ylab="Number of Reads",ylim=c(0, ymax), legend = rownames(plottable), xaxt="n", xlab="")
axis(1, at=m, labels=colnames(counts), las=2, lwd=0)
dev.off()
#########end of summary

#outlier detection
norm_factor=d2$sample[3]
colnames(norm_factor)='norm_factor'
norm_factor_rate=rep('', ncol(counts))
norm_factor_rate[(norm_factor>1.5 | norm_factor<0.66)]="*"
gene_count10=(expressed[3, ])
gene_count10_rate=rep('', ncol(counts))
gene_count10_rate[gene_count10<(median(gene_count10)/2)]="*"
Top100=PerTop[100, ]
Top100_rate=rep('', ncol(counts))
Top100_rate[Top100>35]="*"
outlier=data.frame(norm_factor, norm_factor_rate, gene_count10, gene_count10_rate, Top100, Top100_rate)
write.csv(outlier, "Outlier_Detection.csv")




#heatmap and MDS plot without outliers
goodsamples=rownames(outlier)[rowSums(outlier=="*")==0]
cat ("Number of Samples\t", ncol(counts), "\nNumber of Outliers\t", ncol(counts)-length(goodsamples), "\n", file=file_qc, append=TRUE)
if (length(goodsamples)<ncol(counts)) {
	countsFO=countsF[, goodsamples]
	cat (length(goodsamples), "out of", ncol(counts), "samples left after outlier detection.\n")
	if (ncol(countsFO)>2) {
	d2=DGEList(counts=countsFO)
	d2=calcNormFactors(d2) 
	y=voom(d2)
	pdf('norm_MDSplot_No_Outliers.pdf')
	plotMDS(y) # saved as picture
	dev.off()
	png('norm_MDSplot_No_Outliers.png')
	plotMDS(y) # saved as picture
	dev.off()

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
	pdf('Overall_Heatmap_No_Outliers.pdf',width=8, height=10)
	par(oma=c(2.5,0,0,0))  #outer margin where par(mar=c(bottom,left,top,right))
	heatplot(subdata, labRow=" ") #saved as picture #add key=FALSE if on color key is needed
	dev.off()
	png('Overall_Heatmap_No_Outliers.png',width=8*72, height=10*72)
	par(oma=c(2.5,0,0,0))  #outer margin where par(mar=c(bottom,left,top,right))
	heatplot(subdata, labRow=" ") #saved as picture #add key=FALSE if on color key is needed
	dev.off()
	}
}
