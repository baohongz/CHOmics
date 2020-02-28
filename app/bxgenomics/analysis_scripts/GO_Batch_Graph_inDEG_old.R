####GO plot

options(stringsAsFactors=F)
suppressPackageStartupMessages(library(stringr))

# system('find ../ -name "*GO_Analysis*" > GO_folders.txt')
# system('cp /public/scripts/php/GOinfo_files/*.* .')

#add this to get comparison names
upfile=list.files("../Downstream/", pattern="up_list.txt|Up_list.txt")
comp=str_replace(upfile, "_up_list.txt", "")

plotGO<- function(data2plot, title, m) {
data2plot[, 2]=str_replace_all(data2plot[, 2], "_", " ") #do this for human so long names can wrap
xmax=max(data2plot[, 6])
labels=wrap.labels(data2plot[, 2], 35)
#par(oma=c(0,9,0,2))  #outer margin where par(mar=c(bottom,left,top,right))
barplot(data2plot[, 6], horiz=TRUE, xlab="Number of Genes", names.arg=labels, las=2, cex.name=0.9, main= title, xlim=c(0, xmax*1.2) )
text(data2plot[, 6], m, labels=formatC(data2plot[, 4], digits=1, format="f"), pos=4, cex=0.9)
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
folders=read.table("GO_folders.txt", header=F)
folders=folders[order(folders[,1]), 1] #now folder become character

list=c("biological_process.txt", "cellular_component.txt" , "molecular_function.txt","kegg.txt" ,"msigdb.txt" ,  "interpro.txt", "reactome.txt" , "wikipathways.txt"    )
list=list[1:6] #jsut use first 6 for plotting
titles=c("Biological Process", "Cellular Component" , "Molecular Function", "KEGG Pathway", "Molecular Signature" , "Interpro Protein Domain", "Reactome" , "Wikipathways"  )
m=barplot(1:10)
n=length(list)
ncol=3
nrow=ceiling(n/ncol)
pdf('Functional_Enrichment_summary.pdf', height=nrow*5, width=ncol*5)
for (f in 1:length(folders) ) {
	folder=paste(folders[f], "/", sep="")
#	comp=str_extract(folder, "out/.+?/Downstream")
#	comp=str_replace_all(str_extract(comp, "/.+?/"), "/", "")
	if (grepl("Analysis_Up", folder)) {comp_title=paste(comp, "Up-Regulated")
	} else
	{comp_title=paste(comp, "Down-Regulated") }
	par(mfrow=c(nrow, ncol), mar=c(4,15,3,2), oma=c(6,1,3,1) ) #par(mar=c(bottom,left,top,right))
	for (i in 1:length(list) ) {
		file=paste(folder, list[i], sep='')
		data=read.delim(file, sep="\t", header=T, fill=T)
		#dedup for kegg
		if (list[i]=="kegg.txt") {
			data=data[!duplicated(data[, 2]), ]
		}
		data2plot=data[10:1, ] #choose what to plot, inverse order so first one on top
		plotGO(data2plot, titles[i], m)
		box("figure", col="grey")
		#combine all top10 data into a big table
		Comparison=rep(comp_title, 10)
		Category=rep(titles[i], 10)
		outdata=cbind(Comparison, Category, data[1:10, ])
		if (f==1 & i==1) {alldata=outdata}
		else{alldata=rbind(alldata, outdata) }
	}
	mtext(comp_title, side=1, line=6)
}
dev.off()
write.csv(alldata, "Functional_Enrichment_summary.csv", row.names=F)

#write Info file
infoFile='info_details.txt'
cat("Comparison\tCategory\tURL\n", file=infoFile)

for (i in 1:length(folders) ) {
	folder=folders[i]
	#comp=str_extract(folder, "out/.+?/Downstream")
	#comp=str_replace_all(str_extract(comp, "/.+?/"), "/", "")
	if (grepl("Analysis_Up", folder)) {comp_title=paste(comp, "Up-Regulated")
	} else
	{comp_title=paste(comp, "Down-Regulated") }
	url=paste(folder, '/geneOntology.html', sep='')
	url=str_replace(url, "\\.\\.\\/", '')

	 cat(comp_title, "\t\t", url, "\n", sep="", file=infoFile, append=T)
}