<?php

namespace Database\Seeders;

use App\Enums\ConversationStatusEnum;
use App\Enums\ConversationTypeEnum;
use App\Enums\MessageStatusEnum;
use App\Enums\MessageTypeEnum;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Models\UserBlock;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MessagingSystemSeeder extends Seeder
{
    /**
     * Mesajlaşma sistemi üçün test verilənlərini yaradır.
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('Mesajlaşma sistemi üçün test verilənləri yaradılır...');

        // Mövcud verilənləri yoxlayırıq
        $existingConversations = Conversation::count();
        $existingMessages = Message::count();
        $existingUserBlocks = UserBlock::count();

        if ($existingConversations > 0 || $existingMessages > 0 || $existingUserBlocks > 0) {
            if ($this->command->confirm("Artıq {$existingConversations} söhbət, {$existingMessages} mesaj və {$existingUserBlocks} istifadəçi bloku var. Bütün mövcud verilənləri silmək istəyirsiniz?", true)) {
                try {
                    DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                    Message::truncate();
                    Conversation::truncate();
                    UserBlock::truncate();
                    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
                    $this->command->info("Bütün mövcud mesajlaşma verilənləri silindi.");
                } catch (\Exception $e) {
                    $this->command->error("Verilənləri silərkən xəta: " . $e->getMessage());
                    return;
                }
            } else {
                $this->command->info("Mövcud verilənlər saxlanılır və onlara yeniləri əlavə ediləcək.");
            }
        }

        // İstifadəçiləri əldə edirik
        try {
            $users = User::where('is_system', false)->limit(10)->get();

            if ($users->count() < 2) {
                $this->command->error('Ən azı 2 istifadəçi olmalıdır! Sistem istifadəçisi olmayan istifadəçilər yaradılmalıdır.');
                return;
            }
        } catch (\Exception $e) {
            $this->command->error("İstifadəçilər əldə edilərkən xəta: " . $e->getMessage());
            return;
        }

        // İstifadəçi bloklarını yaradırıq
        $this->createUserBlocks($users);

        $this->command->info("İstifadəçi sayı: " . $users->count());
        $this->command->info('İstifadəçilər arasında söhbətlər yaradılır...');

        $totalConversations = 0;
        $totalMessages = 0;

        // İstifadəçilər arasında söhbətlər yaradırıq
        foreach ($users as $creator) {
            foreach ($users as $receiver) {
                // Eyni istifadəçi ilə söhbət yoxdur
                if ($creator->id == $receiver->id) {
                    continue;
                }

                // Əgər istifadəçilər bir-birini bloklamışsa, söhbət yaratmırıq
                if ($this->areUsersMutuallyBlocked($creator->id, $receiver->id)) {
                    continue;
                }

                // Hər istifadəçi cütü üçün təsadüfi sayda söhbət yaradılır
                $conversationsToCreate = rand(1, 2);

                for ($c = 0; $c < $conversationsToCreate; $c++) {
                    try {
                        DB::beginTransaction();

                        // Söhbət növünü seçirik
                        $conversationType = $this->getRandomConversationType();

                        // Söhbət statusu
                        $conversationStatus = $this->getRandomConversationStatus();

                        // Söhbət yaradırıq
                        $conversation = new Conversation();
                        $conversation->creator_id = $creator->id;
                        $conversation->receiver_id = $receiver->id;
                        $conversation->type = $conversationType;
                        $conversation->status = $conversationStatus;
                        $conversation->is_pinned = (bool)rand(0, 1);
                        $conversation->last_activity_at = Carbon::now()->subDays(rand(0, 30));

                        if ($conversationStatus === ConversationStatusEnum::ARCHIVED) {
                            $conversation->archived_at = Carbon::now()->subDays(rand(1, 10));
                        }

                        $conversation->save();
                        $totalConversations++;

                        // Bu söhbət üçün mesaj sayını təyin edirik
                        $messageCount = rand(3, 20);
                        $conversationMessages = [];
                        $lastMessageDate = Carbon::now()->subDays(rand(0, 30));

                        for ($m = 0; $m < $messageCount; $m++) {
                            // Mesaj göndərəni təyin edirik (növbəli)
                            $senderId = ($m % 2 === 0) ? $creator->id : $receiver->id;

                            // Mesaj tarixini təyin edirik
                            $messageDate = clone $lastMessageDate;
                            $messageDate->subMinutes(rand(5, 60)); // Əvvəlki mesajdan 5-60 dəqiqə əvvəl

                            // Mesaj növünü təyin edirik
                            $messageType = $this->getRandomMessageType($m);

                            // Mesaj məzmunu
                            $content = $this->generateMessageContent($messageType, $m);

                            // Əlavələr (şəkil, fayl və s.)
                            $attachments = $this->generateAttachments($messageType);

                            // Mesaj statusu
                            $messageStatus = $this->getRandomMessageStatus();

                            // Çatdırılma və oxunma tarixləri
                            $deliveredAt = null;
                            $readAt = null;

                            if ($messageStatus === MessageStatusEnum::DELIVERED || $messageStatus === MessageStatusEnum::READ) {
                                $deliveredAt = (clone $messageDate)->addSeconds(rand(10, 120));
                            }

                            if ($messageStatus === MessageStatusEnum::READ) {
                                $readAt = (clone $deliveredAt)->addSeconds(rand(10, 300));
                            }

                            // Redaktə edilmə durumu
                            $isEdited = rand(0, 100) < 10; // 10% ehtimal
                            $editedAt = $isEdited ? (clone $messageDate)->addMinutes(rand(1, 10)) : null;

                            // Meta verilənlər
                            $metaData = null;
                            if ($isEdited) {
                                $metaData = [
                                    'edit_history' => [
                                        [
                                            'content' => 'Əvvəlki məzmun: ' . fake()->sentence(rand(3, 8)),
                                            'edited_at' => $editedAt->format('Y-m-d H:i:s'),
                                            'edited_by' => $senderId
                                        ]
                                    ]
                                ];
                            }

                            // Sistem mesajı olma durumu
                            $isSystem = $messageType === MessageTypeEnum::SYSTEM;
                            if ($isSystem) {
                                $senderId = 1; // Admin/sistem istifadəçisi ID-si
                            }

                            // Mesaj yaradırıq
                            $message = new Message();
                            $message->conversation_id = $conversation->id;
                            $message->sender_id = $senderId;
                            $message->type = $messageType;
                            $message->content = $content;
                            $message->attachments = $attachments;
                            $message->status = $messageStatus;
                            $message->is_system = $isSystem;
                            $message->is_edited = $isEdited;
                            $message->edited_at = $editedAt;
                            $message->delivered_at = $deliveredAt;
                            $message->read_at = $readAt;
                            $message->meta_data = $metaData;
                            $message->created_at = $messageDate;
                            $message->updated_at = $isEdited ? $editedAt : $messageDate;
                            $message->save();

                            $conversationMessages[] = $message;
                            $totalMessages++;

                            $lastMessageDate = $messageDate;
                        }

                        // Söhbətin son mesaj məlumatlarını yeniləyirik
                        if (!empty($conversationMessages)) {
                            // Mesajları zamanına görə sıralayırıq (ən yenidən ən köhnəyə)
                            usort($conversationMessages, function($a, $b) {
                                return $b->created_at <=> $a->created_at;
                            });

                            // Ən son mesajı götürürük
                            $lastMessage = $conversationMessages[0];

                            // Meta məlumatları hazırlayırıq
                            $lastMessageContent = $lastMessage->content;
                            if (mb_strlen($lastMessageContent) > 50) {
                                $lastMessageContent = mb_substr($lastMessageContent, 0, 50) . '...';
                            }

                            // Şəkil, fayl və ya səs mesajı olduqda məzmunu uyğunlaşdırırıq
                            if ($lastMessage->type === MessageTypeEnum::IMAGE) {
                                $lastMessageContent = 'Şəkil göndərildi';
                            } elseif ($lastMessage->type === MessageTypeEnum::FILE) {
                                $lastMessageContent = 'Fayl göndərildi';
                            } elseif ($lastMessage->type === MessageTypeEnum::VOICE) {
                                $lastMessageContent = 'Səs mesajı göndərildi';
                            }

                            // Söhbət meta məlumatlarını və son aktivlik tarixini yeniləyirik
                            $conversation->meta_data = [
                                'last_message' => [
                                    'content' => $lastMessageContent,
                                    'sent_at' => $lastMessage->created_at,
                                    'sender_id' => $lastMessage->sender_id
                                ]
                            ];
                            $conversation->last_activity_at = $lastMessage->created_at;
                            $conversation->save();
                        }

                        DB::commit();
                    } catch (\Exception $e) {
                        DB::rollBack();
                        Log::error("Söhbət yaradılarkən xəta: " . $e->getMessage());
                        $this->command->error("Söhbət yaradılarkən xəta baş verdi: " . $e->getMessage());
                    }
                }
            }
        }

        $this->command->info("Uğurla {$totalConversations} söhbət və {$totalMessages} mesaj yaradıldı!");
    }

    /**
     * İstifadəçilər arasında blokları yaradır
     *
     * @param Collection $users
     * @return void
     */
    private function createUserBlocks($users): void
    {
        $this->command->info('İstifadəçi blokları yaradılır...');

        $totalBlocks = 0;
        $blockReasons = [
            'Narahat edici mesajlar göndərirdi',
            'Lazımsız reklam mesajları',
            'Təhqiramiz davranış',
            'İstəmirəm',
            'Tanımıram',
            null, // Bəzən səbəb göstərilmir
        ];

        // Təsadüfi blokları yaradırıq
        foreach ($users as $blocker) {
            // Hər istifadəçi təsadüfi sayda başqa istifadəçini bloklayır
            $blockCount = rand(0, 3); // 0-3 blok

            if ($blockCount > 0) {
                // Bloklanacaq istifadəçiləri təsadüfi seçirik
                $potentialBlockees = $users->where('id', '!=', $blocker->id)->shuffle()->take($blockCount);

                foreach ($potentialBlockees as $blocked) {
                    try {
                        // 70% ehtimalla aktiv blok, 30% silinmiş (vaxtı keçmiş) blok
                        $isActiveBlock = rand(1, 100) <= 70;

                        // Meta məlumatları təyin edirik
                        $blockedAt = Carbon::now()->subDays(rand(1, 90));
                        $metaData = [
                            'blocked_at' => $blockedAt->toIso8601String(),
                            'blocker_ip' => '192.168.' . rand(0, 255) . '.' . rand(0, 255)
                        ];

                        // Əgər keçmiş blokdursa metaData-ya unblocked_at əlavə edirik
                        if (!$isActiveBlock) {
                            $unblockedAt = (clone $blockedAt)->addDays(rand(1, 30));
                            $metaData['unblocked_at'] = $unblockedAt->toIso8601String();
                        }

                        $userBlock = new UserBlock();
                        $userBlock->blocker_id = $blocker->id;
                        $userBlock->blocked_id = $blocked->id;
                        $userBlock->reason = $blockReasons[array_rand($blockReasons)];
                        $userBlock->meta_data = $metaData; // tam obyekti bir dəfə təyin edirik
                        $userBlock->created_at = $blockedAt;
                        $userBlock->updated_at = $blockedAt;

                        // Əgər keçmiş blokdursa, soft delete tətbiq edirik
                        if (!$isActiveBlock) {
                            $unblockedAt = (clone $blockedAt)->addDays(rand(1, 30));
                            $userBlock->deleted_at = $unblockedAt;
                        }

                        $userBlock->save();
                        $totalBlocks++;

                    } catch (\Exception $e) {
                        Log::error("İstifadəçi bloku yaradılarkən xəta: " . $e->getMessage());
                        $this->command->error("İstifadəçi bloku yaradılarkən xəta: " . $e->getMessage());
                    }
                }
            }
        }

        $this->command->info("Uğurla {$totalBlocks} istifadəçi bloku yaradıldı!");
    }

    /**
     * İki istifadəçi bir-birini bloklayıbsa, true qaytarır
     *
     * @param int $userId1
     * @param int $userId2
     * @return bool
     */
    private function areUsersMutuallyBlocked(int $userId1, int $userId2): bool
    {
        // Birinci istifadəçi ikincini bloklayıb?
        $block1 = UserBlock::where('blocker_id', $userId1)
            ->where('blocked_id', $userId2)
            ->whereNull('deleted_at')
            ->exists();

        // İkinci istifadəçi birincini bloklayıb?
        $block2 = UserBlock::where('blocker_id', $userId2)
            ->where('blocked_id', $userId1)
            ->whereNull('deleted_at')
            ->exists();

        return $block1 || $block2; // Hər hansı bir blok varsa, true qaytarır
    }

    /**
     * Təsadüfi bir söhbət növü qaytarır
     */
    private function getRandomConversationType(): string
    {
        $types = [
            ConversationTypeEnum::PRIVATE,
            ConversationTypeEnum::PRIVATE,
            ConversationTypeEnum::PRIVATE, // Şəxsi mesajlar daha çox ehtimalla
            ConversationTypeEnum::LISTING,
            ConversationTypeEnum::SUPPORT,
            ConversationTypeEnum::SYSTEM
        ];

        return $types[array_rand($types)];
    }

    /**
     * Təsadüfi bir söhbət statusu qaytarır
     */
    private function getRandomConversationStatus(): string
    {
        $statuses = [
            ConversationStatusEnum::ACTIVE,
            ConversationStatusEnum::ACTIVE,
            ConversationStatusEnum::ACTIVE, // Aktiv söhbətlər daha çox ehtimalla
            ConversationStatusEnum::ACTIVE,
            ConversationStatusEnum::ARCHIVED,
            ConversationStatusEnum::BLOCKED
        ];

        return $statuses[array_rand($statuses)];
    }

    /**
     * Təsadüfi bir mesaj növü qaytarır
     */
    private function getRandomMessageType(int $messageIndex): string
    {
        // İlk və son mesajlar daha çox mətn mesajı olur
        if ($messageIndex <= 1 || rand(0, 10) < 8) {
            return MessageTypeEnum::TEXT;
        }

        $types = [
            MessageTypeEnum::TEXT,
            MessageTypeEnum::IMAGE,
            MessageTypeEnum::FILE,
            MessageTypeEnum::VOICE,
            MessageTypeEnum::SYSTEM
        ];

        return $types[array_rand($types)];
    }

    /**
     * Təsadüfi bir mesaj statusu qaytarır
     */
    private function getRandomMessageStatus(): string
    {
        $statuses = [
            MessageStatusEnum::SENT,
            MessageStatusEnum::DELIVERED,
            MessageStatusEnum::READ,
            MessageStatusEnum::READ // Oxunmuş mesajlar daha çox ehtimalla
        ];

        return $statuses[array_rand($statuses)];
    }

    /**
     * Mesaj növünə görə məzmun yaradır
     */
    private function generateMessageContent(string $type, int $messageIndex): ?string
    {
        switch ($type) {
            case MessageTypeEnum::TEXT:
                $phrases = [
                    'Salam, necəsən?',
                    'Görüşə bilərik?',
                    'Sabah vaxtın var?',
                    'Təşəkkür edirəm',
                    'Çox gözəl',
                    'Bəli, mümkündür',
                    'Mənim üçün uyğundur',
                    'Daha sonra danışaq',
                    'Bu gün hava gözəldir',
                    'Təcili cavab yazarsan?',
                    'Əla xəbərdir',
                    'Narahat olma',
                    'Axşam zəng edəcəm',
                    'Qiymət nə qədərdir?',
                    'Bu problem deyil',
                    'Diqqət yetirəcəm',
                    'Saat neçədə görüşək?',
                    'Harada görüşürük?',
                    'Sabah işə gedirsən?',
                    'Həftə sonu nə planlaşdırırsan?'
                ];

                return $phrases[array_rand($phrases)];

            case MessageTypeEnum::IMAGE:
                return rand(0, 1) ? 'Şəkli göndərirəm' : null;

            case MessageTypeEnum::FILE:
                return rand(0, 1) ? 'Fayl əlavə etdim' : null;

            case MessageTypeEnum::VOICE:
                return rand(0, 1) ? 'Səs mesajı' : null;

            case MessageTypeEnum::SYSTEM:
                $systemMessages = [
                    'Söhbət yaradıldı',
                    'İstifadəçi söhbətə qoşuldu',
                    'Bu söhbət admin tərəfindən bloklandı',
                    'Bu söhbət admin tərəfindən aktivləşdirildi',
                    'Statusunuz yeniləndi'
                ];
                return $systemMessages[array_rand($systemMessages)];

            default:
                return 'Mesaj məzmunu';
        }
    }

    /**
     * Mesaj növünə görə əlavələr yaradır
     */
    private function generateAttachments(string $type): ?array
    {
        switch ($type) {
            case MessageTypeEnum::IMAGE:
                // Şəkil əlavələri
                $count = rand(1, 3); // 1-3 şəkil
                $attachments = [];

                for ($i = 0; $i < $count; $i++) {
                    $imageId = Str::uuid()->toString();
                    $attachments[] = [
                        'id' => 'img_' . $imageId,
                        'type' => 'image',
                        'name' => 'image_' . ($i + 1) . '.jpg',
                        'path' => url('/uploads/photos/messages/' . $imageId . '.jpg'),
                        'size' => rand(100000, 5000000),
                        'mime_type' => 'image/jpeg',
                    ];
                }

                return $attachments;

            case MessageTypeEnum::FILE:
                // Fayl əlavələri
                $fileTypes = [
                    ['ext' => 'pdf', 'mime' => 'application/pdf', 'name' => 'Sənəd'],
                    ['ext' => 'doc', 'mime' => 'application/msword', 'name' => 'Word sənədi'],
                    ['ext' => 'xls', 'mime' => 'application/vnd.ms-excel', 'name' => 'Excel cədvəli'],
                    ['ext' => 'zip', 'mime' => 'application/zip', 'name' => 'Arxiv'],
                    ['ext' => 'txt', 'mime' => 'text/plain', 'name' => 'Mətn faylı']
                ];

                $file = $fileTypes[array_rand($fileTypes)];
                $fileId = Str::uuid()->toString();

                return [
                    [
                        'id' => 'file_' . $fileId,
                        'type' => 'file',
                        'name' => $file['name'] . '_' . rand(1, 100) . '.' . $file['ext'],
                        'path' => url('/uploads/files/message/' . $fileId . '.' . $file['ext']),
                        'size' => rand(50000, 10000000),
                        'mime_type' => $file['mime']
                    ]
                ];

            case MessageTypeEnum::VOICE:
                // Səs yazısı əlavəsi
                $voiceId = Str::uuid()->toString();
                $duration = rand(5, 120); // 5-120 saniyə

                return [
                    [
                        'id' => 'voice_' . $voiceId,
                        'type' => 'voice',
                        'name' => 'voice_message.mp3',
                        'path' => url('/uploads/files/message/' . $voiceId . '.mp3'),
                        'size' => rand(100000, 2000000),
                        'mime_type' => 'audio/mpeg',
                        'duration' => $duration
                    ]
                ];

            default:
                return null;
        }
    }
}
