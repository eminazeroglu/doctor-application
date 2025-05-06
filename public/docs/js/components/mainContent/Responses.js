import { dom } from "../../utils/dom.js";
import { getStatusColorClass } from "../../utils/colors.js";

// This function handles the JSON formatting and syntax highlighting
function formatJSON(json) {
    // First, let's ensure we're working with a string
    if (typeof json !== 'string') {
        // When converting object to string, we use null and 0 for spacing
        // This gives us a clean slate to work with
        json = JSON.stringify(json, null, 0);
    }

    try {
        // Parse the JSON string to ensure valid JSON
        const parsed = JSON.parse(json);

        // Convert to a compact string first (no whitespace)
        let compactJson = JSON.stringify(parsed);

        // Now let's format it with proper indentation
        let formattedJson = '';
        let indentLevel = 0;
        let inString = false;

        // Iterate through each character to build properly formatted JSON
        for (let i = 0; i < compactJson.length; i++) {
            const char = compactJson.charAt(i);

            // Handle string content
            if (char === '"' && compactJson.charAt(i - 1) !== '\\') {
                inString = !inString;
                formattedJson += char;
                continue;
            }

            if (inString) {
                formattedJson += char;
                continue;
            }

            // Handle structural characters
            switch (char) {
                case '{':
                case '[':
                    formattedJson += char;
                    indentLevel++;
                    formattedJson += '\n' + '  '.repeat(indentLevel);
                    break;

                case '}':
                case ']':
                    indentLevel--;
                    formattedJson += '\n' + '  '.repeat(indentLevel) + char;
                    break;

                case ',':
                    formattedJson += char + '\n' + '  '.repeat(indentLevel);
                    break;

                case ':':
                    formattedJson += ': ';
                    break;

                default:
                    formattedJson += char;
            }
        }

        // Apply syntax highlighting with proper spacing
        return formattedJson.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
            let cls = 'text-slate-600 dark:text-slate-300';

            if (/^"/.test(match)) {
                if (/:$/.test(match)) {
                    // Keys - remove any extra whitespace before colon
                    cls = 'text-blue-600 dark:text-blue-400 font-semibold';
                    match = match.replace(/"\s*:$/, '":');
                } else {
                    // String values
                    cls = 'text-green-600 dark:text-green-400';
                }
            } else if (/true|false/.test(match)) {
                cls = 'text-purple-600 dark:text-purple-400';
            } else if (/null/.test(match)) {
                cls = 'text-red-600 dark:text-red-400';
            } else if (/^-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?$/.test(match)) {
                cls = 'text-orange-600 dark:text-orange-400';
            }

            return `<span class="${cls}">${match}</span>`;
        });
    } catch (e) {
        console.error('JSON parsing failed:', e);
        return json;
    }
}

export function createResponses({ responses = {} }) {
    return dom.createElement(`
        <div class="responses-section bg-white dark:bg-dark-800 rounded-lg p-6 shadow-sm">
            <h2 class="section-title text-lg font-semibold mb-4 text-slate-900 dark:text-dark-50">Responses</h2>
            <div class="responses-list space-y-4">
                ${Object.entries(responses).map(([code, response]) => `
                    <div class="response-item border-b border-slate-200 dark:border-dark-700 last:border-0 pb-4">
                        <div class="response-header flex items-center space-x-2 mb-2">
                            <span class="status-code ${getStatusColorClass(code)} px-2 py-1 rounded text-sm font-bold">
                                ${code}
                            </span>
                            <span class="response-description text-slate-600 dark:text-dark-400">
                                ${response.description || ''}
                            </span>
                        </div>

                        ${response ? `
                            <div class="response-example bg-slate-50 dark:bg-dark-700 rounded-lg">
                                <div class="flex items-center justify-between px-4 py-2 border-b border-slate-200 dark:border-dark-600">
                                    <span class="text-xs font-medium text-slate-500 dark:text-dark-400">Response</span>
                                </div>
                                <pre class="p-4 text-sm overflow-x-auto font-mono leading-relaxed"><code class="language-json">${formatJSON(response)}</code></pre>
                            </div>
                        ` : ''}
                    </div>
                `).join('')}
            </div>
        </div>
    `);
}
