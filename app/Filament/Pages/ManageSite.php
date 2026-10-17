<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * @property-read Schema $form
 */
class ManageSite extends Page
{
    protected string $view = 'filament.pages.manage-site';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Configurações do site';

    protected static ?string $title = 'Configurações do site';

    protected static ?string $slug = 'site';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'hero_image' => Setting::read(Setting::HOME_HERO_IMAGE),
            'tagline' => Setting::read(Setting::HOME_TAGLINE),
            'contact_email' => Setting::read(Setting::CONTACT_EMAIL),
            'music_links' => Setting::musicLinks(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Página inicial')
                    ->schema([
                        FileUpload::make('hero_image')
                            ->label('Foto de abertura')
                            ->helperText('Foto horizontal grande (pelo menos 1920 px de largura), sem texto ou logo por cima. Sem foto, a página inicial usa a foto oficial da banda.')
                            ->disk('public')
                            ->directory('site')
                            ->visibility('public')
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(15 * 1024),
                        TextInput::make('tagline')
                            ->label('Frase de abertura')
                            ->helperText('Opcional. Aparece abaixo do nome da banda.')
                            ->maxLength(160),
                    ]),
                Section::make('Música nas plataformas')
                    ->schema([
                        Repeater::make('music_links')
                            ->hiddenLabel()
                            ->helperText('Links para as músicas da banda. Aparecem na página inicial e no rodapé, na ordem desta lista.')
                            ->schema([
                                TextInput::make('name')
                                    ->label('Plataforma')
                                    ->datalist(['Spotify', 'Deezer', 'YouTube Music', 'Apple Music', 'Amazon Music', 'Tidal', 'SoundCloud', 'Bandcamp'])
                                    ->required()
                                    ->maxLength(60),
                                TextInput::make('url')
                                    ->label('Link')
                                    ->url()
                                    ->startsWith(['https://', 'http://'])
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->columns(2)
                            ->reorderable()
                            ->defaultItems(0)
                            ->addActionLabel('Adicionar plataforma'),
                    ]),
                Section::make('Contato')
                    ->schema([
                        TextInput::make('contact_email')
                            ->label('E-mail que recebe as mensagens do site')
                            ->helperText('Sem e-mail, as mensagens ficam só aqui no painel, em "Mensagens de contato".')
                            ->email()
                            ->maxLength(255),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Setting::write(Setting::HOME_HERO_IMAGE, $data['hero_image'] ?? null);
        Setting::write(Setting::HOME_TAGLINE, $data['tagline'] ?? null);
        Setting::write(Setting::CONTACT_EMAIL, $data['contact_email'] ?? null);
        Setting::write(Setting::MUSIC_LINKS, json_encode(array_values($data['music_links'] ?? []), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        Notification::make()
            ->success()
            ->title('Configurações salvas')
            ->send();
    }
}
