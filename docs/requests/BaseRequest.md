# BaseRequest

## 🎯 Əsas Məqsəd

* Laravel FormRequest-lər üçün baza sinifi
* İcazə yoxlaması və validasiya xətalarının formatlanması
* Standart avtorizasiya və validasiya davranışını təmin edir

## 🚀 Sürətli Başlanğıc

~~~php
class UserRequest extends BaseRequest
{
    public function __construct()
    {
        parent::__construct('user');
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users'
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Ad məcburidir',
            'email.unique' => 'Bu email artıq istifadə olunub'
        ];
    }
}
~~~

## 📋 Əsas Funksionallıq

### 1. İcazə Yoxlaması

~~~php
public function authorize(): bool
{
    if ($this->permission):
        // Update əməliyyatı
        if ($this->id) {
            return request()->user()->hasPermission($this->permission . '_update');
        }
        // Create əməliyyatı
        return request()->user()->hasPermission($this->permission . '_create');
    endif;
    
    return true;
}
~~~

### 2. Xəta Formatlaması

~~~php
// Input
[
    'email' => ['Email düzgün formatda deyil', 'Email unique olmalıdır'],
    'name' => ['Ad məcburidir']
]

// Output
[
    'email' => 'Email düzgün formatda deyil',
    'name' => 'Ad məcburidir'
]
~~~

## 🔧 Metodlar və İstifadəsi

### Constructor

~~~php
class ProductRequest extends BaseRequest
{
    public function __construct()
    {
        // Permissions: product_create, product_update
        parent::__construct('product');
    }
}
~~~

### Rules və Messages

~~~php
class CategoryRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'parent_id' => 'nullable|exists:categories,id',
            'is_active' => 'boolean'
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Kateqoriya adı məcburidir',
            'parent_id.exists' => 'Seçilən valideyn kateqoriya mövcud deyil'
        ];
    }
}
~~~

## ⚠️ Vacib Qeydlər

1. Avtorizasiya:
    * İD varsa update icazəsi yoxlanılır
    * İD yoxdursa create icazəsi yoxlanılır
    * Permission null-dırsa avtomatik təsdiqlənir

2. Validasiya xətaları:
    * 422 status kodu ilə qaytarılır
    * Hər field üçün yalnız ilk xəta mesajı göstərilir
    * JSON formatında qaytarılır

## 💡 İstifadə Nümunələri

### Basic Request
~~~php
class UserRequest extends BaseRequest
{
    public function __construct()
    {
        parent::__construct('user');
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'email' => 'required|email'
        ];
    }
}
~~~

### Conditional Rules
~~~php
class ProductRequest extends BaseRequest
{
    public function rules(): array
    {
        $rules = [
            'title' => 'required|string',
            'price' => 'required|numeric'
        ];

        if ($this->isMethod('PUT')) {
            $rules['status'] = 'required|in:active,draft';
        }

        return $rules;
    }
}
~~~

### Custom Messages
~~~php
class ArticleRequest extends BaseRequest
{
    public function messages(): array
    {
        return [
            'title.required' => 'Başlıq məcburidir',
            'content.min' => 'Mətn ən az :min simvol olmalıdır',
            'category_id.exists' => 'Seçilən kateqoriya mövcud deyil'
        ];
    }
}
~~~

## 🔗 Əlaqəli Komponentlər

* Controllers
* Models
* Validation Rules
* Authorization System
* Error Handling

## 📝 Nümunə Response-lar

### Validation Error
~~~json
{
    "name": "Ad məcburidir",
    "email": "Email düzgün formatda deyil"
}
~~~

### Authorization Error
~~~json
{
    "message": "Bu əməliyyat üçün icazəniz yoxdur"
}
~~~
