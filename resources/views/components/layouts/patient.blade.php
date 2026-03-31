@use('App\Models\DeclarationRequest')
@use('App\Models\MedicalEvents\Sql\Encounter')

<section>
    <x-header-navigation x-data="{ showFilter: true }" class="breadcrumb-form">
        <x-slot name="title">{{ $patientFullName }}</x-slot>

        @if(isset($headerActions))
            {{ $headerActions }}
        @else
            @can('create', Encounter::class)
                <a href="{{ route('encounter.create', [legalEntity(), 'patientId' => $id]) }}"
                   class="flex items-center gap-2 button-primary px-5 py-2 text-sm shadow-sm"
                >
                    @icon('plus', 'w-4 h-4')
                    {{ __('patients.starts_interacting') }}
                </a>
            @endcan
        @endif

        <x-slot name="description">
            <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm font-semibold rounded-lg mt-1 border border-gray-100 dark:border-gray-700">
                @icon('file-text', 'w-4 h-4 text-gray-400')
                Декларація №1000000000000
            </div>
        </x-slot>

        <x-slot name="navigation">
        </x-slot>
    </x-header-navigation>

    {{ $slot }}
    <livewire:components.x-message :key="time()" />
</section>
