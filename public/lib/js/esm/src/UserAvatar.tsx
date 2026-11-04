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
 * A user's avatar, rendered with the design system Avatar component.
 *
 * @module     core/UserAvatar
 * @copyright  2026 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {Avatar, type AvatarProps} from '@moodlehq/design-system';

/** The most characters of the initials the design system Avatar can show. */
const MAX_INITIALS = 2;

export interface UserAvatarProps extends Omit<AvatarProps, 'initials' | 'imageSrc'> {
    /** URL of the user's picture. When empty or omitted the initials are shown instead. */
    imageSrc?: string;
    /** The user's initials, as produced by core_user::get_initials(). Only the first two characters are shown. */
    initials?: string;
}

/**
 * Render a user's picture, falling back to their initials.
 *
 * Leave `alt` out when the avatar is decorative, e.g. next to the user's name.
 *
 * @param props Component props.
 * @returns The rendered avatar.
 */
export default function UserAvatar({imageSrc, initials, size = 'md', ...rest}: UserAvatarProps) {
    return (
        <Avatar
            size={size}
            imageSrc={imageSrc || undefined}
            initials={initials ? Array.from(initials).slice(0, MAX_INITIALS).join('') : undefined}
            {...rest}
        />
    );
}
