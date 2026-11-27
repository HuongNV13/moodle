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

use core\context\module as context_module;
use core\tests\router\route_testcase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ResponseInterface;

/**
 * Tests for the mod_forum forums REST API route.
 *
 * @package    mod_forum
 * @category   test
 * @copyright  2026 Danrave Keh
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(forums::class)]
final class forums_test extends route_testcase {
    /**
     * Create a course with a forum and a student enrolled in it, and log the student in.
     *
     * @param array $forumrecord Extra settings for the forum.
     * @return array The course, the forum and the student.
     */
    private function create_forum_with_student(array $forumrecord = []): array {
        $course = self::getDataGenerator()->create_course();
        $forum = self::getDataGenerator()->create_module('forum', ['course' => $course->id] + $forumrecord);
        $student = self::getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);
        return [$course, $forum, $student];
    }

    /**
     * Send a GET request to the discussions of a forum.
     *
     * @param int $forumid
     * @param array $query Query parameters.
     * @return ResponseInterface
     */
    private function get_discussions(int $forumid, array $query = []): ResponseInterface {
        $this->add_class_routes_to_route_loader(forums::class, \core\router\route_loader_interface::ROUTE_GROUP_API);
        $path = "/forums/{$forumid}/discussions";
        if ($query) {
            $path .= '?' . http_build_query($query);
        }
        return $this->process_api_request('GET', $path);
    }

    /**
     * An enrolled student gets the empty structure, with the given values.
     */
    public function test_get_discussions(): void {
        $this->resetAfterTest();
        [, $forum] = $this->create_forum_with_student();

        $response = $this->get_discussions($forum->id, [
            'sortOrder' => 'group',
            'sortDirection' => 'asc',
            'page' => 2,
            'perPage' => 10,
        ]);

        $this->assert_valid_response($response);
        $this->assertEquals((object) [
            'totalCount' => 0,
            'page' => 2,
            'perPage' => 10,
            'sortOrder' => 'group',
            'sortDirection' => 'asc',
        ], $this->decode_response($response));
    }

    /**
     * The defaults are used when only the forum id is given.
     */
    public function test_get_discussions_defaults(): void {
        $this->resetAfterTest();
        [, $forum] = $this->create_forum_with_student();

        $response = $this->get_discussions($forum->id);

        $this->assert_valid_response($response);
        $this->assertEquals((object) [
            'totalCount' => 0,
            'page' => 0,
            'perPage' => null,
            'sortOrder' => null,
            'sortDirection' => null,
        ], $this->decode_response($response));
    }

    /**
     * An unknown forum id is not found.
     */
    public function test_unknown_forum(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $this->assert_not_found_response($this->get_discussions(-1));
    }

    /**
     * A user who is not enrolled in the course cannot get the list.
     */
    public function test_not_enrolled(): void {
        $this->resetAfterTest();
        [, $forum] = $this->create_forum_with_student();
        $this->setUser(self::getDataGenerator()->create_user());

        $this->assertNotEquals(200, $this->get_discussions($forum->id)->getStatusCode());
    }

    /**
     * A student cannot get the list of a hidden forum, but a teacher can.
     */
    public function test_hidden_forum(): void {
        $this->resetAfterTest();
        [$course, $forum, $student] = $this->create_forum_with_student(['visible' => 0]);
        $teacher = self::getDataGenerator()->create_and_enrol($course, 'editingteacher');

        $this->setUser($teacher);
        $this->assert_valid_response($this->get_discussions($forum->id));

        $this->setUser($student);
        $this->assert_access_denied_response($this->get_discussions($forum->id));
    }

    /**
     * Who can get the list of a site home forum without logging in, as in view.php.
     *
     * @param string $user 'none' for a visitor who is not logged in, or 'guest' for the guest user.
     * @param bool $forcelogin Whether "Force users to log in" is on.
     * @param bool $visible Whether the forum is visible.
     * @param bool $allowed Whether the visitor can get the list.
     */
    #[DataProvider('site_home_visitor_provider')]
    public function test_site_home_visitor(string $user, bool $forcelogin, bool $visible, bool $allowed): void {
        $this->resetAfterTest();
        $forum = self::getDataGenerator()->create_module('forum', ['course' => SITEID, 'visible' => (int) $visible]);
        set_config('forcelogin', (int) $forcelogin);
        if ($user === 'guest') {
            $this->setGuestUser();
        } else {
            $this->setUser(0);
        }

        $response = $this->get_discussions($forum->id);

        if ($allowed) {
            $this->assert_valid_response($response);
            $this->assertEquals(0, $this->decode_response($response)->totalCount);
        } else {
            $this->assertNotEquals(200, $response->getStatusCode());
        }
    }

    /**
     * Provides the site home visitor cases.
     *
     * @return array
     */
    public static function site_home_visitor_provider(): array {
        return [
            'Not logged in, visible forum' => ['none', false, true, true],
            'Guest, visible forum' => ['guest', false, true, true],
            'Not logged in, force login on' => ['none', true, true, false],
            'Not logged in, hidden forum' => ['none', false, false, false],
            'Guest, hidden forum' => ['guest', false, false, false],
        ];
    }

    /**
     * A visitor who is not logged in cannot get the list of a forum in a course.
     */
    public function test_course_forum_not_logged_in(): void {
        $this->resetAfterTest();
        [, $forum] = $this->create_forum_with_student();
        set_config('forcelogin', 0);
        $this->setUser(0);

        $this->assertNotEquals(200, $this->get_discussions($forum->id)->getStatusCode());
    }

    /**
     * A visitor who is not logged in needs the capability to view discussions.
     */
    public function test_site_home_not_logged_in_without_capability(): void {
        global $CFG;

        $this->resetAfterTest();
        $forum = self::getDataGenerator()->create_module('forum', ['course' => SITEID]);
        assign_capability(
            'mod/forum:viewdiscussion',
            CAP_PROHIBIT,
            $CFG->notloggedinroleid,
            context_module::instance($forum->cmid),
        );
        set_config('forcelogin', 0);
        $this->setUser(0);

        $this->assert_access_denied_response($this->get_discussions($forum->id));
    }

    /**
     * A user without the capability to view discussions is rejected.
     */
    public function test_without_capability(): void {
        global $DB;

        $this->resetAfterTest();
        [, $forum] = $this->create_forum_with_student();
        $studentrole = $DB->get_field('role', 'id', ['shortname' => 'student']);
        assign_capability('mod/forum:viewdiscussion', CAP_PROHIBIT, $studentrole, context_module::instance($forum->cmid));

        $this->assert_access_denied_response($this->get_discussions($forum->id));
    }

    /**
     * Invalid values are rejected.
     *
     * @param array $query The query parameters.
     */
    #[DataProvider('invalid_values_provider')]
    public function test_invalid_values(array $query): void {
        $this->resetAfterTest();
        [, $forum] = $this->create_forum_with_student();

        $this->assert_invalid_parameter_response($this->get_discussions($forum->id, $query));
    }

    /**
     * Provides invalid sort orders, pages and page sizes.
     *
     * @return array
     */
    public static function invalid_values_provider(): array {
        return [
            'Unknown sort order' => [['sortOrder' => 'unknown']],
            'Sort order in upper case' => [['sortOrder' => 'GROUP']],
            'Numeric sort order' => [['sortOrder' => 1]],
            'Unknown sort direction' => [['sortDirection' => 'up']],
            'Negative page' => [['page' => -1]],
            'Negative page size' => [['perPage' => -1]],
            'Page size 0' => [['perPage' => 0]],
            'Page size above the maximum' => [['perPage' => forums::MAX_PERPAGE + 1]],
        ];
    }

    /**
     * The page size limits, including a site setting larger than the default maximum.
     */
    public function test_perpage_limits(): void {
        $this->resetAfterTest();
        [, $forum] = $this->create_forum_with_student();

        set_config('forum_manydiscussions', 20);
        $response = $this->get_discussions($forum->id, ['perPage' => 1]);
        $this->assertEquals(1, $this->decode_response($response)->perPage);
        $response = $this->get_discussions($forum->id, ['perPage' => forums::MAX_PERPAGE]);
        $this->assertEquals(forums::MAX_PERPAGE, $this->decode_response($response)->perPage);

        // A site that shows more discussions per page can also request them.
        set_config('forum_manydiscussions', 150);
        $response = $this->get_discussions($forum->id, ['perPage' => 150]);
        $this->assertEquals(150, $this->decode_response($response)->perPage);
        $this->assert_invalid_parameter_response($this->get_discussions($forum->id, ['perPage' => 151]));
    }

    /**
     * The route requires the discussion read scope.
     */
    public function test_route_scopes(): void {
        $this->resetAfterTest();
        $this->assert_route_required_scopes(
            [['mod_forum:discussion:read']],
            [forums::class, 'get_discussions'],
        );
    }
}
