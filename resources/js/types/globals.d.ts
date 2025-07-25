import type { route as routeFn } from 'ziggy-js';

declare global {
    const route: typeof routeFn;

    interface Window {
        ReactNativeWebView?: {
            postMessage: (message: string) => void;
        };
    }
}
