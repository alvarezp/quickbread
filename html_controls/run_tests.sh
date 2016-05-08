#!/bin/sh

MODULES="html_element.php html_text.php html_link.php html_list.php html_table.php html_page.php qb_page.php"

if [ "$1" != "" ]; then
	MODULES="$1"
fi

for F in $MODULES; do
    echo ==== Testing "$F" ==== 
    php -f $F
done
