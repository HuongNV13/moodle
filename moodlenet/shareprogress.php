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

/**
 * View the progress of MoodleNet shares.
 *
 * @package   core
 * @copyright 2023 David Woloszyn <david.woloszyn@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\moodlenet\share_recorder;

require_once('../config.php');
require_once($CFG->dirroot .'/course/lib.php');

require_login();
if (isguestuser()) {
    throw new \moodle_exception('noguest');
}

// Check cache for the user's capability to share to MoodleNet and avoid expensive query.
$usercanshare = \cache::make('core', 'moodlenet_usercanshare')->get($USER->id);

if (!$usercanshare) {
    // Check the user has a valid capability.
    // We are checking this way because we are not in the course context and need
    // a way to retrieve the user's courses to see if any of them have the correct capability.
    $capabilities = [
        'moodle/moodlenet:sharecourse',
        'moodle/moodlenet:shareactivity'
    ];

    foreach ($capabilities as $capability) {
        // Get at least one course that contains a capability match.
        list($categories, $courses) = get_user_capability_contexts($capability, false, null, true, '', '', '', '', 1);

        if (!empty($courses)) {
            $usercanshare = true;
            // Let's cache this so we don't perform this check every time.
            \cache::make('core', 'moodlenet_usercanshare')->set($USER->id, $usercanshare);
            break;
        }
    }
}

// Capability was not found.
if (!$usercanshare) {
    throw new \moodle_exception('nocapabilitytousethisservice');
}

// Pagination params.
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 25, PARAM_INT);

$url = $CFG->wwwroot . '/moodlenet/shareprogress.php';
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('moodlenet:shareprogress'));
$PAGE->set_heading(get_string('moodlenet:shareprogress'));
$PAGE->set_pagelayout('standard');

echo $OUTPUT->header();

// Intro paragraph.
echo html_writer::div(get_string('moodlenet:shareprogressinfo'), 'mb-4');

// Get records from DB.
$params = ['userid' => $USER->id];
$sharecount = $DB->count_records('moodlenet_share_progress', $params);
$shares = $DB->get_records('moodlenet_share_progress', $params,
    'status DESC, timecreated DESC', '*', ($page * $perpage), $perpage);
$rowdata = [];

if ($sharecount > 0) {

    foreach ($shares as $share) {
        // Track deletion of resources on Moodle.
        $deleted = false;

        if ($share->type == share_recorder::TYPE_COURSE) {
            $type = get_string('course');
            if ($course = $DB->get_record('course', ['id' => $share->courseid])) {
                $name = $course->fullname;
            } else {
                $name = html_writer::span(get_string('moodlenet:deletedcourse'), 'font-italic');
                $deleted = true;
            }

        } else if ($share->type == share_recorder::TYPE_ACTIVITY) {
            if ($cm = get_coursemodule_from_id('', $share->cmid)) {
                $name = ucfirst($cm->name);
                $type = get_string('modulename', $cm->modname);
            } else {
                $name = html_writer::span(get_string('moodlenet:deletedactivity'), 'font-italic');
                $type = get_string('activity');
                $deleted = true;
            }
        }

        // Add a link to the resource if it was recorded.
        if (!empty($share->resourceurl)) {
            // Bold resource links that aren't deleted.
            $boldclass = !$deleted ? 'font-weight-bold' : null;
            $contents = $name . ' ' . $OUTPUT->pix_icon('i/externallink', get_string('opensinnewwindow'), 'moodle');
            $attributes = [
                'href' => $share->resourceurl,
                'target' => '_blank',
                'rel' => 'noopener noreferrer',
                'class' => $boldclass
            ];
            $name = html_writer::tag('a', $contents, $attributes);
        }

        // Display a badge indicating the status of the share.
        if ($share->status == share_recorder::STATUS_IN_PROGRESS) {
            $status = html_writer::span(get_string('inprogress'), 'badge badge-warning');
        } else if ($share->status == share_recorder::STATUS_SENT) {
            $status = html_writer::span(get_string('sent'), 'badge badge-success');
        } else if ($share->status == share_recorder::STATUS_ERROR) {
            $status = html_writer::span(get_string('error'), 'badge badge-danger');
        }

        // Append table row data.
        $rowdata[] = [
            $name,
            $type,
            userdate($share->timecreated, get_string('strftimedatefullshort', 'core_langconfig')),
            $status,
        ];
    }

    // Build the table.
    $table = new html_table();
    $table->head  = [
        get_string('moodlenet:columnname'),
        get_string('moodlenet:columntype'),
        get_string('moodlenet:columnsenddate'),
        get_string('moodlenet:columnsendstatus')
    ];
    $table->align = ['left', 'left', 'left', 'center'];
    $table->data  = $rowdata;
    echo html_writer::table($table);

    // Pagination.
    $baseurl = new moodle_url($url, ['page' => $page, 'perpage' => $perpage]);
    echo $OUTPUT->paging_bar($sharecount, $page, $perpage, $baseurl);

} else {
    // We have no results.
    echo $OUTPUT->notification(get_string('moodlenet:nosharedresources'), 'info');
}

echo $OUTPUT->footer();
