<?php

namespace App\Repositories\Module;

use App\Enums\CommentTypeEnum;
use App\Exceptions\BaseException;
use App\Models\Comment;
use App\Repositories\BaseRepository;
use App\Services\Filter\CommentFilter;
use Exception;

class CommentRepository extends BaseRepository
{
    public function __construct(Comment $model)
    {
        parent::__construct($model);
        $this->setFilter(new CommentFilter(request()));
        $this->with = [
            'commentable',
            'author',
            'parent',
            'replies',
        ];
        $this->withCount(['replies']);
    }

    public function filters(): array
    {
        $types = collect(CommentTypeEnum::getValues())->map(fn($i) => [
            'id' => $i,
            'name' => CommentTypeEnum::getDescription($i),
        ]);

        return [
            'types' => $types,
        ];
    }

    /**
     * Şərh yaradır və ya yeniləyir
     *
     * @param array $data Şərh məlumatları
     * @return Comment
     * @throws Exception
     */
    public function saveComment(array $data): Comment
    {
        // Əgər ID varsa, mövcud şərhi yeniləyirik
        if (!empty($data['id'])) {
            return $this->updateComment($data['id'], $data);
        }

        // Əks halda yeni şərh yaradırıq
        return $this->createComment($data);
    }

    /**
     * Yeni şərh yaradır
     *
     * @param array $data
     * @return Comment
     * @throws Exception
     */
    protected function createComment(array $data): Comment
    {
        // Model sinfini əldə edirik
        $classPath = CommentTypeEnum::getClassPath($data['type']);

        if (!$classPath) {
            throw new BaseException(['type' => 'Düzgün tipi göndərin'], 422);
        }

        // UUID-yə görə modeli tapırıq
        $model = $classPath::where('uuid', $data['commentable_uuid'])->first();

        if (!$model) {
            throw new BaseException(['commentable_uuid' => 'Göndərilən dəyər yanlışdır'], 422);
        }

        // Şərh məlumatlarını hazırlayırıq
        $commentData = [
            'content' => $data['content'],
            'user_id' => auth()->id(),
            'is_active' => false,
            'parent_id' => $data['parent_id'] ?? null
        ];

        // Şərhi yaradırıq
        $comment = new Comment($commentData);
        $model->comments()->save($comment);

        return $comment->fresh();
    }

    /**
     * Mövcud şərhi yeniləyir
     *
     * @param int $id
     * @param array $data
     * @return Comment
     * @throws Exception
     */
    protected function updateComment(int $id, array $data): Comment
    {
        // Şərhi tapırıq
        $comment = $this->commentRepository->findById($id);

        // Şərhin redaktə edilə biləcəyini yoxlayırıq
        if (!$comment->can_edit) {
            throw new BaseException('You do not have permission to edit this comment');
        }

        // Şərhi redaktə edirik
        $comment->edit(
            $data['content'],
            $data['edit_reason'] ?? null
        );

        // Digər sahələri yeniləyirik
        if (isset($data['is_private'])) {
            $comment->is_private = $data['is_private'];
        }

        // Meta məlumatları yeniləyirik (əgər varsa)
        if (!empty($data['meta_data'])) {
            if (is_array($data['meta_data'])) {
                foreach ($data['meta_data'] as $key => $value) {
                    $comment->setMetaData($key, $value);
                }
            }
        }

        $comment->save();

        return $comment->fresh();
    }

    /**
     * Şərhi silir
     *
     * @param string $uuid Şərhin UUID-si
     * @return bool
     * @throws BaseException
     */
    public function deleteComment(string $uuid): bool
    {
        // UUID-yə görə şərhi tapırıq
        $comment = Comment::where('uuid', $uuid)->first();

        if (!$comment) {
            throw new BaseException('Məlumat tapılmadı', 404);
        }

        // Silmə icazəsini yoxlayırıq
        if (!$this->canDeleteComment($comment)) {
            throw new BaseException('Sizin bu şərhi silmək icazəniz yoxdur');
        }

        // Şərhi silirik
        return $comment->delete();
    }

    /**
     * Şərhin silinə biləcəyini yoxlayır
     *
     * @param Comment $comment
     * @return bool
     */
    protected function canDeleteComment(Comment $comment): bool
    {
        if (!auth()->check()) {
            return false;
        }

        $user = auth()->user();

        // Admin həmişə silə bilər
        if ($user->hasRole('admin')) {
            return true;
        }

        // Şərh sahibi öz şərhini silə bilər
        if ($comment->user_id === $user->id) {
            return true;
        }

        // Məzmunun sahibi də şərhi silə bilər
        $commentable = $comment->commentable;
        if ($commentable && property_exists($commentable, 'user_id') && $commentable->user_id === $user->id) {
            return true;
        }

        return false;
    }
}
