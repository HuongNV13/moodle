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

namespace mod_forum\route\api;

use core\router\route_loader_interface;
use core\tests\router\route_testcase;
use PHPUnit\Framework\Attributes\CoversClass;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ResponseInterface;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/forum/lib.php');

/**
 * Tests for the mod_forum discussions REST API route.
 *
 * @package    mod_forum
 * @category   test
 * @copyright  2026 Phung Vo Anh <phung.voanh@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(discussions::class)]
final class discussions_test extends route_testcase {
    /**
     * Create a course, a forum and a discussion with one reply, authored by another user.
     *
     * @param int $trackingtype Forum tracking type.
     * @param int $trackforums User trackforums preference.
     * @return array [user, discussion, forum, post ids]
     */
    private function setup_discussion(int $trackingtype = FORUM_TRACKING_FORCED, int $trackforums = 1): array {
        global $CFG;
        $CFG->forum_trackreadposts = true;

        $author = self::getDataGenerator()->create_user();
        $user = self::getDataGenerator()->create_user(['trackforums' => $trackforums]);
        $course = self::getDataGenerator()->create_course();
        self::getDataGenerator()->enrol_user($author->id, $course->id);
        self::getDataGenerator()->enrol_user($user->id, $course->id);
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
        $this->setUser($user);
        $this->add_class_routes_to_route_loader(discussions::class, route_loader_interface::ROUTE_GROUP_API);
        return [$user, $discussion, $forum, [$discussion->firstpost, $reply->id]];
    }

    /**
     * Send a PATCH request with a JSON body to the API.
     *
     * @param string $path
     * @param array $body
     * @return ResponseInterface
     */
    private function patch(string $path, array $body): ResponseInterface {
        return $this->process_api_request(
            'PATCH',
            $path,
            body: Utils::streamFor(json_encode((object) $body)),
        );
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
     * All unread posts of the target discussion are marked, in both marking modes, and other discussions are untouched.
     *
     * @param bool $manual Value for forum_usermarksread.
     */
    #[DataProvider('manual_mode_provider')]
    public function test_mark_discussion_read(bool $manual): void {
        global $CFG;
        $this->resetAfterTest();
        $CFG->forum_usermarksread = $manual;
        [$user, $discussion, $forum, $postids] = $this->setup_discussion();

        $generator = self::getDataGenerator()->get_plugin_generator('mod_forum');
        $otherauthor = self::getDataGenerator()->create_user();
        self::getDataGenerator()->enrol_user($otherauthor->id, $forum->course);
        $other = $generator->create_discussion((object) [
            'course' => $forum->course,
            'userid' => $otherauthor->id,
            'forum' => $forum->id,
        ]);

        $this->assertEquals(0, $this->count_read($user->id, $postids));

        $response = $this->patch("/discussions/{$discussion->id}", ['read' => true]);
        $this->assert_valid_response($response);
        $payload = $this->decode_response($response, true);
        $this->assertEquals(['discussionId' => (int) $discussion->id, 'read' => true], $payload);
        $this->assertEquals(2, $this->count_read($user->id, $postids));
        $this->assertEquals(0, $this->count_read($user->id, [$other->firstpost]));

        // Idempotent.
        $response = $this->patch("/discussions/{$discussion->id}", ['read' => true]);
        $this->assert_valid_response($response);
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
     * Forum without tracking, or user who opted out, is refused and nothing is marked.
     *
     * @param int $trackingtype
     * @param int $trackforums
     */
    #[DataProvider('not_tracked_provider')]
    public function test_mark_discussion_read_not_tracked(int $trackingtype, int $trackforums): void {
        $this->resetAfterTest();
        [$user, $discussion, , $postids] = $this->setup_discussion($trackingtype, $trackforums);

        $response = $this->patch("/discussions/{$discussion->id}", ['read' => true]);
        $this->assert_access_denied_response($response);
        $this->assertEquals(0, $this->count_read($user->id, $postids));
    }

    /**
     * Provider for untracked scenarios.
     *
     * @return array
     */
    public static function not_tracked_provider(): array {
        return [
            'tracking off' => [FORUM_TRACKING_OFF, 1],
            'user opted out' => [FORUM_TRACKING_OPTIONAL, 0],
        ];
    }

    /**
     * Only read=true is accepted.
     *
     * @param array $body
     */
    #[DataProvider('invalid_body_provider')]
    public function test_invalid_body(array $body): void {
        $this->resetAfterTest();
        [$user, $discussion, , $postids] = $this->setup_discussion();

        $response = $this->patch("/discussions/{$discussion->id}", $body);
        $this->assert_invalid_parameter_response($response);
        $this->assertEquals(0, $this->count_read($user->id, $postids));
    }

    /**
     * Provider for invalid bodies.
     *
     * @return array
     */
    public static function invalid_body_provider(): array {
        return [
            'read false' => [['read' => false]],
            'missing read' => [[]],
            'wrong field' => [['unread' => true]],
        ];
    }

    /**
     * Non-existent discussion.
     */
    public function test_invalid_discussion(): void {
        $this->resetAfterTest();
        $this->setup_discussion();
        $response = $this->patch('/discussions/999999', ['read' => true]);
        $this->assert_not_found_response($response);
    }

    /**
     * Users who cannot access the course cannot mark its discussions.
     */
    public function test_no_course_access(): void {
        $this->resetAfterTest();
        [, $discussion] = $this->setup_discussion();
        $this->setUser(self::getDataGenerator()->create_user());
        $response = $this->patch("/discussions/{$discussion->id}", ['read' => true]);
        $this->assert_access_denied_response($response);
    }

    /**
     * Users without mod/forum:viewdiscussion cannot mark a discussion as read.
     */
    public function test_no_view_permission(): void {
        global $DB;
        $this->resetAfterTest();
        [, $discussion, $forum] = $this->setup_discussion();

        $cm = get_coursemodule_from_instance('forum', $forum->id, $forum->course, false, MUST_EXIST);
        $role = $DB->get_record('role', ['shortname' => 'student'], '*', MUST_EXIST);
        assign_capability('mod/forum:viewdiscussion', CAP_PROHIBIT, $role->id, \context_module::instance($cm->id)->id);

        $response = $this->patch("/discussions/{$discussion->id}", ['read' => true]);
        $this->assert_access_denied_response($response);
    }

    /**
     * Anonymous requests are rejected and nothing is marked.
     *
     * The exact status is not asserted: core does not yet return 401 for unauthenticated API requests
     * (see moodle_api_authentication_middleware_test).
     */
    public function test_not_logged_in(): void {
        $this->resetAfterTest();
        [$user, $discussion, , $postids] = $this->setup_discussion();
        $this->setUser(0);

        $response = $this->patch("/discussions/{$discussion->id}", ['read' => true]);
        $this->assertNotEquals(200, $response->getStatusCode());
        $this->assertEquals(0, $this->count_read($user->id, $postids));
    }

    /**
     * Users cannot mark a discussion as read in a forum they cannot see because it is hidden.
     */
    public function test_hidden_forum(): void {
        $this->resetAfterTest();
        [$user, $discussion, $forum, $postids] = $this->setup_discussion();
        set_coursemodule_visible(
            get_coursemodule_from_instance('forum', $forum->id, $forum->course, false, MUST_EXIST)->id,
            0,
        );

        $response = $this->patch("/discussions/{$discussion->id}", ['read' => true]);
        $this->assert_access_denied_response($response);
        $this->assertEquals(0, $this->count_read($user->id, $postids));
    }

    /**
     * The route requires the discussion read and write scopes.
     */
    public function test_route_scopes(): void {
        $this->resetAfterTest();
        $this->assert_route_required_scopes(
            [['mod_forum:discussion:read', 'mod_forum:discussion:write']],
            [discussions::class, 'update_discussion'],
        );
    }
}
