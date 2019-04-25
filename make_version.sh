set -e
composer install --no-dev
php -d phar.readonly=0 build_phar.php
mkdir -p bin
mv php2js.phar bin/php2js
git add bin/php2js
git commit bin/php2js -m "Created binary for version $1."
git tag $1
