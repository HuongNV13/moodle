thecodingmachine/safe
----------------------

Import procedure:
```
tempdir=`mktemp -d`
cd $tempdir
composer require thecodingmachine/safe
cd -
cp public/lib/thecodingmachine/safe/readme_moodle* $tempdir
rm -rf public/lib/thecodingmachine/safe
cp -rf $tempdir/vendor/thecodingmachine/safe public/lib/thecodingmachine/safe
cp -rf $tempdir/readme* public/lib/thecodingmachine/safe
rm -f public/lib/thecodingmachine/safe/rector-migrate.php
```

This library is a required dependency of php-css-parser (see
public/lib/php-css-parser/readme_moodle.txt) since php-css-parser 9.1.0.
It is not used directly by Moodle core.

Its classes (Safe\Exceptions\*, Safe\DateTime, Safe\DateTimeImmutable) are
autoloaded via the \Safe::class entry in
public/lib/classes/component.php::$psr4namespaces.

Unlike most vendored libraries, this one defines plain namespaced functions
(e.g. Safe\preg_match()) rather than classes, which PHP cannot autoload.
Only the function files actually used by php-css-parser
(generated/classobj.php, generated/iconv.php, generated/pcre.php,
lib/special_cases.php) are eagerly required on every request, via
public/lib/classes/component.php::$composerautoloadfiles - mirroring what
composer's own "files" autoloader would do for this package's composer.json.
If a future consumer of this library needs functions from other files
(see this package's own composer.json "autoload"/"files" section for the
full list, e.g. preg_replace() lives in lib/special_cases.php, not
generated/pcre.php), add the corresponding file to that list.
