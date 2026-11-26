<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace mod_forum\util;

use PHPUnit\Framework\Attributes\CoversClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/forum/lib.php');

/**
 * Tests for the forum read tracking helpers.
 *
 * @package    mod_forum
 * @category   test
 * @copyright  2026 Huong Nguyen <huongn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(read_tracking::class)]
final class read_tracking_test extends \advanced_testcase {
    /**
     * Create a forum with a discussion and a reply, plus a second discussion, all authored by another user.
     *
     * @param int $trackforums User trackforums preference.
     * @return array [user, discussion, other discussion, post ids of the first discussion]
     */
    private function setup_discussions(int $trackforums = 1): array {
        global $CFG;
        $CFG->forum_trackreadposts = true;

        $author = self::getDataGenerator()->create_user();
        $user = self::getDataGenerator()->create_user(['trackforums' => $trackforums]);
        $course = self::getDataGenerator()->create_course();
        self::getDataGenerator()->enrol_user($author->id, $course->id);
        self::getDataGenerator()->enrol_user($user->id, $course->id);
        $forum = self::getDataGenerator()->create_module('forum', [
            'course' => $course->id,
            'trackingtype' => FORUM_TRACKING_FORCED,
        ]);
        $generator = self::getDataGenerator()->get_plugin_generator('mod_forum');
        $discussion = $generator->create_discussion([
            'course' => $course->id,
            'userid' => $author->id,
            'forum' => $forum->id,
        ]);
        $reply = $generator->create_post([
            'discussion' => $discussion->id,
            'parent' => $discussion->firstpost,
            'userid' => $author->id,
        ]);
        $other = $generator->create_discussion([
            'course' => $course->id,
            'userid' => $author->id,
            'forum' => $forum->id,
        ]);

        return [$user, $discussion, $other, [$discussion->firstpost, $reply->id]];
    }

    /**
     * Count the posts, out of those given, that the user has read.
     *
     * @param int $userid
     * @param array $postids
     * @return int
     */
    private function count_read(int $userid, array $postids): int {
        global $DB;
        [$insql, $params] = $DB->get_in_or_equal($postids, SQL_PARAMS_NAMED);
        $params['userid'] = $userid;
        return $DB->count_records_select('forum_read', "postid $insql AND userid = :userid", $params);
    }

    /**
     * Marking posts as read only affects the given posts.
     */
    public function test_mark_posts_read(): void {
        $this->resetAfterTest();
        [$user, $discussion, $other, $postids] = $this->setup_discussions();

        $this->assertTrue(read_tracking::mark_posts_read($user, [$discussion->firstpost]));
        $this->assertEquals(1, $this->count_read($user->id, $postids));
        $this->assertEquals(0, $this->count_read($user->id, [$other->firstpost]));
    }

    /**
     * An empty list is a successful no-op.
     */
    public function test_mark_posts_read_empty(): void {
        $this->resetAfterTest();
        [$user] = $this->setup_discussions();

        $this->assertTrue(read_tracking::mark_posts_read($user, []));
    }

    /**
     * Users who do not track forums have nothing marked.
     */
    public function test_mark_posts_read_not_tracking(): void {
        global $CFG;
        $this->resetAfterTest();
        [$user, , , $postids] = $this->setup_discussions(0);
        $CFG->forum_allowforcedreadtracking = false;

        $this->assertTrue(read_tracking::mark_posts_read($user, $postids));
        $this->assertEquals(0, $this->count_read($user->id, $postids));
    }

    /**
     * Marking a discussion as read marks all its posts, and only those, and is idempotent.
     */
    public function test_mark_discussion_read(): void {
        $this->resetAfterTest();
        [$user, $discussion, $other, $postids] = $this->setup_discussions();

        $this->assertTrue(read_tracking::mark_discussion_read($user, $discussion->id));
        $this->assertEquals(2, $this->count_read($user->id, $postids));
        $this->assertEquals(0, $this->count_read($user->id, [$other->firstpost]));

        $this->assertTrue(read_tracking::mark_discussion_read($user, $discussion->id));
        $this->assertEquals(2, $this->count_read($user->id, $postids));
    }

    /**
     * The deprecated lib.php functions still work, and emit a deprecation.
     */
    public function test_deprecated_functions(): void {
        $this->resetAfterTest();
        [$user, $discussion, , $postids] = $this->setup_discussions();

        $this->assertTrue(forum_tp_mark_discussion_read($user, $discussion->id));
        $this->assertDebuggingCalled();
        $this->assertEquals(2, $this->count_read($user->id, $postids));

        $this->assertTrue(forum_tp_mark_posts_read($user, $postids));
        $this->assertDebuggingCalled();
    }
}
