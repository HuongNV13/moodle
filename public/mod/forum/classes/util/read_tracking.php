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

/**
 * Helpers for forum read tracking.
 *
 * @package    mod_forum
 * @copyright  2026 Huong Nguyen <huongn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class read_tracking {
    /**
     * Mark posts as read.
     *
     * @param \stdClass $user The user to mark the posts as read for
     * @param int[] $postids The ids of the posts to mark as read
     * @return bool Whether all posts were successfully marked as read
     */
    public static function mark_posts_read(\stdClass $user, array $postids): bool {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/mod/forum/lib.php');

        if (!forum_tp_can_track_forums(false, $user)) {
            return true;
        }

        $status = true;

        $now = time();
        $cutoffdate = $now - ($CFG->forum_oldpostdays * 24 * 3600);

        if (empty($postids)) {
            return true;

        } else if (count($postids) > 200) {
            while ($part = array_splice($postids, 0, 200)) {
                $status = self::mark_posts_read($user, $part) && $status;
            }
            return $status;
        }

        list($usql, $postidparams) = $DB->get_in_or_equal($postids, SQL_PARAMS_NAMED, 'postid');

        $insertparams = array(
            'userid1' => $user->id,
            'userid2' => $user->id,
            'userid3' => $user->id,
            'firstread' => $now,
            'lastread' => $now,
            'cutoffdate' => $cutoffdate,
        );
        $params = array_merge($postidparams, $insertparams);

        if ($CFG->forum_allowforcedreadtracking) {
            $trackingsql = "AND (f.trackingtype = ".FORUM_TRACKING_FORCED."
                            OR (f.trackingtype = ".FORUM_TRACKING_OPTIONAL." AND tf.id IS NULL))";
        } else {
            $trackingsql = "AND ((f.trackingtype = ".FORUM_TRACKING_OPTIONAL."  OR f.trackingtype = ".FORUM_TRACKING_FORCED.")
                                AND tf.id IS NULL)";
        }

        // First insert any new entries.
        $sql = "INSERT INTO {forum_read} (userid, postid, discussionid, forumid, firstread, lastread)

                SELECT :userid1, p.id, p.discussion, d.forum, :firstread, :lastread
                    FROM {forum_posts} p
                        JOIN {forum_discussions} d       ON d.id = p.discussion
                        JOIN {forum} f                   ON f.id = d.forum
                        LEFT JOIN {forum_track_prefs} tf ON (tf.userid = :userid2 AND tf.forumid = f.id)
                        LEFT JOIN {forum_read} fr        ON (
                                fr.userid = :userid3
                            AND fr.postid = p.id
                            AND fr.discussionid = d.id
                            AND fr.forumid = f.id
                        )
                    WHERE p.id $usql
                        AND p.modified >= :cutoffdate
                        $trackingsql
                        AND fr.id IS NULL";

        $status = $DB->execute($sql, $params) && $status;

        // Then update all records.
        $updateparams = array(
            'userid' => $user->id,
            'lastread' => $now,
        );
        $params = array_merge($postidparams, $updateparams);
        $status = $DB->set_field_select('forum_read', 'lastread', $now, '
                    userid      =  :userid
                AND lastread    <> :lastread
                AND postid      ' . $usql,
                $params) && $status;

        return $status;
    }

    /**
     * Mark every post of a discussion as read for a user.
     *
     * @param \stdClass $user The user to mark the discussion as read for
     * @param int $discussionid The discussion id
     * @return bool Whether all posts were successfully marked as read
     */
    public static function mark_discussion_read(\stdClass $user, int $discussionid): bool {
        global $CFG, $DB;

        $cutoffdate = time() - ($CFG->forum_oldpostdays*24*60*60);

        $sql = "SELECT p.id
                  FROM {forum_posts} p
                       LEFT JOIN {forum_read} r ON (r.postid = p.id AND r.userid = ?)
                 WHERE p.discussion = ?
                       AND p.modified >= ? AND r.id is NULL";

        if ($posts = $DB->get_records_sql($sql, array($user->id, $discussionid, $cutoffdate))) {
            $postids = array_keys($posts);
            return self::mark_posts_read($user, $postids);
        }

        return true;
    }
}
