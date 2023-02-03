PHP CSS Parser
--------------

Import procedure:
1. Download the latest release from https://github.com/sabberworm/PHP-CSS-Parser/releases
2. Copy all the files from the folder 'lib/Sabberworm/CSS/' in this directory.
3. Apply the following patches from the following pull requests if they have not yet been merged upstream:
   a. https://github.com/sabberworm/PHP-CSS-Parser/pull/115
   b. https://github.com/sabberworm/PHP-CSS-Parser/pull/173

Local modifications:
- lib/php-css-parser/Parsing/ParserState.php has been modified for php82 compatibility
  The fix applied is already upstream, see https://github.com/sabberworm/PHP-CSS-Parser/pull/409.
  See MDL-76410 for more details.
