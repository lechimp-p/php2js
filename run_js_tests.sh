set -e
php php2js.php compile --psr4=Lechimp\\PHP_JS\\JS_Tests\\:$(pwd)/js_tests js_tests/main.php > js_tests.js
nodejs --harmony_array_includes js_tests.js
