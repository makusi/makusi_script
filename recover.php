#!/bin/bash
#@author        xare <xare@eguzkibideoak.info>
#@website       http://www.makusi.tv
#@blog          http://www.makusi.tv
#@version       0.1.0

#/************************ EDIT VARIABLES ************************/
projectName=makusi
backupDir=/home/xaresd/backups
mysqlBackupDir=$backupDir/mysqldump
webDir=/home/virtualmin/makusi.tv/public_html
#/************************ //EDIT VARIABLES **********************/

cd /home/xaresd/backups
tar -vxf $filename.tar $webdir
gzip $filename
mysql -u $username -p $password < filename.sql.gz
