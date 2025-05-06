import { store, actions } from '../../state.js';
import { eventBus, EVENT_TYPES } from '../../events.js';
import { api } from '../../utils/api.js';
import { tokenUtils } from '../../utils/token.js';
import { dom } from '../../utils/dom.js';
import { createHeaderFields } from './HeaderFields.js';
import { createPathParameters } from './PathParameters.js';
import { createQueryParameters } from './QueryParameters.js';
import { createRequestBodyField } from './RequestBodyField.js';
import { createResponsePanel } from './ResponsePanel.js';

export class TestPanel {
    constructor() {
        // Əsas panel elementinin referansı
        this.panelContainer = document.querySelector('.test-panel');

        // Əsas state-lər
        this.endpoint = null;
        this.response = null;
        this.isLoading = false;

        // State-lərin ilkin dəyərlərini təyin edirik
        this.initializeStates();

        // Event dinləyicilərini aktivləşdiririk
        this.init();
    }

    initializeStates() {
        // Sorğu parametrlərinin ilkin dəyərlərini təyin edirik
        this.headers = [{ key: '', value: '' }];
        this.pathParams = {};
        this.queryParams = {};
        this.requestBody = {};

        // Render-lər arası dəyərləri saxlamaq üçün
        this.inputValues = {
            path: {},    // Path parametrləri
            query: {},   // Query parametrləri
            headers: []  // Headers
        };
    }

    init() {
        // Endpoint seçimi dəyişdikdə
        eventBus.on(EVENT_TYPES.ENDPOINT_SELECTED, (endpoint) => {
            this.resetForNewEndpoint(endpoint);
        });

        // Token dəyişikliyinə reaksiya
        window.addEventListener('storage', () => {
            if (this.endpoint?.details?.authorization) {
                this.render();
            }
        });
    }

    resetForNewEndpoint(endpoint) {
        // Yeni endpoint-i mənimsədirik
        this.endpoint = endpoint;

        // Bütün state-ləri sıfırlayırıq
        this.response = null;
        this.pathParams = {};
        this.inputValues.path = {};

        // Query parametrlərini yeniləyirik
        this.initializeQueryParams();

        // Formu sıfırlayırıq
        this.resetForm();

        // Paneli yenidən render edirik
        this.render();
    }

    initializeQueryParams() {
        // Query parametrlərini sıfırlayırıq
        this.queryParams = {};
        this.inputValues.query = {};

        // Default dəyərləri təyin edirik
        if (this.endpoint?.details?.query) {
            Object.entries(this.endpoint.details.query).forEach(([key, param]) => {
                if (param.default !== undefined && param.default !== '') {
                    this.queryParams[key] = param.default;
                    this.inputValues.query[key] = param.default;
                }
            });
        }
    }

    resetForm() {
        // Header-ləri sıfırlayırıq
        this.headers = [{ key: '', value: '' }];

        // Request body-ni yeniləyirik
        if (this.endpoint?.details?.formBody) {
            if (this.endpoint.details.formData) {
                // FormData halında boş obyekt yaradırıq - sonra form ilə doldurulacaq
                this.requestBody = {};
            } else {
                // JSON halında, default dəyərləri ön-doldururuq
                const values = this.endpoint.details.formBody;
                this.requestBody = Object.keys(values).reduce((acc, key) => {
                    acc[key] = values[key].default || '';
                    return acc;
                }, {});
            }
        } else {
            this.requestBody = {};
        }
    }

    renderLoadingButton() {
        return `
            <button type="submit"
                class="submit-request w-full bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded-md transition-colors relative ${
            this.isLoading ? 'opacity-75 cursor-not-allowed' : ''
        }"
                ${this.isLoading ? 'disabled' : ''}>
                <span class="${this.isLoading ? 'invisible' : ''}">
                    Send Request
                </span>
                ${this.isLoading ? `
                    <div class="absolute inset-0 flex items-center justify-center gap-2">
                        <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-sm">Sending...</span>
                    </div>
                ` : ''}
            </button>
        `;
    }

    render() {
        if (!this.endpoint) {
            this.panelContainer.innerHTML = '';
            return;
        }

        const hasToken = tokenUtils.hasToken();
        const method = this.endpoint.method.toUpperCase();

        this.panelContainer.innerHTML = `
            ${this.endpoint.details.authorization ? `
                <div class="auth-status p-4 border-b border-slate-200 dark:border-dark-700">
                    <div class="status-wrapper flex items-center justify-between">
                        <span class="auth-text text-sm font-medium text-slate-700 dark:text-dark-300">
                            Authentication Required
                        </span>
                        ${hasToken ? `
                            <span class="token-status bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300 text-xs px-2 py-1 rounded">
                                Token Available
                            </span>
                        ` : `
                            <span class="token-status bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300 text-xs px-2 py-1 rounded">
                                Token Required
                            </span>
                        `}
                    </div>
                </div>
            ` : ''}
            <div class="request-form-wrapper border-b border-slate-200 dark:border-dark-700">
                <form class="request-form p-4 space-y-4">
                    <div class="headers-section"></div>
                    <div class="path-params-section"></div>
                    <div class="query-params-section"></div>
                    <div class="request-body-section"></div>
                    ${this.renderLoadingButton()}
                </form>
            </div>
            <div class="response-section p-6"></div>
        `;

        const sections = {
            headers: this.panelContainer.querySelector('.headers-section'),
            pathParams: this.panelContainer.querySelector('.path-params-section'),
            queryParams: this.panelContainer.querySelector('.query-params-section'),
            requestBody: this.panelContainer.querySelector('.request-body-section'),
            response: this.panelContainer.querySelector('.response-section')
        };

        this.renderSections(sections, method);

        const form = this.panelContainer.querySelector('.request-form');
        form.addEventListener('submit', (e) => this.handleSubmit(e));
    }

    renderSections(sections, method) {
        // Header section-nu render edirik
        const headerFields = createHeaderFields({
            headers: this.headers,
            onChange: (headers) => {
                this.headers = headers;
                actions.setHeaders(headers);
            }
        });
        sections.headers.appendChild(headerFields);

        // Path parametrləri section-nu render edirik
        const pathParameters = createPathParameters({
            path: this.endpoint.path,
            values: this.inputValues.path,
            onChange: (name, value) => {
                this.pathParams[name] = value;
                this.inputValues.path[name] = value;
            }
        });
        if (pathParameters) {
            sections.pathParams.appendChild(pathParameters);
        }

        // Query parametrləri section-nu render edirik
        if (this.endpoint.details.query) {
            const queryParameters = createQueryParameters({
                query: this.endpoint.details.query,
                values: this.inputValues.query,
                onChange: (key, value) => {
                    this.queryParams[key] = value;
                    this.inputValues.query[key] = value;
                }
            });
            if (queryParameters) {
                sections.queryParams.appendChild(queryParameters);
            }
        }

        // Request body section-nu render edirik
        if (!['GET', 'HEAD'].includes(method)) {
            // formData dəyişənini təyin edirik
            const isFormData = this.endpoint.details.formData === true;

            // İlkin dəyər - formData halında obyekt, əks halda JSON string
            const initialValue = isFormData ?
                this.requestBody :
                JSON.stringify(this.requestBody, null, 2);

            const requestBodyField = createRequestBodyField({
                value: initialValue,
                onChange: (value) => {
                    if (isFormData) {
                        // FormData üçün obyekti birbaşa mənimsəyirik
                        this.requestBody = value;
                    } else {
                        // JSON formatı üçün parse edirik
                        try {
                            this.requestBody = typeof value === 'string'
                                ? JSON.parse(value)
                                : value;
                        } catch (error) {
                            console.error('Invalid JSON:', error);
                        }
                    }
                    actions.setRequestBody(this.requestBody);
                },
                formData: isFormData,
                formFields: this.endpoint.details.formBody
            });
            sections.requestBody.appendChild(requestBodyField);
        }

        // Response section-nu render edirik
        const responsePanel = createResponsePanel({
            response: this.response
        });
        sections.response.appendChild(responsePanel);
    }

    async handleSubmit(e) {
        e.preventDefault();

        // Loading state-i aktivləşdiririk
        this.isLoading = true;
        this.render();

        try {
            // URL-i hazırlayırıq
            const url = this.buildRequestUrl();

            // Header-ləri hazırlayırıq
            const headersObj = this.prepareHeaders();

            // Request body-ni və formData flagını hazırlayırıq
            const method = this.endpoint.method.toUpperCase();
            let requestBody = null;
            const isFormData = this.endpoint.details.formData === true;

            // Non-GET/HEAD methodları üçün body əlavə edirik
            if (!['GET', 'HEAD'].includes(method)) {
                requestBody = this.requestBody;

                // formData flag-i true olduqda, Content-Type header silinirsə əmin olaq
                if (isFormData && headersObj['Content-Type']) {
                    delete headersObj['Content-Type'];
                }
            }

            // Sorğunu göndəririk
            const response = await api.sendRequest({
                endpoint: { ...this.endpoint, path: url },
                headers: headersObj,
                body: requestBody,
                isFormData: isFormData
            });

            // Cavabı saxlayırıq
            this.response = response;
            eventBus.emit(EVENT_TYPES.RESPONSE_RECEIVED, response);

        } catch (error) {
            // Xətanı emal edirik
            this.response = {
                error: error.message,
                status: 'error'
            };
            eventBus.emit(EVENT_TYPES.ERROR_OCCURRED, {
                message: error.message,
                details: error
            });
        } finally {
            // Loading state-i söndürürük
            this.isLoading = false;
            this.render();
        }
    }

    buildRequestUrl() {
        let url = this.endpoint.path;
        const segments = url.split('/').map(segment => {
            if (segment.startsWith('{') && segment.endsWith('}')) {
                const paramName = segment.replace(/{|}/g, '').replace('?', '');
                const isOptional = segment.includes('?');

                if (this.pathParams[paramName]) {
                    return encodeURIComponent(this.pathParams[paramName]);
                }

                if (isOptional) {
                    return null;
                }

                throw new Error(`Missing required path parameter: ${paramName}`);
            }
            return segment;
        });

        url = segments.filter(Boolean).join('/');

        // Query parametrlərini əlavə edirik
        const queryParams = [];
        if (this.endpoint.details.query) {
            Object.entries(this.endpoint.details.query).forEach(([key, param]) => {
                const value = this.queryParams[key] ?? param.default;
                if ((value !== undefined && value !== '') || param.required) {
                    queryParams.push(`${encodeURIComponent(key)}=${encodeURIComponent(value || '')}`);
                }
            });

            if (queryParams.length > 0) {
                url += `?${queryParams.join('&')}`;
            }
        }

        return url;
    }

    prepareHeaders() {
        return this.headers.reduce((acc, header) => {
            if (header.key && header.value) {
                acc[header.key] = header.value;
            }
            return acc;
        }, {});
    }
}
