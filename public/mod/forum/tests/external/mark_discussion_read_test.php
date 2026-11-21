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

namespace mod_forum\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/forum/lib.php');

use core_external\external_api;

/**
 * Tests for the mark_discussion_read external function.
 *
 * @package    mod_forum
 * @category   test
 * @copyright  2026 Phung Vo Anh <phung.voanh@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(mark_discussion_read::class)]
final class mark_discussion_read_test extends \core_external\tests\externallib_testcase {
    /**
     * Create a course, a forum and a discussion with one reply, authored by another user.
     *
     * @param int $trackingtype Forum tracking type.
     * @param int $trackforums User trackforums preference (1 = enabled, 0 = disabled).
     * @return array [user, discussion, forum, post ids]
     */
    private function setup_discussion(
        int $trackingtype = FORUM_TRACKING_FORCED,
        int $trackforums = 1,
    ): array {
        global $CFG;
        $CFG->forum_trackreadposts = true;

        $author = self::getDataGenerator()->create_user();
        $user = self::getDataGenerator()->create_user(['trackforums' => $trackforums]);
        $course = self::getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($author->id, $course->id);
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $forum = self::getDataGenerator()->create_module('forum', (object) [
            'course' => $course->id,
            'trackingtype' => $trackingtype,
        ]);
        $generator = self::getDataGenerator()->get_plugin_generator('mod_forum');
        $discussion = $generator->create_discussion((object) [
            'course' => $course->id,
            'userid' => $author->id,
            'forum' => $forum->id,
        ]);
        $reply = $generator->create_post((object) [
            'discussion' => $discussion->id,
            'parent' => $discussion->firstpost,
            'userid' => $author->id,
        ]);
        self::setUser($user);
        return [$user, $discussion, $forum, [$discussion->firstpost, $reply->id]];
    }

    /**
     * Count posts of the user that are read.
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
     * All unread posts in the target discussion are marked, including when manual marking is enabled,
     * while posts in other discussions remain unread.
     *
     * @param bool $manual Value for forum_usermarksread.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('manual_mode_provider')]
    public function test_mark_discussion_read(bool $manual): void {
        global $CFG;
        $this->resetAfterTest();
        $CFG->forum_usermarksread = $manual;
        [$user, $discussion, $forum, $postids] = $this->setup_discussion();

        // Create a second discussion in the same forum to verify data isolation.
        $generator = self::getDataGenerator()->get_plugin_generator('mod_forum');
        $otherauthor = self::getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($otherauthor->id, $forum->course);
        $otherdiscussion = $generator->create_discussion((object) [
            'course' => $forum->course,
            'userid' => $otherauthor->id,
            'forum' => $forum->id,
        ]);

        $this->assertEquals(0, $this->count_read($user->id, $postids));
        $this->assertEquals(0, $this->count_read($user->id, [$otherdiscussion->firstpost]));

        $result = mark_discussion_read::execute($discussion->id);
        $result = external_api::clean_returnvalue(mark_discussion_read::execute_returns(), $result);

        $this->assertTrue($result['status']);
        $this->assertEmpty($result['warnings']);
        $this->assertEquals(2, $this->count_read($user->id, $postids));
        $this->assertEquals(0, $this->count_read($user->id, [$otherdiscussion->firstpost]));

        // Calling again when all posts are already read remains idempotent and returns status true.
        $secondresult = mark_discussion_read::execute($discussion->id);
        $secondresult = external_api::clean_returnvalue(mark_discussion_read::execute_returns(), $secondresult);
        $this->assertTrue($secondresult['status']);
        $this->assertEmpty($secondresult['warnings']);
        $this->assertEquals(2, $this->count_read($user->id, $postids));
    }

    /**
     * Provider for manual marking mode.
     *
     * @return array
     */
    public static function manual_mode_provider(): array {
        return ['automatic marking' => [false], 'manual marking' => [true]];
    }

    /**
     * Forum without tracking returns a warning and marks nothing.
     */
    public function test_mark_discussion_read_not_tracked(): void {
        $this->resetAfterTest();
        [$user, $discussion, , $postids] = $this->setup_discussion(FORUM_TRACKING_OFF);

        $result = mark_discussion_read::execute($discussion->id);
        $result = external_api::clean_returnvalue(mark_discussion_read::execute_returns(), $result);

        $this->assertFalse($result['status']);
        $this->assertCount(1, $result['warnings']);
        $this->assertEquals('discussion', $result['warnings'][0]['item']);
        $this->assertEquals($discussion->id, $result['warnings'][0]['itemid']);
        $this->assertEquals('forumnottracked', $result['warnings'][0]['warningcode']);
        $this->assertEquals(0, $this->count_read($user->id, $postids));
    }

    /**
     * User who opted out of read tracking in an optional-tracking forum receives a warning and marks nothing.
     */
    public function test_mark_discussion_read_user_not_tracking(): void {
        $this->resetAfterTest();
        [$user, $discussion, , $postids] = $this->setup_discussion(FORUM_TRACKING_OPTIONAL, 0);

        $result = mark_discussion_read::execute($discussion->id);
        $result = external_api::clean_returnvalue(mark_discussion_read::execute_returns(), $result);

        $this->assertFalse($result['status']);
        $this->assertCount(1, $result['warnings']);
        $this->assertEquals('discussion', $result['warnings'][0]['item']);
        $this->assertEquals($discussion->id, $result['warnings'][0]['itemid']);
        $this->assertEquals('forumnottracked', $result['warnings'][0]['warningcode']);
        $this->assertEquals(0, $this->count_read($user->id, $postids));
    }

    /**
     * Non-existent discussion.
     */
    public function test_mark_discussion_read_invalid_discussion(): void {
        $this->resetAfterTest();
        $this->setup_discussion();
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('invaliddiscussionid', 'forum'));
        mark_discussion_read::execute(999999);
    }

    /**
     * Users who cannot access the course cannot mark its discussions.
     */
    public function test_mark_discussion_read_no_access(): void {
        $this->resetAfterTest();
        [, $discussion] = $this->setup_discussion();
        $other = self::getDataGenerator()->create_user();
        self::setUser($other);
        $this->expectException(\require_login_exception::class);
        mark_discussion_read::execute($discussion->id);
    }

    /**
     * Enrolled users without mod/forum:viewdiscussion cannot mark a discussion as read.
     */
    public function test_mark_discussion_read_no_view_permission(): void {
        global $DB;
        $this->resetAfterTest();
        [, $discussion, $forum] = $this->setup_discussion();

        $cm = get_coursemodule_from_instance('forum', $forum->id, $forum->course, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        $studentrole = $DB->get_record('role', ['shortname' => 'student'], '*', MUST_EXIST);
        assign_capability('mod/forum:viewdiscussion', CAP_PROHIBIT, $studentrole->id, $context->id);

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('noviewdiscussionspermission', 'forum'));
        mark_discussion_read::execute($discussion->id);
    }

    /**
     * Users cannot mark a discussion belonging to another group in separate groups mode.
     */
    public function test_mark_discussion_read_separate_groups(): void {
        global $CFG;
        $this->resetAfterTest();
        $CFG->forum_trackreadposts = true;

        $author = self::getDataGenerator()->create_user();
        $user = self::getDataGenerator()->create_user(['trackforums' => 1]);
        $course = self::getDataGenerator()->create_course(['groupmode' => SEPARATEGROUPS, 'groupmodeforce' => 1]);
        $this->getDataGenerator()->enrol_user($author->id, $course->id);
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $group1 = self::getDataGenerator()->create_group(['courseid' => $course->id]);
        $group2 = self::getDataGenerator()->create_group(['courseid' => $course->id]);
        $this->getDataGenerator()->create_group_member(['userid' => $author->id, 'groupid' => $group1->id]);
        $this->getDataGenerator()->create_group_member(['userid' => $user->id, 'groupid' => $group2->id]);

        $forum = self::getDataGenerator()->create_module('forum', (object) [
            'course' => $course->id,
            'trackingtype' => FORUM_TRACKING_FORCED,
            'groupmode' => SEPARATEGROUPS,
        ]);
        $generator = self::getDataGenerator()->get_plugin_generator('mod_forum');
        $discussion = $generator->create_discussion((object) [
            'course' => $course->id,
            'userid' => $author->id,
            'forum' => $forum->id,
            'groupid' => $group1->id,
        ]);

        self::setUser($user);
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('noviewdiscussionspermission', 'forum'));
        mark_discussion_read::execute($discussion->id);
    }
}
