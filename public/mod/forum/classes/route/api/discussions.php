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
use core\exception\not_found_exception;
use core\param;
use core\router\route;
use core\router\schema\example;
use core\router\schema\objects\schema_object;
use core\router\schema\objects\scalar_type;
use core\router\schema\parameters\path_parameter;
use core\router\schema\request_body;
use core\router\schema\response\content\json_media_type;
use core\router\schema\response\content\payload_response_type;
use core\router\schema\response\payload_response;
use core\router\schema\response\response;
use core\router\scope\scopeset;
use mod_forum\local\container;
use mod_forum\route\scope\discussion\read as discussion_read;
use mod_forum\route\scope\discussion\write as discussion_write;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * REST API handlers for forum discussions.
 *
 * Resolves to /api/rest/v2/mod_forum/discussions.
 *
 * @package   mod_forum
 * @copyright 2026 Phung Vo Anh <phung.voanh@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[route(path: '/discussions')]
class discussions {
    /**
     * Update the state of a discussion for the current user.
     *
     * Currently the only supported change is `{"read": true}`, which marks every unread post in the
     * discussion as read for the current user, in both automatic and manual read-tracking modes.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param int $discussionId The discussion identifier.
     * @return payload_response
     */
    #[route(
        path: '/{discussionId}',
        method: ['PATCH'],
        title: 'Update a discussion',
        description: 'Update the state of a discussion for the current user. Only read set to true is supported.',
        pathtypes: [
            new path_parameter(
                name: 'discussionId',
                type: param::INT,
                description: 'The discussion identifier.',
            ),
        ],
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
        ],
    )]
    #[scopeset(
        new discussion_read(),
        new discussion_write(),
    )]
    public function update_discussion(
        ServerRequestInterface $request,
        ResponseInterface $response,
        // The parameter name must match the {discussionId} path parameter for the router to inject it.
        int $discussionId, // phpcs:ignore moodle.NamingConventions.ValidVariableName.VariableNameLowerCase
    ): payload_response {
        global $CFG, $USER;

        require_once($CFG->dirroot . '/mod/forum/lib.php');

        $discussionid = $discussionId; // phpcs:ignore moodle.NamingConventions.ValidVariableName.VariableNameLowerCase
        $body = (array) $request->getParsedBody();
        // The request schema has already cleaned the value to a boolean-like 1 or 0.
        if (empty($body['read'])) {
            throw new invalid_parameter_exception("Only 'read' set to true is supported.");
        }

        $vaultfactory = container::get_vault_factory();
        $discussion = $vaultfactory->get_discussion_vault()->get_from_id($discussionid);
        if (empty($discussion)) {
            throw new not_found_exception('discussion', (string) $discussionid);
        }
        $forum = $vaultfactory->get_forum_vault()->get_from_id($discussion->get_forum_id());
        if (empty($forum)) {
            throw new not_found_exception('forum', (string) $discussion->get_forum_id());
        }

        try {
            require_login($forum->get_course_record(), false, $forum->get_course_module_record(), false, true);
        } catch (\require_login_exception $e) {
            throw new access_denied_exception('noviewdiscussionspermission', 'forum');
        }

        $capabilitymanager = container::get_manager_factory()->get_capability_manager($forum);
        if (!$capabilitymanager->can_view_discussion($USER, $discussion)) {
            throw new access_denied_exception('noviewdiscussionspermission', 'forum');
        }

        $forumrecord = container::get_legacy_data_mapper_factory()->get_forum_data_mapper()->to_legacy_object($forum);
        if (!forum_tp_is_tracked($forumrecord, $USER)) {
            throw new access_denied_exception('forumnottracked', 'forum');
        }

        forum_tp_mark_discussion_read($USER, $discussion->get_id());

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
