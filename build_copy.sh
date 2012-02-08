#!/bin/sh

mv default.config.php config.php
chmod 777 config.php var modules
mv html/index_page_default.html html/index.html
mv html/404_default.html html/404.html
cp public_html/worker.php public_html/index.php

