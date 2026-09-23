<?php

namespace Modules\Cms\Filament\Resources;

use App\Filament\TenantResource;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Modules\Cms\Filament\Resources\EntryResource\Pages;
use Modules\Cms\Models\ContentType;
use Modules\Cms\Models\Entry;
use Modules\Cms\Models\Media;
use UnitEnum;
use Modules\Cms\Blocks\BlockRegistry;

class EntryResource extends TenantResource
{
    protected static ?string $model = Entry::class;

    protected static UnitEnum|string|null $navigationGroup = 'Content';
    protected static ?string $navigationLabel = 'Entries';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Select::make('content_type_id')
                ->label('Content Type')
                ->options(ContentType::orderBy('sort_order')->pluck('name', 'id'))
                ->required()
                ->live()
                ->afterStateUpdated(fn (Set $set) => $set('data', [])),

            Forms\Components\TextInput::make('title')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(function (string $state, Set $set, Get $get) {
					if (! $get('slug')) {
						$set('slug', Str::slug($state));
					}
				}),

            Forms\Components\TextInput::make('slug')
                ->required()
                ->maxLength(200)
                ->unique(ignoreRecord: true),

            Forms\Components\Select::make('status')
                ->options([
                    'draft' => 'Draft',
                    'published' => 'Published',
                    'scheduled' => 'Scheduled',
                ])
                ->default('draft')
                ->required(),

            Forms\Components\DateTimePicker::make('published_at'),

            \Filament\Schemas\Components\Section::make('Custom Fields')
			->schema(fn (Get $get): array => self::buildCustomFields($get('content_type_id')))
			->columnSpanFull(),
			
			// ➡️ NEW SEO SECTION PLACED HERE
			\Filament\Schemas\Components\Section::make('SEO')
				->schema([
					Forms\Components\TextInput::make('seo_title')
						->label('SEO Title')
						->maxLength(255)
						->helperText('Leave blank to use the entry title.'),

					Forms\Components\Textarea::make('seo_description')
						->label('Meta Description')
						->rows(2)
						->maxLength(300),

					Forms\Components\Select::make('og_image_id')
						->label('OG Image')
						->options(Media::latest()->pluck('filename', 'id'))
						->searchable(),

					Forms\Components\TextInput::make('canonical_url')
						->label('Canonical URL')
						->url()
						->maxLength(500),

					Forms\Components\Toggle::make('no_index')
						->label('Hide from search engines'),
				])
				->collapsible()
				->columnSpanFull(),
			
			\Filament\Schemas\Components\Section::make('Content Blocks')
				->schema([
					Forms\Components\Repeater::make('blocks')
						->label('')
						->schema([
							Forms\Components\Select::make('type')
								->options(collect(app(BlockRegistry::class)->all())
									->mapWithKeys(fn ($def, $key) => [$key => $def['label']])
									->toArray())
								->required()
								->live()
								->afterStateUpdated(function (Set $set) {
									// Reset only the data key (not the whole item)
									$set('data', []);
								}),

							\Filament\Schemas\Components\Section::make()
								->schema(function (Get $get) {
									$type = $get('type');
									if (! $type) return [];

									$registry = app(BlockRegistry::class);
									$definition = $registry->get($type);
									if (! $definition) return [];

									$schemaClass = $definition['schema'];
									return $schemaClass::components();
								})
								->visible(fn (Get $get) => filled($get('type')))
								->columnSpanFull(),
						])
						->itemLabel(fn (array $state): ?string => 
							filled($state['type'])
								? (app(BlockRegistry::class)->get($state['type'])['label'] ?? $state['type'])
								: 'New block'
						)
						->collapsible()
						->cloneable()
						->reorderable()
						->addActionLabel('Add block')
						->columnSpanFull(),
				])
				->visible(fn (Get $get) => filled($get('content_type_id')))
				->columnSpanFull(),
        ]);
    }

    protected static function buildCustomFields(?int $contentTypeId): array
    {
        if (! $contentTypeId) {
            return [];
        }

        $type = ContentType::find($contentTypeId);
        if (! $type || empty($type->fields)) {
            return [];
        }

        $components = [];

        foreach ($type->fields as $field) {
            $key = 'data.' . $field['key'];
            $label = $field['label'] ?? $field['key'];

            $components[] = match ($field['type']) {
                'text' => Forms\Components\TextInput::make($key)->label($label),
                'textarea' => Forms\Components\Textarea::make($key)->label($label)->rows(4),
                'richtext' => Forms\Components\RichEditor::make($key)->label($label),
                'number' => Forms\Components\TextInput::make($key)->label($label)->numeric(),
                'boolean' => Forms\Components\Toggle::make($key)->label($label),
                'date' => Forms\Components\DatePicker::make($key)->label($label),
                'select' => Forms\Components\Select::make($key)->label($label)
                    ->options(array_combine($field['options'] ?? [], $field['options'] ?? [])),
                default => Forms\Components\TextInput::make($key)->label($label),
            };
        }

        return $components;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('contentType.name')->label('Type')->badge(),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->color(fn (string $state) => match ($state) {
                        'published' => 'success',
                        'scheduled' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('published_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('updated_at')->since()->label('Updated'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('content_type_id')
                    ->label('Type')
                    ->relationship('contentType', 'name'),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                        'scheduled' => 'Scheduled',
                    ]),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEntries::route('/'),
            'create' => Pages\CreateEntry::route('/create'),
            'edit' => Pages\EditEntry::route('/{record}/edit'),
        ];
    }
}