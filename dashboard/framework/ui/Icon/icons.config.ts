import { faCheck, faCopy, faGlobe } from '@fortawesome/free-solid-svg-icons';

export const ICONS = {
    globe: faGlobe,
    copy: faCopy,
    check: faCheck,
} as const;

export type IconName = keyof typeof ICONS;
