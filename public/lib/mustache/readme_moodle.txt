Description of Mustache library import into moodle.

1) Download the latest version of mustache.php from upstream (found
at https://github.com/bobthecow/mustache.php/releases)

2) Move the src/ and LICENSE file into lib/mustache

e.g.
wget https://github.com/bobthecow/mustache.php/archive/v2.13.0.zip
unzip v2.13.0.zip
cd mustache.php-2.13.0/
mv src /path/to/moodle/lib/mustache/
mv LICENSE /path/to/moodle/lib/mustache/

Changes by Huong Nguyen (MDL-89584)
* Fixed an upstream bug in the 3.2.0 static partial-cache optimisation:
  Compiler::block() (used for template inheritance block overrides) did
  not open its own partial-cache scope like Compiler::section() does, so
  a {{>partial}} used directly inside a block override nested inside an
  active section (or another block) left its "$partial<hash>" cache
  variable undefined at render time, silently dropping the partial's
  output.
* Fixed in src/Compiler.php: block() by giving it its own partial-cache
  scope, matching Compiler::section().
* Reported and fixed upstream at
  https://github.com/bobthecow/mustache.php/pull/439 - verify this is
  merged before reapplying on the next library upgrade.
