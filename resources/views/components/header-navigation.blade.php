@props(['title' => null, 'description' => null, 'breadcrumbs' => []])

@php
    use App\Models\CarePlan;
    use App\Models\Declaration;
    use App\Models\DeclarationRequest;
    use App\Models\Person\Person;
    use App\Models\Preperson;
    use Illuminate\Http\Request;
    use Livewire\Livewire;

    $crumbs = $breadcrumbs;
    $dashboardUrl = legalEntity() ? route('dashboard', [legalEntity()]) : url('/dashboard');

    if (empty($crumbs) || count($crumbs) <= 2) {
        $person = null;
        // A Livewire action runs on its own endpoint, so the page's route is found again from the URL it was sent from
        $route = Livewire::isLivewireRequest()
            ? rescue(static fn () => app('router')->getRoutes()->match(Request::create(Livewire::originalUrl())), null, false)
            : request()->route();

        if ($route) {
            $personParam = $route->parameter('personId') ?? $route->parameter('person');
            if ($personParam) {
                $person = $personParam instanceof Person ? $personParam : Person::find($personParam);
            }

            if (!$person) {
                $declParam = $route->parameter('declaration') ?? $route->parameter('declarationRequest');
                if ($declParam) {
                    $decl = is_numeric($declParam)
                        ? (Declaration::find($declParam) ?? DeclarationRequest::find($declParam))
                        : $declParam;
                    $person = $decl?->person;
                }
            }

            if (!$person) {
                $cpParam = $route->parameter('carePlan');
                if ($cpParam) {
                    $cp = is_numeric($cpParam) ? CarePlan::find($cpParam) : $cpParam;
                    $person = $cp?->person;
                }
            }

            if (!$person) {
                $prepersonParam = $route->parameter('preperson');
                if ($prepersonParam) {
                    $person = $prepersonParam instanceof Preperson ? $prepersonParam : Preperson::find($prepersonParam);
                }
            }
        }

        if ($person) {
            $isPreperson = $person instanceof Preperson;

            $crumbs = [
                ['label' => __('forms.home'), 'url' => $dashboardUrl],
                $isPreperson
                    ? ['label' => __('preperson.label'), 'url' => route('prepersons.index', [legalEntity()])]
                    : ['label' => __('patients.patients'), 'url' => route('persons.index', [legalEntity()])]
            ];

            $patientName = $person->fullName;
            $cleanTitle = trim(str_replace([' - ' . $patientName, $patientName . ' - '], '', $title ?? ''));

            if ($cleanTitle && $cleanTitle !== $patientName) {
                $crumbs[] = [
                    'label' => $patientName,
                    'url' => $isPreperson
                        ? route('prepersons.patient-data', [legalEntity(), 'preperson' => $person->id])
                        : route('persons.patient-data', [legalEntity(), 'person' => $person->id])
                ];
                $crumbs[] = ['label' => $cleanTitle];
            } else {
                $crumbs[] = ['label' => $patientName];
            }
        } else {
            $routeName = $route?->getName() ?? '';
            if (str_starts_with($routeName, 'persons.') && $routeName !== 'persons.index') {
                $crumbs = [
                    ['label' => __('forms.home'), 'url' => $dashboardUrl],
                    ['label' => __('patients.patients'), 'url' => route('persons.index', [legalEntity()])],
                    $title ? ['label' => $title] : null
                ];
                $crumbs = array_filter($crumbs);
            } else {
                $crumbs = [
                    ['label' => __('forms.home'), 'url' => $dashboardUrl],
                    $title ? ['label' => $title] : null
                ];
                $crumbs = array_filter($crumbs);
            }
        }
    }
@endphp

<div {{ $attributes->merge(['class' => 'section-card shift-content relative z-20']) }}>
    <div class="w-full max-w-7xl">
        <!-- Breadcrumbs at the very top -->
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <ol class="breadcrumb-list">
                @php $crumbs = array_values($crumbs); @endphp
                @foreach ($crumbs as $index => $crumb)
                    @php
                        $isFirst = $index === 0;
                        $isLast = $index === count($crumbs) - 1;
                        $hasUrl = isset($crumb['url']) && filled($crumb['url']);
                    @endphp
                    <li @if ($isFirst) class="breadcrumb-first" @endif @if ($isLast) aria-current="page" @endif>
                        @if ($isFirst)
                            <a href="{{ $crumb['url'] ?? $dashboardUrl }}" class="breadcrumb-link">
                                <svg class="breadcrumb-home-icon" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="m19.707 9.293-2-2-7-7a1 1 0 0 0-1.414 0l-7 7-2 2a1 1 0 0 0 1.414 1.414L2 10.414V18a2 2 0 0 0 2 2h3a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h3a2 2 0 0 0 2-2v-7.586l.293.293a1 1 0 0 0 1.414-1.414Z" />
                                </svg>
                                {{ $crumb['label'] }}
                            </a>
                        @else
                            <div class="breadcrumb-separator">
                                <svg class="breadcrumb-chevron" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4" />
                                </svg>
                                @if ($hasUrl && !$isLast)
                                    <a href="{{ $crumb['url'] }}" class="breadcrumb-item-link">{{ $crumb['label'] }}</a>
                                @else
                                    <span class="breadcrumb-item-text">{{ $crumb['label'] }}</span>
                                @endif
                            </div>
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>

        <!-- Title row with page title and action buttons -->
        <header class="page-header">
            <div class="flex w-full flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="page-header-content min-w-0">
                    @if ($title)
                        <h1 class="page-title mb-0!">{{ $title }}</h1>
                    @endif
                    @if ($description)
                        <p class="page-description">{{ $description }}</p>
                    @endif
                </div>

                @if (isset($actions) || trim($slot))
                    <div class="button-group flex shrink-0 items-center gap-2">{{ $slot }} {{ $actions ?? '' }}</div>
                @endif
            </div>
        </header>

        @isset($navigation)
            <div class="page-navigation mt-8">{{ $navigation }}</div>
        @endisset
    </div>
</div>
