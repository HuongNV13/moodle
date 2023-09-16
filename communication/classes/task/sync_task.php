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

namespace core_communication\task;

use core\task\scheduled_task;
use core_communication\api;
use core_communication\processor;

/**
 * Scheduled task to do the sync queue.
 *
 * @package    core_communication
 * @copyright  2023 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync_task extends scheduled_task {

    public function get_name(): string {
        return get_string('tasksync', 'communication');
    }

    public function execute(): void {
        mtrace('\n  Communication sync task is running');
        global $DB;

        // Create room.
        $createroomsyncs = $DB->get_records('communication_sync', ['type' => api::SYNC_CREATE_ROOM]);
        if (count($createroomsyncs) > 0) {
            mtrace('\n  Found ' . count($createroomsyncs) . ' create room records to sync');
            foreach ($createroomsyncs as $sync) {
                create_and_configure_room_task::queue(
                    processor::load_by_id($sync->commid),
                );
                mtrace('\n  Sync for communication with id' . $sync->commid . ' is completed');
                // Delete the sync task record - it is finished.
                $DB->delete_records('communication_sync', ['id' => $sync->id]);
            }
            return;
        }

        // Update room.
        $updateroomsyncs = $DB->get_records('communication_sync', ['type' => api::SYNC_UPDATE_ROOM]);
        if (count($updateroomsyncs) > 0) {
            mtrace('\n  Found ' . count($updateroomsyncs) . ' update room records to sync');
            foreach ($updateroomsyncs as $sync) {
                create_and_configure_room_task::queue(
                    processor::load_by_id($sync->commid),
                );
                mtrace('\n  Sync for communication with id' . $sync->commid . ' is completed');
                // Delete the sync task record - it is finished.
                $DB->delete_records('communication_sync', ['id' => $sync->id]);
            }
            return;
        }

        // Creat user.
        $createusersyncs = $DB->get_records('communication_sync', ['type' => api::SYNC_ADD_USER]);
        if (count($createusersyncs) > 0) {
            mtrace('\n  Found ' . count($createusersyncs) . ' create user records to sync');
            foreach ($createusersyncs as $sync) {
                $customdata = json_decode($sync->customdata);
                add_members_to_room_task::queue(
                    processor::load_by_id($sync->commid),
                    $customdata->userids,
                );
                mtrace('\n  Sync for communication with id' . $sync->commid . ' is completed');
                // Delete the sync task record - it is finished.
                $DB->delete_records('communication_sync', ['id' => $sync->id]);
            }
            return;
        }

        // User permission.
        $userpermissionsyncs = $DB->get_records('communication_sync', ['type' => api::SYNC_USER_PERMISSION]);
        if (count($userpermissionsyncs) > 0) {
            mtrace('\n  Found ' . count($userpermissionsyncs) . ' create user records to sync');
            foreach ($userpermissionsyncs as $sync) {
                $customdata = json_decode($sync->customdata);
                update_room_membership_task::queue(
                    processor::load_by_id($sync->commid),
                    $customdata->userids,
                );
                mtrace('\n  Sync for communication with id' . $sync->commid . ' is completed');
                // Delete the sync task record - it is finished.
                $DB->delete_records('communication_sync', ['id' => $sync->id]);
            }
            return;
        }

        // Remove user.
        $removeusersyncs = $DB->get_records('communication_sync', ['type' => api::SYNC_REMOVE_USER]);
        if (count($removeusersyncs) > 0) {
            mtrace('\n  Found ' . count($removeusersyncs) . ' remove user records to sync');
            foreach ($removeusersyncs as $sync) {
                $customdata = json_decode($sync->customdata);
                remove_members_from_room::queue(
                    processor::load_by_id($sync->commid),
                    $customdata->userids,
                );
                mtrace('\n  Sync for communication with id' . $sync->commid . ' is completed');
                // Delete the sync task record - it is finished.
                $DB->delete_records('communication_sync', ['id' => $sync->id]);
            }
            return;
        }

        // Delete room.
        $deleteroomsyncs = $DB->get_records('communication_sync', ['type' => api::SYNC_DELETE_ROOM]);
        if (count($deleteroomsyncs) > 0) {
            mtrace('\n  Found ' . count($deleteroomsyncs) . ' delete room records to sync');
            foreach ($deleteroomsyncs as $sync) {
                delete_room_task::queue(
                    processor::load_by_id($sync->commid),
                );
                mtrace('\n  Sync for communication with id' . $sync->commid . ' is completed');
                // Delete the sync task record - it is finished.
                $DB->delete_records('communication_sync', ['id' => $sync->id]);
            }
            return;
        }
    }
}
