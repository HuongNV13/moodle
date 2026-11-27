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

use core\exception\access_denied_exception;
use core\exception\invalid_parameter_exception;
use core\param;
use core\router\response\access_denied_response;
use core\router\require_login;
use core\router\route;
use core\router\schema\example;
use core\router\schema\objects\schema_object;
use core\router\schema\objects\scalar_type;
use core\router\schema\parameters\query_parameter;
use core\router\schema\response\content\json_media_type;
use core\router\schema\response\payload_response;
use core\router\schema\response\response;
use core\router\scope\scopeset;
use mod_forum\local\entities\forum;
use mod_forum\local\factories\manager;
use mod_forum\local\vaults\discussion_list;
use mod_forum\route\parameters\path_forum;
use mod_forum\route\scope\discussion\read as discussion_read;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * REST API handlers for the discussions of a forum.
 *
 * Resolves to /api/rest/v2/mod_forum/forums/{forumId}/discussions.
 *
 * @package   mod_forum
 * @copyright 2026 Danrave Keh
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[route(
    path: '/forums/{forumId}',
    pathtypes: [
        new path_forum(),
    ],
)]
class forums {
    /** @var int The largest page size allowed, unless the site's "forum_manydiscussions" setting is larger. */
    public const MAX_PERPAGE = 100;

    /**
     * @var array[] The sort orders the discussion list supports, by the value of the sortOrder parameter,
     *              with the discussion list vault sort order of each sortDirection.
     */
    private const SORT_ORDERS = [
        'lastpost' => [
            'asc' => discussion_list::SORTORDER_LASTPOST_ASC,
            'desc' => discussion_list::SORTORDER_LASTPOST_DESC,
        ],
        'created' => [
            'asc' => discussion_list::SORTORDER_CREATED_ASC,
            'desc' => discussion_list::SORTORDER_CREATED_DESC,
        ],
        'replies' => [
            'asc' => discussion_list::SORTORDER_REPLIES_ASC,
            'desc' => discussion_list::SORTORDER_REPLIES_DESC,
        ],
        'discussion' => [
            'asc' => discussion_list::SORTORDER_DISCUSSION_ASC,
            'desc' => discussion_list::SORTORDER_DISCUSSION_DESC,
        ],
        'starter' => [
            'asc' => discussion_list::SORTORDER_STARTER_ASC,
            'desc' => discussion_list::SORTORDER_STARTER_DESC,
        ],
        'group' => [
            'asc' => discussion_list::SORTORDER_GROUP_ASC,
            'desc' => discussion_list::SORTORDER_GROUP_DESC,
        ],
    ];

    /**
     * Get the list of discussions of a forum.
     *
     * The access checks match view.php. When "Force users to log in" is off, visitors who are not logged in
     * and the guest user can get the list of a visible site home forum, if they can view discussions.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param forum $forum The forum, loaded from the path parameter.
     * @param manager $managerfactory Injected dependency.
     * @return payload_response
     */
    #[route(
        path: '/discussions',
        method: ['GET'],
        title: 'List the discussions of a forum',
        description: 'Get a page of the discussions of a forum.',
        queryparams: [
            new query_parameter(
                name: 'sortOrder',
                type: param::ALPHA,
                description: 'What to sort the discussions by: lastpost, created, replies, discussion (title), '
                    . 'starter (name of the user who started it) or group. Defaults to lastpost.',
            ),
            new query_parameter(
                name: 'sortDirection',
                type: param::ALPHA,
                description: 'The sort direction: asc or desc. Defaults to desc.',
            ),
            new query_parameter(
                name: 'page',
                type: param::INT,
                description: 'The page number, starting from 0. Must be 0 or greater.',
                default: 0,
            ),
            new query_parameter(
                name: 'perPage',
                type: param::INT,
                description: 'The number of discussions per page, from 1 to ' . self::MAX_PERPAGE . ', or to the site '
                    . '"forum_manydiscussions" setting if that is larger.',
            ),
            new query_parameter(
                name: 'groupId',
                type: param::INT,
                description: 'The group id to filter the discussions by.',
            ),
        ],
        responses: [
            new response(
                content: [
                    new json_media_type(
                        schema: new schema_object(
                            content: [
                                'totalCount' => new scalar_type(param::INT),
                                'page' => new scalar_type(param::INT),
                                'perPage' => new scalar_type(param::INT),
                                'sortOrder' => new scalar_type(param::ALPHA),
                                'sortDirection' => new scalar_type(param::ALPHA),
                            ],
                        ),
                        example: new example(
                            name: 'Discussion list',
                            summary: 'The first page of the discussions of a forum',
                            value: [
                                'totalCount' => 0,
                                'page' => 0,
                                'perPage' => 20,
                                'sortOrder' => 'lastpost',
                                'sortDirection' => 'desc',
                            ],
                        ),
                    ),
                ],
            ),
            new access_denied_response(),
        ],
        // The login checks are done in the method, because the path parameters are not resolved yet when the
        // authentication middleware runs, and visitors can view a site home forum like in view.php.
        requirelogin: new require_login(requirelogin: false),
    )]
    #[scopeset(
        new discussion_read(),
    )]
    public function get_discussions(
        ServerRequestInterface $request,
        ResponseInterface $response,
        forum $forum,
        manager $managerfactory,
    ): payload_response {
        global $USER;

        // Same checks as view.php: the activity must be visible, and the course login is required, except for
        // a visible site home forum when "Force users to log in" is off.
        $course = $forum->get_course_record();
        $cm = get_fast_modinfo($course)->get_cm($forum->get_course_module_record()->id);
        if (!$cm->uservisible) {
            throw new access_denied_exception('noviewdiscussionspermission', 'forum');
        }
        require_course_login($course, true, $cm, false, true);

        $capabilitymanager = $managerfactory->get_capability_manager($forum);
        if (!$capabilitymanager->can_view_discussions($USER)) {
            throw new access_denied_exception('noviewdiscussionspermission', 'forum');
        }

        $query = $request->getQueryParams();
        $sortorder = $query['sortOrder'] ?? null;
        $sortdirection = $query['sortDirection'] ?? null;
        $page = (int) $query['page'];
        $perpage = $query['perPage'] ?? null;

        if ($sortorder !== null && !array_key_exists($sortorder, self::SORT_ORDERS)) {
            throw new invalid_parameter_exception('Invalid value for sortOrder parameter: ' . $sortorder);
        }
        if ($sortdirection !== null && !in_array($sortdirection, ['asc', 'desc'], true)) {
            throw new invalid_parameter_exception('Invalid value for sortDirection parameter: ' . $sortdirection);
        }
        if ($page < 0) {
            throw new invalid_parameter_exception('Invalid value for page parameter: ' . $page);
        }
        if ($perpage !== null && ((int) $perpage < 1 || (int) $perpage > self::get_max_perpage())) {
            throw new invalid_parameter_exception('Invalid value for perPage parameter: ' . $perpage);
        }

        // TODO MDL-90420 Sort the discussions with the vault sort order from self::SORT_ORDERS.
        // TODO MDL-90423 Filter the discussions by group.
        return new payload_response(
            payload: [
                'totalCount' => 0,
                'page' => $page,
                'perPage' => $perpage === null ? null : (int) $perpage,
                'sortOrder' => $sortorder,
                'sortDirection' => $sortdirection,
            ],
            request: $request,
            response: $response,
        );
    }

    /**
     * The largest page size allowed, so that one request cannot load every discussion of a large forum.
     *
     * @return int
     */
    private static function get_max_perpage(): int {
        global $CFG;
        return max(self::MAX_PERPAGE, (int) ($CFG->forum_manydiscussions ?? 0));
    }
}
