import { useCallback, useEffect, useRef, useState } from 'react';
import { APP } from '../config/app.config';
import { copyText } from '../utils/clipboard.util';

/** Copy helper that flips `copied` to true briefly so a button can show a check mark. */
export const useCopied = () => {
    const [copied, setCopied] = useState(false);
    const timer = useRef<number | undefined>(undefined);

    useEffect(() => () => window.clearTimeout(timer.current), []);

    const copy = useCallback(async (text: string) => {
        if (!(await copyText(text))) return;
        setCopied(true);
        window.clearTimeout(timer.current);
        timer.current = window.setTimeout(() => setCopied(false), APP.copiedFeedbackMs);
    }, []);

    return { copied, copy };
};
