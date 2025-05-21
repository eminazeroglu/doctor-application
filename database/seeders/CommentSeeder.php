<?php

namespace Database\Seeders;

use App\Enums\CommentReactionType;
use App\Enums\UserStatusEnum;
use App\Models\Comment;
use App\Models\User;
use Faker\Factory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CommentSeeder extends Seeder
{
    /**
     * Faker instansı
     */
    protected $faker;

    /**
     * Sistemimizdəki bütün istifadəçi ID-ləri
     */
    protected $userIds;

    /**
     * Emoji istifadə edənlərin faizi
     */
    protected $emojiUsePercentage = 65;

    /**
     * Attachment əlavə edənlərin faizi
     */
    protected $attachmentPercentage = 20;

    /**
     * Spam bildirişi alanların faizi
     */
    protected $spamReportPercentage = 8;

    public function __construct()
    {
        $this->faker = Factory::create();
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        Comment::query()->truncate();

        // Bütün aktiv istifadəçilərin ID-lərini əldə edirik
        $this->userIds = User::where('status', UserStatusEnum::Active)
            ->where('is_system', false)
            ->pluck('id')
            ->toArray();

        // Əgər heç bir istifadəçi yoxdursa, seed etməyi dayandırırıq
        if (empty($this->userIds)) {
            $this->command->info('No active users found. Comment seeding skipped.');
            return;
        }

        // Hər bir model tipi üçün şərhlər yaradırıq
        $this->createUserComments();

        $this->command->info('Comments seeded successfully!');
        Schema::enableForeignKeyConstraints();
    }

    /**
     * İstifadəçi profillərinə şərhlər yaradır
     */
    public function createUserComments(): void
    {
        $count = 0;
        $targetCount = rand(60, 200);

        // Bütün istifadəçilərə şərhlər yaradırıq
        $users = User::where('status', UserStatusEnum::Active)
            ->where('is_system', false)
            ->get();

        foreach ($users as $user) {
            // Hər bir istifadəçinin profilinə 1-5 şərh
            $commentsCount = rand(1, 5);

            for ($i = 0; $i < $commentsCount && $count < $targetCount; $i++) {
                // Şərhi yazacaq təsadüfi istifadəçi (özünə şərh yazmasın)
                $authorIds = array_diff($this->userIds, [$user->id]);
                $authorId = $this->faker->randomElement($authorIds);

                // Parent şərh yaradırıq
                $parentComment = $this->createComment(
                    authorId: $authorId,
                    commentableId: $user->id,
                    commentableType: User::class
                );

                // 40% ehtimalla şərhə 1-3 cavab əlavə edirik
                if ($this->faker->boolean(40)) {
                    $repliesCount = rand(1, 3);

                    for ($j = 0; $j < $repliesCount; $j++) {
                        // Cavabı yazacaq təsadüfi istifadəçi
                        $replyAuthorId = $this->faker->randomElement($this->userIds);

                        $this->createComment(
                            authorId: $replyAuthorId,
                            commentableId: $user->id,
                            commentableType: User::class,
                            parentId: $parentComment->id,
                            isReply: true
                        );

                        $count++;
                    }
                }

                $count++;

                // Hədəf sayına çatdıqda dayandırırıq
                if ($count >= $targetCount) {
                    break;
                }
            }
        }

        $this->command->info("Created {$count} user profile comments");
    }

    /**
     * Yeni şərh yaradır
     *
     * @param int $authorId Şərh yazan istifadəçinin ID-si
     * @param int $commentableId Şərh yazılan obyektin ID-si
     * @param string $commentableType Şərh yazılan obyektin tipi
     * @param int|null $parentId Əgər cavab şərhidirsə, parent şərhin ID-si
     * @param bool $isListingComment Elan şərhidirmi?
     * @param bool $isCompanyComment Şirkət şərhidirmi?
     * @param bool $isReply Cavab şərhidir?
     * @return Comment Yaradılan şərh obyekti
     */
    protected function createComment(
        int $authorId,
        int $commentableId,
        string $commentableType,
        ?int $parentId = null,
        bool $isListingComment = false,
        bool $isCompanyComment = false,
        bool $isReply = false
    ): Comment {
        // Tarix təyin edirik (son 6 ay ərzində)
        $createdAt = Carbon::now()->subDays(rand(0, 180));

        // Model növünə görə şərh məzmununu yaradırıq
        $content = $this->generateCommentContent($commentableType, $isListingComment, $isCompanyComment, $isReply);

        // Emoji əlavə edirik (bəzi şərhlərə)
        if ($this->faker->boolean($this->emojiUsePercentage)) {
            $content = $this->addEmojisToContent($content);
        }

        // Meta data generasiyası
        $metaData = $this->generateMetaData($authorId, $content, $createdAt, $isReply);

        // Şərh məlumatlarını hazırlayırıq
        $data = [
            'user_id' => $authorId,
            'parent_id' => $parentId,
            'commentable_id' => $commentableId,
            'commentable_type' => $commentableType,
            'content' => $content,
            'meta_data' => $metaData,
            'is_private' => $this->faker->boolean(10), // 10% ehtimalla şəxsi şərh
            'is_active' => true,
            'created_at' => $createdAt,
            'updated_at' => isset($metaData['last_edited_at']) ? Carbon::parse($metaData['last_edited_at']) : $createdAt
        ];

        // Şərhi yaradıb qaytarırıq
        return Comment::create($data);
    }

    /**
     * Şərh üçün meta data generasiya edir
     */
    protected function generateMetaData(int $authorId, string $content, Carbon $createdAt, bool $isReply): array
    {
        $metaData = [];

        // Reaksiyalar (cavab şərhlərində daha az ehtimalla)
        if ($this->faker->boolean($isReply ? 50 : 70)) {
            $metaData['reactions'] = $this->generateReactions();
        }

        // Redaktə tarixçəsi (30% ehtimalla)
        if ($this->faker->boolean(30)) {
            $editHistory = $this->generateEditHistory($content, $authorId, $createdAt);
            $metaData['edit_history'] = $editHistory;
            $metaData['last_edited_at'] = $editHistory[count($editHistory) - 1]['edited_at'];
        }

        // Fayllar (20% ehtimalla və əsas şərhlər üçün)
        if (!$isReply && $this->faker->boolean($this->attachmentPercentage)) {
            $metaData['attachments'] = $this->generateAttachments();
        }

        // Spam bildirişləri (8% ehtimalla)
        if ($this->faker->boolean($this->spamReportPercentage)) {
            $metaData['spam_reports'] = $this->generateSpamReports();

            // Əgər 3+ spam bildirişi varsa, şərhi deaktiv edirik
            if (count($metaData['spam_reports']) >= 3) {
                $data['is_active'] = false;
            }
        }

        return $metaData;
    }

    /**
     * Şərh məzmunu generasiya edir
     */
    protected function generateCommentContent(
        string $commentableType,
        bool $isListingComment = false,
        bool $isCompanyComment = false,
        bool $isReply = false
    ): string {
        // Əgər cavab şərhidirsə
        if ($isReply) {
            return $this->faker->boolean(30)
                ? '@' . $this->faker->userName . ' ' . $this->faker->sentence(rand(5, 15))
                : $this->faker->paragraph(rand(1, 3));
        }

        // İstifadəçi profilinə şərh
        if ($commentableType === User::class) {
            $phrases = [
                "Sizin profiliniz çox maraqlıdır!",
                "Paylaşımlarınız həmişə diqqətimi çəkir.",
                "Sizinlə əməkdaşlıq etmək çox xoş olardı.",
                "Məhsullarınız və xidmətləriniz haqqında daha ətraflı məlumat ala bilərəm?",
                "Profilinizi izləməkdən məmnun oluram.",
                "Daha çox məlumat paylaşsanız sevindirici olar.",
                "Sosial mediada da aktivsiniz?",
                "Sizi tövsiyə etməkdən zövq alıram!",
                "Uğurlarınızı izləmək çox maraqlıdır.",
                "Sizinlə əlaqə saxlamaq istərdim, mümkündür?"
            ];

            return $this->faker->randomElement($phrases) . ' ' . $this->faker->paragraph(rand(1, 3));
        }

        // Elan şərhi
        if ($isListingComment) {
            $positiveTemplates = [
                "Bu elan diqqətimi cəlb etdi. {sentence}",
                "Qiymət münasibdir. {sentence}",
                "Keyfiyyət/qiymət nisbəti əladır. {sentence}",
                "Məhsul haqqında əlavə məlumat ala bilərəm? {sentence}",
                "Bu, axtardığıma tam uyğundur. {sentence}",
                "Oxşar məhsullarınız varmı? {sentence}",
                "Əlaqə nömrənizə zəng etdim, çatmadı. {sentence}",
                "Nə vaxt baxmaq olar? {sentence}",
                "Qiymətdə endirim mümkündürmü? {sentence}",
                "Məhsulu necə əldə edə bilərəm? {sentence}"
            ];

            $negativeTemplates = [
                "Qiymət bir az yüksəkdir. {sentence}",
                "Oxşar məhsulu daha ucuz görmüşəm. {sentence}",
                "Keyfiyyəti haqqında suallarım var. {sentence}",
                "Əlavə xərclər var? {sentence}",
                "Baxdığım digər variantlardan niyə bu daha yaxşıdır? {sentence}"
            ];

            $template = $this->faker->boolean(70)
                ? $this->faker->randomElement($positiveTemplates)
                : $this->faker->randomElement($negativeTemplates);

            return str_replace('{sentence}', $this->faker->paragraph(rand(1, 4)), $template);
        }

        // Şirkət şərhi
        if ($isCompanyComment) {
            $templates = [
                "Xidmətiniz haqqında təəssüratlarım: {sentiment}. {details}",
                "Şirkətinizlə əməkdaşlıq təcrübəm: {sentiment}. {details}",
                "Məhsullarınızın keyfiyyəti: {sentiment}. {details}",
                "Müştəri xidmətiniz: {sentiment}. {details}",
                "Şirkətiniz haqqında fikirlərim: {sentiment}. {details}"
            ];

            $sentiments = [
                'positive' => ['Əla', 'Çox yaxşı', 'Məmnunam', 'Təqdirəlayiqdir', 'Yüksək səviyyədə'],
                'negative' => ['Qənaətbəxş deyil', 'İnkişaf etdirmək lazımdır', 'Gözləntilərimə cavab vermədi', 'Təəssüf ki, məyus oldum']
            ];

            $sentiment = $this->faker->boolean(75)
                ? $this->faker->randomElement($sentiments['positive'])
                : $this->faker->randomElement($sentiments['negative']);

            $template = $this->faker->randomElement($templates);

            return str_replace(
                ['{sentiment}', '{details}'],
                [$sentiment, $this->faker->paragraph(rand(2, 5))],
                $template
            );
        }

        // Default şərh
        return $this->faker->paragraph(rand(2, 5));
    }

    /**
     * Mətnə emoji əlavə edir
     */
    protected function addEmojisToContent(string $content): string
    {
        // Əlavə ediləcək emoji sayı (1-3)
        $emojiCount = rand(1, 3);

        // Əsas emojilər siyahısı
        $emojis = [
            // Üz ifadələri
            ':)' => '😊',
            ':(' => '😢',
            ';)' => '😉',
            ':D' => '😃',
            ':P' => '😛',
            ':/' => '😕',
            '<3' => '❤️',
            ':*' => '😘',
            // Digər emojilər
            'haha' => 'haha 😄',
            'lol' => 'lol 😂',
            'wow' => 'wow 😮',
            'super' => 'super 👍',
            'əla' => 'əla ⭐',
            'məyus' => 'məyus 😔',
            'hmm' => 'hmm 🤔',
            'maraqlı' => 'maraqlı 🤩',
            'təşəkkürlər' => 'təşəkkürlər 🙏',
            'gözləyirəm' => 'gözləyirəm ⏳',
        ];

        // Şirkət kommentləri üçün əlavə emojilər
        $businessEmojis = [
            'əla xidmət' => 'əla xidmət ⭐⭐⭐⭐⭐',
            'keyfiyyətli' => 'keyfiyyətli 👌',
            'tövsiyə edirəm' => 'tövsiyə edirəm 👍',
            'pis deyil' => 'pis deyil 🤷‍♂️',
            'qənaətbəxş deyil' => 'qənaətbəxş deyil 👎',
            'bahalı' => 'bahalı 💰',
            'münasib qiymət' => 'münasib qiymət 💸',
        ];

        // Elan kommentləri üçün əlavə emojilər
        $listingEmojis = [
            'nə qədər' => 'nə qədər 💲',
            'maraqlanıram' => 'maraqlanıram 👀',
            'baxmaq istəyirəm' => 'baxmaq istəyirəm 👁️',
            'əlaqə saxlayacam' => 'əlaqə saxlayacam 📞',
            'sual var' => 'sual var ❓',
            'bəyəndim' => 'bəyəndim 💯',
            'endirim' => 'endirim 🏷️',
        ];

        // Bütün emoji variantlarını birləşdiririk
        $allEmojis = $emojis;
        if (strpos($content, 'xidmət') !== false || strpos($content, 'şirkət') !== false) {
            $allEmojis = array_merge($allEmojis, $businessEmojis);
        }
        if (strpos($content, 'elan') !== false || strpos($content, 'məhsul') !== false || strpos($content, 'qiymət') !== false) {
            $allEmojis = array_merge($allEmojis, $listingEmojis);
        }

        // Təsadüfi emoji seçirik
        $selectedKeys = array_rand($allEmojis, min($emojiCount, count($allEmojis)));
        if (!is_array($selectedKeys)) {
            $selectedKeys = [$selectedKeys];
        }

        // Eyni zamanda 50% ehtimalla emoji simvollarını da (məs: :D, ;)) əlavə edirik
        $emojiSymbols = [':)', ':(', ';)', ':D', ':P', '<3', ':*', ':-)', ';-)'];
        if ($this->faker->boolean(50)) {
            // Mətndə 1-2 emoji simvolunu təsadüfi yerləşdiririk
            $symbolCount = rand(1, 2);
            for ($i = 0; $i < $symbolCount; $i++) {
                $symbol = $this->faker->randomElement($emojiSymbols);

                // Mətni sözlərə bölürük
                $words = explode(' ', $content);
                // Təsadüfi pozisiya seçirik
                $position = rand(0, count($words) - 1);
                // Sözün sonuna emoji əlavə edirik
                $words[$position] = $words[$position] . ' ' . $symbol;
                // Mətni yenidən birləşdiririk
                $content = implode(' ', $words);
            }
        }

        // Seçilmiş emojiləri mətnə əlavə edirik
        foreach ($selectedKeys as $key) {
            $searchText = $key;
            $replaceText = $allEmojis[$key];

            // Kontekstə əsasən axtarırıq və əvəz edirik
            if (strpos(strtolower($content), strtolower($searchText)) !== false) {
                // Regex ilə tam sözü tapıb əvəz edirik
                $content = preg_replace('/\b' . preg_quote($searchText, '/') . '\b/i', $replaceText, $content, 1);
            } else {
                // Əgər söz mətnə uyğun deyilsə, sadəcə sona əlavə edirik
                if ($this->faker->boolean(30)) {
                    $content .= ' ' . ($searchText === $replaceText ? $replaceText : $replaceText);
                }
            }
        }

        return $content;
    }

    /**
     * Şərh reaksiyaları generasiya edir
     */
    protected function generateReactions(): array
    {
        $reactionTypes = CommentReactionType::getValues();
        $reactions = [];

        // 1-3 reaksiya tipi seçirik
        $selectedTypes = $this->faker->randomElements(
            $reactionTypes,
            $this->faker->numberBetween(1, 3)
        );

        foreach ($selectedTypes as $type) {
            // Hər bir reaksiya tipinə 1-10 istifadəçi əlavə edirik
            $userCount = $this->faker->numberBetween(1, 10);
            $reactions[$type] = $this->faker->randomElements(
                $this->userIds,
                min($userCount, count($this->userIds))
            );
        }

        return $reactions;
    }

    /**
     * Fayllar/əlavələr generasiya edir
     */
    protected function generateAttachments(): array
    {
        $fileTypes = ['image', 'document', 'video'];
        $attachmentCount = rand(1, 3);
        $attachments = [];

        for ($i = 0; $i < $attachmentCount; $i++) {
            $fileType = $this->faker->randomElement($fileTypes);

            switch ($fileType) {
                case 'image':
                    $extension = $this->faker->randomElement(['jpg', 'png', 'webp']);
                    $name = 'photo_' . rand(1000, 9999) . '.' . $extension;
                    $url = '/storage/comments/' . Str::random(10) . '/' . $name;
                    break;
                case 'document':
                    $extension = $this->faker->randomElement(['pdf', 'doc', 'txt']);
                    $name = 'doc_' . rand(1000, 9999) . '.' . $extension;
                    $url = '/storage/comments/' . Str::random(10) . '/' . $name;
                    break;
                case 'video':
                    $extension = $this->faker->randomElement(['mp4', 'mov']);
                    $name = 'video_' . rand(1000, 9999) . '.' . $extension;
                    $url = '/storage/comments/' . Str::random(10) . '/' . $name;
                    break;
            }

            $attachments[] = [
                'type' => $fileType,
                'url' => $url,
                'name' => $name,
                'size' => rand(50, 5000) * 1024, // 50KB-5MB
                'uploaded_at' => Carbon::now()->subDays(rand(0, 30))->toDateTimeString()
            ];
        }

        return $attachments;
    }

    /**
     * Spam bildirişləri generasiya edir
     */
    protected function generateSpamReports(): array
    {
        $reportsCount = rand(1, 5); // 1-5 bildiriş
        $reports = [];

        $reasons = [
            'Spam məzmun',
            'Təhqiredici ifadələr',
            'Aldadıcı məlumat',
            'Uyğunsuz məzmun',
            'Reklam məzmunu',
            null // Bəzən səbəb göstərilmir
        ];

        for ($i = 0; $i < $reportsCount; $i++) {
            // Bildiriş edən təsadüfi istifadəçi
            $reporterId = $this->faker->randomElement($this->userIds);

            // Təsadüfi tarix (son 30 gün ərzində)
            $reportedAt = Carbon::now()->subDays(rand(0, 30))->toDateTimeString();

            // Təsadüfi səbəb
            $reason = $this->faker->randomElement($reasons);

            $reports[] = [
                'reported_by' => $reporterId,
                'reported_at' => $reportedAt,
                'reason' => $reason
            ];
        }

        return $reports;
    }

    /**
     * Şərh redaktə tarixçəsi generasiya edir
     */
    protected function generateEditHistory(string $finalContent, int $authorId, Carbon $createdAt): array
    {
        $editCount = $this->faker->numberBetween(1, 2);
        $history = [];

        // Original məzmun
        $originalContent = $this->faker->paragraph(rand(1, 3));

        // Redaktə səbəbləri
        $reasons = [
            'Yazı xətasını düzəltdim',
            'Əlavə məlumat daxil etdim',
            'Fikrimi daha aydın ifadə etmək istədim',
            'Mətnə düzəliş etdim',
            'Emoji əlavə etdim',
            null // Bəzən səbəb göstərilmir
        ];

        for ($i = 0; $i < $editCount; $i++) {
            $editTime = $createdAt->copy()->addHours(rand(1, 48));

            $history[] = [
                'old_content' => $i === 0 ? $originalContent : $this->faker->paragraph(rand(1, 3)),
                'edited_at' => $editTime->toDateTimeString(),
                'edited_by' => $authorId,
                'reason' => $this->faker->randomElement($reasons)
            ];
        }

        return $history;
    }
}
