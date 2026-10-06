<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    /**
     * Display a listing of blog posts.
     */
    public function index(Request $request): Response
    {
        $query = BlogPost::query();

        // Search
        if ($request->filled('search')) {
            $s = trim($request->query('search'));
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                    ->orWhere('excerpt', 'like', "%{$s}%")
                    ->orWhere('category', 'like', "%{$s}%")
                    ->orWhere('author_name', 'like', "%{$s}%");
            });
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->query('category'));
        }

        // Filter by status
        if ($request->filled('status')) {
            if ($request->query('status') === 'published') {
                $query->where('is_published', true);
            } elseif ($request->query('status') === 'draft') {
                $query->where('is_published', false);
            }
        }

        $posts = $query->latest('created_at')
            ->paginate(12)
            ->withQueryString();

        $metrics = [
            'total_posts' => BlogPost::count(),
            'published_posts' => BlogPost::where('is_published', true)->count(),
            'draft_posts' => BlogPost::where('is_published', false)->count(),
            'categories_count' => BlogPost::whereNotNull('category')->distinct('category')->count('category'),
        ];

        $categories = BlogPost::whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->pluck('category')
            ->values();

        return Inertia::render('Admin/Blogs/Index', [
            'posts' => $posts,
            'metrics' => $metrics,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category', 'status']),
        ]);
    }

    /**
     * Store a newly created blog post.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:blog_posts,slug',
            'excerpt' => 'nullable|string|max:500',
            'content' => 'required|string',
            'cover_image' => 'nullable|string|max:2048',
            'category' => 'nullable|string|max:100',
            'author_name' => 'nullable|string|max:100',
            'read_time_minutes' => 'nullable|integer|min:1|max:120',
            'is_published' => 'boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ]);

        $slug = ! empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['title']);

        // Check uniqueness for generated slug
        if (BlogPost::where('slug', $slug)->exists()) {
            $slug = $slug.'-'.Str::random(4);
        }

        $isPublished = $validated['is_published'] ?? true;

        BlogPost::create([
            'title' => $validated['title'],
            'slug' => $slug,
            'excerpt' => $validated['excerpt'] ?? null,
            'content' => $validated['content'],
            'cover_image' => $validated['cover_image'] ?? null,
            'category' => $validated['category'] ?? 'General',
            'author_name' => $validated['author_name'] ?: 'Editorial Team',
            'read_time_minutes' => $validated['read_time_minutes'] ?? max(1, (int) ceil(str_word_count(strip_tags($validated['content'])) / 200)),
            'is_published' => $isPublished,
            'published_at' => $isPublished ? now() : null,
            'meta_title' => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
        ]);

        return redirect()->back()->with('success', 'Blog article published successfully.');
    }

    /**
     * Update an existing blog post.
     */
    public function update(Request $request, BlogPost $blogPost): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:blog_posts,slug,'.$blogPost->id,
            'excerpt' => 'nullable|string|max:500',
            'content' => 'required|string',
            'cover_image' => 'nullable|string|max:2048',
            'category' => 'nullable|string|max:100',
            'author_name' => 'nullable|string|max:100',
            'read_time_minutes' => 'nullable|integer|min:1|max:120',
            'is_published' => 'boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ]);

        $slug = ! empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : $blogPost->slug;

        $isPublished = $validated['is_published'] ?? $blogPost->is_published;
        $publishedAt = $blogPost->published_at;
        if ($isPublished && ! $publishedAt) {
            $publishedAt = now();
        }

        $blogPost->update([
            'title' => $validated['title'],
            'slug' => $slug,
            'excerpt' => $validated['excerpt'] ?? null,
            'content' => $validated['content'],
            'cover_image' => $validated['cover_image'] ?? null,
            'category' => $validated['category'] ?? $blogPost->category,
            'author_name' => ! empty($validated['author_name']) ? $validated['author_name'] : $blogPost->author_name,
            'read_time_minutes' => $validated['read_time_minutes'] ?? $blogPost->read_time_minutes,
            'is_published' => $isPublished,
            'published_at' => $publishedAt,
            'meta_title' => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
        ]);

        return redirect()->back()->with('success', 'Blog article updated successfully.');
    }

    /**
     * Quick toggle published status.
     */
    public function toggleStatus(BlogPost $blogPost): RedirectResponse
    {
        $newStatus = ! $blogPost->is_published;
        $blogPost->update([
            'is_published' => $newStatus,
            'published_at' => $newStatus && ! $blogPost->published_at ? now() : $blogPost->published_at,
        ]);

        return redirect()->back()->with('success', 'Article status updated.');
    }

    /**
     * Upload cover image asynchronously for blog editor.
     */
    public function uploadCover(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:4096', 'dimensions:max_width=4000,max_height=4000'],
        ]);

        $path = $request->file('image')->store('blogs', 'public');

        return response()->json([
            'url' => asset('storage/'.$path),
        ]);
    }

    /**
     * Delete a blog post.
     */
    public function destroy(BlogPost $blogPost): RedirectResponse
    {
        $cover = $blogPost->cover_image;
        $blogPost->delete();

        if ($cover) {
            $path = str_replace('/storage/', '', parse_url($cover, PHP_URL_PATH) ?? $cover);
            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        return redirect()->back()->with('success', 'Blog article deleted.');
    }
}
