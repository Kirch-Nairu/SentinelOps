import '../css/app.css';
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import { installAutomaticSync } from './offline/sync';

createInertiaApp({
  resolve: name => {
    const pages = import.meta.glob('./Pages/**/*.tsx', { eager: true }) as Record<string, { default: React.ComponentType<any> }>;
    return pages[`./Pages/${name}.tsx`];
  },
  setup({ el, App, props }) { createRoot(el).render(<App {...props} />); },
  progress: { color: '#8bc4ad' },
});

if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => void navigator.serviceWorker.register('/sw.js'));
}
installAutomaticSync();
