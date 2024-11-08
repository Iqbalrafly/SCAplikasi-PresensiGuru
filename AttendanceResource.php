<?php

namespace App\Filament\Resources;

use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Enums\UserType;
use App\Filament\Resources\AttendanceResource\Pages;
use App\Models\Attendance;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use function Filament\Support\get_model_label;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationGroup(): ?string
    {
        return trans('admin.menu.main');
    }

    public static function getNavigationLabel(): string
    {
        return trans('admin.resources.attendance_report');
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
        // return auth()->user()->type == UserType::Admin;
        return true;
    }

    public static function getModelLabel(): string
    {
        return trans('admin.resources.attendance');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('guru', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('type')
                    ->options(fn () => AttendanceType::class)
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options(fn () => AttendanceStatus::class)
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\DateTimePicker::make('recorded_at')
                    ->label(trans('admin.fields.recorded_at'))
                    ->translateLabel()
                    ->required(),
                Forms\Components\Textarea::make('notes')
                    ->label(trans('admin.fields.notes'))
                    ->translateLabel()
                    ->columnSpanFull()
                    ->hidden(fn ($record) => empty($record->notes))
                    ->visibleOn(['view']),
                Forms\Components\FileUpload::make('image_clockin')
                    ->label(trans('admin.fields.image_clockin'))
                    ->translateLabel()
                    ->disabled()
                    ->hidden(fn ($record) => empty($record->image_clockin))
                    ->visibleOn(['view']),
                Forms\Components\FileUpload::make('image_clockout')
                    ->label(trans('admin.fields.image_clockout'))
                    ->translateLabel()
                    ->disabled()
                    ->hidden(fn ($record) => empty($record->image_clockout))
                    ->visibleOn(['view']),
            ]);
    }

    public static function table(Table $table): Table
    {
        $user = auth()->user();
        return $table
            ->modifyQueryUsing(fn ($query) => $query
                ->when(
                    $user->type != UserType::Admin,
                    fn ($query) => $query->where('user_id', $user->id)
                )
            )
            ->defaultSort('recorded_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('no')
                    ->rowIndex(),
                Tables\Columns\TextColumn::make('guru.name')
                    ->description(fn ($record) => $record->guru?->nrp)
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->formatStateUsing(fn ($state) => str($state->value)->headline())
                    ->label(trans('admin.fields.type'))
                    ->translateLabel()
                    ->searchable()
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')
                    ->formatStateUsing(fn ($state) => str($state->value)->headline())
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('recorded_at')
                    ->label(trans('admin.fields.recorded_at'))
                    ->translateLabel()
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\ImageColumn::make('image_clockin')
                    ->label(trans('admin.fields.image_clockin'))
                    ->translateLabel()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\ImageColumn::make('image_clockout')
                    ->label(trans('admin.fields.image_clockout'))
                    ->translateLabel()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('status')
                    ->options(fn () => AttendanceStatus::class)
                    ->searchable()
                    ->multiple()
                    ->preload(),
                Tables\Filters\Filter::make('recorded_at')
                    ->form([
                        DatePicker::make('recorded_from')
                            ->label(trans('admin.fields.recorded_from'))
                            ->translateLabel(),
                        DatePicker::make('recorded_until')
                            ->label(trans('admin.fields.recorded_until'))
                            ->translateLabel(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['recorded_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('recorded_at', '>=', $date),
                            )
                            ->when(
                                $data['recorded_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('recorded_at', '<=', $date),
                            );
                    }),
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
            'index' => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            'view' => Pages\ViewAttendance::route('/{record}'),
            'edit' => Pages\EditAttendance::route('/{record}/edit'),
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
