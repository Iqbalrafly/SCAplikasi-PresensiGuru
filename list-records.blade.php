<x-filament-panels::page
    @class([
        'fi-resource-list-records-page',
        'fi-resource-' . str_replace('/', '-', $this->getResource()::getSlug()),
    ])
>
<div>
    <div class="flex items-center pb-4 gap-x-3">
        @if (!$hasClockin && in_array(auth()->user()->type, [\App\Enums\UserType::Guru]))
            <x-filament::modal width="xl">
                <x-slot name="trigger">
                    <x-filament::button onclick="openCameraClockin()" color="success">
                        Clock In
                    </x-filament::button>
                </x-slot>

                <x-slot name="heading">
                    Clock In
                </x-slot>

                @livewire(\App\Livewire\ClockinCameraStream::class)

                <div class="flex gap-4">
                    <x-filament::button id="clockin-capture" onclick="captureCameraClockin()" color="success">
                        {{ trans('admin.actions.capture') }}
                    </x-filament::button>

                    <x-filament::button id="clockin-retake" onclick="retakeImageClockin()">
                        {{ trans('admin.actions.retake') }}
                    </x-filament::button>

                    <x-filament::button id="clockin-save" 
                        wire:click="clockinSave()" 
                        color="success" tag="button" type="submit" wire:disabled="!latitude && !longitude"
                        style="display: none">
                        {{ trans('admin.actions.save') }}
                    </x-filament::button>
                </div>

            </x-filament::modal>
        @endif

        @if (!$hasClockout && in_array(auth()->user()->type, [\App\Enums\UserType::Guru]))
            <x-filament::modal width="xl">
                <x-slot name="trigger">
                    <x-filament::button onclick="openCameraClockout()" color="warning">
                        Clock Out
                    </x-filament::button>
                </x-slot>

                <x-slot name="heading">
                    Clock Out
                </x-slot>

                @livewire(\App\Livewire\ClockoutCameraStream::class)

                <div class="flex gap-4">
                    <x-filament::button id="clockout-capture" onclick="captureCameraClockout()" color="success">
                        {{ trans('admin.actions.capture') }}
                    </x-filament::button>

                    <x-filament::button id="clockout-retake" onclick="retakeImageClockout()">
                        {{ trans('admin.actions.retake') }}
                    </x-filament::button>

                    <x-filament::button id="clockout-save" 
                        wire:click="clockoutSave()" 
                        color="success" tag="button" type="submit" wire:disabled="!latitude && !longitude"
                        style="display: none">
                        {{ trans('admin.actions.save') }}
                    </x-filament::button>
                </div>

            </x-filament::modal>
        @endif
    </div>

    <div class="flex flex-col gap-y-6">
        <x-filament-panels::resources.tabs />

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE, scopes: $this->getRenderHookScopes()) }}

        {{ $this->table }}

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_AFTER, scopes: $this->getRenderHookScopes()) }}
    </div>
</div>
</x-filament-panels::page>

<script>
    document.getElementById('clockin-save')?.addEventListener('click', function() {
        if(!clockin_image_data_url) {
            captureCameraClockin();
            return;
        }
        
        @this.set('imageDataUrl', clockin_image_data_url);

        if(!clockin_latitude || !clockin_longitude) {
            getGPSLocation();
            return;
        }
        
        @this.set('latitude', clockin_latitude);
        @this.set('longitude', clockin_longitude);
    });

    document.getElementById('clockout-save')?.addEventListener('click', function() {
        if(!clockout_image_data_url) {
            captureCameraClockout();
        }
        var clockoutImageDataUrl = clockout_image_data_url;
        @this.set('imageDataUrl', clockoutImageDataUrl);

        if(!clockout_latitude || !clockout_longitude) {
            getGPSLocation();
            return;
        }
        
        @this.set('latitude', clockout_latitude);
        @this.set('longitude', clockout_longitude);
    });
</script>