PHP CSS Parser
--------------

Import procedure:
```
tempdir=`mktemp -d`
cd $tempdir
composer require sabberworm/php-css-parser
cd -
cp public/lib/php-css-parser/readme_moodle* $tempdir
rm -rf public/lib/php-css-parser
cp -rf $tempdir/vendor/sabberworm/php-css-parser public/lib/php-css-parser
cp -rf $tempdir/readme* public/lib/php-css-parser
```

Since 9.1.0, this library depends on thecodingmachine/safe, which is also
vendored separately in public/lib/safe - see its own readme_moodle.txt for
its import procedure.

No Moodle-specific patches are currently applied. The previous patch (commenting
out a trailing consumeWhiteSpace() call in Rule::parse(), for
https://github.com/sabberworm/PHP-CSS-Parser/issues/173) is no longer needed as
of 9.0.0: upstream rewrote comment parsing and the underlying bug (trailing
comments being swallowed) is now fixed natively.
