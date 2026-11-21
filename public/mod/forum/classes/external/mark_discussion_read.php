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

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use core_external\external_warnings;
use mod_forum\local\container;

/**
 * Web service to mark every unread post of a discussion as read for the current user.
 *
 * This is the ajax equivalent of markposts.php?mark=read&d=X.
 *
 * @package   mod_forum
 * @category  external
 * @copyright 2026 Phung Vo Anh <phung.voanh@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mark_discussion_read extends external_api {
    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'discussionid' => new external_value(PARAM_INT, 'Identifier of the discussion to mark as read'),
        ]);
    }

    /**
     * Mark all unread posts of a discussion as read for the current user.
     *
     * Returns status false with a 'forumnottracked' warning when read tracking
     * is not available for the current user in the discussion's forum.
     *
     * @param int $discussionid The discussion identifier.
     * @return array Status of the operation and warnings.
     * @throws \moodle_exception If the discussion/forum is invalid or the user cannot view it.
     */
    public static function execute(int $discussionid): array {
        global $CFG, $USER;

        require_once($CFG->dirroot . '/mod/forum/lib.php');

        $params = self::validate_parameters(self::execute_parameters(), ['discussionid' => $discussionid]);

        $vaultfactory = container::get_vault_factory();
        $discussionentity = $vaultfactory->get_discussion_vault()->get_from_id($params['discussionid']);
        if (empty($discussionentity)) {
            throw new \moodle_exception('invaliddiscussionid', 'forum');
        }

        $forumentity = $vaultfactory->get_forum_vault()->get_from_id($discussionentity->get_forum_id());
        if (empty($forumentity)) {
            throw new \moodle_exception('invalidforumid', 'forum');
        }

        self::validate_context($forumentity->get_context());

        $capabilitymanager = container::get_manager_factory()->get_capability_manager($forumentity);
        if (!$capabilitymanager->can_view_discussion($USER, $discussionentity)) {
            throw new \moodle_exception('noviewdiscussionspermission', 'forum');
        }

        $legacydatamapperfactory = container::get_legacy_data_mapper_factory();
        $forumrecord = $legacydatamapperfactory->get_forum_data_mapper()->to_legacy_object($forumentity);

        $warnings = [];
        if (!forum_tp_is_tracked($forumrecord, $USER)) {
            $warnings[] = [
                'item' => 'discussion',
                'itemid' => $discussionentity->get_id(),
                'warningcode' => 'forumnottracked',
                'message' => 'Read tracking is not available for this user in this forum.',
            ];
            return ['status' => false, 'warnings' => $warnings];
        }

        return [
            'status' => (bool) forum_tp_mark_discussion_read($USER, $discussionentity->get_id()),
            'warnings' => $warnings,
        ];
    }

    /**
     * Describe the return structure of the external service.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status' => new external_value(PARAM_BOOL, 'True if the discussion was marked as read'),
            'warnings' => new external_warnings(),
        ]);
    }
}
