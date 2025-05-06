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
        if (!isset($this->custom_fields) || !is_array($this->custom_fields)) {
            $this->custom_fields = [];
        }

        $this->custom_fields[$key] = $value;

        return $this;
    }
}
