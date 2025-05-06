<?php

namespace App\Models;

use App\Enums\CommentTypeEnum;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, MorphTo};
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',           // Şərhi yazan istifadəçi
        'parent_id',         // Cavab şərhi üçün
        'content',           // Şərhin mətni
        'meta_data',         // Əlavə məlumatlar
        'is_private',        // Şərhin görünürlüyü
        'is_active'          // Aktivlik statusu
    ];

    protected $casts = [
        'meta_data' => 'json',
        'is_private' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $appends = ['commentable_type_text', 'formatted_content', 'reactions_summary', 'can_edit'];

    /*
    |--------------------------------------------------------------------------
    | ƏLAQƏLƏR
    |--------------------------------------------------------------------------
    */

    /**
     * Polymorphic əlaqə - şərhin aid olduğu model (Listing, Company və s.)
     */
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Şərhi yazan istifadəçi
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Əsas şərh (əgər bu bir cavab şərhidirsə)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id')->with('author');
    }

    /**
     * Bu şərhə verilən cavablar
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')->with('author')->orderBy('created_at');
    }

    /*
    |--------------------------------------------------------------------------
    | VIRTUAL ATTRIBUTES
    |--------------------------------------------------------------------------
    */

    /**
     * Markdown və xüsusi formatlaşdırma tətbiq edilmiş mətn
     */
    protected function formattedContent(): Attribute
    {
        return Attribute::make(
            get: function () {
                $content = $this->content;

                // @mentions
                $content = preg_replace(
                    '/@(\w+)/',
                    '<a href="/users/$1" class="mention">@$1</a>',
                    $content
                );

                // Emojilər
                $content = $this->replaceEmojis($content);

                return $content;
            }
        );
    }

    protected function commentableTypeText(): Attribute
    {
        return Attribute::make(
            get: function () {
                return $this->commentable_type ? CommentTypeEnum::getDescription($this->commentable_type) : null;
            }
        );
    }

    /**
     * Reaksiyaların xülasəsi
     */
    protected function reactionsSummary(): Attribute
    {
        return Attribute::make(
            get: function () {
                $reactions = $this->meta_data['reactions'] ?? [];

                return [
                    'total' => array_sum(array_map('count', $reactions)),
                    'types' => array_map('count', $reactions),
                    'current_user' => $this->getCurrentUserReactions()
                ];
            }
        );
    }

    /**
     * Şərhin redaktə edilə bilməsi icazəsi
     */
    protected function canEdit(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->checkCanEdit()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | META DATA YÖNƏTİM METODLARİ
    |--------------------------------------------------------------------------
    */

    /**
     * Meta datadan müəyyən dəyər əldə edir
     *
     * @param string $key Meta data açarı
     * @param mixed $default Default dəyər
     * @return mixed Meta datadakı dəyər və ya default dəyər
     */
    public function getMetaData(string $key, $default = null)
    {
        return $this->meta_data[$key] ?? $default;
    }

    /**
     * Meta datada bir açarın olub-olmadığını yoxlayır
     *
     * @param string $key Meta data açarı
     * @return bool Açar varsa true, yoxdursa false
     */
    public function hasMetaData(string $key): bool
    {
        return isset($this->meta_data[$key]);
    }

    /**
     * Meta data-ya dəyər əlavə edir və ya yeniləyir
     *
     * @param string $key Meta data açarı
     * @param mixed $value Dəyər
     * @return bool Əməliyyat uğurlu oldumu?
     */
    public function setMetaData(string $key, $value): bool
    {
        $metaData = $this->meta_data ?? [];
        $metaData[$key] = $value;

        return $this->update(['meta_data' => $metaData]);
    }

    /**
     * Meta data-dan bir açarı silir
     *
     * @param string $key Meta data açarı
     * @return bool Əməliyyat uğurlu oldumu?
     */
    public function removeMetaData(string $key): bool
    {
        if (!$this->hasMetaData($key)) {
            return true;
        }

        $metaData = $this->meta_data;
        unset($metaData[$key]);

        return $this->update(['meta_data' => $metaData]);
    }

    /*
    |--------------------------------------------------------------------------
    | BUSINESS METODLARI
    |--------------------------------------------------------------------------
    */

    /**
     * Şərhi redaktə edir və tarixçəni yeniləyir
     */
    public function edit(string $newContent, ?string $reason = null): bool
    {
        if (!$this->can_edit) {
            return false;
        }

        // Redaktə tarixçəsini yeniləyirik
        $editHistory = $this->getMetaData('edit_history', []);
        $editHistory[] = [
            'old_content' => $this->content,
            'edited_at' => now()->toDateTimeString(),
            'edited_by' => auth()->id(),
            'reason' => $reason
        ];

        $metaData = $this->meta_data ?? [];
        $metaData['edit_history'] = $editHistory;
        $metaData['last_edited_at'] = now()->toDateTimeString();

        return $this->update([
            'content' => $newContent,
            'meta_data' => $metaData
        ]);
    }

    /**
     * Şərhə reaksiya əlavə edir və ya çıxarır
     */
    public function toggleReaction(string $type): bool
    {
        if (!auth()->check()) {
            return false;
        }

        $userId = auth()->id();
        $reactions = $this->getMetaData('reactions', []);

        // Reaksiyanı əlavə et və ya çıxar
        if (!isset($reactions[$type])) {
            $reactions[$type] = [];
        }

        if (in_array($userId, $reactions[$type])) {
            $reactions[$type] = array_values(array_diff($reactions[$type], [$userId]));
        } else {
            $reactions[$type][] = $userId;
        }

        // Boş array-ləri təmizlə
        $reactions = array_filter($reactions, fn($users) => !empty($users));

        return $this->setMetaData('reactions', $reactions);
    }

    /**
     * İstifadəçinin bu şərhə verdiyi reaksiyanı yoxlayır
     *
     * @param string $type Reaksiya tipi
     * @return bool İstifadəçinin reaksiyası varsa true
     */
    public function hasReaction(string $type): bool
    {
        if (!auth()->check()) {
            return false;
        }

        $userId = auth()->id();
        $reactions = $this->getMetaData('reactions', []);

        return isset($reactions[$type]) && in_array($userId, $reactions[$type]);
    }

    /**
     * Şərhə fayl əlavə edir
     *
     * @param array $file Fayl məlumatları (type, url, name)
     * @return bool Əməliyyat uğurlu oldu?
     */
    public function addAttachment(array $file): bool
    {
        $attachments = $this->getMetaData('attachments', []);
        $attachments[] = $file;

        return $this->setMetaData('attachments', $attachments);
    }

    /**
     * Şərhin fayllarını əldə edir
     *
     * @return array Fayllar
     */
    public function getAttachments(): array
    {
        return $this->getMetaData('attachments', []);
    }

    /**
     * Şərhin spam olduğunu bildir
     */
    public function markAsSpam(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        $userId = auth()->id();
        $spamReports = $this->getMetaData('spam_reports', []);

        // Eyni istifadəçi təkrar bildiriş edə bilməz
        foreach ($spamReports as $report) {
            if ($report['reported_by'] === $userId) {
                return false;
            }
        }

        $spamReports[] = [
            'reported_by' => $userId,
            'reported_at' => now()->toDateTimeString()
        ];

        // Əgər 3 və ya daha çox spam report varsa, şərhi deaktiv et
        if (count($spamReports) >= 3 && $this->is_active) {
            $this->is_active = false;
            $this->save();
        }

        return $this->setMetaData('spam_reports', $spamReports);
    }

    /**
     * Şərhin redaktə edilə bilməsini yoxlayır
     *
     * @return bool Şərh redaktə edilə bilərsə true
     */
    protected function checkCanEdit(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        // Admin həmişə redaktə edə bilər
        if (auth()->user()->hasRole('admin')) {
            return true;
        }

        // Müəllif yalnız müəyyən şərtlər altında redaktə edə bilər
        if ($this->user_id === auth()->id()) {
            return true;
        }

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPE METODLARI
    |--------------------------------------------------------------------------
    */

    /**
     * Aktiv şərhləri filtrləyir
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Ancaq əsas şərhləri filtrləyir (cavabları yox)
     */
    public function scopeParentOnly($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Müəyyən istifadəçinin görə biləcəyi şərhləri filtrləyir
     */
    public function scopeVisibleTo($query, $userId)
    {
        return $query->where(function($q) use ($userId) {
            $q->where('is_private', false)
                ->orWhere('user_id', $userId)
                ->orWhereHas('commentable', function($q) use ($userId) {
                    $q->where('user_id', $userId);
                });
        });
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER METODLARI
    |--------------------------------------------------------------------------
    */

    /**
     * Cari istifadəçinin reaksiyalarını qaytarır
     */
    protected function getCurrentUserReactions(): array
    {
        if (!auth()->check()) {
            return [];
        }

        $userId = auth()->id();
        $userReactions = [];
        $reactions = $this->getMetaData('reactions', []);

        foreach ($reactions as $type => $users) {
            if (in_array($userId, $users)) {
                $userReactions[] = $type;
            }
        }

        return $userReactions;
    }

    /**
     * Şərh mətninə emoji dəstəyi əlavə edir
     *
     * @param string $content Orijinal mətn
     * @return string Emoji ilə zənginləşdirilmiş mətn
     */
    protected function replaceEmojis(string $content): string
    {
        // Əsas emojilər - hər gün istifadə olunanlar
        $basicEmojis = [
            // Üz ifadələri
            ':)' => '😊',
            ':-)' => '😊',
            ':(' => '😢',
            ':-(' => '😢',
            ';)' => '😉',
            ';-)' => '😉',
            ':D' => '😃',
            ':-D' => '😃',
            ':P' => '😛',
            ':-P' => '😛',
            ':p' => '😛',
            ':-p' => '😛',
            ':/' => '😕',
            ':-/' => '😕',
            ':|' => '😐',
            ':-|' => '😐',
            ':o' => '😮',
            ':-o' => '😮',
            ':O' => '😮',
            ':-O' => '😮',
            ':*' => '😘',
            ':-*' => '😘',

            // Reaksiyalar
            '<3' => '❤️',
            ':heart:' => '❤️',
            ':+1:' => '👍',
            ':-1:' => '👎',
            ':thumbsup:' => '👍',
            ':thumbsdown:' => '👎',
            ':clap:' => '👏',
            ':fire:' => '🔥',
            ':100:' => '💯',
            ':star:' => '⭐',
        ];

        // Genişləndirilmiş emojilər - daha xüsusi hallar
        $extendedEmojis = [
            // Əlavə üz ifadələri
            ':\'(' => '😢',
            ':\'-)' => '😂',
            ':-))' => '😄',
            ':-))))))' => '🤣',
            'xD' => '😆',
            'XD' => '😆',
            ':-S' => '😖',
            ':-s' => '😖',
            '8)' => '😎',
            '8-)' => '😎',
            'B)' => '😎',
            'B-)' => '😎',
            '(y)' => '👍',
            '(n)' => '👎',

            // Heyvanlar və təbiət
            ':cat:' => '🐱',
            ':dog:' => '🐶',
            ':panda:' => '🐼',
            ':monkey:' => '🐵',
            ':unicorn:' => '🦄',
            ':sun:' => '☀️',
            ':moon:' => '🌙',
            ':star:' => '⭐',
            ':rainbow:' => '🌈',
            ':earth:' => '🌍',

            // Qida və içkilər
            ':pizza:' => '🍕',
            ':hamburger:' => '🍔',
            ':coffee:' => '☕',
            ':tea:' => '🍵',
            ':cake:' => '🍰',
            ':beer:' => '🍺',
            ':wine:' => '🍷',

            // Fəaliyyətlər və obyektlər
            ':computer:' => '💻',
            ':phone:' => '📱',
            ':book:' => '📚',
            ':movie:' => '🎬',
            ':music:' => '🎵',
            ':car:' => '🚗',
            ':airplane:' => '✈️',
            ':money:' => '💰',
            ':gift:' => '🎁',
            ':time:' => '⏰',
            ':camera:' => '📷',

            // Xüsusi hallar və mərasimlər
            ':birthday:' => '🎂',
            ':christmas:' => '🎄',
            ':halloween:' => '🎃',
            ':award:' => '🏆',
            ':medal:' => '🥇',
        ];

        // Xüsusi emoji prosesləmə üçün daha mürəkkəb qaydalar
        $specialPatterns = [
            // Müxtəlif uzunluqlu gülüş səsləri
            '/(?<![a-zA-Z0-9])ha{2,}h?(?![a-zA-Z0-9])/i' => '😄', // haha, hahaha, ...
            '/(?<![a-zA-Z0-9])he{2,}h?(?![a-zA-Z0-9])/i' => '😄', // hehe, hehehe, ...
            '/(?<![a-zA-Z0-9])lo+l(?![a-zA-Z0-9])/i' => '😂',    // lol, loool, ...
            '/(?<![a-zA-Z0-9])lmao+(?![a-zA-Z0-9])/i' => '🤣',   // lmao, lmaooo, ...
            '/(?<![a-zA-Z0-9])rofl(?![a-zA-Z0-9])/i' => '🤣',    // rofl

            // Emosional reaksiyalar
            '/(?<![a-zA-Z0-9])wow+(?![a-zA-Z0-9])/i' => '😮',    // wow, woww, ...
            '/(?<![a-zA-Z0-9])omg(?![a-zA-Z0-9])/i' => '😱',     // omg
            '/(?<![a-zA-Z0-9])wtf(?![a-zA-Z0-9])/i' => '😳',     // wtf
        ];

        // Emojilər haqqında məlumat əldə edirik
        $emojiSettings = $this->getEmojiSettings();

        // Hansı emoji setlərinin aktiv olduğunu yoxlayırıq
        $emojiMap = $basicEmojis; // Əsas emojilər həmişə aktiv

        if ($emojiSettings['use_extended_emojis'] ?? true) {
            $emojiMap = array_merge($emojiMap, $extendedEmojis);
        }

        // Əvvəlcə sadə əvəzləmələri edirik
        $result = str_replace(
            array_keys($emojiMap),
            array_values($emojiMap),
            $content
        );

        // Xüsusi patternləri emal edirik (əgər aktiv edilmişsə)
        if ($emojiSettings['use_pattern_detection'] ?? true) {
            foreach ($specialPatterns as $pattern => $emoji) {
                $result = preg_replace($pattern, $emoji, $result);
            }
        }

        return $result;
    }

    /**
     * Emoji ayarlarını əldə edir.
     * Bu metod gələcəkdə emoji tərcihləri saxlayan sistemdən
     * istifadəçi və sistem səviyyəsində parametrlər əldə edə bilər.
     *
     * @return array Emoji ayarları
     */
    protected function getEmojiSettings(): array
    {
        // Burada məsələn, settings cədvəlindən və ya istifadəçi preferencesindən
        // emoji parametrləri əldə edilə bilər

        // Hələlik standart parametrləri qaytarırıq
        return [
            'use_extended_emojis' => true,   // Genişləndirilmiş emoji seti
            'use_pattern_detection' => true, // Mətndə pattern-lərə görə emoji əvəzləmə
        ];
    }

    /**
     * Şərhin redaktə edilib-edilmədiyini göstərir
     *
     * @return bool Şərh redaktə edilmişsə true
     */
    public function isEdited(): bool
    {
        return $this->hasMetaData('last_edited_at');
    }

    /**
     * Şərh haqqında əsas məlumatları qaytarır
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'content' => $this->formatted_content,
            'author' => [
                'id' => $this->author->id,
                'name' => $this->author->name,
                'avatar' => $this->author->avatar_url
            ],
            'created_at' => $this->created_at,
            'reactions' => $this->reactions_summary,
            'replies_count' => $this->replies()->count(),
            'can_edit' => $this->can_edit,
            'is_edited' => $this->isEdited(),
            'attachments' => $this->getAttachments()
        ];
    }
}
