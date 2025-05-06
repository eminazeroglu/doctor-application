import { tokenUtils } from './token.js';
import { ENV } from '../config/env.js';

/**
 * UUID v4 generasiya edən köməkçi funksiya
 * Hər sorğuda unikal identifikator yaratmaq üçün istifadə olunur
 */
function generateUUIDv4() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        const r = Math.random() * 16 | 0;
        const v = c === 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
}

export const api = {
    /**
     * API sənədləşdirməsini əldə edir
     * @returns {Promise<Object>} API sənədləşdirməsi
     */
    async getDocumentation() {
        const response = await fetch(ENV.documentationURL);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    },

    /**
     * API sorğusu göndərir
     * @param {Object} options Sorğu parametrləri
     * @param {Object} options.endpoint Endpoint məlumatları (path, method)
     * @param {Object} [options.headers={}] HTTP başlıqları
     * @param {Object|null} [options.body=null] Sorğu gövdəsi
     * @param {boolean} [options.isFormData=false] FormData formatında göndəriləcəkmi
     * @returns {Promise<Object>} API cavabı
     */
    async sendRequest({ endpoint, headers = {}, body = null, isFormData = false }) {
        const myUUID = generateUUIDv4();
        const token = tokenUtils.getToken();

        // Default başlıqları təyin edirik
        const defaultHeaders = {
            'Accept': 'application/json',
            'X-API-Nonce': myUUID,
            'X-API-Timestamp': Math.floor(Date.now() / 1000),
            ...(token ? { 'Authorization': `Bearer ${token}` } : {})
        };

        // Əgər FormData deyilsə, Content-Type əlavə edirik
        if (!isFormData) {
            defaultHeaders['Content-Type'] = 'application/json';
        }

        // URL-i hazırlayırıq
        const path = endpoint.path.startsWith('/') ? endpoint.path.slice(1) : endpoint.path;
        const url = `${ENV.baseURL}${path}`;

        try {
            const method = endpoint.method.toUpperCase();

            // Sorğu parametrlərini hazırlayırıq
            const options = {
                method,
                headers: {
                    ...defaultHeaders,
                    ...headers
                }
            };

            // Body-ni əlavə edirik (yalnız GET və HEAD olmayan sorğular üçün)
            if (body && !['GET', 'HEAD'].includes(method)) {
                if (isFormData) {
                    // FormData obyekti yaradırıq
                    const formData = new FormData();

                    /**
                     * Obyekti FormData-ya rekursiv şəkildə çevirir
                     * @param {Object} obj Çevriləcək obyekt
                     * @param {string} [prefix=''] İç-içə sahələrdə path prefiksi
                     */
                    function processObject(obj, prefix = '') {
                        for (const [key, value] of Object.entries(obj)) {
                            const fieldName = prefix ? `${prefix}[${key}]` : key;

                            // Null/undefined dəyərləri keçirik
                            if (value === null || value === undefined) {
                                continue;
                            }

                            if (Array.isArray(value)) {
                                // Array tipli dəyərlər
                                if (value.length > 0 && value[0] instanceof File) {
                                    // Fayl massivi
                                    value.forEach(file => {
                                        formData.append(`${fieldName}[]`, file, file.name);
                                    });
                                } else {
                                    // Normal massiv
                                    value.forEach((item, index) => {
                                        if (typeof item === 'object' && item !== null && !(item instanceof File)) {
                                            // İç-içə obyektlər üçün
                                            processObject(item, `${fieldName}[${index}]`);
                                        } else {
                                            // Sadə dəyərlər üçün
                                            formData.append(`${fieldName}[]`, item);
                                        }
                                    });
                                }
                            } else if (value instanceof File) {
                                // Tək fayl
                                formData.append(fieldName, value, value.name);
                            } else if (typeof value === 'object' && value !== null) {
                                // İç-içə obyektlər (location[coordinates][latitude] kimi)
                                processObject(value, fieldName);
                            } else if (typeof value === 'boolean') {
                                // Boolean dəyərlər (0/1 formatında göndərilir)
                                formData.append(fieldName, value ? '1' : '0');
                            } else {
                                // Sadə dəyərlər (string, number və s.)
                                formData.append(fieldName, value);
                            }
                        }
                    }

                    // Obyekti FormData-ya çeviririk
                    processObject(body);
                    options.body = formData;
                } else {
                    // JSON formatında göndəririk
                    options.body = JSON.stringify(body);
                }
            }

            // Sorğunu göndəririk
            const response = await fetch(url, options);

            // Cavabı JSON formatında alırıq
            let responseData;
            try {
                responseData = await response.json();
            } catch (error) {
                // JSON parse xətası olduqda
                responseData = {
                    message: 'Invalid JSON response',
                    rawResponse: await response.text()
                };
            }

            // Standartlaşdırılmış cavab obyekti yaradırıq
            const result = {
                status: response.status,
                data: responseData,
                headers: Object.fromEntries(response.headers.entries())
            };

            // Əgər cavab uğurlu deyilsə, xəta məlumatlarını əlavə edirik
            if (!response.ok) {
                if (response.status === 401) {
                    // 401 (Unauthorized) cavabı olduqda, tokeni silirik
                    tokenUtils.removeToken();
                }
                result.error = responseData.message || 'Unknown error occurred';
            }

            return result;

        } catch (error) {
            // Şəbəkə xətası və ya digər istisna hallarında
            return {
                status: 500,
                error: error.message,
                data: null
            };
        }
    }
};
