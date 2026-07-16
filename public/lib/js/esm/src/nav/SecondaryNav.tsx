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
 * Secondary navigation tab bar, using the "nav-pill" design-system component.
 *
 * @module     core/nav/SecondaryNav
 * @copyright  2026 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {useEffect, useRef, type ReactNode} from 'react';
import {NavPill} from '@moodlehq/design-system';
import {requireAsync} from '@moodle/lms/core/amd';

export interface SecondaryNavNode {
    key: string;
    text: string;
    href: string | null;
    active: boolean;
    forceintomoremenu: boolean;
    showchildreninsubmenu: boolean;
    children: SecondaryNavNode[];
}

export interface SecondaryNavProps {
    items: SecondaryNavNode[];
    morelabel: string;
    istablist: boolean;
}

/**
 * Check whether a node, or any of its descendants, is the currently active node.
 *
 * Used to highlight a submenu's trigger pill when the active page is one of its children.
 *
 * @param node The node to check.
 * @returns True if the node or one of its descendants is active.
 */
const isNodeActive = (node: SecondaryNavNode): boolean =>
    node.active || node.children.some(isNodeActive);

/**
 * Plain dropdown-item links for the "More" overflow menu and submenu dropdowns.
 *
 * @moodlehq/design-system has no menu/dropdown component yet, so this reuses Bootstrap's
 * dropdown-item markup (matching legacy moremenu_children.mustache) rather than NavPill, which
 * is designed for the top-level tab bar only.
 *
 * Note: an overflow item that itself has children (showchildreninsubmenu) is rendered as a
 * single flat link to its own href, ignoring its children — nested dropdowns-within-a-dropdown
 * are not supported.
 *
 * @param props Component props.
 * @param props.items The nodes to render as dropdown items.
 * @returns The rendered dropdown items.
 */
function DropdownItems({items}: {items: SecondaryNavNode[]}) {
    return (
        <>
            {items.map((item) => (
                <a
                    key={item.key}
                    className={`dropdown-item${item.active ? ' active' : ''}`}
                    href={item.href ?? '#'}
                    aria-current={item.active ? 'page' : undefined}
                    role="menuitem"
                >
                    {item.text}
                </a>
            ))}
        </>
    );
}

/**
 * A dropdown-toggle styled to match NavPill's own markup (indicator dot + label span), for
 * visual consistency with the pills either side of it. @moodlehq/design-system has no
 * menu/dropdown-trigger component, so this reuses NavPill's CSS classes directly on a plain
 * Bootstrap dropdown-toggle anchor rather than its React component (which doesn't support
 * data-bs-toggle="dropdown"/role="menuitem").
 *
 * @param props Component props.
 * @param props.label The visible label for the toggle.
 * @param props.selected Whether one of the dropdown's own items is currently active.
 * @param props.children The dropdown menu to render alongside the toggle.
 * @returns The rendered dropdown toggle and menu.
 */
function PillDropdownToggle({label, selected, children}: {label: string; selected: boolean; children: ReactNode}) {
    const classes = ['mds-nav-pill', 'dropdown-toggle', selected ? 'mds-nav-pill--selected' : null]
        .filter(Boolean)
        .join(' ');

    return (
        <div className="dropdown">
            <a
                href="#"
                className={classes}
                role="menuitem"
                data-bs-toggle="dropdown"
                aria-haspopup="true"
                aria-expanded="false"
                aria-current={selected ? 'page' : undefined}
            >
                {selected && <span className="mds-nav-pill__indicator" aria-hidden="true" />}
                <span className="mds-nav-pill__label">{label}</span>
            </a>
            {children}
        </div>
    );
}

/**
 * A leaf pill rendered for an `istablist` secondary nav (e.g. admin/search.php's site-admin
 * category tabs): these switch between in-page `.tab-pane`s via Bootstrap's `data-bs-toggle="tab"`
 * data-attribute API rather than navigating to a new page, so they need real ARIA tab semantics
 * (role="tab"/aria-selected + a roving tabindex) that the NavPill React component deliberately
 * doesn't support (it always sets role/aria-current for plain page-navigation links). Styled with
 * NavPill's own CSS classes for visual consistency.
 *
 * @param props Component props.
 * @param props.node The node to render as a tab.
 * @returns The rendered tab.
 */
function TabPill({node}: {node: SecondaryNavNode}) {
    const selected = isNodeActive(node);

    return (
        <a
            href={node.href ?? '#'}
            className={`mds-nav-pill${selected ? ' active' : ''}`}
            role="tab"
            data-bs-toggle="tab"
            data-text={node.text}
            data-disableactive="true"
            aria-selected={selected ? 'true' : 'false'}
            tabIndex={selected ? 0 : -1}
        >
            <span className="mds-nav-pill__label">{node.text}</span>
        </a>
    );
}

/**
 * A dropdown trigger for a node whose children should render in a submenu (e.g. single-activity
 * format's "Course"/"Activity" groups).
 *
 * @param props Component props.
 * @param props.node The node whose children should render in a submenu.
 * @returns The rendered submenu trigger and dropdown.
 */
function SubmenuTrigger({node}: {node: SecondaryNavNode}) {
    return (
        <PillDropdownToggle label={node.text} selected={isNodeActive(node)}>
            <div className="dropdown-menu">
                <DropdownItems items={node.children} />
            </div>
        </PillDropdownToggle>
    );
}

/**
 * Render the appropriate pill for a visible top-level node: a submenu trigger, an ARIA tab
 * (istablist), or a plain NavPill page-navigation link.
 *
 * @param item The node to render.
 * @param istablist Whether the secondary nav is rendered as an ARIA tablist.
 * @returns The rendered pill.
 */
const renderPill = (item: SecondaryNavNode, istablist: boolean) => {
    if (item.showchildreninsubmenu && item.children.length > 0) {
        return <SubmenuTrigger node={item} />;
    }
    if (istablist) {
        return <TabPill node={item} />;
    }
    return <NavPill label={item.text} href={item.href ?? '#'} selected={isNodeActive(item)} />;
};

/**
 * Root component for the secondary navigation tab bar.
 *
 * @param props Component props.
 * @param props.items The top-level secondary navigation nodes.
 * @param props.morelabel The localised label for the "More" overflow dropdown.
 * @param props.istablist Whether the secondary nav is rendered as an ARIA tablist.
 * @returns The rendered secondary navigation tab bar.
 */
export default function SecondaryNav({items, morelabel, istablist}: SecondaryNavProps) {
    const visible = items.filter((item) => !item.forceintomoremenu);
    const overflow = items.filter((item) => item.forceintomoremenu);
    const menuRef = useRef<HTMLUListElement>(null);

    // Restore keyboard arrow-key/Home/End navigation for the tablist, matching the legacy
    // moremenu.js behaviour, by reusing the existing core/menu_navigation AMD module rather than
    // re-implementing roving-tabindex handling. Bootstrap's own data-attribute API (triggered by
    // data-bs-toggle="tab" on TabPill) handles the actual panel-switching independently of this.
    useEffect(() => {
        if (!istablist || !menuRef.current) {
            return undefined;
        }

        let cancelled = false;

        requireAsync<(menu: HTMLElement) => void>('core/menu_navigation').then((menuNavigation) => {
            if (!cancelled && menuRef.current) {
                menuNavigation(menuRef.current);
            }
            return undefined;
        });

        return () => {
            cancelled = true;
        };
    }, [istablist]);

    return (
        <ul ref={menuRef} className="nav more-nav" role={istablist ? 'tablist' : 'menubar'}>
            {visible.map((item) => (
                <li key={item.key} role="none" className="nav-item d-flex align-items-center">
                    {renderPill(item, istablist)}
                </li>
            ))}
            {overflow.length > 0 && (
                <li role="none" className="nav-item d-flex align-items-center dropdownmoremenu">
                    <PillDropdownToggle label={morelabel} selected={overflow.some(isNodeActive)}>
                        <div className="dropdown-menu dropdown-menu-start" data-region="moredropdown">
                            <DropdownItems items={overflow} />
                        </div>
                    </PillDropdownToggle>
                </li>
            )}
        </ul>
    );
}
