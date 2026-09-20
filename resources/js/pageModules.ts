import type { ComponentType } from 'react';

export const pageModules = import.meta.glob<{ default: ComponentType }>([
    './Pages/**/*.tsx',
    '!./Pages/**/*.test.tsx',
]);
