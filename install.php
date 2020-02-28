<?php

$BXAF_CONFIG_CUSTOM['PAGE_LOGIN_REQUIRED']	= false;
include_once(__DIR__ . "/bxaf_lite/config.php");

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<?php include_once($BXAF_CONFIG['BXAF_PAGE_HEADER']); ?>
</head>
<body>
	<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_MENU'])) include_once($BXAF_CONFIG['BXAF_PAGE_MENU']); ?>
	<div id="bxaf_page_wrapper" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_WRAPPER']; ?>">
		<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_LEFT'])) include_once($BXAF_CONFIG['BXAF_PAGE_LEFT']); ?>
		<div id="bxaf_page_right" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT']; ?>">
			<div id="bxaf_page_right_content" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT_CONTENT']; ?>">




<div class="container-fluid">
	<div class="row">

		<h2 class="w-100 my-3">
			CHO Genomics System Installation Guide
		</h2>
		<p class="w-100 mb-5 lead text-success">We assume you will install most bioinformatics tools in folder /public/programs/. Also, we assume you will install the system in folder /var/www/html/chomics/ and your system will have URL like this: <span class="text-danger">http:/my.domain.name/chomics/</span>. If your installation is different, please update your system settings accordingly.</p>


		<h4 class="w-100 my-1">Step 1. Prepare Operating System</h4>
		<p class="w-100 my-3">The system is tested and developed on CentOS, 64bit, v7. Other Linux system may also work. After installing CentOS (minimal installation), please make sure to install "Development Tools" and some basic commands. You also need to install AMP (Apache, Mariadb, and PHP) and configure them correctly. We assume your web is in folder <span class="text-danger">/var/www/html/</span> and apache user/group are <span class="text-danger">apache/apache</span>. In addition, you may need to turn off firewall and SELINUX. Please visit your website first to verify. Here are the suggested commands:</p>

<pre class="text-danger">

# Install development tools
sudo yum -y install wget unzip bzip2
sudo yum -y group install "Development Tools"
sudo yum -y install man-pages man-db man
# Or, try with this command: sudo yum groupinstall "Development Tools"



# Stop firewalld
systemctl stop firewalld

# Disable firewalld
systemctl disable firewalld

# Status of firewalld
systemctl status firewalld


# temporarily disable SELinux
sudo setenforce 0

# Permanently disable SELinux, edit file and change to "SELINUX=disabled"
sudo vi /etc/selinux/config

# Save the file and reboot your CentOS system
sudo shutdown -r now

sudo sestatus



# Install MariaDB
sudo yum -y install mariadb-server

# Allow remote access, only necessary if filewall is running
firewall-cmd --zone=public --add-service=mysql --permanent

# Set the root password
/usr/bin/mysql_secure_installation

# Make sure to launch mariadb at reboot
sudo systemctl enable mariadb.service

# Start and stop the database service
sudo systemctl start mariadb.service


# Installing Apache
sudo yum -y install httpd
sudo systemctl start httpd.service
sudo systemctl enable httpd.service

# If you still have filewall running
sudo firewall-cmd --permanent --zone=public --add-service=http
sudo firewall-cmd --permanent --zone=public --add-service=https
sudo firewall-cmd --reload

# Install PHP
sudo yum-config-manager --enable remi-php72
sudo yum -y install php php-opcache

# Getting MySQL Support In PHP
sudo yum -y install php-mysqlnd php-pdo
sudo yum -y install php-gd php-ldap php-odbc php-pear php-xml php-xmlrpc php-mbstring php-soap curl curl-devel

# Install phpMyAdmin
sudo yum -y install phpMyAdmin

# Update phpMyAdmin settings, if necessary
sudo vi /etc/httpd/conf.d/phpMyAdmin.conf

# Update apache settings, if necessary
sudo vi /etc/httpd/conf/httpd.conf

# Restart Apache Server
sudo systemctl restart httpd.service


</pre>



		<h4 class="w-100 my-1">Step 2. Install Bioinformatics Tools</h4>

		<ol>
			<li class="my-3"><strong>fastqc</strong>
				<ol>
					<li>Project website: <a href="http://www.bioinformatics.babraham.ac.uk/projects/fastqc/" target="_blank">http://www.bioinformatics.babraham.ac.uk/projects/fastqc</a></li>
					<li>Download and Install:

<pre class="text-danger">

wget <a href="http://www.bioinformatics.babraham.ac.uk/projects/fastqc/fastqc_v0.11.5.zip">http://www.bioinformatics.babraham.ac.uk/projects/fastqc/fastqc_v0.11.5.zip</a>
unzip fastqc_v0.11.5.zip
mv FastQC <strong>/public/programs/fastqc/fastqc_v0.11.5</strong>
ln -s <strong>/public/programs/fastqc/fastqc_v0.11.5</strong> <strong>/public/programs/fastqc/latest</strong>
chmod 775 -R <strong>/public/programs/fastqc/*</strong>

</pre>
					</li>
				</ol>
			</li>
			<li><strong>samtools and tabix</strong>
				<ol>
					<li>Project website: http://www.htslib.org/</li>
					<li>Download and Install:

<pre class="text-danger">

wget  https://github.com/samtools/samtools/releases/download/1.8/samtools-1.8.tar.bz2
wget https://github.com/samtools/bcftools/releases/download/1.8/bcftools-1.8.tar.bz2
wget https://github.com/samtools/htslib/releases/download/1.8/htslib-1.8.tar.bz2

bzip2 -d bcftools-1.8.tar.bz2
bzip2 -d htslib-1.8.tar.bz2
bzip2 -d samtools-1.8.tar.bz2

tar xf bcftools-1.8.tar
tar xf htslib-1.8.tar
tar xf samtools-1.8.tar

cd samtools-1.8/
./configure --prefix=/usr/local
make
sudo make install

cd ../htslib-1.8/
./configure --prefix=/usr/local
make
sudo make install

cd ../bcftools-1.8/
./configure --prefix=/usr/local
make
sudo make install

</pre>
					</li>
				</ol>
			</li>


			<li class="my-3"><strong>subread (subjunc and featureCounts)</strong>
				<ol>
					<li>Project website: http://subread.sourceforge.net/</li>
					<li>Download and install:

<pre class="text-danger">

wget http://downloads.sourceforge.net/project/subread/subread-1.5.0-p1/subread-1.5.0-p1-Linux-x86_64.tar.gz
sudo mkdir <strong>/public/programs/subread</strong>
sudo mv subread-1.5.0-p1-Linux-x86_64.tar.gz /public/programs/subread
cd /public/programs/subread
sudo tar zxvf subread-1.5.0-p1-Linux-x86_64.tar.gz subread-1.5.0-p1-Linux-x86_64
sudo mkdir latest
sudo cp -r subread-1.5.0-p1-Linux-x86_64/* latest/

</pre>

					</li>
				</ol>
			</li>
			<li class="my-3"><strong>gsea</strong>
				<ol>
					<li>Website: https://github.com/erickramer/GSEAR (free) or http://software.broadinstitute.org/gsea/downloads.jsp (newest)</li>
					<li>Download and Install:

<pre class="text-danger">

wget https://github.com/erickramer/GSEAR/archive/master.zip
unzip master.zip
mv GSEAR-master/src/gsea <strong>/public/programs/gsea</strong>

</pre>

					</li>
				</ol>
			</li>

			<li class="my-3"><strong>Homer</strong>

<pre class="text-danger">

cd /public/programs/
mkdir homer
cd homer
wget http://homer.salk.edu/homer/configureHomer.pl

perl ./configureHomer.pl -install
perl ./configureHomer.pl -install mm10

perl ./configureHomer.pl -list
perl ./configureHomer.pl -update
chmod -R 775 *

</pre>

			</li>
			<li class="my-3">
				<strong>Install R Packages</strong><BR /><BR />

<pre class="text-danger">

sudo R
source("http://bioconductor.org/biocLite.R");
biocLite( c("Rsubread","limma","edgeR","PGSEA","made4","XML","annotate","genefilter","openssl", "httr","stringr","reshpage","piano","optparse","pathview","data.table","impute", "combinat", "explor","missMDA","KEGGgraph","KEGGREST","pathview","FactoMineR","org.Mm.eg.db"));

q();

wget https://cran.r-project.org/src/contrib/Archive/MetaDE/MetaDE_1.0.5.tar.gz
sudo R CMD INSTALL MetaDE_1.0.5.tar.gz

sudo R

#remove old version first,
remove.packages("BiocInstaller") ;
#RankProd requires latest bioconductor installer
source("https://bioconductor.org/biocLite.R"); #if error occurs, re-start R
biocLite("RankProd");
#make sure the version installed is RankProd_3.4.0 or later

library("Rsubread");
library("limma");
library("edgeR");
library("PGSEA");
library("made4");
library("stringr");
library("piano");
library("optparse");
library("data.table");
library("explor");
library("missMDA");
library("GO.db");
library("AnnotationDbi");
library("stats4");
library("BiocGenerics");
library("parallel");
library("BiocGenerics");
library("Biobase");
library("S4Vectors");
library("KEGG.db");
library("annaffy");
library("ade4");
library("RColorBrewer");
library("gplots");
library("scatterplot3d");
library("genefilter");
library("pathview");
library("help=pathview");
library("MetaDE");
library("FactoMineR");

</pre>

			</li>
		</ol>






		<h4 class="w-100 my-1">Step 3. Install CHO Genomics Tools</h4>

		<ol>
			<li class="my-3"><strong>Download and install CHO Genomics package</strong>

				<p class="w-100 my-3">Please download the system package (file name: <strong>chomics.tar.gz</strong>) from the URL we send to you in the e-mail. Then use the following commands to install:</p>

<pre class="text-danger">
tar xzvf chomics.tar.gz
mv chomics <strong>/var/www/html/.</strong>
</pre>

			</li>


			<li class="my-3"><strong>Set up database and load contents</strong>

				<p class="w-100 my-3">The initial database contents are in file <strong>/var/www/html/chomics/bxaf_setup/config.sql.gz</strong> </p>

<pre class="text-danger">

# Start the MariaDB shell
/usr/bin/mysql -u root -p

# Create database and set up user permissions
CREATE USER 'chomics'@'localhost' IDENTIFIED BY 'CHOMICS@2020';
CREATE DATABASE IF NOT EXISTS `chomics`;
GRANT ALL PRIVILEGES ON `chomics`.* TO 'chomics'@'localhost';
FLUSH PRIVILEGES;

zcat <strong>/var/www/html/chomics/bxaf_setup/config.sql.gz</strong> | /usr/bin/mysql -u root -p chomics

</pre>

			</li>


			<li class="my-3"><strong>Download CHO genome information package</strong>

				<p class="w-100 my-3">Notice that, we have a symbolic link in folder <strong>/var/www/html/chomics/app_data/files_core/PICR</strong>. This folder container CHO genome information, which is over 5GB. If you don't have this package, please delete this symbolic link, download the compressed package, uncompress and move it in this folder.</p>

<pre class="text-danger">
rm -rf <strong>/var/www/html/chomics/app_data/files_core/gsea</strong>
wget <a target="_blank" href="http://chomics.com/gsea.tar.gz">http://chomics.com/gsea.tar.gz</a>
tar xzvf gsea.tar.gz
mv gsea <strong>/var/www/html/chomics/app_data/files_core/.</strong>

rm -rf <strong>/var/www/html/chomics/app_data/files_core/PICR</strong>
wget <a target="_blank" href="http://chomics.com/PICR.tar.gz">http://chomics.com/PICR.tar.gz</a>
tar xzvf PICR.tar.gz
mv PICR <strong>/var/www/html/chomics/app_data/files_core/.</strong>

</pre>

			</li>


			<li class="my-3"><strong>Update config file (<strong>/var/www/html/chomics/bxaf_setup/config.sql</strong>)</strong>

				<p class="w-100 my-3">Please review and update <strong>/var/www/html/chomics/bxaf_setup/config.sql</strong> according to your server settings. Also, update file ownership and permissions.</p>

<pre class="text-danger">
sudo chown <strong>apache:apache</strong> -R <strong>/var/www/html/chomics/</strong>
sudo chmod 775 -R <strong>/var/www/html/chomics/</strong>
</pre>


			</li>


			<li class="my-3"><strong>Test your package</strong>

				<p class="w-100 my-3">Please open a browser (Firefox) and visit <span class="text-danger">http:/my.domain.name/chomics/</span> to test the system.</p>

			</li>

		</ol>



	</div>

</div>






            </div>
		    <?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>
		</div>
	</div>
</body>
</html>