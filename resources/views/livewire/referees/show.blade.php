<?php

use App\Models\Referees\Referee;
use App\Models\Referees\RefereeSeason;
use Livewire\Volt\Component;

new class extends Component
{
    public Referee $referee;

    public function mount(Referee $referee): void
    {
        $this->authorize('view_referee');

        $this->referee = $referee;
        $this->referee->load([
            'league',
            'refereeCategory',
            'refereeRole',
            'identityDocument',
            'medicalExams' => fn ($query) => $query
                ->orderByDesc('season_year')
                ->orderByDesc('exam_date'),
            'physicalTests' => fn ($query) => $query
                ->orderByDesc('season_year')
                ->orderByDesc('test_date'),
            'seasons' => fn ($query) => $query
                ->with('designatedBy')
                ->orderByDesc('season_year')
                ->orderBy('competition'),
        ]);
    }
};

?>

<section class="container mx-auto w-full max-w-7xl px-4 py-6 sm:px-6">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <flux:button variant="ghost" icon="arrow-left" :href="route('referees.index')" wire:navigate>
                {{ __('Back to referee list') }}
            </flux:button>
            <flux:heading size="xl" class="mt-4">{{ __('Referee profile') }}</flux:heading>
            <flux:subheading class="mt-1">
                {{ __("View the referee's identity, career, aptitudes and seasonal designations.") }}
            </flux:subheading>
        </div>

        <div class="flex flex-wrap gap-2">
            @can('edit_referee')
                <flux:button variant="outline" icon="pencil-square" :href="route('referees.edit', $referee)" wire:navigate>
                    {{ __('Edit') }}
                </flux:button>
            @endcan

            @can('manage_seasons')
                <flux:button variant="primary" color="green" icon="calendar-days"
                    :href="route('referees.designations.index', ['search' => $referee->person_id ?: $referee->fullName()])"
                    wire:navigate>
                    {{ __('Designate') }}
                </flux:button>
            @endcan
        </div>
    </div>

    <div class="mb-6 rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-[#0E1526]">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
            @if($referee->profile_photo_path)
                <img src="{{ asset('storage/'.$referee->profile_photo_path) }}" alt="{{ $referee->fullName() }}"
                    class="h-28 w-28 shrink-0 rounded-xl object-cover">
            @else
                <div class="flex h-28 w-28 shrink-0 items-center justify-center rounded-xl bg-neutral-800 text-3xl font-semibold text-white dark:bg-neutral-700">
                    {{ strtoupper(Str::substr($referee->first_name, 0, 1).Str::substr($referee->last_name, 0, 1)) }}
                </div>
            @endif

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-2xl font-semibold text-neutral-900 dark:text-white">{{ $referee->fullName() }}</h1>
                    <flux:badge size="sm" :color="$referee->is_active ? 'green' : 'red'">
                        {{ $referee->is_active ? __('Active') : __('Inactive') }}
                    </flux:badge>
                    @if($referee->is_fifa_listed)
                        <flux:badge size="sm" color="blue">{{ __('FIFA listed') }}</flux:badge>
                    @endif
                </div>
                <div class="mt-2 font-mono text-sm text-neutral-500">{{ $referee->person_id ?? __('Not provided') }}</div>
                <div class="mt-3 flex flex-wrap gap-x-6 gap-y-2 text-sm text-neutral-600 dark:text-neutral-300">
                    <span>{{ $referee->refereeRole?->name ?? __('Not provided') }}</span>
                    <span>{{ $referee->refereeCategory?->name ?? __('Not provided') }}</span>
                    <span>{{ $referee->league?->code ?? __('Not provided') }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-[#0E1526]">
                <h2 class="mb-5 text-xl font-semibold text-neutral-900 dark:text-white">{{ __('Personal information') }}</h2>
                <dl class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm text-neutral-500">{{ __('Date of birth') }}</dt>
                        <dd class="mt-1 font-medium text-neutral-900 dark:text-white">
                            {{ $referee->date_of_birth?->format('d/m/Y') ?? __('Not provided') }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-neutral-500">{{ __('Gender') }}</dt>
                        <dd class="mt-1 font-medium text-neutral-900 dark:text-white">
                            {{ $referee->gender ? ucfirst(__($referee->gender)) : __('Not provided') }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-neutral-500">{{ __('Education level') }}</dt>
                        <dd class="mt-1 font-medium text-neutral-900 dark:text-white">
                            {{ $referee->education_level ?: __('Not provided') }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-neutral-500">{{ __('Profession') }}</dt>
                        <dd class="mt-1 font-medium text-neutral-900 dark:text-white">
                            {{ $referee->profession ?: __('Not provided') }}
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-[#0E1526]">
                <h2 class="mb-5 text-xl font-semibold text-neutral-900 dark:text-white">{{ __('Contact details') }}</h2>
                <dl class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm text-neutral-500">{{ __('Phone number') }}</dt>
                        <dd class="mt-1 font-medium text-neutral-900 dark:text-white">
                            @if($referee->phone)
                                <a href="tel:{{ $referee->phone }}" class="hover:text-blue-600 hover:underline">{{ $referee->phone }}</a>
                            @else
                                {{ __('Not provided') }}
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-neutral-500">{{ __('E-mail Address') }}</dt>
                        <dd class="mt-1 break-all font-medium text-neutral-900 dark:text-white">
                            @if($referee->email)
                                <a href="mailto:{{ $referee->email }}" class="hover:text-blue-600 hover:underline">{{ $referee->email }}</a>
                            @else
                                {{ __('Not provided') }}
                            @endif
                        </dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-sm text-neutral-500">{{ __('Home address') }}</dt>
                        <dd class="mt-1 font-medium text-neutral-900 dark:text-white">
                            {{ $referee->address ?: __('Not provided') }}
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-[#0E1526]">
                <h2 class="mb-5 text-xl font-semibold text-neutral-900 dark:text-white">{{ __('Recorded results') }}</h2>

                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <h3 class="mb-3 font-semibold text-neutral-900 dark:text-white">{{ __('Medical aptitude') }}</h3>
                        <div class="space-y-3">
                            @forelse($referee->medicalExams as $exam)
                                <div class="rounded-lg bg-neutral-50 p-4 dark:bg-neutral-900/70">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <div class="font-medium text-neutral-900 dark:text-white">
                                                {{ $exam->season_year ? $exam->season_year.'–'.($exam->season_year + 1) : '—' }}
                                            </div>
                                            <div class="mt-1 text-sm text-neutral-500">{{ $exam->exam_date?->format('d/m/Y') ?? '—' }}</div>
                                        </div>
                                        <flux:badge size="sm" :color="$exam->result === 'passed' ? 'green' : ($exam->result === 'failed' ? 'red' : 'amber')">
                                            {{ __(ucfirst($exam->result)) }}
                                        </flux:badge>
                                    </div>
                                    @if($exam->file_path)
                                        <a href="{{ asset('storage/'.$exam->file_path) }}" target="_blank" rel="noopener"
                                            class="mt-3 inline-block text-sm font-medium text-blue-600 hover:underline dark:text-blue-400">
                                            {{ __('View current certificate') }}
                                        </a>
                                    @endif
                                </div>
                            @empty
                                <p class="rounded-lg bg-neutral-50 p-4 text-sm text-neutral-500 dark:bg-neutral-900/70">
                                    {{ __('No medical examination recorded.') }}
                                </p>
                            @endforelse
                        </div>
                    </div>

                    <div>
                        <h3 class="mb-3 font-semibold text-neutral-900 dark:text-white">{{ __('Physical aptitude') }}</h3>
                        <div class="space-y-3">
                            @forelse($referee->physicalTests as $test)
                                <div class="rounded-lg bg-neutral-50 p-4 dark:bg-neutral-900/70">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <div class="font-medium text-neutral-900 dark:text-white">
                                                {{ $test->season_year ? $test->season_year.'–'.($test->season_year + 1) : '—' }}
                                            </div>
                                            <div class="mt-1 text-sm text-neutral-500">{{ $test->test_date?->format('d/m/Y') ?? '—' }}</div>
                                            @if($test->level)
                                                <div class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">{{ $test->level }}</div>
                                            @endif
                                        </div>
                                        <flux:badge size="sm" :color="$test->result === 'passed' ? 'green' : ($test->result === 'failed' ? 'red' : 'amber')">
                                            {{ __(ucfirst($test->result)) }}
                                        </flux:badge>
                                    </div>
                                    @if($test->file_path)
                                        <a href="{{ asset('storage/'.$test->file_path) }}" target="_blank" rel="noopener"
                                            class="mt-3 inline-block text-sm font-medium text-blue-600 hover:underline dark:text-blue-400">
                                            {{ __('View current report') }}
                                        </a>
                                    @endif
                                </div>
                            @empty
                                <p class="rounded-lg bg-neutral-50 p-4 text-sm text-neutral-500 dark:bg-neutral-900/70">
                                    {{ __('No physical test recorded.') }}
                                </p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-[#0E1526]">
                <h2 class="mb-5 text-xl font-semibold text-neutral-900 dark:text-white">{{ __('Refereeing career') }}</h2>
                <dl class="space-y-4">
                    <div>
                        <dt class="text-sm text-neutral-500">{{ __('Affiliated league') }}</dt>
                        <dd class="mt-1 font-medium text-neutral-900 dark:text-white">
                            {{ $referee->league ? $referee->league->code.' – '.$referee->league->name : __('Not provided') }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-neutral-500">{{ __('Category') }}</dt>
                        <dd class="mt-1 font-medium text-neutral-900 dark:text-white">
                            {{ $referee->refereeCategory?->name ?? __('Not provided') }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-neutral-500">{{ __('Function') }}</dt>
                        <dd class="mt-1 font-medium text-neutral-900 dark:text-white">
                            {{ $referee->refereeRole?->name ?? __('Not provided') }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-neutral-500">{{ __('Start year') }}</dt>
                        <dd class="mt-1 font-medium text-neutral-900 dark:text-white">
                            {{ $referee->start_year ?? __('Not provided') }}
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-[#0E1526]">
                <h2 class="mb-5 text-xl font-semibold text-neutral-900 dark:text-white">{{ __('Identity Document') }}</h2>
                @if($referee->identityDocument)
                    @php
                        $identityType = match ($referee->identityDocument->type) {
                            'passport' => __('Passport'),
                            'national_id' => __('National ID'),
                            default => __('Other'),
                        };
                    @endphp
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm text-neutral-500">{{ __('Type of document') }}</dt>
                            <dd class="mt-1 font-medium text-neutral-900 dark:text-white">{{ $identityType }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-neutral-500">{{ __('Number') }}</dt>
                            <dd class="mt-1 font-medium text-neutral-900 dark:text-white">
                                {{ $referee->identityDocument->number ?: __('Not provided') }}
                            </dd>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <dt class="text-sm text-neutral-500">{{ __('Issue date') }}</dt>
                                <dd class="mt-1 font-medium text-neutral-900 dark:text-white">
                                    {{ $referee->identityDocument->issue_date?->format('d/m/Y') ?? '—' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm text-neutral-500">{{ __('Expiry date') }}</dt>
                                <dd class="mt-1 font-medium text-neutral-900 dark:text-white">
                                    {{ $referee->identityDocument->expiry_date?->format('d/m/Y') ?? '—' }}
                                </dd>
                            </div>
                        </div>
                    </dl>
                @else
                    <p class="text-sm text-neutral-500">{{ __('Not provided') }}</p>
                @endif
            </div>

            <div class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-[#0E1526]">
                <h2 class="mb-5 text-xl font-semibold text-neutral-900 dark:text-white">{{ __('Season designations') }}</h2>
                <div class="space-y-3">
                    @forelse($referee->seasons as $season)
                        <div class="rounded-lg bg-neutral-50 p-4 dark:bg-neutral-900/70">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="font-medium text-neutral-900 dark:text-white">
                                        {{ $season->season_year }}–{{ $season->season_year + 1 }}
                                    </div>
                                    <div class="mt-1 text-sm text-neutral-500">
                                        {{ RefereeSeason::competitionLabel($season->competition) }}
                                    </div>
                                </div>
                                <flux:badge size="sm" :color="$season->status === 'active' ? 'green' : 'red'">
                                    {{ $season->status === 'active' ? __('Active') : __('Inactive') }}
                                </flux:badge>
                            </div>
                            @if($season->designated_at)
                                <div class="mt-3 text-xs text-neutral-500">
                                    {{ __('Designated on') }} {{ $season->designated_at->format('d/m/Y') }}
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-neutral-500">{{ __('No seasonal designation recorded.') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</section>
