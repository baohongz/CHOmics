#works in DEG folder
#one folder up should have rpkm_annot.txt, pheno.txt and comparison.txt
options(stringsAsFactors=F)
suppressPackageStartupMessages(library(limma))
suppressPackageStartupMessages(library(edgeR))
suppressPackageStartupMessages(library(stringr))
suppressPackageStartupMessages(library(made4))
suppressPackageStartupMessages(library(genefilter))

system('find -name "*vs*alldata.csv" | sort >DEG_alldata.list')
file='../rpkm_annot.csv'
FPKM=read.csv(file, row.names=1)
FileList=read.table('DEG_alldata.list', header=F)
for (i in 1:nrow(FileList) ) {
	file=FileList[i,1]
	name=str_extract(file, "Overview/.+_alldata.csv") #get comparion name
	name=str_replace_all(name, "Overview/|_alldata.csv", "")
	data=read.csv(file, row.names=1)
	sel=match("logFC", colnames(data))
	subdata=data[, sel:(sel+2)] #onyl select logFC, P-value and FDR columns
            #subdata=data[, c(sel, (sel+2), (sel+3) )] #edgeR
	colnames(subdata)=paste(name, colnames(subdata), sep="_") #add name to column headers
	FPKM=cbind(FPKM, subdata[rownames(FPKM),]) #add to FPKM table
}
#col.order(FPKM) #confirm all correctly combined
FPKM[is.na(FPKM)]=''
write.csv(FPKM, "FPKM_all_comparions.csv")

##Use scripts to process
#got the pheno and comparison tables
phenos=read.table("../pheno.txt", sep="\t", header=T)
comparisons=read.table("../comparison.txt", sep="\t", header=F)
#update sample names
phenos$Sample=make.names(phenos$Sample)
phenos=phenos[order(phenos$Sample), ]

#Get data ready for genomicDB
#select rows to turn into data.
sel1=match(phenos$Sample, colnames(FPKM))
if ( sum(is.na(sel1)) >0 ) {cat('Some samples not found in FPKM file!\n', sel1, '\n')}
sel2=which(grepl('\\.vs\\.', colnames(FPKM)) ) #comparison rows
data=FPKM[, c(sel1, sel2)]
setdiff(colnames(FPKM), colnames(data)) #these should all be annotations
ID=rownames(data)
data=cbind(ID, data)

file="/public/scripts/R/template_files/Sample_info_template_0526_2016.csv"
sinfot=read.csv(file)

sinfo=as.data.frame(matrix(, nrow=ncol(data), ncol=ncol(sinfot)))
sinfo[is.na(sinfo)]="" #not checkd first time
colnames(sinfo)=colnames(sinfot)
list=colnames(data)
sinfo[, 1:2]=list
sinfo[1, 3]="Identifier"
#identify first logFC
pos1=which(grepl("_logFC", list))[1]
sinfo[2:(pos1-1), 3]="Data"
sinfo[pos1:length(list), 3]="Statistical Value"
#for auto turn off 4) Statistical.Category
#sinfo[grepl("_logFC", list), 4]="logFC"
#sinfo[grepl("_P.Value", list), 4]="P-value"
#sinfo[grepl("_adj.P.Val", list), 4]="FDR"
# 10)Comparison.Table.Headers
sinfo[grepl("_logFC", list), 10]="logFC"
sinfo[grepl("_P.Value", list), 10]="P-value"
sinfo[grepl("_adj.P.Val", list), 10]="FDR"

table(sinfo[,3]) #check to confirm
table(sinfo[,10]) #check to confirm

#now work on comparisons
#user can assign NickNames to phenotypes by adding "Nickname" row to pheno file
#if not, add Letters as Nicknames
Phenotype=phenos$Phenotype
if (sum(grepl('Nickname', colnames(phenos) ) )>0)
{cat('Using user provided Nicknames.\n')
} else {
	Uph=sort(unique(Phenotype))
	LL=toupper(letters[1:length(Uph)])
	phenos$Nickname=LL[match(Phenotype,Uph)]
}

#put phenotype to Experiment
sel=match(phenos$Sample, list)
if ( sum(is.na(sel1)) >0 ) {cat('Some samples not found in list for sampleInfo!\n', sel1, '\n')}
#sinfo[sel, 6]='Ab Production 10E9'
sinfo[sel, 6]=Phenotype
#add nickname
sinfo[sel, 11]=phenos$Nickname

#now get comparison nickname and order. (Order will be those in file)
C1=LL[match(comparisons$V1, Uph)]
C2=LL[match(comparisons$V2, Uph)]
comparisons$Order=rownames(comparisons)
comparisons$Nickname=str_c(C1, '-', C2)
comparisons$Longname=str_c(comparisons$V1, '.vs.', comparisons$V2)

sel2=which(grepl('.vs.', list))
s_comp=str_replace(list[sel2], '_logFC', '')
s_comp=str_replace(s_comp, '_adj.P.Val', '')
s_comp=str_replace(s_comp, '_P.Value', '')

sel3=match(s_comp, comparisons$Longname)
if ( sum(is.na(sel3))>0) {cat("Some comparisons not found!\n")}
sinfo[sel2, 11]=comparisons$Nickname[sel3]
sinfo[sel2, 12]=comparisons$Order[sel3]

#output
header=colnames(sinfo)
header=str_replace_all(header, "\\.", " ")
header[3]='Identifier/Data/Statistical Value'
header[11]='Nickname for Samples/Comparisons'
sinfo=rbind(header, sinfo)
write.csv(sinfo, "Auto_sample_info.csv", row.names=F)
write.table(data[, ],  "FPKM4genomicDB_auto.txt", sep="\t", row.names=F,quote=F)

#################################
