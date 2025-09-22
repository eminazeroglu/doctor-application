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

        // Bütün array-i geri təyin edirik
        $this->custom_fields = $customFields;

        return $this;
    }

    /**
     * Birdən çox custom field dəyərini təyin edir
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

        return $this;
    }

    /**
     * Custom field-i silir
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
        }

        return $this;
    }
}
