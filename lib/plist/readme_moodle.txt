CFPropertyList library
---------------

Downloaded last release from: https://github.com/TECLIB/CFPropertyList/tags

Import procedure:

- Copy all the files from the CFPropertyList-XXX/src/CFPropertyList folder into lib/plist/classes/CFPropertyList.
- Copy all the .md files from CFPropertyList-XXX to lib/plist/.

Removed:
 * .gitignore
 * composer.json
 * build.sh
 * examples
 * tests

Added:
 * readme_moodle.txt

Local modifications:
- lib/plist/classes/CFPropertyList/CFBinaryPropertyList.php has been minimally modified for php82 compatibility
  The fix applied is already upstream, see https://github.com/TECLIB/CFPropertyList/pull/73.
  See MDL-76410 for more details.
