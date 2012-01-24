#!/bin/sh
REPO="file:///home/pahomov/www/phpbee.org/svn/phpbee.org"
LASTFNAME="public_html/download/`ls -tr public_html/download/ | tail -n1`";
MTIME=`stat -n -f '%c' $LASTFNAME`
LASTDATE=`date -r $MTIME "+%Y-%m-%d"  `

FNAME="phpbee-`date "+%d%b%y"`.zip"
echo $FNAME


mkdir build
cd build


PHPUNIT=`cd tests && phpunit run.php`
if [ "$?" -ne "0" ]
then	
	echo $PHPUNIT | mail -s 'phpbee build failed' alex@kochetov.com
	echo $PHPUNIT
	exit 1;
fi


svn log -r '{'$LASTDATE'}':'{'`date -v +1d "+%Y-%m-%d"`'}' --xml --verbose $REPO > Changelog.xml
xsltproc ../svn2cl.xsl Changelog.xml  > Changelog.txt
xsltproc ../svn2html.xsl Changelog.xml  > Changelog.html
svn export $REPO . --force
mv default.config.php config.php
chmod 777 config.php var
mv html/index_page_default.html html/index.html
php phar.php
find . -name public_html -mindepth 2 -exec sh -c "L=\`dirname {}\`; mkdir -p public_html/\$L; cp -r {}/* public_html/\$L ; " \;
rm -fr libs
cp public_html/worker.php public_html/index.php
zip -r phpbee.zip config.php gs_libs.phar.gz html modules packages public_html Changelog.txt
zip phpbee.zip var
mv phpbee.zip ..
cd ..
cp build/Changelog.html public_html/download/Changelog-$FNAME.html
rm -fr build


cp phpbee.zip public_html/download/$FNAME
echo $FNAME > html/last_build.html
