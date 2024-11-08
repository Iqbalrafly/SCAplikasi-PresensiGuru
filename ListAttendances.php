<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Enums\AttendanceStatus;
use App\Enums\AttendanceTimeOff;
use App\Enums\AttendanceType;
use App\Enums\UserType;
use App\Filament\Resources\AttendanceResource;
use App\Models\Attendance;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ListAttendances extends ListRecords
{
    protected static string $resource = AttendanceResource::class;

    protected static string $view = 'filament.resources.attendance-resource.pages.list-records';

    public $imageDataUrl;
    public $latitude;
    public $longitude;
    public $hasClockin;
    public $hasClockout;

    public function mount(): void
    {
        parent::mount();

        $user = auth()->user();
        $clockin = Attendance::query()
            ->whereDate('recorded_at', now()->format('Y-m-d'))
            ->where('user_id', $user->id)
            ->where('type', AttendanceType::ClockIn->value)
            ->where('status', AttendanceStatus::ClockIn->value)
            ->exists();
        $clockout = Attendance::query()
            ->whereDate('recorded_at', now()->format('Y-m-d'))
            ->where('user_id', $user->id)
            ->where('type', AttendanceType::ClockOut->value)
            ->where('status', AttendanceStatus::ClockOut->value)
            ->exists();
        $this->hasClockin = $clockin;
        $this->hasClockout = $clockout;
    }

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
            ExportAction::make()
                ->exports([
                    ExcelExport::make('table')->fromTable(),
                ])
                ->label('Unduh')
            ->hidden(fn () => auth()->user()->type != UserType::Admin),
            Action::make('requestTimeoff')
                ->label(trans('admin.actions.request_timeoff'))
                ->translateLabel()
                ->color('danger')
                ->modalIconColor('danger')
                ->modalSubmitActionLabel('Submit')
                ->requiresConfirmation()
                ->hidden(fn () => !in_array(auth()->user()->type, [UserType::Guru]))
                ->form([
                    Select::make('type')
                        ->label(trans('admin.fields.type'))
                        ->translateLabel()
                        ->options(fn () => AttendanceTimeOff::class)
                        ->searchable()
                        ->preload()
                        ->required(),
                    DatePicker::make('recorded_at')
                        ->label(trans('admin.fields.recorded_at'))
                        ->translateLabel()
                        ->required(),
                    Textarea::make('notes')
                        ->label(trans('admin.fields.notes'))
                        ->translateLabel()
                        ->required(),
                ])
                ->action(function ($data) {
                    switch($data['type']) {
                        case AttendanceTimeOff::Sick->value:
                            $status = AttendanceStatus::SickPending;
                            break;
                        case AttendanceTimeOff::Leave->value:
                            $status = AttendanceStatus::LeavePending;
                            break;
                        case AttendanceTimeOff::Permit->value:
                            $status = AttendanceStatus::PermitPending;
                            break;
                        default:
                            $status = AttendanceStatus::Absent;
                            break;
                    }
                    
                    DB::beginTransaction();
                    try {
                        
                        $userId = auth()->user()->id;
                        $attendance = Attendance::create([
                            'user_id' => $userId,
                            'type' => $data['type'],
                            'status' => $status,
                            'recorded_at' => $data['recorded_at'],
                            'notes' => $data['notes'],
                        ]);

                        DB::commit();

                        Notification::make()
                            ->title('Request submitted')
                            ->success()
                            ->send();

                        return;
                    } catch (\Throwable $throw) {
                        DB::rollBack();

                        Notification::make()
                            ->title($throw->getMessage())
                            ->danger()
                            ->send();

                        Log::error($throw);

                        return;
                    }
                })
        ];
    }

    public function clockinSave()
    {
        if(empty($this->latitude) || empty($this->longitude)) {
            Notification::make()
                ->title('Your GPS location cannot be located')
                ->danger()
                ->send();

            return $this->redirectRoute('filament.admin.resources.attendances.index');
        }

        DB::beginTransaction();
        try {
            $userId = auth()->user()->id;
            $attendance = Attendance::create([
                'user_id' => $userId,
                'type' => AttendanceType::ClockIn,
                'status' => AttendanceStatus::ClockIn,
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'recorded_at' => now(),
            ]);

            $filename = $userId . '-clockin-' . now()->timestamp . '.jpg';
            $attendance->addMediaFromBase64($this->imageDataUrl)
                ->usingFileName($filename)
                ->toMediaCollection('clockin');

            DB::commit();

            Notification::make()
                ->title('Clockin Sukses')
                ->success()
                ->send();

            return $this->redirectRoute('filament.admin.resources.attendances.index');
        } catch (\Throwable $throw) {
            DB::rollBack();

            Notification::make()
                ->title($throw->getMessage())
                ->danger()
                ->send();

            Log::error($throw);

            return $this->redirectRoute('filament.admin.resources.attendances.index');
        }
    }

    public function clockoutSave()
    {
        if(empty($this->latitude) || empty($this->longitude)) {
            Notification::make()
                ->title('Your GPS location cannot be located')
                ->danger()
                ->send();

            return $this->redirectRoute('filament.admin.resources.attendances.index');
        }

        DB::beginTransaction();
        try {
            $userId = auth()->user()->id;
            $attendance = Attendance::create([
                'user_id' => $userId,
                'type' => AttendanceType::ClockOut,
                'status' => AttendanceStatus::ClockOut,
                'recorded_at' => now(),
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ]);

            $filename = $userId . '-clockout-' . now()->timestamp . '.jpg';
            $attendance->addMediaFromBase64($this->imageDataUrl)
                ->usingFileName($filename)
                ->toMediaCollection('clockout');

            DB::commit();

            Notification::make()
                ->title('Clockout Sukses')
                ->success()
                ->send();

            return $this->redirectRoute('filament.admin.resources.attendances.index');
        } catch (\Throwable $throw) {
            DB::rollBack();

            Notification::make()
                ->title($throw->getMessage())
                ->danger()
                ->send();

            Log::error($throw);

            return $this->redirectRoute('filament.admin.resources.attendances.index');
        }
    }
}
