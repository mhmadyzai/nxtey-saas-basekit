<?php

namespace Modules\Blog\Filament\Resources\Posts;

use App\Models\Post;
use BackedEnum;
use App\Filament\TenantResource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Blog\Filament\Resources\Posts\Pages\CreatePost;
use Modules\Blog\Filament\Resources\Posts\Pages\EditPost;
use Modules\Blog\Filament\Resources\Posts\Pages\ListPosts;
use Modules\Blog\Filament\Resources\Posts\Schemas\PostForm;
use Modules\Blog\Filament\Resources\Posts\Tables\PostsTable;

class PostResource extends TenantResource
{
    protected static ?string $model = Post::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'title';
	
	public static function form(Schema $schema): Schema
    {
        return PostForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PostsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPosts::route('/'),
            'create' => CreatePost::route('/create'),
            'edit' => EditPost::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
