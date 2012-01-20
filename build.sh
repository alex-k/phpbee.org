#!/bin/sh

mkdir build
cd build
svn export svn+ssh://svn@ytech.ru/home/svn/gs_blank_site . --force
mv default.config.php config.php
chmod 777 config.php var
mv html/index_page_default.html html/index.html
php phar.php
find . -name public_html -mindepth 2 -exec sh -c "L=\`dirname {}\`; mkdir -p public_html/\$L; cp -r {}/* public_html/\$L ; " \;
rm -fr libs
cp public_html/worker.php public_html/index.php
zip -r phpbee.zip config.php gs_libs.phar.gz html modules packages public_html 
zip phpbee.zip var
mv phpbee.zip ..
cd ..
rm -fr build

cp phpbee.zip public_html/download/phpbee-`date "+%d%b%y"`.zip
