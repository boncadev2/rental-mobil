import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import LogoutConfirmation from '@/Components/LogoutConfirmation';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

document.addEventListener('click', (event) => {
    const button = event.target.closest('button');

    if (! button || button.dataset.logoutConfirmationAction || ! ['Keluar', '↪ Keluar', 'Log Out'].includes(button.textContent.trim())) {
        return;
    }

    event.preventDefault();
    event.stopImmediatePropagation();
    window.dispatchEvent(new Event('logout-confirmation'));
}, true);

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.jsx`,
            import.meta.glob('./Pages/**/*.jsx'),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(<><App {...props} /><LogoutConfirmation /></>);
    },
    progress: {
        color: '#4B5563',
    },
});
