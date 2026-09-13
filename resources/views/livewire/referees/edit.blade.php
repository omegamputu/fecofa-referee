<?php

use App\Models\League;
use App\Models\Referees\Referee;
use App\Models\Referees\RefereeCategory;
use App\Models\Referees\RefereeExamPeriod;
use App\Models\Referees\RefereeMedicalExam;
use App\Models\Referees\RefereePhysicalTest;
use App\Models\Referees\RefereeRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public ?Referee $referee = null;

    // Champs du formulaire
    public string $last_name = '';

    public string $first_name = '';

    public ?string $date_of_birth = null;

    public ?string $gender = null;

    public ?string $education_level = null;

    public ?string $profession = null;

    public ?string $phone = null;

    public ?string $email = null;

    public ?string $address = null;

    public ?string $identity_type = null;

    public ?string $number = null;

    public ?string $issue_date = null;

    public ?string $expiry_date = null;

    //
    public int $originalLeagueId; // Pour vérifier si la ligue a changé

    public ?int $league_id = null;

    public ?int $start_year = null;

    public ?int $referee_category_id = null;

    public ?int $referee_role_id = null;

    // Upload photo
    public $profile_photo = null;

    public ?string $profile_photo_preview = null;

    // Listes pour les <select>
    public $leagues = [];

    public $categories = [];

    public $roles = [];

    public string $medical_exam_date = '';

    public string $medical_result = RefereeMedicalExam::RESULT_PENDING;

    public ?string $medical_notes = null;

    public $medical_certificate = null;

    public bool $medicalAptitudeTouched = false;

    public string $physical_test_date = '';

    public string $physical_result = RefereePhysicalTest::RESULT_PENDING;

    public ?string $physical_level = null;

    public ?string $physical_notes = null;

    public $physical_report = null;

    public bool $physicalAptitudeTouched = false;

    public ?int $aptitudeSeasonYear = null;

    public bool $aptitudePeriodOpen = false;

    public ?string $aptitudePeriodOpensAt = null;

    public ?string $aptitudePeriodClosesAt = null;

    public function mount(Referee $referee)
    {
        $this->referee = $referee;

        // ----- 1. Précharger les listes -----
        $this->leagues = League::select('id', 'code', 'name')
            ->orderBy('code')
            ->get();

        $this->categories = RefereeCategory::select('id', 'name')
            ->orderBy('name')
            ->get();

        $this->roles = RefereeRole::select('id', 'name')
            ->orderBy('name')
            ->get();

        // ----- 2. Hydrater les champs depuis le modèle -----
        $this->last_name = $referee->last_name;
        $this->first_name = $referee->first_name;
        $this->date_of_birth = $referee->date_of_birth?->format('Y-m-d');
        $this->gender = $referee->gender;
        $this->education_level = $referee->education_level;
        $this->profession = $referee->profession;

        $this->phone = $referee->phone;
        $this->email = $referee->email;
        $this->address = $referee->address;

        $this->originalLeagueId = $referee->league_id; // Pour vérifier si la ligue a changé

        $this->league_id = $referee->league_id;
        $this->referee_category_id = $referee->referee_category_id;
        $this->start_year = $referee->start_year;
        $this->referee_role_id = $referee->referee_role_id;

        // ----- 3. Document d’identité existant (s’il y en a un) -----
        /** @var \App\Models\Referees\IdentityDocument|null $doc */
        $doc = $referee->identityDocument()->first();

        if ($doc) {
            $this->identity_type = $doc->type;
            $this->number = $doc->number;
            $this->issue_date = $doc->issue_date?->format('Y-m-d');
            $this->expiry_date = $doc->expiry_date?->format('Y-m-d');
        }

        // La preview ne sert que pour un nouveau fichier ; la photo actuelle
        // est affichée dans le Blade via $referee->profile_photo_path.
        $this->profile_photo_preview = null;
        $this->loadAptitudePeriod();
    }

    /**
     * Preview live quand on choisit une nouvelle photo
     */
    public function updatedProfilePhoto(): void
    {
        if ($this->profile_photo) {
            $this->validateOnly('profile_photo', [
                'profile_photo' => ['image', 'max:2048'], // 2 Mo
            ]);

            $this->profile_photo_preview = $this->profile_photo->temporaryUrl();
        }
    }

    public function updated(string $property): void
    {
        if (str_starts_with($property, 'medical_')) {
            $this->medicalAptitudeTouched = true;
        }

        if (str_starts_with($property, 'physical_')) {
            $this->physicalAptitudeTouched = true;
        }
    }

    protected function uploadProfilePhoto(): ?string
    {
        if (! $this->profile_photo) {
            return null;
        }

        // 👉 Dossier exact souhaité
        return $this->profile_photo->store(
            'referees/profile_photos', // <— sous-dossier ici
            'public'                   // disk "public"
        );
    }

    // Règles de validation
    public function rules(): array
    {
        return [
            'last_name' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['required', 'in:male,female'],
            'education_level' => ['nullable', 'string', 'max:255'],
            'profession' => ['nullable', 'string', 'max:255'],

            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],

            'league_id' => ['required', 'exists:leagues,id'],
            'referee_category_id' => ['required', 'exists:referee_categories,id'],
            'start_year' => ['nullable', 'integer', 'min:1980', 'max:'.now()->year],
            'referee_role_id' => ['required', 'exists:referee_roles,id'],

            'profile_photo' => ['nullable', 'image', 'max:2048'],

            'identity_type' => ['nullable', Rule::in(['passport', 'national_id', 'other'])],
            'number' => [
                Rule::requiredIf($this->identity_type === 'passport'),
                'nullable',
                'string',
                'max:255',
            ],
            'issue_date' => [
                Rule::requiredIf($this->identity_type === 'passport'),
                'nullable',
                'date',
            ],
            'expiry_date' => [
                Rule::requiredIf($this->identity_type === 'passport'),
                'nullable',
                'date',
                'after_or_equal:issue_date',
            ],
        ];
    }

    public function update(): void
    {
        $this->authorize('edit_referee');

        // 1. Validation des datas
        $data = $this->validate();

        $aptitudeTouched = $this->medicalAptitudeTouched || $this->physicalAptitudeTouched;
        $examPeriod = $aptitudeTouched && $this->aptitudeSeasonYear
            ? RefereeExamPeriod::query()
                ->where('season_year', $this->aptitudeSeasonYear)
                ->open()
                ->first()
            : null;

        if ($aptitudeTouched && ! $examPeriod) {
            throw ValidationException::withMessages([
                'aptitude_period' => __('Medical and physical results can only be recorded during an open pre-season examination period.'),
            ]);
        }

        $medicalData = $this->medicalAptitudeTouched
            ? validator([
                'medical_exam_date' => $this->medical_exam_date,
                'medical_result' => $this->medical_result,
                'medical_notes' => $this->medical_notes,
                'medical_certificate' => $this->medical_certificate,
            ], [
                'medical_exam_date' => ['required', 'date', 'before_or_equal:today'],
                'medical_result' => ['required', Rule::in(RefereeMedicalExam::results())],
                'medical_notes' => ['nullable', 'string', 'max:2000'],
                'medical_certificate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            ])->validate()
            : null;

        $physicalData = $this->physicalAptitudeTouched
            ? validator([
                'physical_test_date' => $this->physical_test_date,
                'physical_result' => $this->physical_result,
                'physical_level' => $this->physical_level,
                'physical_notes' => $this->physical_notes,
                'physical_report' => $this->physical_report,
            ], [
                'physical_test_date' => ['required', 'date', 'before_or_equal:today'],
                'physical_result' => ['required', Rule::in(RefereePhysicalTest::results())],
                'physical_level' => ['nullable', 'string', 'max:255'],
                'physical_notes' => ['nullable', 'string', 'max:2000'],
                'physical_report' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            ])->validate()
            : null;

        $existingMedicalExam = $medicalData
            ? $this->referee->medicalExams()->where('season_year', $this->aptitudeSeasonYear)->first()
            : null;
        $existingPhysicalTest = $physicalData
            ? $this->referee->physicalTests()->where('season_year', $this->aptitudeSeasonYear)->first()
            : null;

        $certificatePath = $medicalData
            ? ($this->medical_certificate?->store('referees/aptitudes/medical', 'public')
                ?? $existingMedicalExam?->file_path)
            : null;
        $reportPath = $physicalData
            ? ($this->physical_report?->store('referees/aptitudes/physical', 'public')
                ?? $existingPhysicalTest?->file_path)
            : null;

        // ----- 2. Transaction pour tout mettre à jour proprement -----
        DB::transaction(function () use ($data, $medicalData, $physicalData, $certificatePath, $reportPath): void {
            // On recharge avec lock pour éviter la concurrence
            $referee = Referee::lockForUpdate()->findOrFail($this->referee->id);

            if ($medicalData || $physicalData) {
                $periodStillOpen = RefereeExamPeriod::query()
                    ->where('season_year', $this->aptitudeSeasonYear)
                    ->lockForUpdate()
                    ->first()?->isOpen();

                if (! $periodStillOpen) {
                    throw ValidationException::withMessages([
                        'aptitude_period' => __('The examination period closed before the results were saved.'),
                    ]);
                }
            }

            $leagueChanged = $data['league_id'] !== $this->originalLeagueId;

            // Si la ligue a changé, il faut générer un NOUVEL ID pour

            if ($leagueChanged) {
                $league = League::findOrFail($data['league_id']);

                // Génère un NOUVEL ID pour la nouvelle ligue
                $newPersonId = $this->generateRefereeId($league);

                $referee->update([
                    'person_id' => $newPersonId,
                    'league_id' => $league->id,
                ]);

            }

            // a) Mise à jour de l’arbitre
            $referee->fill([
                'last_name' => $this->last_name,
                'first_name' => $this->first_name,
                'date_of_birth' => $this->date_of_birth ?: null,
                'gender' => $this->gender,
                'education_level' => $this->education_level,
                'profession' => $this->profession,

                'phone' => $this->phone,
                'email' => $this->email,
                'address' => $this->address,

                'league_id' => $this->league_id,
                'referee_category_id' => $this->referee_category_id,
                'start_year' => $this->start_year,
                'referee_role_id' => $this->referee_role_id,
            ]);

            // b) Gestion de la photo (si nouvelle)
            if ($this->profile_photo) {
                // $path = $this->profile_photo->store('referees', 'public');
                $path = $this->uploadProfilePhoto();
                $referee->profile_photo_path = $path;
            }

            $referee->save();

            // c) Document d’identité
            if ($this->identity_type) {
                $payload = [
                    'type' => $this->identity_type,
                    'number' => $this->number,
                    'issue_date' => $this->issue_date ?: null,
                    'expiry_date' => $this->expiry_date ?: null,
                ];

                // updateOrCreate sur la relation hasOne
                $referee->identityDocument()
                    ->updateOrCreate(
                        ['referee_id' => $referee->id],
                        $payload
                    );
            } else {
                // Si aucun type sélectionné, on supprime le document éventuel
                $referee->identityDocument()->delete();
            }

            if ($medicalData) {
                $referee->medicalExams()->updateOrCreate(
                    ['season_year' => $this->aptitudeSeasonYear],
                    [
                        'exam_date' => $medicalData['medical_exam_date'],
                        'result' => $medicalData['medical_result'],
                        'file_path' => $certificatePath,
                        'notes' => $medicalData['medical_notes'],
                        'recorded_by' => auth()->id(),
                    ]
                );

                $latestExam = $referee->medicalExams()
                    ->orderByDesc('exam_date')
                    ->orderByDesc('id')
                    ->first();

                $referee->has_medical_clearance = $latestExam?->result === RefereeMedicalExam::RESULT_PASSED;
            }

            if ($physicalData) {
                $referee->physicalTests()->updateOrCreate(
                    ['season_year' => $this->aptitudeSeasonYear],
                    [
                        'test_date' => $physicalData['physical_test_date'],
                        'result' => $physicalData['physical_result'],
                        'level' => $physicalData['physical_level'],
                        'file_path' => $reportPath,
                        'notes' => $physicalData['physical_notes'],
                        'recorded_by' => auth()->id(),
                    ]
                );

                $latestTest = $referee->physicalTests()
                    ->orderByDesc('test_date')
                    ->orderByDesc('id')
                    ->first();

                $referee->has_physical_clearance = $latestTest?->result === RefereePhysicalTest::RESULT_PASSED;
            }

            $referee->save();
            $this->referee = $referee->fresh();
        });

        // ----- 3. Feedback et redirection -----
        session()->flash('status', __('Referee updated successfully.'));

        // Redirection vers la liste (adapter le nom de route si besoin)
        $this->redirectRoute('referees.index');
    }

    public function with(): array
    {
        return [
            'medicalExams' => $this->referee->medicalExams()
                ->orderByDesc('exam_date')
                ->orderByDesc('id')
                ->limit(5)
                ->get(),
            'physicalTests' => $this->referee->physicalTests()
                ->orderByDesc('test_date')
                ->orderByDesc('id')
                ->limit(5)
                ->get(),
        ];
    }

    private function loadAptitudePeriod(): void
    {
        $period = RefereeExamPeriod::query()
            ->open()
            ->orderByDesc('season_year')
            ->first();

        $this->aptitudePeriodOpen = $period !== null;
        $this->aptitudeSeasonYear = $period?->season_year;
        $this->aptitudePeriodOpensAt = $period?->opens_at?->format('d/m/Y');
        $this->aptitudePeriodClosesAt = $period?->closes_at?->format('d/m/Y');

        if (! $period) {
            return;
        }

        $medicalExam = $this->referee->medicalExams()
            ->where('season_year', $period->season_year)
            ->first();
        $physicalTest = $this->referee->physicalTests()
            ->where('season_year', $period->season_year)
            ->first();

        $this->medical_exam_date = $medicalExam?->exam_date?->format('Y-m-d') ?? today()->format('Y-m-d');
        $this->medical_result = $medicalExam?->result ?? RefereeMedicalExam::RESULT_PENDING;
        $this->medical_notes = $medicalExam?->notes;

        $this->physical_test_date = $physicalTest?->test_date?->format('Y-m-d') ?? today()->format('Y-m-d');
        $this->physical_result = $physicalTest?->result ?? RefereePhysicalTest::RESULT_PENDING;
        $this->physical_level = $physicalTest?->level;
        $this->physical_notes = $physicalTest?->notes;

        $this->medicalAptitudeTouched = false;
        $this->physicalAptitudeTouched = false;
    }

    public function generateRefereeId(League $league): string
    {
        League::whereKey($league->id)->lockForUpdate()->firstOrFail();

        $lastNumber = Referee::where('league_id', $league->id)
            ->whereNotNull('person_id')
            ->pluck('person_id')
            ->reduce(function (int $maximum, string $personId): int {
                return preg_match('/-(\d+)$/', $personId, $matches)
                    ? max($maximum, (int) $matches[1])
                    : $maximum;
            }, 0);

        $number = $lastNumber + 1;

        // Formatage sur 6 chiffres
        $formattedNumber = str_pad($number, 6, '0', STR_PAD_LEFT);

        // Final ID : LIFKIN-000123, etc.
        return "{$league->code}-{$formattedNumber}";
    }
}

?>

<div>
    <section class="container mx-auto w-full max-w-7xl px-6 pb-8">
        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <!-- Title -->
        <header class="mb-6">
            <h1 class="text-3xl font-semibold text-neutral-900 dark:text-neutral-400">{{ __("Update referee") }}</h1>
            <p class="mt-1 text-sm text-neutral-500">
                {{ __("Fill in the referee personal details, league and function.") }}
            </p>
        </header>

        <div class="grid grid-cols-3 gap-4 mb-6 dark:text-gray-700">
            <div class="flex flex-col col-span-2 gap-4 mb-6">
                <div class="bg-white dark:bg-[#0E1526] dark:border dark:border-neutral-700 w-full p-6 rounded-xl">
                    <h2 class="text-xl font-semibold mb-4 dark:text-slate-400">
                        {{ __("Referee Information") }}
                    </h2>

                    <div class="flex flex-row gap-2">
                        <div class="w-full">
                            <div class="flex flex-row gap-4 mb-4">
                                <flux:input label="{{ __('Last name') }}" wire:model.defer="last_name"
                                    placeholder="NDALA NGAMBO" class="mb-2" required />
                                <flux:input label="{{ __('First name') }}" wire:model.defer="first_name" class="mb-2"
                                    placeholder="Jean-Jacques" required />
                            </div>

                            <div class="flex flex-row gap-4 mb-4">
                                <flux:input type="date" label="{{ __('Date of birth') }}"
                                    wire:model.defer="date_of_birth" class="mb-2" />

                                <flux:select label="{{ __('Gender') }}" wire:model.defer="gender" class="mb-2" required>
                                    <flux:select.option value="">{{ __('Select gender') }}</flux:select.option>
                                    <flux:select.option value="male">{{ ucfirst(__('male')) }}</flux:select.option>
                                    <flux:select.option value="female">{{ ucfirst(__('female')) }}</flux:select.option>
                                </flux:select>
                            </div>

                            <div class="flex flex-row gap-4 mb-0">
                                <flux:input type="text" label="{{ __('Education level') }}"
                                    wire:model.defer="education_level" placeholder="Ex: L2, G3, DEA, D6" class="mb-2" />

                                <flux:input type="text" label="{{ __('Profession') }}" wire:model.defer="profession"
                                    placeholder="Ex: Policier, Ingénieur, Libéral, Etudiant" class="mb-2" />
                            </div>
                        </div>

                        {{-- Photo de profil --}}
                        <div class="flex flex-col items-start gap-4">
                            @if ($profile_photo_preview)
                                <div class="border p-2">
                                    <img src="{{ $profile_photo_preview }}" class="h-12 w-12 rounded object-cover"
                                        alt="Photo preview">
                                </div>
                            @elseif(!empty($referee?->profile_photo_path ?? null))
                                <img src="{{ asset('storage/' . $referee->profile_photo_path) }}"
                                    class="h-24 w-24 rounded object-cover overflow-hidden" alt="Referee photo">
                            @else
                                <div class="col-span-full">
                                    <label for="photo" class="block text-sm/6 font-medium text-white">Photo</label>
                                    <div class="mt-2 flex items-center gap-x-3">
                                        <svg viewBox="0 0 24 24" fill="currentColor" data-slot="icon" aria-hidden="true"
                                            class="size-12 text-gray-500">
                                            <path
                                                d="M18.685 19.097A9.723 9.723 0 0 0 21.75 12c0-5.385-4.365-9.75-9.75-9.75S2.25 6.615 2.25 12a9.723 9.723 0 0 0 3.065 7.097A9.716 9.716 0 0 0 12 21.75a9.716 9.716 0 0 0 6.685-2.653Zm-12.54-1.285A7.486 7.486 0 0 1 12 15a7.486 7.486 0 0 1 5.855 2.812A8.224 8.224 0 0 1 12 20.25a8.224 8.224 0 0 1-5.855-2.438ZM15.75 9a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"
                                                clip-rule="evenodd" fill-rule="evenodd" />
                                        </svg>
                                        <button type="button"
                                            class="rounded-md bg-white/10 px-3 py-2 text-sm font-semibold text-white inset-ring inset-ring-white/5 hover:bg-white/20">Change</button>
                                    </div>
                                </div>
                            @endif

                            <div class="flex-1">
                                <flux:input type="file" wire:model="profile_photo" label="Photo de profil"
                                    accept="image/*" />
                                <flux:text class="mt-1 text-xs text-neutral-400">
                                    {{ __("JPG, PNG, max 2 Mo") }}
                                </flux:text>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-[#0E1526] dark:border dark:border-neutral-700 p-6 rounded-xl">
                    <h2 class="text-xl font-semibold mb-4 dark:text-slate-400">
                        {{ __("Contact details") }}
                    </h2>

                    <div class="flex flex-col gap-2">
                        <div class="flex flex-row gap-4 mb-4">
                            <flux:input type="tel" wire:model.defer="phone" label="{{ __('Phone number') }}"
                                placeholder="Ex: +243000000000" />
                            <flux:input type="email" wire:model.defer="email" label="{{ __('E-mail Address') }}"
                                placeholder="Ex: johndoe@example.com" />
                        </div>
                        <div class="block w-117">
                            <flux:textarea wire:model.defer="address" rows="4" label="{{ __('Home address') }}" />
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-[#0E1526] dark:border dark:border-neutral-700 w-full p-6 rounded-xl">
                    <h2 class="text-xl font-semibold mb-4 dark:text-slate-400">
                        {{ __("Identity Document") }}
                    </h2>

                    <div class="flex flex-row gap-2">
                        <div class="w-full">
                            <div class="flex flex-row gap-4 mb-4">
                                <flux:select wire:model="identity_type" label="{{ __('Type of document') }}">
                                    <flux:select.option value="">{{ __("Choose") }}</flux:select.option>
                                    <flux:select.option value="passport">{{ __("Passport") }}</flux:select.option>
                                    <flux:select.option value="national_id">{{ __("National ID") }}
                                    </flux:select.option>
                                    <flux:select.option value="other">{{ __('Other') }}</flux:select.option>
                                </flux:select>

                                <flux:input wire:model.defer="number" label="{{ __('Number') }}"
                                    placeholder="Ex: P0000123" />

                                <div x-data>
                                    <div x-show="$wire.identity_type === 'passport'" x-transition>
                                        <div class="flex flex-row gap-2">
                                            <flux:input type="date" wire:model.defer="issue_date"
                                                label="{{ __('Issue date') }}" />
                                            <flux:input type="date" wire:model.defer="expiry_date"
                                                label="{{ __('Expiry date') }}" />
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                @include('livewire.referees.partials.aptitudes')
            </div>

            <div class="flex flex-col gap-4">
                <div class="bg-white dark:bg-[#0E1526] dark:border dark:border-neutral-700 p-6 rounded-xl">
                    <h2 class="text-xl font-semibold mb-4 dark:text-slate-400">
                        {{ __("Affiliated league") }}
                    </h2>
                    <div>
                        <flux:select wire:model.defer="league_id" class="mb-4" required>
                            <flux:select.option value="">{{ __('Select league') }}</flux:select.option>
                            @foreach($leagues as $league)
                                <flux:select.option value="{{ $league['id'] }}">
                                    {{ $league['code'] }} – {{ $league['name'] }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>

                        @error('league_id')
                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <div class="bg-white dark:bg-[#0E1526] dark:border dark:border-neutral-700 p-6 rounded-xl">
                    <h2 class="text-xl font-semibold mb-4 dark:text-slate-400">
                        {{ __("Refereeing career") }}
                    </h2>

                    <flux:input type="number" wire:model.defer="start_year" label="{{ __('Start year') }}" min="1980"
                        placeholder="Ex: 2008" class="mb-2" />

                    <flux:select label="{{ __('Category') }}" wire:model.defer="referee_category_id" class="mb-2"
                        required>
                        <flux:select.option value="">{{ __('Select category') }}</flux:select.option>
                        @foreach ($categories as $category)
                            <flux:select.option value="{{ $category['id'] }}">
                                {{ $category['name'] }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select label="{{ __('Function') }}" wire:model.defer="referee_role_id" required>
                        <flux:select.option value="">{{ __('Select function') }}</flux:select.option>
                        @foreach($roles as $role)
                            <flux:select.option value="{{ $role['id'] }}">
                                {{ $role['name'] }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                @include('livewire.referees.partials.aptitude-results')
            </div>
        </div>

    </section>

    {{-- Footer fixe avec bouton Enregistrer --}}
    <div x-data="{ show: false }" x-init="
        const toggleFooter = () => { show = window.scrollY > 200 };
        toggleFooter();
        window.addEventListener('scroll', toggleFooter);
    " x-show="show" x-transition.opacity.duration.200ms
        class="fixed bottom-0 left-0 right-0 z-40 bg-neutral-900 dark:bg-[#0080C0] pointer-events-none">
        <div class="pointer-events-auto mx-auto flex max-w-7xl items-center justify-between px-6 py-3">
            {{-- Texte optionnel --}}
            <span class="hidden sm:inline text-sm text-neutral-300">
                {{ __("Des modifications n'ont pas été enregistrées") }}
            </span>

            {{-- Bouton Enregistrer --}}
            <flux:button class="text-white font-medium rounded-3xl text-sm px-4 py-3 focus:outline-none cursor-pointer"
                wire:click="update" wire:loading.attr="disabled">
                <span wire:loading.remove>{{ __('Save') }}</span>
                <span wire:loading>{{ __('Saving...') }}</span>
            </flux:button>
        </div>
    </div>
</div>
