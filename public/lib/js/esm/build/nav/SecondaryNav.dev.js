var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { Fragment, jsxDEV } from "react/jsx-dev-runtime";
/**
 * Secondary navigation tab bar, using the "nav-pill" design-system component.
 *
 * @module     core/nav/SecondaryNav
 * @copyright  2026 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { useEffect, useRef } from "react";
import { NavPill } from "@moodlehq/design-system";
import { requireAsync } from "@moodle/lms/core/amd";
const isNodeActive = /* @__PURE__ */ __name((node) => node.active || node.children.some(isNodeActive), "isNodeActive");
function DropdownItems({ items }) {
  return /* @__PURE__ */ jsxDEV(Fragment, { children: items.map((item) => /* @__PURE__ */ jsxDEV(
    "a",
    {
      className: `dropdown-item${item.active ? " active" : ""}`,
      href: item.href ?? "#",
      "aria-current": item.active ? "page" : void 0,
      role: "menuitem",
      children: item.text
    },
    item.key,
    false,
    {
      fileName: "public/lib/js/esm/src/nav/SecondaryNav.tsx",
      lineNumber: 74,
      columnNumber: 17
    },
    this
  )) }, void 0, false, {
    fileName: "public/lib/js/esm/src/nav/SecondaryNav.tsx",
    lineNumber: 72,
    columnNumber: 9
  }, this);
}
__name(DropdownItems, "DropdownItems");
function PillDropdownToggle({ label, selected, children }) {
  const classes = ["mds-nav-pill", "dropdown-toggle", selected ? "mds-nav-pill--selected" : null].filter(Boolean).join(" ");
  return /* @__PURE__ */ jsxDEV("div", { className: "dropdown", children: [
    /* @__PURE__ */ jsxDEV(
      "a",
      {
        href: "#",
        className: classes,
        role: "menuitem",
        "data-bs-toggle": "dropdown",
        "aria-haspopup": "true",
        "aria-expanded": "false",
        "aria-current": selected ? "page" : void 0,
        children: [
          selected && /* @__PURE__ */ jsxDEV("span", { className: "mds-nav-pill__indicator", "aria-hidden": "true" }, void 0, false, {
            fileName: "public/lib/js/esm/src/nav/SecondaryNav.tsx",
            lineNumber: 117,
            columnNumber: 30
          }, this),
          /* @__PURE__ */ jsxDEV("span", { className: "mds-nav-pill__label", children: label }, void 0, false, {
            fileName: "public/lib/js/esm/src/nav/SecondaryNav.tsx",
            lineNumber: 118,
            columnNumber: 17
          }, this)
        ]
      },
      void 0,
      true,
      {
        fileName: "public/lib/js/esm/src/nav/SecondaryNav.tsx",
        lineNumber: 108,
        columnNumber: 13
      },
      this
    ),
    children
  ] }, void 0, true, {
    fileName: "public/lib/js/esm/src/nav/SecondaryNav.tsx",
    lineNumber: 107,
    columnNumber: 9
  }, this);
}
__name(PillDropdownToggle, "PillDropdownToggle");
function TabPill({ node }) {
  const selected = isNodeActive(node);
  return /* @__PURE__ */ jsxDEV(
    "a",
    {
      href: node.href ?? "#",
      className: `mds-nav-pill${selected ? " active" : ""}`,
      role: "tab",
      "data-bs-toggle": "tab",
      "data-text": node.text,
      "data-disableactive": "true",
      "aria-selected": selected ? "true" : "false",
      tabIndex: selected ? 0 : -1,
      children: /* @__PURE__ */ jsxDEV("span", { className: "mds-nav-pill__label", children: node.text }, void 0, false, {
        fileName: "public/lib/js/esm/src/nav/SecondaryNav.tsx",
        lineNumber: 151,
        columnNumber: 13
      }, this)
    },
    void 0,
    false,
    {
      fileName: "public/lib/js/esm/src/nav/SecondaryNav.tsx",
      lineNumber: 141,
      columnNumber: 9
    },
    this
  );
}
__name(TabPill, "TabPill");
function SubmenuTrigger({ node }) {
  return /* @__PURE__ */ jsxDEV(PillDropdownToggle, { label: node.text, selected: isNodeActive(node), children: /* @__PURE__ */ jsxDEV("div", { className: "dropdown-menu", children: /* @__PURE__ */ jsxDEV(DropdownItems, { items: node.children }, void 0, false, {
    fileName: "public/lib/js/esm/src/nav/SecondaryNav.tsx",
    lineNumber: 168,
    columnNumber: 17
  }, this) }, void 0, false, {
    fileName: "public/lib/js/esm/src/nav/SecondaryNav.tsx",
    lineNumber: 167,
    columnNumber: 13
  }, this) }, void 0, false, {
    fileName: "public/lib/js/esm/src/nav/SecondaryNav.tsx",
    lineNumber: 166,
    columnNumber: 9
  }, this);
}
__name(SubmenuTrigger, "SubmenuTrigger");
const renderPill = /* @__PURE__ */ __name((item, istablist) => {
  if (item.showchildreninsubmenu && item.children.length > 0) {
    return /* @__PURE__ */ jsxDEV(SubmenuTrigger, { node: item }, void 0, false, {
      fileName: "public/lib/js/esm/src/nav/SecondaryNav.tsx",
      lineNumber: 184,
      columnNumber: 16
    });
  }
  if (istablist) {
    return /* @__PURE__ */ jsxDEV(TabPill, { node: item }, void 0, false, {
      fileName: "public/lib/js/esm/src/nav/SecondaryNav.tsx",
      lineNumber: 187,
      columnNumber: 16
    });
  }
  return /* @__PURE__ */ jsxDEV(NavPill, { label: item.text, href: item.href ?? "#", selected: isNodeActive(item) }, void 0, false, {
    fileName: "public/lib/js/esm/src/nav/SecondaryNav.tsx",
    lineNumber: 189,
    columnNumber: 12
  });
}, "renderPill");
function SecondaryNav({ items, morelabel, istablist }) {
  const visible = items.filter((item) => !item.forceintomoremenu);
  const overflow = items.filter((item) => item.forceintomoremenu);
  const menuRef = useRef(null);
  useEffect(() => {
    if (!istablist || !menuRef.current) {
      return void 0;
    }
    let cancelled = false;
    requireAsync("core/menu_navigation").then((menuNavigation) => {
      if (!cancelled && menuRef.current) {
        menuNavigation(menuRef.current);
      }
      return void 0;
    });
    return () => {
      cancelled = true;
    };
  }, [istablist]);
  return /* @__PURE__ */ jsxDEV("ul", { ref: menuRef, className: "nav more-nav", role: istablist ? "tablist" : "menubar", children: [
    visible.map((item) => /* @__PURE__ */ jsxDEV("li", { role: "none", className: "nav-item d-flex align-items-center", children: renderPill(item, istablist) }, item.key, false, {
      fileName: "public/lib/js/esm/src/nav/SecondaryNav.tsx",
      lineNumber: 232,
      columnNumber: 17
    }, this)),
    overflow.length > 0 && /* @__PURE__ */ jsxDEV("li", { role: "none", className: "nav-item d-flex align-items-center dropdownmoremenu", children: /* @__PURE__ */ jsxDEV(PillDropdownToggle, { label: morelabel, selected: overflow.some(isNodeActive), children: /* @__PURE__ */ jsxDEV("div", { className: "dropdown-menu dropdown-menu-start", "data-region": "moredropdown", children: /* @__PURE__ */ jsxDEV(DropdownItems, { items: overflow }, void 0, false, {
      fileName: "public/lib/js/esm/src/nav/SecondaryNav.tsx",
      lineNumber: 240,
      columnNumber: 29
    }, this) }, void 0, false, {
      fileName: "public/lib/js/esm/src/nav/SecondaryNav.tsx",
      lineNumber: 239,
      columnNumber: 25
    }, this) }, void 0, false, {
      fileName: "public/lib/js/esm/src/nav/SecondaryNav.tsx",
      lineNumber: 238,
      columnNumber: 21
    }, this) }, void 0, false, {
      fileName: "public/lib/js/esm/src/nav/SecondaryNav.tsx",
      lineNumber: 237,
      columnNumber: 17
    }, this)
  ] }, void 0, true, {
    fileName: "public/lib/js/esm/src/nav/SecondaryNav.tsx",
    lineNumber: 230,
    columnNumber: 9
  }, this);
}
__name(SecondaryNav, "SecondaryNav");
export {
  SecondaryNav as default
};
//# sourceMappingURL=SecondaryNav.dev.js.map
