<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Cms\Models\ContentType;
use Modules\Cms\Models\Entry;

return new class extends Migration
{
    public function up(): void
    {
        // Prevent running on central DB (safety check)
        if (! tenancy()->initialized) {
            return;
        }

        $page = ContentType::firstOrCreate(
            ['slug' => 'page'],
            [
                'name' => 'Page',
                'description' => 'Static pages like About, Contact',
                'icon' => 'heroicon-o-document-text',
                'fields' => [
                    ['key' => 'body', 'label' => 'Body', 'type' => 'richtext'],
                    ['key' => 'featured_image', 'label' => 'Featured Image URL', 'type' => 'text'],
                ],
                'sort_order' => 1,
            ]
        );

        $post = ContentType::firstOrCreate(
            ['slug' => 'post'],
            [
                'name' => 'Blog Post',
                'description' => 'Timed blog posts',
                'icon' => 'heroicon-o-newspaper',
                'fields' => [
                    ['key' => 'excerpt', 'label' => 'Excerpt', 'type' => 'textarea'],
                    ['key' => 'body', 'label' => 'Body', 'type' => 'richtext'],
                    ['key' => 'author', 'label' => 'Author', 'type' => 'text'],
                ],
                'sort_order' => 2,
            ]
        );

        Entry::firstOrCreate(
            ['content_type_id' => $page->id, 'slug' => 'home'],
            [
                'title' => 'Home',
                'status' => 'published',
                'published_at' => now(),
                'data' => [
                    'body' => '<h1>Welcome</h1><p>This is your home page. Edit it in the CMS.</p>',
                ],
            ]
        );

        Entry::firstOrCreate(
            ['content_type_id' => $post->id, 'slug' => 'hello-world'],
            [
                'title' => 'Hello World',
                'status' => 'published',
                'published_at' => now(),
                'data' => [
                    'excerpt' => 'Our first blog post.',
                    'body' => '<p>Welcome to the blog!</p>',
                    'author' => 'Admin',
                ],
            ]
        );
		
		Menu::firstOrCreate(
			['slug' => 'header'],
			[
				'name' => 'Header Menu',
				'location' => 'header',
				'items' => [
					['type' => 'entry', 'label' => 'Home', 'slug' => 'home', 'target' => '_self'],
					['type' => 'entry', 'label' => 'Blog', 'slug' => 'hello-world', 'target' => '_self'],
				],
			]
		);

		Menu::firstOrCreate(
			['slug' => 'footer'],
			[
				'name' => 'Footer Menu',
				'location' => 'footer',
				'items' => [
					['type' => 'url', 'label' => 'Privacy', 'url' => '/privacy', 'target' => '_self'],
					['type' => 'url', 'label' => 'Terms', 'url' => '/terms', 'target' => '_self'],
				],
			]
		);Menu::firstOrCreate(
			['slug' => 'header'],
			[
				'name' => 'Header Menu',
				'location' => 'header',
				'items' => [
					['type' => 'entry', 'label' => 'Home', 'slug' => 'home', 'target' => '_self'],
					['type' => 'entry', 'label' => 'Blog', 'slug' => 'hello-world', 'target' => '_self'],
				],
			]
		);

		Menu::firstOrCreate(
			['slug' => 'footer'],
			[
				'name' => 'Footer Menu',
				'location' => 'footer',
				'items' => [
					['type' => 'url', 'label' => 'Privacy', 'url' => '/privacy', 'target' => '_self'],
					['type' => 'url', 'label' => 'Terms', 'url' => '/terms', 'target' => '_self'],
				],
			]
		);
    }

    public function down(): void
    {
        // Non-destructive
    }
};