@php
    $seasonMedicalExam = $aptitudeSeasonYear
        ? $medicalExams->firstWhere('season_year', $aptitudeSeasonYear)
        : null;
    $seasonPhysicalTest = $aptitudeSeasonYear
        ? $physicalTests->firstWhere('season_year', $aptitudeSeasonYear)
        : null;
@endphp

<div class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-[#0E1526]">
    <div class="mb-4">
        <h2 class="text-xl font-semibold text-neutral-900 dark:text-slate-300">
            {{ __('Seasonal aptitudes') }}
        </h2>
        <p class="mt-1 text-sm text-neutral-500">
            {{ __('Medical and physical examinations are recorded once during the pre-season period.') }}
        </p>
    </div>

    @error('aptitude_period')
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-200">
            {{ $message }}
        </div>
    @enderror

    @if($aptitudePeriodOpen)
        <div class="mb-5 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200">
            <div class="font-semibold">
                {{ __('Open period') }} · {{ $aptitudeSeasonYear }}–{{ $aptitudeSeasonYear + 1 }}
            </div>
            <div class="mt-1">
                {{ __('From :start to :end', ['start' => $aptitudePeriodOpensAt, 'end' => $aptitudePeriodClosesAt]) }}
            </div>
        </div>

        <div class="space-y-6">
            <div>
                <div class="mb-3 flex items-center justify-between gap-2">
                    <h3 class="font-semibold text-neutral-900 dark:text-slate-300">{{ __('Medical aptitude') }}</h3>
                    <flux:badge size="sm" :color="$seasonMedicalExam?->result === 'passed' ? 'green' : 'red'">
                        {{ $seasonMedicalExam?->result === 'passed' ? __('Valid') : __('Not valid') }}
                    </flux:badge>
                </div>

                <div class="space-y-3">
                    <flux:input type="date" wire:model="medical_exam_date" :label="__('Exam date')" required />
                    <flux:select wire:model="medical_result" :label="__('Result')" required>
                        <flux:select.option value="pending">{{ __('Pending') }}</flux:select.option>
                        <flux:select.option value="passed">{{ __('Passed') }}</flux:select.option>
                        <flux:select.option value="failed">{{ __('Failed') }}</flux:select.option>
                    </flux:select>
                    <flux:input type="file" wire:model="medical_certificate" :label="__('Medical certificate')"
                        accept=".pdf,.jpg,.jpeg,.png" />
                    @if($seasonMedicalExam?->file_path)
                        <a class="text-sm font-medium text-blue-600 hover:underline dark:text-blue-400"
                            href="{{ asset('storage/'.$seasonMedicalExam->file_path) }}" target="_blank" rel="noopener">
                            {{ __('View current certificate') }}
                        </a>
                    @endif
                    <flux:textarea wire:model="medical_notes" rows="2" :label="__('Notes')" />
                </div>
            </div>

            <div class="border-t border-neutral-200 pt-5 dark:border-neutral-700">
                <div class="mb-3 flex items-center justify-between gap-2">
                    <h3 class="font-semibold text-neutral-900 dark:text-slate-300">{{ __('Physical aptitude') }}</h3>
                    <flux:badge size="sm" :color="$seasonPhysicalTest?->result === 'passed' ? 'green' : 'red'">
                        {{ $seasonPhysicalTest?->result === 'passed' ? __('Valid') : __('Not valid') }}
                    </flux:badge>
                </div>

                <div class="space-y-3">
                    <flux:input type="date" wire:model="physical_test_date" :label="__('Test date')" required />
                    <flux:select wire:model="physical_result" :label="__('Result')" required>
                        <flux:select.option value="pending">{{ __('Pending') }}</flux:select.option>
                        <flux:select.option value="passed">{{ __('Passed') }}</flux:select.option>
                        <flux:select.option value="failed">{{ __('Failed') }}</flux:select.option>
                    </flux:select>
                    <flux:input wire:model="physical_level" :label="__('Test level')"
                        :placeholder="__('Example: FIFA high-intensity test')" />
                    <flux:input type="file" wire:model="physical_report" :label="__('Physical test report')"
                        accept=".pdf,.jpg,.jpeg,.png" />
                    @if($seasonPhysicalTest?->file_path)
                        <a class="text-sm font-medium text-blue-600 hover:underline dark:text-blue-400"
                            href="{{ asset('storage/'.$seasonPhysicalTest->file_path) }}" target="_blank" rel="noopener">
                            {{ __('View current report') }}
                        </a>
                    @endif
                    <flux:textarea wire:model="physical_notes" rows="2" :label="__('Notes')" />
                </div>
            </div>
        </div>

        <p class="mt-5 text-xs text-neutral-500">
            {{ __('Use the Save button at the bottom of the page to record these results.') }}
        </p>
    @else
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-200">
            {{ __('No examination period is currently open. The aptitude form is locked until the administration schedules a pre-season period.') }}
        </div>
    @endif

</div>
