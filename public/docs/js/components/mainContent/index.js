import { eventBus, EVENT_TYPES } from '../../events.js';
import { createEndpointHeader } from './EndpointHeader.js';
import { createParameters } from './Parameters.js';
import { createRequestBody } from './RequestBody.js';
import { createResponses } from './Responses.js';

export class MainContent {
    constructor() {
        this.contentWrapper = document.querySelector('.content-wrapper');
        this.init();
    }

    init() {
        this.renderEmptyState();

        eventBus.on(EVENT_TYPES.ENDPOINT_SELECTED, (endpoint) => {
            this.renderEndpoint(endpoint);
        });
    }

    renderEmptyState() {
        this.contentWrapper.innerHTML = `
            <div class="empty-state flex py-10 items-center justify-center text-slate-500 dark:text-dark-400">
                Yan paneldən bir API ünvanı seçin
            </div>
        `;
    }

    // Path parametrlərini çıxarmaq üçün köməkçi funksiya
    extractPathParams(path) {
        // URL-dən {param} formatında olan bütün parametrləri tapırıq
        const paramMatches = path.match(/{([^}]+)}/g);
        if (!paramMatches) return null;

        // Hər tapılan parametr üçün bir obyekt yaradırıq
        const pathParams = {};
        paramMatches.forEach(match => {
            // {param} formatından param hissəsini götürürük
            const paramName = match.replace(/{|}/g, '');

            // Parametr haqqında məlumatları təyin edirik
            pathParams[paramName] = {
                type: 'string', // Default olaraq string
                description: `URL path parameter: ${paramName}`,
                required: true, // Path parametrləri həmişə məcburidir
                example: '', // Boş example
                in: 'path' // Parametrin növü
            };
        });

        return pathParams;
    }

    renderEndpoint(endpoint) {
        if (!endpoint) {
            this.renderEmptyState();
            return;
        }

        const { path, method, details } = endpoint;
        this.contentWrapper.innerHTML = '';

        // Path parametrlərini çıxarırıq
        const pathParams = this.extractPathParams(path);

        // Komponentləri yaradırıq və əlavə edirik
        const header = createEndpointHeader({
            path,
            method,
            details
        });

        // Parameters komponentinə həm path, həm də query parametrlərini ötürürük
        const parameters = createParameters({
            parameters: details.query || [],
            pathParams: pathParams
        });

        const requestBody = createRequestBody({
            body: details.formBody
        });

        const responses = createResponses({
            responses: details.responses
        });

        // Komponentləri DOM-a əlavə edirik
        this.contentWrapper.append(
            header,
            parameters,
            requestBody,
            responses
        );
    }
}
