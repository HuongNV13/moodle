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
 * Tests for core/UserAvatar, the design-system-backed user avatar.
 *
 * @copyright  2026 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {render} from '@testing-library/react';
import UserAvatar from '@moodle/lms/core/UserAvatar';

// @moodlehq/design-system is ESM only, so Jest cannot resolve it from a CommonJS test bundle.
// Stand Avatar in with markup that records the props it was given, so these tests are about
// UserAvatar's own prop mapping, not the design system's internal rendering.
jest.mock('@moodlehq/design-system', () => ({
    Avatar: ({size, imageSrc, alt, initials, className}: {
        size?: string; imageSrc?: string; alt?: string; initials?: string; className?: string;
    }) => (
        <span
            data-testid="mds-avatar"
            className={className}
            data-size={size}
            data-image-src={imageSrc}
            data-alt={alt}
            data-initials={initials}
        />
    ),
}), {virtual: true});

describe('core/UserAvatar', () => {
    it('passes the picture url and alt text to the design system Avatar', () => {
        const {getByTestId} = render(
            <UserAvatar imageSrc="https://example.com/pluginfile.php/5/user/icon/f2" alt="Jane Doe" />
        );

        const avatar = getByTestId('mds-avatar');
        expect(avatar.getAttribute('data-image-src')).toBe('https://example.com/pluginfile.php/5/user/icon/f2');
        expect(avatar.getAttribute('data-alt')).toBe('Jane Doe');
    });

    it('passes the initials when there is no picture', () => {
        const {getByTestId} = render(<UserAvatar initials="JD" />);

        const avatar = getByTestId('mds-avatar');
        expect(avatar.getAttribute('data-initials')).toBe('JD');
        expect(avatar.hasAttribute('data-image-src')).toBe(false);
    });

    it('keeps only the first two characters of long initials', () => {
        const {getByTestId} = render(<UserAvatar initials="JDX" />);

        expect(getByTestId('mds-avatar').getAttribute('data-initials')).toBe('JD');
    });

    it('does not split a character made of multiple code units when truncating initials', () => {
        // Each of these is a single character that takes two UTF-16 code units.
        const {getByTestId} = render(<UserAvatar initials="𠮷𠮟𠮷" />);

        expect(getByTestId('mds-avatar').getAttribute('data-initials')).toBe('𠮷𠮟');
    });

    it('treats an empty picture url as no picture so the initials show', () => {
        const {getByTestId} = render(<UserAvatar imageSrc="" initials="JD" />);

        const avatar = getByTestId('mds-avatar');
        expect(avatar.hasAttribute('data-image-src')).toBe(false);
        expect(avatar.getAttribute('data-initials')).toBe('JD');
    });

    it('defaults to the md size and forwards an explicit size', () => {
        const {getByTestId, rerender} = render(<UserAvatar initials="JD" />);
        expect(getByTestId('mds-avatar').getAttribute('data-size')).toBe('md');

        rerender(<UserAvatar initials="JD" size="lg" />);
        expect(getByTestId('mds-avatar').getAttribute('data-size')).toBe('lg');
    });

    it('forwards a class name to the design system Avatar', () => {
        const {getByTestId} = render(<UserAvatar initials="JD" className="realuser" />);

        expect(getByTestId('mds-avatar').className).toBe('realuser');
    });
});
