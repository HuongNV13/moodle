RTLCSS
------

Downloaded from: https://github.com/moodlehq/rtlcss-php

Import procedure:

- Copy all the files from the folder 'src/MoodleHQ/RTLCSS/' in this directory.
- Copy the license file.
- Review the local changes defined below, if any. Reapply
  them if needed. If already available upstream, please remove
  them from the list.

Local changes:

- Updated RTLCSS.php to use PHP-CSS-Parser 9.x's Declaration/DeclarationList
  API (Sabberworm\CSS\Property\Declaration, Sabberworm\CSS\RuleSet\DeclarationList,
  getPropertyName()/setPropertyName()) in place of the removed Rule/RuleSet
  classes and getRule()/setRule() methods, and render CSS value objects to
  string explicitly before pattern matching, since they no longer implement
  __toString(). These changes have not yet been submitted upstream.
