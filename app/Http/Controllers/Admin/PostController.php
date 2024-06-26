<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PostRequest;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\User;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::with(['user', 'category', 'tags', 'comments']);

        if (!auth()->user()->is_admin) {
            $posts = $posts
                ->where('user_id', '=', auth()->user()->id)
                ->orWhere('is_published', '=', 1);
        }

        $posts = $posts->paginate(10);

        return view('admin.posts.index', compact('posts'));
    }

    public function create()
    {
        $categories = Category::pluck('name', 'id')->all();
        $tags = Tag::pluck('name', 'name')->all();

        if (!auth()->user()->is_admin) {
            return view('admin.posts.create', compact('categories', 'tags'));
        }

        $users = User::pluck('name', 'id')->all();

        return view('admin.posts.create', compact('categories', 'tags', 'users'));
    }

    public function store(PostRequest $request)
    {
        $post = Post::create(
            [
                'title'       => $request->title,
                'body'        => $request->body,
                'category_id' => $request->category_id,
            ]
        );

        $tagsId = collect($request->tags)->map(
            function ($tag) {
                return Tag::firstOrCreate(['name' => $tag])->id;
            }
        );

        $post->tags()->attach($tagsId);

        flash()->overlay('Post created successfully.');

        return redirect('/admin/posts');
    }

    public function show(Post $post)
    {
        $post = $post->load(['user', 'category', 'tags', 'comments']);

        return view('admin.posts.show', compact('post'));
    }

    public function edit(Post $post)
    {
        if ($post->user_id != auth()->user()->id && auth()->user()->is_admin == false) {
            flash()->overlay("You can't edit other peoples post.");

            return redirect('/admin/posts');
        }

        $categories = Category::pluck('name', 'id')->all();
        $tags = Tag::pluck('name', 'name')->all();

        if (!auth()->user()->is_admin) {
            return view('admin.posts.edit', compact('post', 'categories', 'tags'));
        }

        $users = User::pluck('name', 'id')->all();

        return view('admin.posts.edit', compact('post', 'categories', 'tags', 'users'));
    }

    public function update(PostRequest $request, Post $post)
    {
        $post->update(
            array_merge(
                [
                    'title'       => $request->title,
                    'body'        => $request->body,
                    'category_id' => $request->category_id,
                ],
                (auth()->user()->is_admin ? [
                    'user_id'     => $request->user_id,
                ] : [])
            )
        );

        $tagsId = collect($request->tags)->map(
            function ($tag) {
                return Tag::firstOrCreate(['name' => $tag])->id;
            }
        );

        $post->tags()->sync($tagsId);
        flash()->overlay('Post updated successfully.');

        return redirect('/admin/posts');
    }

    public function destroy(Post $post)
    {
        if ($post->user_id != auth()->user()->id && auth()->user()->is_admin == false) {
            flash()->overlay("You can't delete other peoples post.");

            return redirect('/admin/posts');
        }

        $post->delete();
        flash()->overlay('Post deleted successfully.');

        return redirect('/admin/posts');
    }

    public function publish(Post $post)
    {
        $post->is_published = !$post->is_published;
        $post->save();
        flash()->overlay('Post changed successfully.');

        return redirect('/admin/posts');
    }
}
