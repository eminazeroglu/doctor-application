<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class AttributeTypeEnum extends Enum
{
    // Mətn tipləri
    const Text = 'text';           // Sadə mətn sahəsi
    const Textarea = 'textarea';   // Uzun mətn sahəsi
    const Html = 'html';           // HTML redaktoru
    const Email = 'email';         // E-poçt sahəsi
    const Phone = 'phone';         // Telefon nömrəsi
    const Url = 'url';             // Web ünvanı

    // Rəqəm tipləri
    const Integer = 'integer';     // Tam rəqəm
    const Decimal = 'decimal';     // Kəsr rəqəm
    const Price = 'price';         // Qiymət sahəsi
    const Range = 'range';         // Aralıq seçimi

    // Seçim tipləri
    const Select = 'select';       // Tək seçim
    const MultiSelect = 'multi_select'; // Çoxlu seçim
    const Radio = 'radio';         // Radio düymələri
    const Checkbox = 'checkbox';   // Çoxlu seçim qutuları

    // Tarix və vaxt
    const Date = 'date';           // Tarix
    const Time = 'time';           // Vaxt
    const DateTime = 'datetime';   // Tarix və vaxt
    const Year = 'year';           // İl seçimi
    const Month = 'month';         // Ay seçimi

    // Media tipləri
    const Image = 'image';         // Tək şəkil
    const Gallery = 'gallery';     // Şəkil qalereyası
    const File = 'file';           // Fayl yükləmə

    // Xüsusi tiplər
    const Color = 'color';         // Rəng seçimi
    const Location = 'location';   // Yer/Ünvan seçimi
    const Boolean = 'boolean';     // Bəli/Xeyr seçimi

    public static function getDescription($value): string
    {
        return match ($value) {
            self::Text => t('enums.attributeType.text'),
            self::Textarea => t('enums.attributeType.textarea'),
            self::Html => t('enums.attributeType.html'),
            self::Email => t('enums.attributeType.email'),
            self::Phone => t('enums.attributeType.phone'),
            self::Url => t('enums.attributeType.url'),

            self::Integer => t('enums.attributeType.integer'),
            self::Decimal => t('enums.attributeType.decimal'),
            self::Price => t('enums.attributeType.price'),
            self::Range => t('enums.attributeType.range'),

            self::Select => t('enums.attributeType.select'),
            self::MultiSelect => t('enums.attributeType.multiSelect'),
            self::Radio => t('enums.attributeType.radio'),
            self::Checkbox => t('enums.attributeType.checkbox'),

            self::Date => t('enums.attributeType.date'),
            self::Time => t('enums.attributeType.time'),
            self::DateTime => t('enums.attributeType.dateTime'),
            self::Year => t('enums.attributeType.year'),
            self::Month => t('enums.attributeType.month'),

            self::Image => t('enums.attributeType.image'),
            self::Gallery => t('enums.attributeType.gallery'),
            self::File => t('enums.attributeType.file'),

            self::Color => t('enums.attributeType.color'),
            self::Location => t('enums.attributeType.location'),
            self::Boolean => t('enums.attributeType.boolean'),

            default => self::getKey($value),
        };
    }

}
