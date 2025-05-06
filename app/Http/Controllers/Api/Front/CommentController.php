<?php

namespace App\Http\Controllers\Api\Front;

use App\Enums\CommentTypeEnum;
use App\Http\Controllers\Controller;
use App\Repositories\Module\CommentRepository;
use App\Traits\Controller\HasValidatesRequests;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CommentController extends Controller
{
    use HasValidatesRequests;

    public CommentRepository $commentRepository;

    public function __construct(CommentRepository $commentRepository)
    {
        $this->commentRepository = $commentRepository;
    }

    /**
     * Müəyyən model üçün şərh əlavə edir
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     * @throws Exception
     */
    public function save(Request $request): JsonResponse
    {
        $rules = [
            'type' => ['required', 'in:listing,company,user'],
            'commentable_uuid' => ['required'],
            'content' => ['required'],
        ];

        if ($request->has('id')) {
            $rules['id'] = ['required', 'exists:comments,id'];
        }

        $this->validateRequest($request, $rules);

        $comment = $this->commentRepository->saveComment($request->all());

        $message = $request->has('id') ? 'Comment updated successfully' : 'Comment added successfully';

        return response()->json([
            'message' => $message,
            'comment' => $comment->getSummary()
        ], 201);

    }

    /**
     * Delete comment by UUID
     *
     * @param string $uuid Comment UUID
     * @return JsonResponse
     */
    public function delete(string $uuid): JsonResponse
    {
        try {
            $result = $this->commentRepository->deleteComment($uuid);

            if ($result) {
                return response()->json([
                    'message' => 'Məlumat uğurla silindi'
                ]);
            }

            return response()->json([
                'message' => 'Şərhi silmək mümkün olmadı'
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }


}
