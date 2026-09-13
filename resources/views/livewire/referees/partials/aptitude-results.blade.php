<div class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-[#0E1526]">
    <h2 class="mb-4 text-xl font-semibold text-neutral-900 dark:text-slate-300">
        {{ __('Recorded results') }}
    </h2>

    <div class="space-y-2 text-sm">
        @if($medicalExams->isNotEmpty())
            @foreach($medicalExams->take(3) as $exam)
                <div class="flex items-center justify-between gap-2 rounded-lg bg-neutral-50 p-3 dark:bg-neutral-900/70">
                    <span>
                        {{ __('Medical') }} ·
                        {{ $exam->season_year ? $exam->season_year.'–'.($exam->season_year + 1) : '—' }}
                    </span>
                    <flux:badge size="sm" :color="$exam->result === 'passed' ? 'green' : ($exam->result === 'failed' ? 'red' : 'amber')">
                        {{ __(ucfirst($exam->result)) }}
                    </flux:badge>
                </div>
            @endforeach
        @else
            <div class="rounded-lg bg-neutral-50 p-3 text-neutral-500 dark:bg-neutral-900/70">
                {{ __('No medical examination recorded.') }}
            </div>
        @endif

        @if($physicalTests->isNotEmpty())
            @foreach($physicalTests->take(3) as $test)
                <div class="flex items-center justify-between gap-2 rounded-lg bg-neutral-50 p-3 dark:bg-neutral-900/70">
                    <span>
                        {{ __('Physical') }} ·
                        {{ $test->season_year ? $test->season_year.'–'.($test->season_year + 1) : '—' }}
                    </span>
                    <flux:badge size="sm" :color="$test->result === 'passed' ? 'green' : ($test->result === 'failed' ? 'red' : 'amber')">
                        {{ __(ucfirst($test->result)) }}
                    </flux:badge>
                </div>
            @endforeach
        @else
            <div class="rounded-lg bg-neutral-50 p-3 text-neutral-500 dark:bg-neutral-900/70">
                {{ __('No physical test recorded.') }}
            </div>
        @endif
    </div>
</div>
