var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { jsxDEV } from "react/jsx-dev-runtime";
/**
 * The user menu in the navbar: the user's avatar and first name, which open a menu of links.
 *
 * @module     core/UserMenu
 * @copyright  2026 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { forwardRef } from "react";
import {
  Dropdown,
  DropdownItemAction,
  DropdownItemCustom,
  DropdownItemDivider,
  DropdownItemExpandable,
  DropdownItemHeader
} from "@moodlehq/design-system";
import UserAvatar from "@moodle/lms/core/UserAvatar";
const toProps = /* @__PURE__ */ __name((attributes) => Object.fromEntries(attributes.map(({ name, value }) => [name, value])), "toProps");
const Trigger = forwardRef(/* @__PURE__ */ __name(function Trigger2({ userfirstname, label, avatars, metadata, ...rest }, ref) {
  return /* @__PURE__ */ jsxDEV("button", { ref, type: "button", className: "btn dropdown-toggle", ...rest, children: [
    /* @__PURE__ */ jsxDEV("span", { className: "userbutton", children: [
      metadata.map(({ content, classes }, index) => /* @__PURE__ */ jsxDEV("span", { className: "usertext d-flex me-3", children: /* @__PURE__ */ jsxDEV("span", { className: `meta d-flex ${classes}`, dangerouslySetInnerHTML: { __html: content } }, void 0, false, {
        fileName: "public/lib/js/esm/src/UserMenu.tsx",
        lineNumber: 130,
        columnNumber: 25
      }, this) }, index, false, {
        fileName: "public/lib/js/esm/src/UserMenu.tsx",
        lineNumber: 129,
        columnNumber: 21
      }, this)),
      /* @__PURE__ */ jsxDEV("span", { className: "avatars", children: avatars.map(({ className, ...avatar }, index) => /* @__PURE__ */ jsxDEV("span", { className: `avatar ${className}`, children: /* @__PURE__ */ jsxDEV(UserAvatar, { ...avatar }, void 0, false, {
        fileName: "public/lib/js/esm/src/UserMenu.tsx",
        lineNumber: 136,
        columnNumber: 29
      }, this) }, index, false, {
        fileName: "public/lib/js/esm/src/UserMenu.tsx",
        lineNumber: 135,
        columnNumber: 25
      }, this)) }, void 0, false, {
        fileName: "public/lib/js/esm/src/UserMenu.tsx",
        lineNumber: 133,
        columnNumber: 17
      }, this)
    ] }, void 0, true, {
      fileName: "public/lib/js/esm/src/UserMenu.tsx",
      lineNumber: 127,
      columnNumber: 13
    }, this),
    userfirstname && /* @__PURE__ */ jsxDEV("span", { className: "userfirstname d-none d-xl-block", dir: "auto", children: userfirstname }, void 0, false, {
      fileName: "public/lib/js/esm/src/UserMenu.tsx",
      lineNumber: 141,
      columnNumber: 31
    }, this),
    /* @__PURE__ */ jsxDEV("span", { className: "visually-hidden", children: label }, void 0, false, {
      fileName: "public/lib/js/esm/src/UserMenu.tsx",
      lineNumber: 142,
      columnNumber: 13
    }, this)
  ] }, void 0, true, {
    fileName: "public/lib/js/esm/src/UserMenu.tsx",
    lineNumber: 126,
    columnNumber: 9
  }, this);
}, "Trigger"));
const renderIcon = /* @__PURE__ */ __name((iconhtml) => iconhtml ? /* @__PURE__ */ jsxDEV("i", { "aria-hidden": "true", dangerouslySetInnerHTML: { __html: iconhtml } }, void 0, false, {
  fileName: "public/lib/js/esm/src/UserMenu.tsx",
  lineNumber: 154,
  columnNumber: 16
}) : void 0, "renderIcon");
const renderItem = /* @__PURE__ */ __name((item, index) => {
  switch (item.type) {
    case "link":
      return /* @__PURE__ */ jsxDEV(
        DropdownItemAction,
        {
          label: item.title,
          href: item.url,
          startIcon: renderIcon(item.iconhtml),
          className: item.classes.join(" ") || void 0,
          ...toProps(item.attributes)
        },
        index,
        false,
        {
          fileName: "public/lib/js/esm/src/UserMenu.tsx",
          lineNumber: 170,
          columnNumber: 17
        }
      );
    case "header":
      return /* @__PURE__ */ jsxDEV(
        DropdownItemHeader,
        {
          label: item.title,
          className: item.classes.join(" ") || void 0,
          ...toProps(item.attributes)
        },
        index,
        false,
        {
          fileName: "public/lib/js/esm/src/UserMenu.tsx",
          lineNumber: 181,
          columnNumber: 17
        }
      );
    case "text":
      return /* @__PURE__ */ jsxDEV(
        DropdownItemCustom,
        {
          className: item.classes.join(" ") || void 0,
          ...toProps(item.attributes),
          children: item.content
        },
        index,
        false,
        {
          fileName: "public/lib/js/esm/src/UserMenu.tsx",
          lineNumber: 190,
          columnNumber: 17
        }
      );
    case "divider":
      return /* @__PURE__ */ jsxDEV(DropdownItemDivider, {}, index, false, {
        fileName: "public/lib/js/esm/src/UserMenu.tsx",
        lineNumber: 199,
        columnNumber: 20
      });
    case "submenu":
      return /* @__PURE__ */ jsxDEV(DropdownItemExpandable, { label: item.title, children: item.items.map((link, linkIndex) => /* @__PURE__ */ jsxDEV(
        DropdownItemAction,
        {
          label: link.title,
          href: link.url,
          startIcon: /* @__PURE__ */ jsxDEV("i", { className: link.active ? "fa-solid fa-check" : void 0, "aria-hidden": "true" }, void 0, false, {
            fileName: "public/lib/js/esm/src/UserMenu.tsx",
            lineNumber: 208,
            columnNumber: 40
          }),
          "aria-current": link.active ? "true" : void 0,
          ...toProps(link.attributes)
        },
        linkIndex,
        false,
        {
          fileName: "public/lib/js/esm/src/UserMenu.tsx",
          lineNumber: 204,
          columnNumber: 25
        }
      )) }, index, false, {
        fileName: "public/lib/js/esm/src/UserMenu.tsx",
        lineNumber: 202,
        columnNumber: 17
      });
    default:
      return null;
  }
}, "renderItem");
function UserMenu({ userfirstname, label, avatars, metadata, items }) {
  return /* @__PURE__ */ jsxDEV(
    Dropdown,
    {
      placement: "bottom-end",
      trigger: /* @__PURE__ */ jsxDEV(Trigger, { userfirstname, label, avatars, metadata }, void 0, false, {
        fileName: "public/lib/js/esm/src/UserMenu.tsx",
        lineNumber: 230,
        columnNumber: 22
      }, this),
      children: items.map(renderItem)
    },
    void 0,
    false,
    {
      fileName: "public/lib/js/esm/src/UserMenu.tsx",
      lineNumber: 228,
      columnNumber: 9
    },
    this
  );
}
__name(UserMenu, "UserMenu");
export {
  UserMenu as default
};
//# sourceMappingURL=UserMenu.dev.js.map
