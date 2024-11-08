<?php

namespace App\Filament\Resources;

use App\Enums\UserType;
use App\Filament\Resources\GuruResource\Pages;
use App\Filament\Resources\GuruResource\RelationManagers;
use App\Models\Guru;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use function Filament\Support\get_model_label;

class GuruResource extends Resource
{
    protected static ?string $model = Guru::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function canViewAny(): bool
    {
        return auth()->user()->type == UserType::Admin;
    }

    public static function getNavigationGroup(): ?string
    {
        return trans('admin.menu.master');
    }

    public static function getNavigationLabel(): string
    {
        return static::$navigationLabel ?? static::getTitleCasePluralModelLabel();
    }

    public static function getNavigationBadge(): ?string
    {
        return null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return static::$navigationBadgeTooltip;
    }

    public static function getNavigationBadgeColor(): string | array | null
    {
        return null;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->type == UserType::Admin;
    }

    public static function getModelLabel(): string
    {
        return static::$modelLabel ?? static::getLabel() ?? get_model_label(static::getModel());
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(trans('admin.fields.name'))
                    ->translateLabel()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('nrp')
                    ->maxLength(255),
                Forms\Components\TextInput::make('birth_place')
                    ->label(trans('admin.fields.birth_place'))
                    ->translateLabel()
                    ->maxLength(255),
                Forms\Components\DatePicker::make('birth_date')
                    ->label(trans('admin.fields.birth_date'))
                    ->translateLabel(),
                Forms\Components\TextInput::make('latitude')
                    ->maxLength(255),
                Forms\Components\TextInput::make('longitude')
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('email_verified_at')
                    ->label(trans('admin.fields.email_verified_at'))
                    ->translateLabel(),
                Forms\Components\TextInput::make('password')
                    ->label(trans('admin.fields.password'))
                    ->translateLabel()
                    ->password()
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('no')
                    ->rowIndex(),
                Tables\Columns\TextColumn::make('name')
                    ->label(trans('admin.fields.name'))
                    ->translateLabel()
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nrp')
                    ->searchable(),
                Tables\Columns\TextColumn::make('birth_place')
                    ->label(trans('admin.fields.birth_place'))
                    ->translateLabel()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('birth_date')
                    ->label(trans('admin.fields.birth_date'))
                    ->translateLabel()
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('latitude')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('longitude')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // 
            ]);
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
            'index' => Pages\ListGurus::route('/'),
            'create' => Pages\CreateGuru::route('/create'),
            'view' => Pages\ViewGuru::route('/{record}'),
            'edit' => Pages\EditGuru::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
