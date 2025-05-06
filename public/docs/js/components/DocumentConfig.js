import { eventBus, EVENT_TYPES } from '../events.js';

export class DocumentConfig {
    constructor() {
        this.init();
    }

    init() {
        eventBus.on(EVENT_TYPES.DATA_LOADED, (data) => {
            this.render(data.config);
        });
    }

    render(config) {
        if (!config) return;

        const { info, servers, authorization } = config;

        // Update API info
        if (info) {
            document.querySelector('.api-title').textContent = info.title || '';
            document.querySelector('.api-description').textContent = info.description || '';
            document.querySelector('.version-value').textContent = info.version || '';
            document.querySelector('.auth-container').textContent = authorization || '';
        }

        // Update server URLs
        if (servers?.length) {
            const serverList = document.querySelector('.server-list');
            const serverElements = servers.map(server => `
                <div class="server-item flex gap-2 items-center bg-slate-50 dark:bg-dark-700 py-2 px-3 rounded-md">
                    ${server.description ? `
                        <p class="text-sm text-slate-600 dark:text-dark-400">${server.description}: </p>
                    ` : ''}
                    <code class="text-sm text-slate-800 dark:text-dark-100">${server.url}</code>
                </div>
            `).join('');

            serverList.innerHTML = serverElements;
        }
    }
}
