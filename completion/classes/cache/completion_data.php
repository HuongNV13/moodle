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
 * Cache data source for the completion.
 *
 * @package   core_completion
 * @copyright 2021 Huong Nguyen <huongn@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace core_completion\cache;

use cache_definition;

/**
 * Class completion_data
 *
 * @package   core_completion
 * @copyright 2021 Huong Nguyen <huongn@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class completion_data implements \cache_data_source {

    /** @var completion_data the singleton instance of this class. */
    protected static $instance = null;

    /**
     * Returns an instance of the data source class that the cache can use for loading data using the other methods
     * specified by this interface.
     *
     * @param cache_definition $definition
     * @return completion_data
     */
    public static function get_instance_for_cache(cache_definition $definition): completion_data {
        if (is_null(self::$instance)) {
            self::$instance = new completion_data();
        }
        return self::$instance;
    }

    /**
     * Loads the data for the key provided ready formatted for caching.
     *
     * @param string|int $key The key to load.
     * @return mixed What ever data should be returned, or false if it can't be loaded.
     */
    public function load_for_cache($key) {
        global $DB;

        $cacheddata = [];
        [$userid, $courseid] = explode('_', $key);
        $course = get_course($courseid);
        // Default data to return when no completion data is found.
        $defaultdata = [
                'id' => 0,
                'userid' => $userid,
                'completionstate' => 0,
                'viewed' => 0,
                'overrideby' => null,
                'timemodified' => 0
        ];

        $alldatabycmc = $DB->get_records_sql('SELECT cm.id AS cmid, cmc.*
                                                FROM {course_modules} cm
                                           LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid=cm.id
                                                     AND cmc.userid=?
                                               WHERE cm.course=?', [$userid, $courseid]);

        if ($alldatabycmc) {
            // Reindex by course module id.
            foreach ($alldatabycmc as $data) {
                if (empty($data->coursemoduleid)) {
                    $cacheddata[$data->cmid] = $defaultdata;
                    $cacheddata[$data->cmid]['coursemoduleid'] = $data->cmid;
                } else {
                    $cacheddata[$data->cmid] = (array) $data;
                }
                $cacheddata[$data->cmid]['loadedother'] = false;
            }
            $cacheddata['cacherev'] = $course->cacherev;
        }

        // Return null instead of false, because false will not be cached.
        return $cacheddata ?: null;
    }

    /**
     * Loads several keys for the cache.
     *
     * @param array $keys An array of keys each of which will be string|int.
     * @return array An array of matching data items.
     */
    public function load_many_for_cache(array $keys) {
        $results = [];

        foreach ($keys as $key) {
            $results[] = $this->load_for_cache($key);
        }

        return $results;
    }
}
