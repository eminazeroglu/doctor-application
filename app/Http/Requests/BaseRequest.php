<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BaseRequest extends FormRequest
{

    protected mixed $permission;

    public function __construct($permission = null)
    {
        parent::__construct();
        $this->server = new \Symfony\Component\HttpFoundation\ServerBag();

        $this->permission = $permission;
    }

    public function authorize(): bool
    {
        if ($this->permission):
            if ($this->id) return request()->user()->hasPermission($this->permission . '_update');
            else return request()->user()->hasPermission($this->permission . '_create');
        endif;
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function messages(): array
    {
        return [];
    }

    public function failedValidation(Validator $validator)
    {
        $response = $this->transformValidationErrors($validator->errors()->toArray());
        throw new HttpResponseException(response()->json($response, 422));
    }

    private function transformValidationErrors(array $errors): array
    {
        array_walk($errors, static fn(&$value) => $value = $value[0]);
        return $errors;
    }
}
