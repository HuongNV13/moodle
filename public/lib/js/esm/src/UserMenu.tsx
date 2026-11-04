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
 * The user menu in the navbar: the user's avatar and first name, which open a menu of links.
 *
 * @module     core/UserMenu
 * @copyright  2026 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {forwardRef, type ButtonHTMLAttributes, type ReactNode} from 'react';
import {
    Dropdown,
    DropdownItemAction,
    DropdownItemCustom,
    DropdownItemDivider,
    DropdownItemExpandable,
    DropdownItemHeader,
} from '@moodlehq/design-system';
import UserAvatar, {type UserAvatarProps} from '@moodle/lms/core/UserAvatar';

export interface UserMenuAttribute {
    name: string;
    value: string;
}

export interface UserMenuLinkItem {
    type: 'link';
    title: string;
    url: string;
    /** The item's icon, already rendered by Moodle's icon system, or an empty string for none. */
    iconhtml: string;
    classes: string[];
    attributes: UserMenuAttribute[];
}

export interface UserMenuHeaderItem {
    type: 'header';
    title: string;
    classes: string[];
    attributes: UserMenuAttribute[];
}

export interface UserMenuTextItem {
    type: 'text';
    content: string;
    classes: string[];
    attributes: UserMenuAttribute[];
}

export interface UserMenuDividerItem {
    type: 'divider';
}

export interface UserMenuSubmenuLink {
    title: string;
    url: string;
    active: boolean;
    attributes: UserMenuAttribute[];
}

export interface UserMenuSubmenuItem {
    type: 'submenu';
    title: string;
    iconhtml: string;
    items: UserMenuSubmenuLink[];
}

export type UserMenuItem =
    UserMenuLinkItem | UserMenuHeaderItem | UserMenuTextItem | UserMenuDividerItem | UserMenuSubmenuItem;

export interface UserMenuAvatar extends Pick<UserAvatarProps, 'size' | 'imageSrc' | 'initials'> {
    /** The class the theme styles the avatar's wrapper with, e.g. "current" or "realuser". */
    className: string;
}

export interface UserMenuMetadata {
    /** HTML, e.g. "You are logged in as ...", or a count of failed logins. */
    content: string;
    classes: string;
}

export interface UserMenuProps {
    /** The first name shown beside the avatar, or an empty string for none. */
    userfirstname: string;
    /** What the menu is called, e.g. "User menu". It is read out as part of the trigger's name. */
    label: string;
    avatars: UserMenuAvatar[];
    metadata: UserMenuMetadata[];
    items: UserMenuItem[];
}

type TriggerProps = ButtonHTMLAttributes<HTMLButtonElement> &
    Pick<UserMenuProps, 'userfirstname' | 'label' | 'avatars' | 'metadata'>;

/**
 * Turn the attributes plugins added to an item into props.
 *
 * @param attributes The attributes, as a list of names and values.
 * @returns The attributes as an object.
 */
const toProps = (attributes: UserMenuAttribute[]): Record<string, string> =>
    Object.fromEntries(attributes.map(({name, value}) => [name, value]));

/**
 * The button that opens the menu.
 */
const Trigger = forwardRef<HTMLButtonElement, TriggerProps>(function Trigger(
    {userfirstname, label, avatars, metadata, ...rest},
    ref
) {
    return (
        <button ref={ref} type="button" className="btn dropdown-toggle" {...rest}>
            <span className="userbutton">
                {metadata.map(({content, classes}, index) => (
                    <span className="usertext d-flex me-3" key={index}>
                        <span className={`meta d-flex ${classes}`} dangerouslySetInnerHTML={{__html: content}} />
                    </span>
                ))}
                <span className="avatars">
                    {avatars.map(({className, ...avatar}, index) => (
                        <span className={`avatar ${className}`} key={index}>
                            <UserAvatar {...avatar} />
                        </span>
                    ))}
                </span>
            </span>
            {userfirstname && <span className="userfirstname d-none d-xl-block" dir="auto">{userfirstname}</span>}
            <span className="visually-hidden">{label}</span>
        </button>
    );
});

/**
 * Wrap the icon HTML Moodle rendered in the element the design system expects for an item's icon.
 *
 * @param iconhtml The icon HTML, or an empty string for none.
 * @returns The icon element, or undefined when there is no icon.
 */
const renderIcon = (iconhtml: string) =>
    iconhtml ? <i aria-hidden="true" dangerouslySetInnerHTML={{__html: iconhtml}} /> : undefined;

/**
 * Render one item of the menu.
 *
 * The links in a submenu are choices, such as a language. The active one has a check before its title, and the
 * others have an empty icon of the same size, so that the titles line up.
 *
 * @param item The item.
 * @param index Its position in the menu.
 * @returns The rendered item.
 */
const renderItem = (item: UserMenuItem, index: number): ReactNode => {
    switch (item.type) {
        case 'link':
            return (
                <DropdownItemAction
                    key={index}
                    label={item.title}
                    href={item.url}
                    startIcon={renderIcon(item.iconhtml)}
                    className={item.classes.join(' ') || undefined}
                    {...toProps(item.attributes)}
                />
            );
        case 'header':
            return (
                <DropdownItemHeader
                    key={index}
                    label={item.title}
                    className={item.classes.join(' ') || undefined}
                    {...toProps(item.attributes)}
                />
            );
        case 'text':
            return (
                <DropdownItemCustom
                    key={index}
                    className={item.classes.join(' ') || undefined}
                    {...toProps(item.attributes)}
                >
                    {item.content}
                </DropdownItemCustom>
            );
        case 'divider':
            return <DropdownItemDivider key={index} />;
        case 'submenu':
            return (
                <DropdownItemExpandable key={index} label={item.title}>
                    {item.items.map((link, linkIndex) => (
                        <DropdownItemAction
                            key={linkIndex}
                            label={link.title}
                            href={link.url}
                            startIcon={<i className={link.active ? 'fa-solid fa-check' : undefined} aria-hidden="true" />}
                            aria-current={link.active ? 'true' : undefined}
                            {...toProps(link.attributes)}
                        />
                    ))}
                </DropdownItemExpandable>
            );
        default:
            return null;
    }
};

/**
 * Render the user menu.
 *
 * @param props Component props.
 * @returns The rendered user menu.
 */
export default function UserMenu({userfirstname, label, avatars, metadata, items}: UserMenuProps) {
    return (
        <Dropdown
            placement="bottom-end"
            trigger={<Trigger userfirstname={userfirstname} label={label} avatars={avatars} metadata={metadata} />}
        >
            {items.map(renderItem)}
        </Dropdown>
    );
}
