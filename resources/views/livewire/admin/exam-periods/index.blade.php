<?php

use App\Models\Referees\RefereeExamPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Component;

new class extends Component
{
    public int $seasonYear = 0;

    public string $opensAt = '';

    public string $closesAt = '';

    public bool $periodLocked = false;

    public function mount(): void
    {
        $this->authorize('admin_access');

        $today = now();
        $this->seasonYear = $today->month >= 7 ? $today->year : $today->year - 1;
        $this->loadPeriod();
    }

    public function updatedSeasonYear(): void
    {
        $this->loadPeriod();
    }

    public function save(): void
    {
        $this->authorize('admin_access');

        $data = $this->validate([
            'seasonYear' => ['required', 'integer', 'between:2000,2100'],
            'opensAt' => ['required', 'date'],
            'closesAt' => ['required', 'date', 'after_or_equal:opensAt'],
        ]);

        $overlapExists = RefereeExamPeriod::query()
            ->where('season_year', '!=', $data['seasonYear'])
            ->whereDate('opens_at', '<=', $data['closesAt'])
            ->whereDate('closes_at', '>=', $data['opensAt'])
            ->exists();

        if ($overlapExists) {
            throw ValidationException::withMessages([
                'opensAt' => __('This period overlaps another scheduled examination period.'),
            ]);
        }

        DB::transaction(function () use ($data): void {
            $period = RefereeExamPeriod::query()
                ->where('season_year', $data['seasonYear'])
                ->lockForUpdate()
                ->first();

            if ($period && $period->closes_at->isBefore(today())) {
                throw ValidationException::withMessages([
                    'seasonYear' => __('A closed examination period can no longer be modified.'),
                ]);
            }

            if ($period) {
                $period->update([
                    'opens_at' => $data['opensAt'],
                    'closes_at' => $data['closesAt'],
                    'updated_by' => auth()->id(),
                ]);
            } else {
                RefereeExamPeriod::create([
                    'season_year' => $data['seasonYear'],
                    'opens_at' => $data['opensAt'],
                    'closes_at' => $data['closesAt'],
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
            }
        });

        $this->loadPeriod();
        session()->flash('status', __('The examination period was saved.'));
    }

    public function with(): array
    {
        return [
            'periods' => RefereeExamPeriod::query()
                ->with(['createdBy:id,name', 'updatedBy:id,name'])
                ->orderByDesc('season_year')
                ->get(),
        ];
    }

    private function loadPeriod(): void
    {
        $period = RefereeExamPeriod::query()
            ->where('season_year', $this->seasonYear)
            ->first();

        $this->opensAt = $period?->opens_at?->format('Y-m-d') ?? '';
        $this->closesAt = $period?->closes_at?->format('Y-m-d') ?? '';
        $this->periodLocked = $period?->closes_at?->isBefore(today()) ?? false;
        $this->resetValidation();
    }
};

?>

<section class="container mx-auto h-full w-full max-w-6xl px-4 py-6 sm:px-6">
    <x-auth-session-status class="mb-4 text-center" :status="session('status')" />

    <div class="mb-6">
        <flux:heading size="xl">{{ __('Pre-season examination periods') }}</flux:heading>
        <flux:subheading>
            {{ __('Schedule the window during which medical and physical results may be recorded.') }}
        </flux:subheading>
    </div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,420px)_minmax(0,1fr)]">
        <div class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-[#0E1526]">
            <flux:heading size="lg">{{ __('Schedule a period') }}</flux:heading>

            <div class="mt-5 space-y-4">
                <flux:input type="number" wire:model.live.debounce.300ms="seasonYear" :label="__('Season start year')"
                    min="2000" max="2100" />
                <flux:input type="date" wire:model="opensAt" :label="__('Opening date')" :disabled="$periodLocked" />
                <flux:input type="date" wire:model="closesAt" :label="__('Closing date')" :disabled="$periodLocked" />

                @if($periodLocked)
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-200">
                        {{ __('This period is closed and its dates are locked.') }}
                    </div>
                @endif

                <div class="flex justify-end">
                    <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled"
                        :disabled="$periodLocked" class="cursor-pointer">
                        {{ __('Save') }}
                    </flux:button>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-[#0E1526]">
            <div class="border-b border-neutral-200 px-5 py-4 dark:border-neutral-700">
                <flux:heading size="lg">{{ __('Scheduled periods') }}</flux:heading>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-xs uppercase text-neutral-500">
                        <tr>
                            <th class="px-5 py-3">{{ __('Season') }}</th>
                            <th class="px-5 py-3">{{ __('Opening') }}</th>
                            <th class="px-5 py-3">{{ __('Closing') }}</th>
                            <th class="px-5 py-3">{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                        @forelse($periods as $period)
                            <tr>
                                <td class="px-5 py-3 font-semibold">{{ $period->seasonLabel() }}</td>
                                <td class="px-5 py-3">{{ $period->opens_at->format('d/m/Y') }}</td>
                                <td class="px-5 py-3">{{ $period->closes_at->format('d/m/Y') }}</td>
                                <td class="px-5 py-3">
                                    @if($period->isOpen())
                                        <flux:badge color="green" size="sm">{{ __('Open') }}</flux:badge>
                                    @elseif($period->opens_at->isAfter(today()))
                                        <flux:badge color="blue" size="sm">{{ __('Scheduled') }}</flux:badge>
                                    @else
                                        <flux:badge color="zinc" size="sm">{{ __('Closed') }}</flux:badge>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-8 text-center text-neutral-500">
                                    {{ __('No examination period scheduled.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
