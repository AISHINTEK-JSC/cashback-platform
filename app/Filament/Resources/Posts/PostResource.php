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
use Filament\Schemas\Components\Utilities\Get;
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
                            TextInput::make('slug')->label('Đường dẫn')->prefix('/blog/')->required()->alphaDash()->maxLength(255)->unique(ignoreRecord: true)
                                ->helperText('Không dấu, nối bằng gạch ngang, nên chứa từ khoá chính. Hạn chế đổi sau khi đã đăng vì link cũ sẽ hỏng.'),
                            Textarea::make('excerpt')->label('Tóm tắt')->rows(2)->maxLength(500)
                                ->helperText('Hiện ở danh sách bài viết, dùng làm mô tả khi chia sẻ nếu bỏ trống meta description.'),
                            RichEditor::make('content')->label('Nội dung')->required()->fileAttachmentsDisk('uploads')->fileAttachmentsVisibility('public')->fileAttachmentsDirectory('posts/content')
                                ->helperText('Chia bài bằng Tiêu đề 2, Tiêu đề 3; chèn link tới bài liên quan, trang mã giảm giá, tỷ lệ hoàn tiền để giữ người đọc ở lại lâu hơn.'),
                        ]),
                        Section::make('SEO')
                            ->description('Cách bài hiện trên Google và khi chia sẻ Facebook, Zalo. Để trống thì dùng tiêu đề và tóm tắt.')
                            ->collapsible()
                            ->schema([
                                TextInput::make('meta_title')->label('Meta title')->maxLength(255)->live(debounce: 500)
                                    ->placeholder(fn (Get $get): ?string => $get('title'))
                                    ->helperText(fn (Get $get): string => self::lengthHint(($get('meta_title') ?: $get('title')).' - '.setting('site_name'), 60, 'Tiêu đề trên Google (kể cả tên website)')),
                                Textarea::make('meta_description')->label('Meta description')->rows(2)->maxLength(500)->live(debounce: 500)
                                    ->placeholder(fn (Get $get): ?string => $get('excerpt'))
                                    ->helperText(fn (Get $get): string => self::lengthHint((string) ($get('meta_description') ?: $get('excerpt')), 160, 'Mô tả trên Google')),
                            ]),
                    ]),
                Group::make()
                    ->schema([
                        Section::make()->schema([
                            Select::make('status')->label('Trạng thái')->options(PostStatus::class)->default(PostStatus::Draft)->required(),
                            DateTimePicker::make('published_at')->label('Ngày đăng')->default(now())->seconds(false)
                                ->helperText('Chọn thời điểm trong tương lai để hẹn giờ đăng. Để trống thì đăng ngay khi lưu ở trạng thái "Đã đăng".'),
                            Select::make('category_id')->label('Danh mục')->relationship('category', 'name')->createOptionForm([
                                TextInput::make('name')->label('Tên')->required(),
                                TextInput::make('slug')->label('Đường dẫn')->required()->alphaDash(),
                            ]),
                            FileUpload::make('thumbnail')->label('Ảnh đại diện')->image()->disk('uploads')->visibility('public')->directory('posts')
                                ->imageEditor()->imageEditorAspectRatioOptions(['1.91:1', '16:9', null])->maxSize(4096)
                                ->helperText('Nên dùng ảnh ngang 1200×630 để hiển thị đẹp khi chia sẻ. Bỏ trống thì dùng ảnh mặc định của website.'),
                        ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                ImageColumn::make('thumbnail')->label('')->disk('uploads')->visibility('public')->square(),
                TextColumn::make('title')->label('Tiêu đề')->searchable()->limit(60),
                TextColumn::make('category.name')->label('Danh mục')->placeholder('-'),
                TextColumn::make('status')->label('Trạng thái')->badge()
                    ->description(fn (Post $record): ?string => $record->isScheduled() ? 'Hẹn '.$record->published_at->format('H:i d/m') : null),
                TextColumn::make('view_count')->label('Lượt xem')->sortable(),
                TextColumn::make('published_at')->label('Ngày đăng')->dateTime('d/m/Y')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Trạng thái')->options(PostStatus::class),
                SelectFilter::make('category_id')->label('Danh mục')->relationship('category', 'name'),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('open')->label(fn (Post $record): string => $record->isPublished() ? 'Xem' : 'Xem trước')->icon(Heroicon::OutlinedArrowTopRightOnSquare)->url(fn (Post $record) => route('posts.show', $record))->openUrlInNewTab(),
            ]);
    }

    /**
     * Gợi ý độ dài cho tiêu đề/mô tả SEO: Google cắt bớt phần vượt quá khoảng $max ký tự.
     */
    private static function lengthHint(string $text, int $max, string $label): string
    {
        $length = mb_strlen(trim($text));

        return $length > $max
            ? "{$label}: {$length}/{$max} ký tự, dài quá nên Google sẽ cắt bớt phần cuối."
            : "{$label}: {$length}/{$max} ký tự.";
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
