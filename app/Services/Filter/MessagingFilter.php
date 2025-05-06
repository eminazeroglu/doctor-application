<?php

namespace App\Services\Filter;

use Illuminate\Support\Facades\DB;

class MessagingFilter extends BaseFilter
{
    protected array $filters = [
        'q',
        'status',
        'type',
        'user_id',
        'date_range',
        'is_system',
        'conversation_id',
        'sender_id'
    ];

    protected function filterType($query, $value)
    {
        return $query->where('type', $value);
    }

    protected function filterUserId($query, $value)
    {
        return $query->where(function($q) use ($value) {
            $q->where('creator_id', $value)
                ->orWhere('receiver_id', $value);
        });
    }

    protected function filterQ($query, $value)
    {
        $search = $value;
        return $query->where(function($q) use ($search) {
            // İstifadəçi adlarında axtarış
            $q->whereHas('creator', function($query) use ($search) {
                $query->where(DB::raw("CONCAT(name, ' ', surname)"), 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
                ->orWhereHas('receiver', function($query) use ($search) {
                    $query->where(DB::raw("CONCAT(name, ' ', surname)"), 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })
                // Mesaj məzmununda axtarış
                ->orWhereHas('messages', function($query) use ($search) {
                    $query->where('content', 'like', "%{$search}%");
                });
        });
    }

    protected function filterConversationId($query, $value)
    {
        return $query->where('conversation_id', $value);
    }

    protected function filterSenderId($query, $value)
    {
        return $query->where('sender_id', $value);
    }

    protected function filterIsSystem($query, $value)
    {
        return $query->where('is_system', $value);
    }
}
