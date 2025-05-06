import { dom } from "../../utils/dom.js";

function isOptionalParameter(param) {
    return param.endsWith('?}');
}

// Parametr adını təmizləyən funksiya
function cleanParamName(param) {
    return param.replace(/{|}/g, '').replace('?', '');
}

export function createPathParameters({ path, values = {}, onChange }) {
    const paramMatches = path.match(/{[^}]+}/g);
    if (!paramMatches) return null;

    const container = dom.createElement(`
        <div class="path-parameters-section bg-white dark:bg-dark-800 rounded-lg space-y-4">
            <h3 class="text-sm font-medium text-slate-700 dark:text-dark-300">
                Path Parameters
            </h3>
            <div class="parameters-list space-y-3">
                ${paramMatches.map(match => {
        const isOptional = isOptionalParameter(match);
        const paramName = cleanParamName(match);
        const savedValue = values[paramName] || '';

        return `
                        <div class="parameter-item">
                            <div class="param-header flex items-center gap-2 mb-1">
                                <code class="text-xs bg-slate-100 dark:bg-dark-700 px-1.5 py-0.5 rounded">
                                    ${match}
                                </code>
                                ${!isOptional ? `
                                    <span class="required-badge text-red-500 text-xs">Required</span>
                                ` : `
                                    <span class="optional-badge text-blue-500 text-xs">Optional</span>
                                `}
                            </div>
                            <input
                                type="text"
                                class="path-param-input w-full p-2 text-sm bg-slate-50 dark:bg-dark-700 rounded-md border border-slate-200 dark:border-dark-600"
                                placeholder="Enter ${paramName} value"
                                data-param-type="path"
                                data-param-name="${paramName}"
                                data-optional="${isOptional}"
                                value="${savedValue}"
                            />
                        </div>
                    `;
    }).join('')}
            </div>
        </div>
    `);

    container.querySelectorAll('.path-param-input').forEach(input => {
        input.addEventListener('input', (e) => {
            const paramName = e.target.dataset.paramName;
            const paramValue = e.target.value.trim();
            onChange(paramName, paramValue);
        });
    });

    return container;
}
