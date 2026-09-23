<?php

namespace Modules\Cms\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Cms\Blocks\BlockRegistry;

class CmsServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(BlockRegistry::class, function () {
			$registry = new BlockRegistry();

			$registry->register('hero', [
				'label' => 'Hero',
				'icon' => 'heroicon-o-sparkles',
				'schema' => \Modules\Cms\Blocks\Schemas\HeroSchema::class,
				'view' => 'cms::blocks.hero',
			]);

			$registry->register('text', [
				'label' => 'Text',
				'icon' => 'heroicon-o-document-text',
				'schema' => \Modules\Cms\Blocks\Schemas\TextSchema::class,
				'view' => 'cms::blocks.text',
			]);

			$registry->register('image', [
				'label' => 'Image',
				'icon' => 'heroicon-o-photo',
				'schema' => \Modules\Cms\Blocks\Schemas\ImageSchema::class,
				'view' => 'cms::blocks.image',
			]);

			$registry->register('cta', [
				'label' => 'Call to Action',
				'icon' => 'heroicon-o-megaphone',
				'schema' => \Modules\Cms\Blocks\Schemas\CtaSchema::class,
				'view' => 'cms::blocks.cta',
			]);

			$registry->register('gallery', [
				'label' => 'Gallery',
				'icon' => 'heroicon-o-rectangle-stack',
				'schema' => \Modules\Cms\Blocks\Schemas\GallerySchema::class,
				'view' => 'cms::blocks.gallery',
			]);

			return $registry;
		});
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'cms');
    }
}
