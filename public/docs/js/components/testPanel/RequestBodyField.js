import { dom } from "../../utils/dom.js";

export function createRequestBodyField({ value, onChange, formData = false, formFields = null }) {
    // Əgər formData true deyilsə və ya formFields yoxdursa, standart JSON redaktoru göstəririk
    if (!formData || !formFields) {
        const container = dom.createElement(`
            <div class="request-body-container space-y-3">
                <div class="body-title flex justify-between items-center">
                    <label class="text-sm font-medium text-slate-700 dark:text-dark-300">
                        Request Body (JSON)
                    </label>
                </div>
                <textarea
                    class="body-content w-full p-3 text-sm font-mono bg-slate-50 dark:bg-dark-700 rounded-md border border-slate-300 dark:border-dark-600"
                    rows="8"
                    placeholder="Enter JSON body"
                >${typeof value === 'string' ? value : JSON.stringify(value, null, 2)}</textarea>
            </div>
        `);

        const textarea = container.querySelector('.body-content');
        textarea.addEventListener('input', (e) => onChange(e.target.value));

        return container;
    }

    // FormData true olduqda, Postman stilində form yaradırıq
    const container = dom.createElement(`
        <div class="request-body-container space-y-3">
            <div class="body-title flex justify-between items-center mb-4">
                <label class="text-sm font-medium text-slate-700 dark:text-dark-300">
                    Form Data
                </label>
                <button type="button" class="add-field-btn text-sm text-blue-500 hover:text-blue-600 dark:text-blue-400 dark:hover:text-blue-300">
                    + Add Field
                </button>
            </div>
            <div class="form-fields space-y-2">
                <div class="form-table bg-white dark:bg-dark-800 border border-slate-200 dark:border-dark-700 rounded-md overflow-hidden">
                    <table class="w-full border-collapse">
                        <thead class="bg-slate-100 dark:bg-dark-700">
                            <tr>
                                <th class="w-10 px-3 py-3 text-left border-b border-slate-200 dark:border-dark-600"></th>
                                <th class="text-left px-3 py-3 text-sm font-medium text-slate-600 dark:text-dark-300 border-b border-slate-200 dark:border-dark-600">Key</th>
                                <th class="w-32 text-left px-3 py-3 text-sm font-medium text-slate-600 dark:text-dark-300 border-b border-slate-200 dark:border-dark-600">Type</th>
                                <th class="text-left px-3 py-3 text-sm font-medium text-slate-600 dark:text-dark-300 border-b border-slate-200 dark:border-dark-600">Value</th>
                                <th class="w-10 px-3 py-3 border-b border-slate-200 dark:border-dark-600"></th>
                            </tr>
                        </thead>
                        <tbody class="form-fields-body">
                            <!-- Dynamic fields will be added here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `);

    const formFieldsBody = container.querySelector('.form-fields-body');
    const addFieldBtn = container.querySelector('.add-field-btn');

    // Form dəyərlərini saxlayacaq obyekt
    const formValues = value && typeof value === 'object' ? {...value} : {};

    // Checkbox-ların vəziyyətini izləmək üçün obyekt
    const checkedFields = {};

    // Sahələri izləmək üçün Map
    const fieldMap = new Map();

    // Sahənin array tipində olub-olmadığını yoxlayır
    function isArrayField(fieldName, fieldConfig) {
        return fieldName.endsWith('[]') || (fieldConfig && fieldConfig.isArray === true);
    }

    // Array sahəsinin adını alır ([] olmadan)
    function getArrayBaseName(fieldName) {
        return fieldName.endsWith('[]') ? fieldName.slice(0, -2) : fieldName;
    }

    // Sahənin əlavə edilib-edilmədiyini yoxlayır
    function isFieldAdded(fieldName) {
        // Əgər sahə artıq əlavə edilibsə, true qaytarırıq
        return fieldMap.has(fieldName);
    }

    // Field əlavə etmək üçün funksiya
    function addFieldRow(fieldName, fieldConfig, fieldValue = null, isChecked = true) {
        // Əgər field artıq əlavə edilibsə, onu təmizləyirik
        if (isFieldAdded(fieldName)) {
            const existingRow = fieldMap.get(fieldName);
            if (existingRow && existingRow.parentNode) {
                existingRow.remove();
            }

            // Sahəni formValues-dən silirik
            clearFieldValue(fieldName);
        }

        const isArrayField = fieldConfig.isArray || fieldName.endsWith('[]');
        const isNestedField = fieldName.includes('[') && !isArrayField;
        const isFileField = fieldConfig.type === 'file';
        const isBooleanField = fieldConfig.type === 'boolean';
        const isSelectField = fieldConfig.type === 'select';

        // Checkbox vəziyyətini yeniləyirik - ilkin olaraq həmişə true olsun
        checkedFields[fieldName] = isChecked;

        // Sahənin tipini təyin edirik
        let fieldType = 'Text';
        if (isFileField) {
            fieldType = 'File';
        } else if (fieldConfig.type === 'textarea') {
            fieldType = 'Text';
        } else if (isBooleanField) {
            fieldType = 'Text';
        } else if (isSelectField) {
            fieldType = 'Select';
        }

        // Default dəyəri təyin edirik
        if (fieldValue === null) {
            if (isBooleanField) {
                fieldValue = fieldConfig.default || '0';
            } else if (isSelectField) {
                fieldValue = fieldConfig.default || '';
            } else if (!isFileField) {
                fieldValue = fieldConfig.default || '';
            }
        }

        // Custom field (yeni əlavə olunan sahə) və ya default field
        const isCustomField = fieldName.startsWith('custom_field_');

        // Array sahələri üçün xüsusi işləmə
        if (isArrayField && !isFileField) {
            // Array sahəsi üçün xüsusi row yaradırıq
            const row = createArrayRow(fieldName, fieldConfig, fieldValue);
            formFieldsBody.appendChild(row);
            fieldMap.set(fieldName, row);
            return row;
        }

        // Normal sahələr üçün standart row yaradırıq
        const row = dom.createElement(`
            <tr class="form-field-row border-t border-slate-200 dark:border-dark-700 hover:bg-slate-50 dark:hover:bg-dark-800" data-field-name="${fieldName}">
                <td class="px-3 py-3 border-r border-slate-200 dark:border-dark-700">
                    <input type="checkbox" class="field-checkbox h-4 w-4" ${isChecked ? 'checked' : ''}>
                </td>
                <td class="px-3 py-3 border-r border-slate-200 dark:border-dark-700">
                    <input type="text" class="field-key w-full bg-transparent border-0 p-0" value="${fieldName}" ${!isCustomField ? 'readonly' : ''}>
                </td>
                <td class="px-3 py-3 border-r border-slate-200 dark:border-dark-700">
                    <div class="field-type">
                        <span class="text-sm">${fieldType}</span>
                    </div>
                </td>
                <td class="px-3 py-3 border-r border-slate-200 dark:border-dark-700">
                    ${createFieldInput(fieldName, fieldConfig, fieldValue)}
                </td>
                <td class="px-3 py-3 text-center">
                    <button type="button" class="delete-field text-red-500 hover:text-red-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </td>
            </tr>
        `);

        // Input elementinə event listener əlavə edirik
        const inputElement = row.querySelector('input[type="text"].field-value, input[type="file"], textarea, select.field-value');
        const keyElement = row.querySelector('input.field-key');
        const checkboxElement = row.querySelector('.field-checkbox');

        // Key elementinə event listener əlavə edirik (custom fieldlər üçün)
        if (keyElement && !keyElement.readOnly) {
            keyElement.addEventListener('input', (e) => {
                const oldKey = fieldName;
                const newKey = e.target.value;

                // Əgər açar dəyişibsə
                if (oldKey !== newKey) {
                    // Dəyəri köçürürük
                    const currentValue = formValues[oldKey];

                    // Köhnə açarı silirik
                    delete formValues[oldKey];
                    delete checkedFields[oldKey];
                    fieldMap.delete(oldKey);

                    // Yeni açarı əlavə edirik
                    formValues[newKey] = currentValue;
                    checkedFields[newKey] = checkboxElement.checked;
                    fieldMap.set(newKey, row);

                    // Data attributunu yeniləyirik
                    row.dataset.fieldName = newKey;

                    // Sahə adını yeniləyirik
                    fieldName = newKey;

                    // Dəyişikliyi bildiririk
                    onChange(formValues);
                }
            });
        }

        if (inputElement) {
            if (isFileField) {
                inputElement.addEventListener('change', (e) => {
                    if (isArrayField) {
                        // Çoxlu fayl seçimi
                        const files = Array.from(e.target.files);
                        updateFormValue(fieldName, files, checkboxElement.checked);
                    } else {
                        // Tək fayl seçimi
                        const file = e.target.files.length > 0 ? e.target.files[0] : null;
                        updateFormValue(fieldName, file, checkboxElement.checked);
                    }
                });
            } else if (isBooleanField) {
                inputElement.addEventListener('input', (e) => {
                    const value = e.target.value;
                    updateFormValue(fieldName, value, checkboxElement.checked);
                });
            } else if (isSelectField) {
                // Select inputu üçün change eventini dinləyirik
                const selectElement = row.querySelector('select.field-value');
                if (selectElement) {
                    selectElement.addEventListener('change', (e) => {
                        updateFormValue(fieldName, e.target.value, checkboxElement.checked);
                    });

                    // İlkin seçimi əsasında dəyəri təyin edirik
                    if (fieldValue) {
                        updateFormValue(fieldName, fieldValue, checkboxElement.checked);
                    }
                }
            } else {
                inputElement.addEventListener('input', (e) => {
                    updateFormValue(fieldName, e.target.value, checkboxElement.checked);
                });
            }
        }

        // Checkbox dəyişikliyi
        checkboxElement.addEventListener('change', (e) => {
            const isChecked = e.target.checked;
            checkedFields[fieldName] = isChecked;

            let currentValue;

            // Mövcud dəyəri alırıq
            if (isFileField && inputElement?.files && inputElement.files.length) {
                if (isArrayField) {
                    currentValue = Array.from(inputElement.files);
                } else {
                    currentValue = inputElement.files[0];
                }
            } else if (isSelectField) {
                const selectElement = row.querySelector('select.field-value');
                currentValue = selectElement ? selectElement.value : '';
            } else if (isBooleanField) {
                currentValue = inputElement ? inputElement.value : '';
            } else {
                currentValue = inputElement ? inputElement.value : '';
            }

            updateFormValue(fieldName, currentValue, isChecked);
        });

        // Silmə düyməsinə event listener əlavə edirik
        row.querySelector('.delete-field').addEventListener('click', () => {
            row.remove();
            fieldMap.delete(fieldName);
            delete checkedFields[fieldName];
            updateFormValue(fieldName, null, false);
        });

        formFieldsBody.appendChild(row);
        fieldMap.set(fieldName, row);

        // İlkin dəyəri təyin edirik
        if (fieldValue !== null) {
            updateFormValue(fieldName, fieldValue, isChecked);
        }

        return row;
    }

    // Array sahələri üçün xüsusi row yaratma
    function createArrayRow(fieldName, fieldConfig, fieldValue) {
        const arrayBaseName = getArrayBaseName(fieldName);
        const isArrayTypeField = true;

        // Array marker-i əlavə olunmuş formda row yaradırıq
        const row = dom.createElement(`
            <tr class="form-field-row array-field border-t border-slate-200 dark:border-dark-700 hover:bg-slate-50 dark:hover:bg-dark-800" data-field-name="${fieldName}">
                <td class="px-3 py-3 border-r border-slate-200 dark:border-dark-700">
                    <input type="checkbox" class="field-checkbox h-4 w-4" checked>
                </td>
                <td class="px-3 py-3 border-r border-slate-200 dark:border-dark-700">
                    <div class="flex items-center">
                        <span class="field-key">${fieldName}</span>
                        <span class="ml-2 text-xs bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 px-1.5 py-0.5 rounded">Array</span>
                    </div>
                </td>
                <td class="px-3 py-3 border-r border-slate-200 dark:border-dark-700">
                    <div class="field-type">
                        <span class="text-sm">${fieldConfig.type || 'Text'}</span>
                    </div>
                </td>
                <td class="px-3 py-3 border-r border-slate-200 dark:border-dark-700">
                    <div class="array-container space-y-2">
                        <div class="array-inputs space-y-2">
                            <!-- Array items will be added here -->
                        </div>
                        <button type="button" class="add-array-item text-xs bg-blue-50 dark:bg-blue-900 text-blue-600 dark:text-blue-400 px-2 py-1 rounded">
                            + Add Item
                        </button>
                    </div>
                </td>
                <td class="px-3 py-3 text-center">
                    <button type="button" class="delete-field text-red-500 hover:text-red-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </td>
            </tr>
        `);

        // Elementi sahə "map"-inə əlavə edirik
        fieldMap.set(fieldName, row);

        const arrayContainer = row.querySelector('.array-inputs');
        const addArrayItemBtn = row.querySelector('.add-array-item');
        const checkboxElement = row.querySelector('.field-checkbox');

        // Array dəyərlərini saxlamaq üçün
        let arrayValues = [];

        // Array elementi dəyişdikdə updateForValue funksiyasını çağıran yardımçı
        function updateArrayValue() {
            // Bütün input elementlərinin dəyərlərini toplayırıq
            const inputs = arrayContainer.querySelectorAll('.array-item-input');
            arrayValues = Array.from(inputs).map(input => input.value);

            // Dəyəri yeniləyirik
            updateFormValue(arrayBaseName, arrayValues, checkboxElement.checked);
        }

        // Array elementlərini əlavə etmək üçün funksiya
        function addArrayItem(value = '') {
            const itemContainer = dom.createElement(`
                <div class="array-item flex items-center gap-2">
                    <input
                        type="text"
                        class="array-item-input flex-1 p-2 text-sm border rounded"
                        value="${value}"
                    >
                    <button type="button" class="remove-array-item text-red-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            `);

            // Input dəyişdikdə
            const input = itemContainer.querySelector('.array-item-input');
            input.addEventListener('input', updateArrayValue);

            // Element sildikdə
            const removeBtn = itemContainer.querySelector('.remove-array-item');
            removeBtn.addEventListener('click', () => {
                itemContainer.remove();
                updateArrayValue();
            });

            // Elementi əlavə edirik
            arrayContainer.appendChild(itemContainer);

            // Dəyərləri yeniləyirik
            updateArrayValue();
        }

        // "Add Item" düyməsi üçün event listener
        addArrayItemBtn.addEventListener('click', () => {
            addArrayItem();
        });

        // Checkbox dəyişikliyi
        checkboxElement.addEventListener('change', (e) => {
            updateFormValue(arrayBaseName, arrayValues, e.target.checked);
        });

        // Silmə düyməsinə event listener əlavə edirik
        row.querySelector('.delete-field').addEventListener('click', () => {
            row.remove();
            fieldMap.delete(fieldName);
            delete checkedFields[arrayBaseName];
            clearFieldValue(arrayBaseName);
            onChange(formValues);
        });

        // Əgər ilkin dəyərlər varsa, onları əlavə edirik
        if (Array.isArray(fieldValue) && fieldValue.length > 0) {
            fieldValue.forEach(val => addArrayItem(val));
        } else if (fieldValue && !Array.isArray(fieldValue)) {
            // Tək dəyər varsa, onu əlavə edirik
            addArrayItem(fieldValue);
        } else {
            // Heç bir dəyər yoxdursa, boş bir element əlavə edirik
            addArrayItem();
        }

        return row;
    }

    // Sahənin tipinə görə input elementi yaradırıq
    function createFieldInput(fieldName, fieldConfig, fieldValue) {
        const isFileField = fieldConfig.type === 'file';
        const isArrayField = fieldConfig.isArray || fieldName.endsWith('[]');
        const isBooleanField = fieldConfig.type === 'boolean';
        const isSelectField = fieldConfig.type === 'select';

        // Bütün input elementlərinə tətbiq edəcəyimiz ümumi border və stil sinifləri
        const commonClasses = "border border-slate-300 dark:border-dark-600 rounded p-2 text-sm w-full bg-slate-50 dark:bg-dark-700";

        if (isFileField) {
            return `
                <input
                    type="file"
                    class="field-value ${commonClasses.replace('bg-slate-50 dark:bg-dark-700', '')}"
                    ${fieldConfig.multiple || isArrayField ? 'multiple' : ''}
                    ${fieldConfig.accept ? `accept="${fieldConfig.accept}"` : ''}
                >
            `;
        } else if (fieldConfig.type === 'textarea') {
            return `
                <textarea
                    class="field-value ${commonClasses}"
                    rows="${fieldConfig.rows || 3}"
                >${fieldValue || ''}</textarea>
            `;
        } else if (isSelectField) {
            let options = '';
            if (fieldConfig.options && Array.isArray(fieldConfig.options)) {
                options = fieldConfig.options.map(option => `
                    <option value="${option.value}" ${fieldValue === option.value ? 'selected' : ''}>
                        ${option.label}
                    </option>
                `).join('');
            }

            return `
                <select class="field-value ${commonClasses}">
                    <option value="">Seçin...</option>
                    ${options}
                </select>
            `;
        } else if (isBooleanField) {
            return `
                <input
                    type="text"
                    class="field-value ${commonClasses}"
                    value="${fieldValue || ''}"
                >
            `;
        } else {
            return `
                <input
                    type="text"
                    class="field-value ${commonClasses}"
                    value="${fieldValue || ''}"
                >
            `;
        }
    }

    // FormValue-dən dəyəri təmizləyən funksiya
    function clearFieldValue(fieldName) {
        if (fieldName.includes('[')) {
            // Nested sahələr üçün
            const parts = parseNestedFieldName(fieldName);
            removeNestedValue(formValues, parts);
        } else {
            // Sadə sahələr üçün
            delete formValues[fieldName];
        }
    }

    // Form dəyərini yeniləyən funksiya
    function updateFormValue(fieldName, fieldValue, isIncluded) {
        // Checkbox vəziyyətini yadda saxlayırıq
        checkedFields[fieldName] = isIncluded;

        // Əgər sahə daxil edilmirsə, onu formdan çıxarırıq
        if (!isIncluded) {
            clearFieldValue(fieldName);
        } else {
            // Əvvəlcə dəyəri təmizləyirik ki təkrar olmasın
            clearFieldValue(fieldName);

            // Sahəni formValues-ə əlavə edirik
            if (fieldName.includes('[')) {
                // Nested sahələr üçün
                const parts = parseNestedFieldName(fieldName);
                setNestedValue(formValues, parts, fieldValue);
            } else {
                // Sadə sahələr üçün
                formValues[fieldName] = fieldValue;
            }
        }

        // Parent komponentə dəyişiklik haqqında məlumat veririk
        onChange(formValues);
    }

    // Nested sahə adını parse edirik (məs: "contact[name]" və ya "images[]")
    function parseNestedFieldName(fieldName) {
        // Array sonluğunu təmizləyirik (əgər varsa)
        const name = fieldName.endsWith('[]') ? fieldName.slice(0, -2) : fieldName;

        const parts = [];
        let currentName = '';
        let inBracket = false;

        for (let i = 0; i < name.length; i++) {
            const char = name[i];

            if (char === '[') {
                if (currentName) {
                    parts.push(currentName);
                    currentName = '';
                }
                inBracket = true;
            } else if (char === ']') {
                if (currentName) {
                    parts.push(currentName);
                    currentName = '';
                } else {
                    // Boş mötərizə array göstərir (məs: "images[]")
                    parts.push('');
                }
                inBracket = false;
            } else {
                currentName += char;
            }
        }

        if (currentName) {
            parts.push(currentName);
        }

        return parts;
    }

    // Nested strukturda dəyər yerləşdiririk
    function setNestedValue(obj, parts, value) {
        if (!parts.length) return;

        let current = obj;
        const lastIdx = parts.length - 1;

        // Yolu yaradırıq
        for (let i = 0; i < lastIdx; i++) {
            const part = parts[i];

            // Yoxlayırıq, path mövcud deyilsə yaradırıq
            if (!current[part]) {
                current[part] = {};
            }

            current = current[part];
        }

        // Son elementi təyin edirik
        const lastPart = parts[lastIdx];

        // Massiv sahəsi
        if (fieldMap.has(parts[0] + '[]')) {
            if (!Array.isArray(current[lastPart])) {
                current[lastPart] = Array.isArray(value) ? value : [value];
            } else {
                current[lastPart] = Array.isArray(value) ? value : [value];
            }
        } else {
            // Normal sahə
            current[lastPart] = value;
        }
    }

    // Nested strukturdan dəyəri silirik
    function removeNestedValue(obj, parts) {
        if (!parts.length) return;

        let current = obj;
        const lastIdx = parts.length - 1;

        // Yolu izləyirik
        for (let i = 0; i < lastIdx; i++) {
            const part = parts[i];

            if (!current[part]) {
                // Yol mövcud deyilsə, silirik əhəmiyyəti yoxdur
                return;
            }

            current = current[part];
        }

        // Son elementi silirik
        delete current[parts[lastIdx]];

        // Əgər obyekt boşdursa, onu da silirik
        if (Object.keys(current).length === 0 && lastIdx > 0) {
            removeNestedValue(obj, parts.slice(0, lastIdx));
        }
    }

    // Nested strukturdan dəyər alırıq
    function getNestedValue(obj, parts) {
        let current = obj;

        for (const part of parts) {
            if (!current || !current[part]) {
                return null;
            }

            current = current[part];
        }

        return current;
    }

    // Yeni sahə əlavə etmək üçün event listener
    addFieldBtn.addEventListener('click', () => {
        // Sadə mətn sahəsi əlavə edirik
        const fieldName = `custom_field_${Date.now()}`;
        const fieldConfig = {
            type: 'text',
            required: false
        };

        addFieldRow(fieldName, fieldConfig, '', true);
    });

    // formFields parametri verilmişdirsə, dinamik inputları yaradırıq
    if (formFields) {
        Object.entries(formFields).forEach(([fieldName, fieldConfig]) => {
            // İlkin dəyəri tapırıq
            let fieldValue = null;

            if (value && typeof value === 'object') {
                if (fieldName.includes('[')) {
                    // Nested sahələr üçün
                    const parts = parseNestedFieldName(fieldName);
                    fieldValue = getNestedValue(value, parts);
                } else {
                    // Sadə sahələr üçün
                    fieldValue = value[fieldName];
                }
            }

            // Default dəyəri təyin edirik
            if (fieldValue === null && fieldConfig.default !== undefined) {
                fieldValue = fieldConfig.default;
            }

            // Yeni sətir əlavə edirik
            addFieldRow(fieldName, fieldConfig, fieldValue, true);
        });
    }

    return container;
}
