var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { jsxDEV } from "react/jsx-dev-runtime";
/**
 * A user's avatar, rendered with the design system Avatar component.
 *
 * @module     core/UserAvatar
 * @copyright  2026 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { Avatar } from "@moodlehq/design-system";
const MAX_INITIALS = 2;
function UserAvatar({ imageSrc, initials, size = "md", ...rest }) {
  return /* @__PURE__ */ jsxDEV(
    Avatar,
    {
      size,
      imageSrc: imageSrc || void 0,
      initials: initials ? Array.from(initials).slice(0, MAX_INITIALS).join("") : void 0,
      ...rest
    },
    void 0,
    false,
    {
      fileName: "public/lib/js/esm/src/UserAvatar.tsx",
      lineNumber: 46,
      columnNumber: 9
    },
    this
  );
}
__name(UserAvatar, "UserAvatar");
export {
  UserAvatar as default
};
//# sourceMappingURL=UserAvatar.dev.js.map
