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
use core\router\schema\request_body;
use core\router\schema\response\content\json_media_type;
use core\router\schema\response\content\payload_response_type;
use core\router\schema\response\payload_response;
use core\router\schema\response\response;
use core\router\scope\scopeset;
use mod_forum\local\entities\discussion;
use mod_forum\local\entities\forum;
use mod_forum\local\factories\legacy_data_mapper;
use mod_forum\local\factories\manager;
use mod_forum\route\parameters\path_discussion;
use mod_forum\route\scope\discussion\read as discussion_read;
use mod_forum\route\scope\discussion\write as discussion_write;
use mod_forum\util\read_tracking;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * REST API handlers for forum discussions.
 *
 * Resolves to /api/rest/v2/mod_forum/discussions/{discussionId}.
 *
 * @package   mod_forum
 * @copyright 2026 Phung Vo Anh <phung.voanh@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[route(
    path: '/discussions/{discussionId}',
    pathtypes: [
        new path_discussion(),
    ],
)]
class discussions {
    /**
     * Update the state of a discussion for the current user.
     *
     * Currently the only supported change is `{"read": true}`, which marks every unread post in the
     * discussion as read for the current user, in both automatic and manual read-tracking modes.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param discussion $discussion The discussion, loaded from the path parameter.
     * @param forum $forum The forum the discussion belongs to.
     * @param legacy_data_mapper $mapper Injected dependency.
     * @param manager $managerfactory Injected dependency.
     * @return payload_response
     */
    #[route(
        method: ['PATCH'],
        title: 'Update a discussion',
        description: 'Update the state of a discussion for the current user. Only read set to true is supported.',
        requestbody: new request_body(
            required: true,
            content: new payload_response_type(
                schema: new schema_object(
                    content: [
                        'read' => new scalar_type(
                            type: param::BOOL,
                            required: true,
                            description: 'Must be true. Marks all posts in the discussion as read for the current user.',
                        ),
                    ],
                ),
                examples: [
                    new example(
                        name: 'Mark a discussion as read',
                        summary: 'Marks every unread post in the discussion as read for the current user',
                        value: ['read' => true],
                    ),
                ],
            ),
        ),
        responses: [
            new response(
                content: [
                    new json_media_type(
                        schema: new schema_object(
                            content: [
                                'discussionId' => new scalar_type(param::INT),
                                'read' => new scalar_type(param::BOOL),
                            ],
                        ),
                        example: new example(
                            name: 'Discussion marked as read',
                            summary: 'The discussion state after the update',
                            value: [
                                'discussionId' => 99,
                                'read' => true,
                            ],
                        ),
                    ),
                ],
            ),
            new access_denied_response(),
        ],
        requirelogin: new require_login(false, true, 'course'),
    )]
    #[scopeset(
        new discussion_read(),
        new discussion_write(),
    )]
    public function update_discussion(
        ServerRequestInterface $request,
        ResponseInterface $response,
        discussion $discussion,
        forum $forum,
        legacy_data_mapper $mapper,
        manager $managerfactory,
    ): payload_response {
        global $CFG, $USER;

        require_once($CFG->dirroot . '/mod/forum/lib.php');

        $body = (array) $request->getParsedBody();
        // The request schema has already cleaned the value to a boolean-like 1 or 0.
        if (empty($body['read'])) {
            throw new invalid_parameter_exception("Only 'read' set to true is supported.");
        }

        // The route-level course login does not cover the visibility or availability of the activity itself.
        $cm = get_fast_modinfo($forum->get_course_record())->get_cm($forum->get_course_module_record()->id);
        if (!$cm->uservisible) {
            throw new access_denied_exception('noviewdiscussionspermission', 'forum');
        }

        $capabilitymanager = $managerfactory->get_capability_manager($forum);
        if (!$capabilitymanager->can_view_discussion($USER, $discussion)) {
            throw new access_denied_exception('noviewdiscussionspermission', 'forum');
        }

        $forumrecord = $mapper->get_forum_data_mapper()->to_legacy_object($forum);
        if (!forum_tp_is_tracked($forumrecord, $USER)) {
            throw new access_denied_exception('forumnottracked', 'forum');
        }

        read_tracking::mark_discussion_read($USER, $discussion->get_id());

        return new payload_response(
            payload: [
                'discussionId' => $discussion->get_id(),
                'read' => true,
            ],
            request: $request,
            response: $response,
        );
    }
}
