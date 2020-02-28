#GSEA Plot
options(stringsAsFactors=F)
suppressPackageStartupMessages(library(stringr))

# cmd='find ../ -name "gsea_report*.xls" > gsea_reportfiles.txt'
# system(cmd)
# system('cp /public/scripts/php/GSEAinfo_files/*.* .')

plotGSEA2<- function(data2plot, title, m) {
xmax=max(data2plot[, 14])
labels=wrap.labels(data2plot[, 1], 35)
#par(oma=c(0,9,0,2))  #outer margin where par(mar=c(bottom,left,top,right))
barplot(data2plot[, 14], horiz=TRUE, xlab="Number of Genes in Core Enrichment", names.arg=labels, las=2, cex.name=0.7, main=title, xlim=c(0, xmax*1.2) )
#now add FDR value
if (length(m)<10) {
m=barplot(data2plot[, 14], horiz=TRUE, xlab="Number of Genes in Core Enrichment", names.arg=labels, las=2, cex.name=0.7, main=title, xlim=c(0, xmax*1.2) )}
text(data2plot[, 14], m, labels=formatC(data2plot[, 8], digits=1, format="g"), pos=4, cex=0.7)
}

wrap.it <- function(x, len)
{
  sapply(x, function(y) paste(strwrap(y, len),
                              collapse = "\n"),
         USE.NAMES = FALSE)
}

wrap.labels <- function(x, len)
{
  if (is.list(x))
  {
    lapply(x, wrap.it, len)
  } else {
    wrap.it(x, len)
  }
}

list=read.table("gsea_reportfiles.txt", header=F)
list=list[order(list[,1]), 1] #now list become character
m=barplot(1:10)
n=length(list)
ncol=2
nrow=ceiling(n/ncol)
pdf('GSEA_top10_1page_summary.pdf', height=nrow*5, width=10)
par(mfrow=c(nrow, ncol), mar=c(4,15,3,2), oma=c(6,1,3,1) ) #par(mar=c(bottom,left,top,right))
for (i in 1:length(list) ) {
	file=list[i]
	data=read.table(file, sep="\t", header=T, quote = "", comment.char = "")
	data=data[!is.na(data$NES), ] #remove NA rows that show up as FDR 1 in graph
	tagP=str_extract(data[, 11], "\\d+")
	tagP=as.numeric(tagP)
	N_core=round(data[, 4]*tagP/100)
	data=cbind(data, tagP, N_core)
	data2plot=data[10:1, ] #choose what to plot, inverse order so first one on top
	data2plot[, 1]=str_replace_all(data2plot[, 1], "_", " ") #do this for human so long names can wrap
	#comp=str_replace_all(str_extract(file, "GSEA_.+?vs.+?/"), "/", "")
	comp=str_replace_all(str_extract(file, "GSEA_.+?/"), "/", "")
	if (grepl("_neg_", file)) {dirS= "Negative Enrichment"
	} else
	{dirS="Positive Enrichment" }
	title=paste(comp, dirS)
	plotGSEA2(data2plot, title,m)
	box("figure", col="grey")
	Comparison=rep(comp, 10)
	Category=rep(dirS, 10)
	outdata=cbind(Comparison, Category, data2plot[10:1, ])
	if (i==1) {alldata=outdata}
	else{alldata=rbind(alldata, outdata) }
}
dev.off()
write.csv(alldata, "GSEA_summary.csv", row.names=F)


#write Info file
infoFile='info_details.txt'
cat("Comparison\tCategory\tURL\tDisplay\tDescription\n", file=infoFile)
description="Below are the top 10 functional categories from GSEA analysis for up (positive) and down(negative) regulated genes.  Click view details above to  view all functional categories. If you are new to GSEA analysis, here is a guide on <a href='http://www.broadinstitute.org/gsea/doc/GSEAUserGuideTEXT.htm' target='_blank'>how to interpret GSEA results</a>."
for (i in 1:length(list) ) {
	file=list[i]
	#comp=str_replace_all(str_extract(file, "GSEA_.+?vs.+?/"), "/", "")
	comp=str_replace_all(str_extract(file, "GSEA_.+?/"), "/", "")
	comp1=str_replace(comp, "GSEA_", "")
	if (grepl("_neg_", file)) {
	report=str_extract(file, "gsea_report_for.+xls")
	url=str_replace(file, report, '')
	url=str_replace(url, "\\.\\.\\/", '')
	cat(comp, "\t\t", url, "\tGSEA Analysis Results of ", comp1, "\t", description, "\n", sep="", file=infoFile, append=T)
	}
}
