<?php

namespace App\Services\Module;

use App\Exceptions\BaseException;
use App\Models\SeoLink;
use App\Repositories\Module\CategoryRepository;
use App\Repositories\Module\SeoLinkRepository;
use App\Services\BaseCrudService;
use App\Services\Helpers\Seo\MetaSuggestionGenerator;
use App\Services\Helpers\Seo\SeoScoreCalculator;
use App\Services\Validators\MetaTagValidator;
use Illuminate\Support\Collection;

class SeoLinkService extends BaseCrudService
{
    /**
     * Servisin istifadə etdiyi əsas helper siniflər
     */
    protected MetaTagValidator $validator;
    protected SeoScoreCalculator $scoreCalculator;
    protected MetaSuggestionGenerator $suggestionGenerator;

    /**
     * Service konstruktoru - asılılıqları inject edirik
     */
    public function __construct(
        SeoLinkRepository $repository,
        MetaTagValidator $validator,
        SeoScoreCalculator $scoreCalculator,
        MetaSuggestionGenerator $suggestionGenerator
    ) {
        parent::__construct($repository);
        $this->validator = $validator;
        $this->scoreCalculator = $scoreCalculator;
        $this->suggestionGenerator = $suggestionGenerator;
    }

    /**
     * Yeni SEO yazısı yaratmaq
     *
     * @param array $data SEO məlumatları
     * @throws BaseException
     */
    public function create(array $data): SeoLink
    {
        // URL-in unique olmasını yoxlayırıq
        if ($this->repository->findByUrl($data['url'])) {
            throw new BaseException('Bu URL üçün artıq SEO məlumatı mövcuddur');
        }

        // Məcburi meta tagların mövcudluğunu yoxlayırıq
        if (!isset($data['basic_meta']['title']) || !isset($data['basic_meta']['description'])) {
            throw new BaseException('Title və Description meta tagları məcburidir');
        }

        // Morphable model yoxlaması
        if (isset($data['seoable_type']) && isset($data['seoable_id'])) {
            // Model class-ının mövcudluğunu yoxlayırıq
            if (!class_exists($data['seoable_type'])) {
                throw new BaseException('Göstərilən model tapılmadı');
            }

            // Model-in mövcudluğunu yoxlayırıq
            $model = $data['seoable_type']::find($data['seoable_id']);
            if (!$model) {
                throw new BaseException('Göstərilən model məlumatı tapılmadı');
            }
        }

        // SEO yazısını yaradırıq
        $seo = parent::create($data);

        // SEO score-u hesablayırıq
        $score = $this->scoreCalculator->calculate($seo);
        $seo->update([
            'score' => $score['score'],
            'analysis' => $score['details']
        ]);

        return $seo;
    }

    /**
     * URL-ə görə SEO məlumatlarını əldə etmək
     */
    public function getByUrl(string $url): ?Seo
    {
        return $this->repository->findByUrl($url);
    }

    /**
     * Meta tag əlavə etmək
     *
     * @param string $uuid SEO yazısının UUID-si
     * @param array $tagData Tag məlumatları
     */
    public function addMetaTag(string $uuid, array $tagData): SeoLink
    {
        // SEO yazısını tapırıq
        $seo = $this->repository->findByUuid($uuid);

        // Tag məlumatlarını validate edirik
        $errors = $this->validator->validate($tagData);
        if (!empty($errors)) {
            throw new BaseException($errors);
        }

        // Tag-ı əlavə edirik
        $seo->addMetaTag(
            $tagData['group'],
            $tagData['type'],
            $tagData['content']
        );

        // SEO score-u yenidən hesablayırıq
        $score = $this->scoreCalculator->calculate($seo);
        $seo->score = $score['score'];
        $seo->analysis = $score['details'];

        $seo->save();

        return $seo;
    }

    /**
     * Meta tag silmək
     */
    public function removeMetaTag(string $uuid, string $group, string $type): SeoLink
    {
        $seo = $this->repository->findByUuid($uuid);
        $seo->removeMetaTag($group, $type);

        // Score yenidən hesablanır
        $score = $this->scoreCalculator->calculate($seo);
        $seo->score = $score['score'];
        $seo->analysis = $score['details'];

        $seo->save();

        return $seo;
    }

    /**
     * Preview məlumatlarını generasiya etmək
     */
    public function generatePreviews(string $uuid): array
    {
        $seo = $this->repository->findByUuid($uuid);

        return [
            'html' => $seo->generateMetaTags(),
            'google' => $seo->getGooglePreviewData(),
            'social' => $seo->getSocialPreviewData()
        ];
    }

    /**
     * Meta tag təklifləri generasiya etmək
     */
    public function generateSuggestions(string $uuid): Collection
    {
        $seo = $this->repository->findByUuid($uuid);
        return $this->suggestionGenerator->generate($seo);
    }

    /**
     * SEO analizi aparmaq
     */
    public function analyzeSeo(string $uuid): array
    {
        $seo = $this->repository->findByUuid($uuid);
        return $this->scoreCalculator->getDetailedAnalysis($seo);
    }

    /**
     * Sitemap üçün aktiv SEO yazılarını əldə etmək
     */
    public function getSitemapData(): Collection
    {
        return $this->repository->getForSitemap();
    }

    /**
     * Bulk SEO yeniləməsi
     */
    public function bulkUpdate(array $items): Collection
    {
        $updated = collect();

        foreach ($items as $item) {
            try {
                $seo = $this->repository->findByUuid($item['uuid']);
                $seo->update($item['data']);

                // Score yeniləməsi
                $score = $this->scoreCalculator->calculate($seo);
                $seo->update([
                    'score' => $score['score'],
                    'analysis' => $score['details']
                ]);

                $updated->push($seo);
            } catch (\Exception $e) {
                // Xətaları log edirik amma prosesi dayandırmırıq
                \Log::error("SEO bulk update error for UUID {$item['uuid']}: " . $e->getMessage());
                continue;
            }
        }

        return $updated;
    }

    /**
     * SEO tarixçəsini əldə etmək
     */
    public function getHistory(string $uuid): array
    {
        $seo = $this->repository->findByUuid($uuid);
        return $seo->history ?? [];
    }

    /**
     * Validasiya xətalarını yoxlamaq
     */
    public function validate(array $data): array
    {
        return $this->validator->validateAll($data);
    }

    /**
     * @param $type
     * @return \Illuminate\Database\Eloquent\Collection|array
     */
    public function getTypeOptions($type): \Illuminate\Database\Eloquent\Collection|array
    {
        if ($type === 'App\Models\Listing') {
            return [];
        }
        else if ($type === 'App\Models\Page') {
            return [];
        }
        else if ($type === 'App\Models\Category') {
            return app(CategoryRepository::class)->paginateAndFilter();
        }
        return [];
    }
}
