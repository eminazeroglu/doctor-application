import { dom } from "../../utils/dom.js";

// JSON-u təmizləyən və formatlayan funksiya
function cleanAndFormatJSON(json) {
    if (typeof json !== 'string') {
        json = JSON.stringify(json);
    }

    try {
        // JSON-u parse edib yenidən format edirik
        const parsed = JSON.parse(json);
        json = JSON.stringify(parsed, null, 2);
    } catch (e) {
        console.error('JSON parsing failed:', e);
        return json;
    }

    // HTML teqlərini qaçış (escape) et
    json = json.replace(/</g, '&lt;').replace(/>/g, '&gt;');

    // Sintaksis vurğulamanı tətbiq edirik
    return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
        let cls = 'text-slate-600 dark:text-slate-300';

        if (/^"/.test(match)) {
            if (/:$/.test(match)) {
                // Açarlar (keys)
                cls = 'text-blue-600 dark:text-blue-400 font-semibold';
                match = match.replace(/\s+:$/, ':');
            } else {
                // String dəyərlər
                cls = 'text-green-600 dark:text-green-400';
            }
        } else if (/true|false/.test(match)) {
            // Boolean dəyərlər
            cls = 'text-purple-600 dark:text-purple-400';
        } else if (/null/.test(match)) {
            // Null dəyərlər
            cls = 'text-red-600 dark:text-red-400';
        } else if (/^-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?$/.test(match)) {
            // Rəqəmlər
            cls = 'text-orange-600 dark:text-orange-400';
        }

        return `<span class="${cls}">${match}</span>`;
    });
}

// Status koduna görə badge hazırlayan funksiya
function getStatusBadge(status) {
    let color, text;

    if (status >= 200 && status < 300) {
        color = 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100';
        text = 'Success';
    } else if (status >= 400 && status < 500) {
        color = 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100';
        text = 'Client Error';
    } else if (status >= 500) {
        color = 'bg-orange-100 text-orange-800 dark:bg-orange-800 dark:text-orange-100';
        text = 'Server Error';
    } else {
        color = 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-100';
        text = 'Info';
    }

    return `
        <div class="flex flex-1 items-center gap-2">
            <span class="status-code font-mono shrink-0 text-lg">
                ${status}
            </span>
            <span class="status-badge px-2 py-1 shrink-0 rounded-full text-xs font-medium ${color}">
                ${text}
            </span>
        </div>
    `;
}

export function createResponsePanel({ response }) {
    if (!response) {
        return dom.createElement(`
            <div class="empty-response h-2/3 flex items-center justify-center text-slate-500 dark:text-dark-400">
                <div class="text-center">
                    <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <p>Response will appear here</p>
                </div>
            </div>
        `);
    }

    const formattedResponse = cleanAndFormatJSON(response.data);
    const statusBadge = getStatusBadge(response.status);

    return dom.createElement(`
        <div class="response-container overflow-y-auto border dark:border-dark-700 rounded-lg">
            <div class="response-header p-4 border-b border-slate-200 dark:border-dark-700 sticky top-0 bg-white dark:bg-dark-800">
                <div class="header-content flex items-center justify-between">
                    ${statusBadge}
                    <div class="response-meta text-xs text-slate-500 dark:text-slate-400">
                        ${new Date().toLocaleTimeString('en-US', {hour12: false})}
                    </div>
                </div>

                <div class="header-content flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        ${response.error ? `
                            <span class="error-message text-red-600 dark:text-red-400 text-sm">
                                ${response.error}
                            </span>
                        ` : ''}
                    </div>
                </div>
            </div>
            <div class="response-body p-4 bg-slate-50 dark:bg-dark-700">
                <pre class="response-data font-mono text-sm overflow-x-auto leading-relaxed">${formattedResponse}</pre>
            </div>
            ${response.headers ? `
                <div class="response-headers p-4 border-t border-slate-200 dark:border-dark-700 bg-white dark:bg-dark-800">
                    <h4 class="text-sm font-medium text-slate-700 dark:text-dark-300 mb-2">Response Headers</h4>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        ${Object.entries(response.headers).map(([key, value]) => `
                            <div class="font-medium text-slate-600 dark:text-dark-400">${key}:</div>
                            <div class="text-slate-500 dark:text-dark-500">${value}</div>
                        `).join('')}
                    </div>
                </div>
            ` : ''}
        </div>
    `);
}
