<?php

namespace App\Filament\Resources\Posts;

use App\Enums\PostStatus;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Filament\Support\NavigationGroup;
use App\Models\Post;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Content;

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'bài viết';

    protected static ?string $pluralModelLabel = 'Bài viết';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make()
                    ->columnSpan(2)
                    ->schema([
                        Section::make()->schema([
                            TextInput::make('title')->label('Tiêu đề')->required()->maxLength(255)->live(onBlur: true)
                                ->afterStateUpdated(fn (Set $set, ?string $state, string $operation) => $operation === 'create' ? $set('slug', Str::slug((string) $state)) : null),
                            TextInput::make('slug')->label('Đường dẫn')->required()->alphaDash()->maxLength(255)->unique(ignoreRecord: true),
                            Textarea::make('excerpt')->label('Tóm tắt')->rows(2)->maxLength(500),
                            RichEditor::make('content')->label('Nội dung')->required()->fileAttachmentsDisk('public')->fileAttachmentsDirectory('posts/content'),
                        ]),
                        Section::make('SEO')->collapsible()->schema([
                            TextInput::make('meta_title')->label('Meta title')->maxLength(255),
                            Textarea::make('meta_description')->label('Meta description')->rows(2)->maxLength(500),
                        ]),
                    ]),
                Group::make()
                    ->schema([
                        Section::make()->schema([
                            Select::make('status')->label('Trạng thái')->options(PostStatus::class)->default(PostStatus::Draft)->required(),
                            DateTimePicker::make('published_at')->label('Ngày đăng')->default(now()),
                            Select::make('category_id')->label('Danh mục')->relationship('category', 'name')->createOptionForm([
                                TextInput::make('name')->label('Tên')->required(),
                                TextInput::make('slug')->label('Đường dẫn')->required()->alphaDash(),
                            ]),
                            FileUpload::make('thumbnail')->label('Ảnh đại diện')->image()->disk('public')->directory('posts'),
                        ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                ImageColumn::make('thumbnail')->label('')->disk('public')->square(),
                TextColumn::make('title')->label('Tiêu đề')->searchable()->limit(60),
                TextColumn::make('category.name')->label('Danh mục')->placeholder('-'),
                TextColumn::make('status')->label('Trạng thái')->badge(),
                TextColumn::make('view_count')->label('Lượt xem')->sortable(),
                TextColumn::make('published_at')->label('Ngày đăng')->dateTime('d/m/Y')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Trạng thái')->options(PostStatus::class),
                SelectFilter::make('category_id')->label('Danh mục')->relationship('category', 'name'),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('open')->label('Xem')->icon(Heroicon::OutlinedArrowTopRightOnSquare)->url(fn (Post $record) => route('posts.show', $record))->openUrlInNewTab(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPosts::route('/'),
            'create' => CreatePost::route('/create'),
            'edit' => EditPost::route('/{record}/edit'),
        ];
    }
}
