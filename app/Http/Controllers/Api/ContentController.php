<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Inquiry;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    /**
     * List all published pages for footer / navigation.
     */
    public function pages(): JsonResponse
    {
        $pages = Page::published()
            ->get(['id', 'title', 'slug', 'meta_title', 'meta_description']);

        return response()->json([
            'success' => true,
            'data' => $pages,
        ]);
    }

    /**
     * Get single CMS page by slug (e.g. about-us, privacy-policy).
     */
    public function page(string $slug): JsonResponse
    {
        $page = Page::published()
            ->where('slug', $slug)
            ->first();

        if (! $page) {
            return response()->json([
                'success' => false,
                'message' => 'Page not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $page,
        ]);
    }

    /**
     * List published blog posts with category filter & search.
     */
    public function blogs(Request $request): JsonResponse
    {
        $query = BlogPost::published();

        if ($request->filled('category')) {
            $query->where('category', $request->query('category'));
        }

        if ($request->filled('search')) {
            $s = trim($request->query('search'));
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                    ->orWhere('excerpt', 'like', "%{$s}%")
                    ->orWhere('content', 'like', "%{$s}%");
            });
        }

        $perPage = min(30, max(1, (int) $request->query('per_page', 9)));
        $posts = $query->latest('published_at')->paginate($perPage);

        // Get list of unique categories
        $categories = BlogPost::published()
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category');

        return response()->json([
            'success' => true,
            'data' => $posts->items(),
            'categories' => $categories,
            'meta' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'total' => $posts->total(),
            ],
        ]);
    }

    /**
     * Get single blog post by slug with related posts.
     */
    public function blogDetail(string $slug): JsonResponse
    {
        $post = BlogPost::published()
            ->where('slug', $slug)
            ->first();

        if (! $post) {
            return response()->json([
                'success' => false,
                'message' => 'Article not found.',
            ], 404);
        }

        $related = BlogPost::published()
            ->where('id', '!=', $post->id)
            ->where('category', $post->category)
            ->take(3)
            ->get(['id', 'title', 'slug', 'excerpt', 'cover_image', 'category', 'author_name', 'read_time_minutes', 'published_at']);

        return response()->json([
            'success' => true,
            'data' => [
                'post' => $post,
                'related_posts' => $related,
            ],
        ]);
    }

    /**
     * Submit customer inquiry from Next.js Contact Us page.
     */
    public function submitContact(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:150',
            'phone' => 'nullable|string|max:30',
            'subject' => 'nullable|string|max:200',
            'message' => 'required|string|max:5000',
        ]);

        $inquiry = Inquiry::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'subject' => $validated['subject'] ?? 'General Store Inquiry',
            'message' => $validated['message'],
            'status' => 'unread',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thank you for reaching out! We have received your message and will get back to you shortly.',
            'data' => [
                'id' => $inquiry->id,
                'created_at' => $inquiry->created_at,
            ],
        ], 201);
    }
}
