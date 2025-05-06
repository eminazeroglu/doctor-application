<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => ':Attribute qəbul edilməlidir',
    'active_url' => ':Attribute doğru URL deyil',
    'after' => ':Attribute :date tarixindən sonra olmalıdır',
    'after_or_equal' => ':Attribute :date tarixi ilə eyni və ya sonra olmalıdır',
    'alpha' => ':Attribute yalnız hərflərdən ibarət ola bilər',
    'alpha_dash' => ':Attribute yalnız hərf, rəqəm və tire simvolundan ibarət ola bilər',
    'alpha_num' => ':Attribute yalnız hərf və rəqəmlərdən ibarət ola bilər',
    'array' => ':Attribute massiv formatında olmalıdır',
    'before' => ':Attribute :date tarixindən əvvəl olmalıdır',
    'before_or_equal' => ':Attribute :date tarixindən əvvəl və ya bərabər olmalıdır',
    'between' => [
        'numeric' => ':Attribute :min ilə :max arasında olmalıdır',
        'file' => ':Attribute :min ilə :max KB ölçüsü intervalında olmalıdır',
        'string' => ':Attribute :min ilə :max simvolu intervalında olmalıdır',
        'array' => ':Attribute :min ilə :max intervalında hissədən ibarət olmalıdır',
    ],
    'boolean' => ':Attribute doğru və ya yanlış ola bilər',
    'confirmed' => ':Attribute doğrulanması yanlışdır',
    'date' => ':Attribute tarix formatında olmalıdır',
    'date_format' => ':Attribute :format formatında olmalıdır',
    'different' => ':Attribute və :other fərqli olmalıdır',
    'digits' => ':Attribute :digits rəqəmli olmalıdır',
    'digits_between' => ':Attribute :min ilə :max rəqəmləri intervalında olmalıdır',
    'dimensions' => ':Attribute doğru şəkil ölçülərində deyil',
    'distinct' => ':Attribute dublikat qiymətlidir',
    'email' => ':Attribute doğru email formatında deyil',
    'exists' => 'Seçilmiş :attribute yanlışdır',
    'file' => ':Attribute fayl formatında olmalıdır',
    'filled' => ':Attribute qiyməti olmalıdır',
    'image' => ':Attribute şəkil formatında olmalıdır',
    'in' => 'Seçilmiş :attribute yanlışdır',
    'in_array' => ':Attribute :other qiymətləri arasında olmalıdır',
    'integer' => ':Attribute tam ədəd olmalıdır',
    'ip' => ':Attribute İP adres formatında olmalıdır',
    'ipv4' => ':Attribute İPv4 adres formatında olmalıdır',
    'ipv6' => ':Attribute İPv6 adres formatında olmalıdır',
    'json' => ':Attribute JSON formatında olmalıdır',
    'max' => [
        'numeric' => ':Attribute maksiumum :max rəqəmdən ibarət ola bilər',
        'file' => ':Attribute maksimum :max KB ölçüsündə ola bilər',
        'string' => ':Attribute maksimum :max simvoldan ibarət ola bilər',
        'array' => ':Attribute maksimum :max hədd\'dən ibarət ola bilər',
    ],
    'mimes' => ':Attribute :values tipində fayl olmalıdır',
    'mimetypes' => ':Attribute :values tipində fayl olmalıdır',
    'min' => [
        'numeric' => ':Attribute minimum :min rəqəmdən ibarət ola bilər',
        'file' => ':Attribute minimum :min KB ölçüsündə ola bilər',
        'string' => ':Attribute minimum :min simvoldan ibarət ola bilər',
        'array' => ':Attribute minimum :min hədd\'dən ibarət ola bilər',
    ],
    'not_in' => ' seçilmiş :attribute yanlışdır',
    'numeric' => ':Attribute rəqəmlərdən ibarət olmalıdır',
    'present' => ':Attribute iştirak etməlidir',
    'regex' => ':Attribute formatı yanlışdır',
    'required' => 'Zəhmət olmasa boş buraxmayın',
    'required_if' => ':Attribute (:other :value ikən) mütləqdir',
    'required_unless' => ':Attribute (:other :values \'ə daxil ikən) mütləqdir',
    'required_with' => ':Attribute (:values var ikən) mütləqdir',
    'required_with_all' => ':Attribute (:values var ikən) mütləqdir',
    'required_without' => ':Attribute (:values yox ikən) mütləqdir',
    'required_without_all' => ':Attribute (:values yox ikən) mütləqdir',
    'same' => ':Attribute və :other eyni olmalıdır',
    'size' => [
        'numeric' => ':Attribute :size ölçüsündə olmalıdır',
        'file' => ':Attribute :size KB ölçüsündə olmalıdır',
        'string' => ':Attribute :size simvoldan ibarət olmalıdır',
        'array' => ':Attribute :size hədd\'dən ibarət olmalıdır',
    ],
    'string' => ':Attribute hərf formatında olmalıdır',
    'timezone' => ':Attribute ərazi formatında olmalıdır',
    'unique' => ':Attribute artıq istifadə olunub',
    'uploaded' => ':Attribute yüklənməsi mümkün olmadı',
    'url' => 'Link formatı yanlışdır',
    'current_password' => ':Attribute yanlışdır',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    "password" => [
        'letters' => 'Şifrə ən azı bir hərf əhatə etməlidir.',
        'mixed' => 'Şifrə ən azı bir böyük və bir kiçik hərf əhatə etməlidir.',
        'numbers' => 'Şifrə ən azı bir rəqəm əhatə etməlidir',
        'symbols' => 'Şifrə ən azı bir simvol əhatə etməlidir.',
        'uncompromised' => 'Daxil etdiyiniz şifrə data sızıntısında aşkar edilib. Xahiş edirik başqa şifrə seçin.',
    ],

    'custom' => [
        'password' => [
            'invalid' => 'Şifrəniz yanlışdır.',
        ],
        'current_password' => 'Mövcud şifrə yanlışdır',
        "user" => [
            'not_active' => 'Sizin profiliniz sistemdə aktiv deyil.',
            'profile_block' => 'Profiliniz sistem tərəfindən bloklanmışdır. Texniki dəstəyə müraciət edin',
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    |  following language lines are used to swap attribute place-holders
    | with something more reader friendly such as E-Mail Address instead
    | of "email". This simply helps us make messages a little cleaner.
    |
    */

    'attributes' => [
        'passport_fin' => 'Şəxsiyyət vəsiqə FİN',
        'password' => 'Şifrə',
        'new_password' => 'Yeni şifrə',
        'password_confirmation' => 'Təkrar yeni şifrə',
        'rpassword' => 'Təkrar Şifrə',
        'phone' => 'Telefon nömrəsi',
        'current_password' => 'Mövcud şifrə'
    ],

];
