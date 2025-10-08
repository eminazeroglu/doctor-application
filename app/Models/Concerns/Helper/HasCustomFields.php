<?php

namespace App\Models\Concerns\Helper;

trait HasCustomFields
{
    /**
     * Custom fields sahəsindən bir dəyər əldə edir
     *
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function getCustomFieldValue(string $key, mixed $default = null): mixed
    {
        if (!isset($this->custom_fields) || !is_array($this->custom_fields)) {
            return $default;
        }

        return $this->custom_fields[$key] ?? $default;
    }

    /**
     * Custom fields sahəsinə dəyər əlavə edir
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setCustomFieldValue(string $key, mixed $value): static
    {
        // Mövcud dəyərləri alırıq
        $customFields = $this->custom_fields ?? [];

        // Yeni dəyəri əlavə edirik
        $customFields[$key] = $value;

        // Sadə təyin edirik
        $this->custom_fields = $customFields;

        // Avtomatik save edirik
        $this->save();

        return $this;
    }

    /**
     * Custom fields sahəsinə dəyər əlavə edir və save etmir (yalnız memory-də saxlayır)
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function addCustomFieldValue(string $key, mixed $value): static
    {
        // Mövcud dəyərləri alırıq
        $customFields = $this->custom_fields ?? [];

        // Yeni dəyəri əlavə edirik
        $customFields[$key] = $value;

        // Sadə təyin edirik
        $this->custom_fields = $customFields;

        return $this;
    }

    /**
     * Custom field-i save edərək təyin edir (ayrı metod)
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function saveCustomFieldValue(string $key, mixed $value): bool
    {
        return $this->addCustomFieldValue($key, $value)->save();
    }

    /**
     * Birdən çox custom field dəyərini təyin edir və save edir
     *
     * @param array $fields
     * @return $this
     */
    public function setCustomFields(array $fields): static
    {
        $customFields = $this->custom_fields ?? [];

        foreach ($fields as $key => $value) {
            $customFields[$key] = $value;
        }

        $this->custom_fields = $customFields;

        // Avtomatik save edirik
        $this->save();

        return $this;
    }

    /**
     * Birdən çox custom field dəyərini təyin edir amma save etmir
     *
     * @param array $fields
     * @return $this
     */
    public function addCustomFields(array $fields): static
    {
        $customFields = $this->custom_fields ?? [];

        foreach ($fields as $key => $value) {
            $customFields[$key] = $value;
        }

        $this->custom_fields = $customFields;

        return $this;
    }

    /**
     * Custom field-i silir və save edir
     *
     * @param string $key
     * @return $this
     */
    public function removeCustomField(string $key): static
    {
        $customFields = $this->custom_fields ?? [];

        if (isset($customFields[$key])) {
            unset($customFields[$key]);
            $this->custom_fields = $customFields;

            // Avtomatik save edirik
            $this->save();
        }

        return $this;
    }
}
