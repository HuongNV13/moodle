import{Avatar as t}from"@moodlehq/design-system";import{jsx as o}from"react/jsx-runtime";/**
 * A user's avatar, rendered with the design system Avatar component.
 *
 * @module     core/UserAvatar
 * @copyright  2026 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */const n=2;function s({imageSrc:a,initials:r,size:e="md",...i}){return o(t,{size:e,imageSrc:a||void 0,initials:r?Array.from(r).slice(0,n).join(""):void 0,...i})}export{s as default};
