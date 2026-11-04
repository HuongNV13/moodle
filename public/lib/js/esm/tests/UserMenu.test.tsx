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
 * Tests for core/UserMenu, the design-system-backed user menu in the navbar.
 *
 * @copyright  2026 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import type {ReactElement, ReactNode} from 'react';
import {fireEvent, render, screen, within} from '@testing-library/react';
import UserMenu, {type UserMenuProps} from '@moodle/lms/core/UserMenu';

// @moodlehq/design-system is ESM only, so Jest cannot resolve it from a CommonJS test bundle.
// These stand-ins carry the DOM contract of the real components that UserMenu relies on (the roles,
// the open and close behaviour, the trigger being cloned), so the tests are about UserMenu's own
// mapping of Moodle's menu data onto them, not about the design system's internals. Keyboard
// handling, focus return and positioning belong to the design system and are not re-tested here.
jest.mock('@moodlehq/design-system', () => {
    const {cloneElement: clone, useState: state} = jest.requireActual('react');
    return {
        Avatar: ({size, imageSrc, initials}: {size?: string; imageSrc?: string; initials?: string}) => (
            <span className="mds-avatar" data-size={size} data-image-src={imageSrc} data-initials={initials} />
        ),
        Dropdown: ({trigger, children}: {trigger: ReactElement; children: ReactNode}) => {
            const [open, setOpen] = state(false);
            return (
                <div className="mds-dropdown">
                    {clone(trigger, {
                        'aria-haspopup': 'menu',
                        'aria-expanded': open,
                        onClick: () => setOpen(!open),
                    })}
                    {open && <div role="menu" aria-label="user menu panel">{children}</div>}
                </div>
            );
        },
        DropdownItemAction: ({label, href, startIcon, className, ...rest}: {
            label: string; href: string; startIcon?: ReactNode; className?: string; [key: string]: unknown;
        }) => (
            <a role="menuitem" href={href} className={['mds-dropdown-item', className].filter(Boolean).join(' ')} {...rest}>
                {startIcon}{label}
            </a>
        ),
        DropdownItemCustom: ({children, className, ...rest}: {children: ReactNode; className?: string}) => (
            <div role="presentation" className={className} {...rest}>{children}</div>
        ),
        DropdownItemDivider: () => <div role="separator" />,
        DropdownItemHeader: ({label, className, ...rest}: {label: string; className?: string}) => (
            <div className={className} {...rest}>{label}</div>
        ),
        DropdownItemExpandable: ({label, children}: {label: string; children: ReactNode}) => {
            const [open, setOpen] = state(false);
            return (
                <>
                    <button role="menuitem" aria-haspopup="menu" aria-expanded={open} onClick={() => setOpen(!open)}>
                        {label}
                    </button>
                    {open && <div role="menu" aria-label={label}>{children}</div>}
                </>
            );
        },
    };
}, {virtual: true});

const PROPS: UserMenuProps = {
    userfirstname: 'Jane',
    label: 'User menu',
    avatars: [{size: 'md', initials: 'JD', className: 'current'}],
    metadata: [],
    items: [
        {type: 'link', title: 'Profile', url: 'https://example.com/user/profile.php', iconhtml: '', classes: [], attributes: []},
        {type: 'divider'},
        {type: 'link', title: 'Log out', url: 'https://example.com/login/logout.php', iconhtml: '', classes: [], attributes: []},
    ],
};

/**
 * Render the menu and open it.
 *
 * @param props Props to override.
 * @returns The render result.
 */
const renderOpen = (props: Partial<UserMenuProps> = {}) => {
    const result = render(<UserMenu {...PROPS} {...props} />);
    fireEvent.click(screen.getByRole('button'));
    return result;
};

describe('core/UserMenu', () => {
    it('does not show the items until the trigger is used', () => {
        render(<UserMenu {...PROPS} />);

        expect(screen.queryByRole('menuitem')).toBeNull();
        expect(screen.getByRole('button').getAttribute('aria-expanded')).toBe('false');
    });

    it('names the trigger with its visible first name and the menu label', () => {
        render(<UserMenu {...PROPS} />);

        // The visible text is part of the accessible name (WCAG 2.5.3), with the label as the rest of it.
        expect(screen.getByRole('button', {name: 'Jane User menu'})).toBeTruthy();
    });

    it('names the trigger with just the label when there is no first name', () => {
        render(<UserMenu {...PROPS} userfirstname="" />);

        expect(screen.getByRole('button', {name: 'User menu'})).toBeTruthy();
    });

    it('renders an avatar in the trigger for each user, with the class the theme styles', () => {
        const avatars: UserMenuProps['avatars'] = [
            {size: 'xs', initials: 'JD', className: 'current'},
            {size: 'md', imageSrc: 'https://example.com/pic.png', className: 'realuser'},
        ];
        const {container} = render(<UserMenu {...PROPS} avatars={avatars} />);

        const rendered = container.querySelectorAll('.userbutton .avatars .avatar');
        expect(rendered).toHaveLength(2);
        expect(rendered[0].className).toBe('avatar current');
        expect(rendered[0].querySelector('.mds-avatar')?.getAttribute('data-size')).toBe('xs');
        expect(rendered[0].querySelector('.mds-avatar')?.getAttribute('data-initials')).toBe('JD');
        expect(rendered[1].className).toBe('avatar realuser');
        expect(rendered[1].querySelector('.mds-avatar')?.getAttribute('data-image-src')).toBe('https://example.com/pic.png');
    });

    it('renders the metadata beside the avatar, including the html it carries', () => {
        const {container} = render(<UserMenu {...PROPS} metadata={[
            {content: '<span class="value">2</span> failed login attempts', classes: 'loginfailures'},
        ]} />);

        const meta = container.querySelector('.usertext .meta.loginfailures');
        expect(meta?.innerHTML).toBe('<span class="value">2</span> failed login attempts');
        // It is visible text in the trigger, so it is part of the accessible name too.
        expect(screen.getByRole('button').textContent).toContain('2 failed login attempts');
    });

    it('opens the menu with the items in the order they were given', () => {
        renderOpen();

        expect(screen.getAllByRole('menuitem').map((item) => item.textContent)).toEqual(['Profile', 'Log out']);
        expect(screen.getByRole('button').getAttribute('aria-expanded')).toBe('true');
        expect(screen.getAllByRole('separator')).toHaveLength(1);
    });

    it('renders a link with its url', () => {
        renderOpen();

        expect(screen.getByRole('menuitem', {name: 'Log out'}).getAttribute('href')).toBe('https://example.com/login/logout.php');
    });

    it('passes the classes and attributes that plugins added to a link through', () => {
        renderOpen({items: [{
            type: 'link',
            title: 'Custom',
            url: 'https://example.com/',
            iconhtml: '',
            classes: ['fw-bold', 'my-plugin-class'],
            attributes: [{name: 'data-action', value: 'my-plugin'}, {name: 'data-id', value: '7'}],
        }]});

        const link = screen.getByRole('menuitem', {name: 'Custom'});
        expect(link.classList.contains('fw-bold')).toBe(true);
        expect(link.classList.contains('my-plugin-class')).toBe(true);
        expect(link.classList.contains('mds-dropdown-item')).toBe(true);
        expect(link.getAttribute('data-action')).toBe('my-plugin');
        expect(link.getAttribute('data-id')).toBe('7');
    });

    it('renders the icon html of a link before its title', () => {
        renderOpen({items: [{
            type: 'link',
            title: 'Grades',
            url: 'https://example.com/grades',
            iconhtml: '<i class="icon fa fa-table fa-fw" aria-hidden="true"></i>',
            classes: [],
            attributes: [],
        }]});

        const link = screen.getByRole('menuitem', {name: 'Grades'});
        expect(link.querySelector('i.icon.fa-table')).not.toBeNull();
    });

    it('renders headers and text, which are not menu items', () => {
        renderOpen({items: [
            {type: 'header', title: 'Section', classes: ['small'], attributes: []},
            {type: 'text', content: 'Some text', classes: ['text-muted'], attributes: [{name: 'data-x', value: '1'}]},
            {type: 'link', title: 'Profile', url: 'https://example.com/', iconhtml: '', classes: [], attributes: []},
        ]});

        expect(screen.getByText('Section').className).toContain('small');
        const text = screen.getByText('Some text');
        expect(text.closest('[role="presentation"]')?.className).toContain('text-muted');
        expect(text.closest('[data-x]')?.getAttribute('data-x')).toBe('1');
        expect(screen.getAllByRole('menuitem')).toHaveLength(1);
    });

    it('opens a submenu with its links, marking the active one and passing attributes through', () => {
        renderOpen({items: [
            {
                type: 'submenu',
                title: 'Language',
                iconhtml: '',
                items: [
                    {title: 'English', url: '#', active: true, attributes: []},
                    {
                        title: 'Español',
                        url: 'https://example.com/?lang=es',
                        active: false,
                        attributes: [{name: 'lang', value: 'es'}],
                    },
                ],
            },
            {
                type: 'link',
                title: 'Log out',
                url: 'https://example.com/login/logout.php',
                iconhtml: '',
                classes: [],
                attributes: [],
            },
        ]});

        expect(screen.queryByRole('menuitem', {name: 'English'})).toBeNull();
        fireEvent.click(screen.getByRole('menuitem', {name: 'Language'}));

        const submenu = screen.getByRole('menu', {name: 'Language'});
        const english = within(submenu).getByRole('menuitem', {name: 'English'});
        const spanish = within(submenu).getByRole('menuitem', {name: 'Español'});
        expect(english.getAttribute('aria-current')).toBe('true');
        expect(spanish.hasAttribute('aria-current')).toBe(false);
        expect(spanish.getAttribute('lang')).toBe('es');
        expect(spanish.getAttribute('href')).toBe('https://example.com/?lang=es');
    });

    it('marks the active submenu link with a check, and keeps the space for it on the other links', () => {
        renderOpen({items: [{
            type: 'submenu',
            title: 'Language',
            iconhtml: '',
            items: [
                {title: 'English', url: '#', active: true, attributes: []},
                {title: 'Español', url: 'https://example.com/?lang=es', active: false, attributes: []},
            ],
        }]});
        fireEvent.click(screen.getByRole('menuitem', {name: 'Language'}));

        const english = screen.getByRole('menuitem', {name: 'English'});
        const spanish = screen.getByRole('menuitem', {name: 'Español'});
        // The check is decorative: the state is already conveyed by aria-current.
        expect(english.querySelector('i.fa-check')?.getAttribute('aria-hidden')).toBe('true');
        // An empty icon holds the same space on the others, so the titles line up.
        expect(spanish.querySelector('i')).not.toBeNull();
        expect(spanish.querySelector('i.fa-check')).toBeNull();
    });
});
