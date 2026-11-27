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

namespace mod_forum\route\parameters;

use core\exception\not_found_exception;
use core\param;
use core\router\schema\parameters\mapped_property_parameter;
use core\router\schema\parameters\path_parameter;
use core\router\schema\referenced_object;
use mod_forum\local\container;
use Psr\Http\Message\ServerRequestInterface;

/**
 * A path parameter referencing a forum instance id.
 *
 * This adds the following attributes to the request:
 * - `forum`: the {@see \mod_forum\local\entities\forum} entity
 * - `forumcontext`: the module context of the forum
 * - `course`: the id of the course the forum belongs to
 *
 * @package    mod_forum
 * @copyright  2026 Huong Nguyen <huongn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class path_forum extends path_parameter implements mapped_property_parameter, referenced_object {
    /**
     * Create a new forum path parameter.
     *
     * @param string $name The name of the path parameter
     * @param mixed ...$extra Additional arguments
     */
    public function __construct(
        string $name = 'forumId',
        ...$extra,
    ) {
        $extra['name'] = $name;
        $extra['type'] = param::INT;
        $extra['description'] = 'The forum identifier.';

        parent::__construct(...$extra);
    }

    #[\Override]
    public function add_attributes_for_parameter_value(
        ServerRequestInterface $request,
        string $value,
    ): ServerRequestInterface {
        $forum = container::get_vault_factory()->get_forum_vault()->get_from_id((int) $value);
        if (empty($forum)) {
            throw new not_found_exception('forum', $value);
        }

        return $request
            ->withAttribute('forum', $forum)
            ->withAttribute('forumcontext', $forum->get_context())
            ->withAttribute('course', $forum->get_course_id());
    }
}
