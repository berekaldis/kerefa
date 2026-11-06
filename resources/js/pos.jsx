import React from 'react';
import { createRoot } from 'react-dom/client';
import PosApp from './pos/PosApp';

document.addEventListener('DOMContentLoaded', () => {
    const rootElement = document.getElementById('pos-root');
    if (!rootElement) return;

    let initialData = {};
    const dataElement = document.getElementById('pos-initial-state');
    if (dataElement) {
        try {
            initialData = JSON.parse(dataElement.textContent || '{}');
        } catch (e) {
            console.error('Failed to parse initial POS state JSON', e);
        }
    }

    const root = createRoot(rootElement);
    root.render(
        <React.StrictMode>
            <PosApp initialData={initialData} />
        </React.StrictMode>
    );
});
