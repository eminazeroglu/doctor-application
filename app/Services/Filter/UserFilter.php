<?php

namespace App\Services\Filter;

class UserFilter extends BaseFilter
{
    protected array $filters = [
        'fullname',
        'email',
        'code',
        'gender',
        'role',
        'status',
    ];

    protected function filterFullname($query, $value)
    {
        return $query->fullName($value);
    }

    protected function filterEmail($query, $value) {
        return $query->whereRaw("LOWER(email) LIKE ?", ["%$value%"]);
    }

    protected function filterCode($query, $value) {
        return $query->whereRaw("LOWER(code) LIKE ?", ["%$value%"]);
    }

    protected function filterGender($query, $value) {
        return $query->where("gender", $value);
    }

    protected function filterRole($query, $value) {
        return $query->whereRelation("role", 'id', $value);
    }

}
