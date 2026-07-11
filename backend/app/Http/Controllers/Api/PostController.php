<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Post\CreatePostRequest;
use App\Http\Requests\Post\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Services\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PostController
 * Full CRUD for posts with media upload and scheduling.
 */
class PostController extends Controller
{
    public function __construct(protected PostService $postService)
    {
        $this->middleware('auth:api');
    }

    public function index(Request $request): JsonResponse
    {
        $isAdmin = auth()->user()->isAdmin();
        $posts   = $this->postService->paginate($request->only([
            'search', 'status', 'platform', 'post_type',
            'date_from', 'date_to', 'sort_by', 'sort_dir', 'per_page',
        ]), $isAdmin);

        return response()->json([
            'success' => true,
            'data'    => PostResource::collection($posts->items()),
            'meta'    => [
                'total'        => $posts->total(),
                'per_page'     => $posts->perPage(),
                'current_page' => $posts->currentPage(),
                'last_page'    => $posts->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $post = $this->postService->find($id);

        // Non-admin can only see their own posts
        if (! auth()->user()->isAdmin() && $post->user_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        return response()->json(['success' => true, 'data' => new PostResource($post)]);
    }

    public function store(CreatePostRequest $request): JsonResponse
    {
        try {
            $post = $this->postService->create(
                data:       $request->validated(),
                mediaFiles: $request->file('media', []),
            );

            return response()->json([
                'success' => true,
                'message' => 'Post created successfully.',
                'data'    => new PostResource($post),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function update(UpdatePostRequest $request, int $id): JsonResponse
    {
        try {
            // Only owner or admin can update
            $post = $this->postService->find($id);
            if (! auth()->user()->isAdmin() && $post->user_id !== auth()->id()) {
                return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
            }

            $post = $this->postService->update(
                id:         $id,
                data:       $request->validated(),
                mediaFiles: $request->file('media', []),
            );

            return response()->json([
                'success' => true,
                'message' => 'Post updated successfully.',
                'data'    => new PostResource($post),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getCode() ?: 422);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $post = $this->postService->find($id);
            if (! auth()->user()->isAdmin() && $post->user_id !== auth()->id()) {
                return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
            }

            $this->postService->delete($id);
            return response()->json(['success' => true, 'message' => 'Post deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getCode() ?: 422);
        }
    }

    public function deleteMedia(int $mediaId): JsonResponse
    {
        try {
            $this->postService->deleteMedia($mediaId);
            return response()->json(['success' => true, 'message' => 'Media deleted.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function stats(): JsonResponse
    {
        $userId = auth()->user()->isAdmin() ? null : auth()->id();
        return response()->json([
            'success' => true,
            'data'    => $this->postService->getDashboardStats(),
        ]);
    }
}
