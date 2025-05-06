import { dom } from "../../utils/dom.js";

export function createQueryParameters({ query, values = {}, onChange }) {
    if (!query || Object.keys(query).length === 0) return null;

    const container = dom.createElement(`
        <div class="query-parameters-section bg-white dark:bg-dark-800 rounded-lg space-y-4">
            <h3 class="text-sm font-medium text-slate-700 dark:text-dark-300">
                Query Parameters
            </h3>
            <div class="parameters-list space-y-3">
                ${Object.entries(query).map(([key, param]) => {
        // Use saved value if exists, otherwise use default
        const savedValue = values[key] !== undefined ? values[key] : param.default || '';

        return `
                        <div class="parameter-item">
                            <div class="param-header flex items-center gap-2 mb-1">
                                <code class="text-xs bg-slate-100 dark:bg-dark-700 px-1.5 py-0.5 rounded">
                                    ${key}
                                </code>
                                <div class="flex items-center gap-2">
                                    ${param.required ? `
                                        <span class="required-badge text-red-500 text-xs">Required</span>
                                    ` : ''}
                                    ${param.default ? `
                                        <span class="default-badge text-slate-500 dark:text-dark-400 text-xs">
                                            Default: ${param.default}
                                        </span>
                                    ` : ''}
                                </div>
                            </div>
                            <input
                                type="${param.type === 'number' ? 'number' : 'text'}"
                                class="query-param-input w-full p-2 text-sm bg-slate-50 dark:bg-dark-700 rounded-md border border-slate-200 dark:border-dark-600"
                                placeholder="${param.description || `Enter ${key} value`}"
                                data-param-type="query"
                                data-param-name="${key}"
                                value="${savedValue}"
                                ${param.required ? 'required' : ''}
                            />
                            ${param.description ? `
                                <p class="text-xs text-slate-500 dark:text-dark-500 mt-1">
                                    ${param.description}
                                </p>
                            ` : ''}
                        </div>
                    `;
    }).join('')}
            </div>
        </div>
    `);

    container.querySelectorAll('.query-param-input').forEach(input => {
        input.addEventListener('input', (e) => {
            const paramName = e.target.dataset.paramName;
            const paramValue = e.target.value.trim();
            onChange(paramName, paramValue);
        });
    });

    return container;
}
