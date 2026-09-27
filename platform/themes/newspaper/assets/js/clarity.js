import Clarity from '@microsoft/clarity';

const projectId = 'vt8mo0ns8b';

if (typeof window !== 'undefined' && typeof document !== 'undefined' && !window.__gznClarityInitialized) {
    window.__gznClarityInitialized = true;
    Clarity.init(projectId);
}
