<?php 

use Livewire\Volt\Component;
use Illuminate\Support\Facades\DB;
use App\Models\Referees\RefereeRole;
use App\Models\Instructors\Instructor;
use App\Models\Referees\RefereeCategory;
use App\Models\Instructors\InstructorRole;
use Livewire\WithFileUploads;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

new class extends Component {
    use WithFileUploads;

    public ?Instructor $instructor = null;

    public array $roles = [];
    public array $instructor_roles = [];
    public array $categories = [];

    // Champs du formulaire
    public ?int $referee_category_id = null; // Category of the referee
    public ?int $referee_role_id = null; // Function of the referee
    public ?int $instructor_role_id = null; // Role of the instructor

    public string $last_name = '';
    public string $first_name = '';
    public ?string $year_of_birth = null;
    public ?string $gender = null;

    public ?string $phone = null;
    public ?string $email = null;
    public ?string $address = null;

    public ?string $profession = null;

    public ?string $education_level = null;

    public ?int $start_year = null; // Year the referee started

    public ?string $identity_type = null;
    public ?string $number = null;
    public ?string $issue_date = null;
    public ?string $expiry_date = null;

    // Upload photo
    public $profile_photo = null;
    public ?string $profile_photo_preview = null;

    public function mount(Instructor $instructor): void
    {
        $this->instructor = $instructor;
        $this->roles = RefereeRole::orderBy('name')->get(['id', 'name'])->toArray();
        $this->instructor_roles = InstructorRole::orderBy('name')->get(['id', 'name'])->toArray();
        $this->categories = RefereeCategory::orderBy('id')->get(['id', 'name'])->toArray();

        //

        $this->last_name = $instructor->last_name;
        $this->first_name = $instructor->first_name;
        $this->gender = $instructor->gender;
        $this->year_of_birth = $instructor->year_of_birth;

        $this->education_level = $instructor->education_level;
        $this->profession = $instructor->profession;

        $this->phone = $instructor->phone;
        $this->email = $instructor->email;
        $this->address = $instructor->address;

        $this->instructor_role_id = $instructor->instructor_role_id;
        $this->referee_role_id = $instructor->referee_role_id;
        $this->referee_category_id = $instructor->referee_category_id;
        $this->start_year = $instructor->start_year;

        //
        $this->profile_photo_preview = null;
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

    protected function uploadProfilePhoto(): ?string
    {
        if (!$this->profile_photo) {
            return null;
        }

        // 👉 Dossier exact souhaité
        return $this->profile_photo->store(
            'instructors/profile_photos', // <— sous-dossier ici
            'public'                   // disk "public"
        );
    }

    public function rules(): array
    {
        return [
            'last_name' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'year_of_birth' => ['nullable', 'integer', 'min:1940', 'max:' . date('Y')],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'education_level' => ['nullable', 'string', 'max:255'],
            'profession' => ['nullable', 'string', 'max:255'],

            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],

            'identity_type' => ['nullable', Rule::in(['passport', 'national_id', 'other', ''])],
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

            'start_year' => ['nullable', 'integer', 'min:1960', 'max:' . date('Y')],
            'referee_category_id' => ['required', 'exists:referee_categories,id'],
            'referee_role_id' => ['required', 'exists:referee_roles,id'],
            'instructor_role_id' => ['required', 'exists:instructor_roles,id'],

            'profile_photo' => ['nullable', 'image', 'max:2048'], // 2 Mo
        ];
    }

    public function save(): void
    {
        //1. Validation des datas
        $data = $this->validate();

        // 2. Transaction
        DB::transaction(function () use ($data) {

            // 3.6 Upload de la photo éventuelle
            $photoPath = null;
            if ($this->profile_photo) {
                $photoPath = $this->uploadProfilePhoto();
            }
            // 
            $this->instructor->fill([
                'instructor_role_id' => $this->instructor_role_id,
                'referee_role_id' => $this->referee_role_id,
                'referee_category_id' => $this->referee_category_id,
                'last_name' => $this->last_name,
                'first_name' => $this->first_name,
                'year_of_birth' => $this->year_of_birth ?: null,
                'gender' => $this->gender,
                'phone' => $this->phone,
                'email' => $this->email,
                'address' => $this->address,
                'education_level' => $this->education_level,
                'profession' => $this->profession,
                'start_year' => $this->start_year ?: null
            ]);

            // b) Gestion de la photo (si nouvelle)
            if ($this->profile_photo) {
                //$path = $this->profile_photo->store('referees', 'public');
                $path = $this->uploadProfilePhoto();
                $this->referee->profile_photo_path = $path;
            }

            $this->instructor->save();
        });

        // Petit flash + redirection vers la liste
        session()->flash('status', __('Instructor updated successfully.'));

        $this->redirectRoute('instructors.index');
    }
}
?>

<div>
    <div class="container mx-auto w-full max-w-7xl px-6 pb-8">
        <section>
            <!-- Session Status -->
            <x-auth-session-status class="text-center" :status="session('status')" />

            <!-- Title -->
            <header class="mb-6">
                <h1 class="text-3xl font-semibold text-neutral-900 dark:text-neutral-400">{{ __("Edit instructor") }}
                </h1>
                <p class="mt-1 text-sm text-neutral-500">
                    {{ __("Fill in the instructor personal details, role and function.") }}
                </p>
            </header>

            <div class="grid grid-cols-3 gap-4 mb-6">
                <div class="flex flex-col col-span-2 gap-4 mb-6">
                    <div class="bg-white dark:bg-[#0E1526] dark:border dark:border-neutral-700 w-full p-6 rounded-xl">
                        <h2 class="text-xl font-semibold dark:text-slate-400 mb-4">
                            {{ __("Instructor Information") }}
                        </h2>

                        <div class="flex flex-row gap-2">
                            <div class="w-full">
                                <div class="flex flex-row gap-4 mb-4">
                                    <flux:input label="{{ __('Last name') }}" wire:model.defer="last_name"
                                        placeholder="DOE" class="mb-2" required />
                                    <flux:input label="{{ __('First name') }}" wire:model.defer="first_name"
                                        class="mb-2" placeholder="JOHN" required />
                                </div>

                                <div class="flex flex-row gap-4 mb-4">
                                    <flux:input type="number" label="{{ __('Year of birth') }}"
                                        wire:model.defer="year_of_birth" placeholder="Ex: 1964" class="mb-2" />

                                    <flux:select label="{{ __('Gender') }}" wire:model.defer="gender" class="mb-2"
                                        required>
                                        <flux:select.option value="">{{ __('Select gender') }}</flux:select.option>
                                        <flux:select.option value="male">{{ ucfirst(__('male')) }}</flux:select.option>
                                        <flux:select.option value="female">{{ ucfirst(__('female')) }}
                                        </flux:select.option>
                                    </flux:select>
                                </div>

                                <div class="flex flex-row gap-4 mb-0">
                                    <flux:input type="text" label="{{ __('Education level') }}"
                                        wire:model.defer="education_level" placeholder="Ex: L2, G3, DEA, D6"
                                        class="mb-2" />

                                    <flux:input type="text" label="{{ __('Profession') }}" wire:model.defer="profession"
                                        placeholder="Ex: Policier, Ingénieur, Libéral, Etudiant" class="mb-2" />
                                </div>
                            </div>

                            {{-- Photo de profil --}}
                            <div class="flex flex-col items-center gap-4">
                                @if ($profile_photo_preview)
                                    <div class="border p-2">
                                        <img src="{{ $profile_photo_preview }}" class="h-12 w-12 rounded object-cover"
                                            alt="Photo preview">
                                    </div>
                                @elseif(!empty($instructor?->profile_photo_path ?? null))
                                    <img src="{{ asset('storage/' . $instructor->profile_photo_path) }}"
                                        class="h-12 w-12 rounded object-cover" alt="instructor photo">
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
                        <h2 class="text-xl font-semibold dark:text-slate-400 mb-4">
                            {{ __("Contact details") }}
                        </h2>

                        <div class="flex flex-col gap-2">
                            <div class="flex flex-row gap-4 mb-4">
                                <flux:input type="tel" wire:model.defer="phone" label="{{ __('Mobile number') }}"
                                    placeholder="Ex: +243000000000" />
                                <flux:input type="email" wire:model.defer="email" label="{{ __('E-mail Address') }}"
                                    placeholder="Ex: johndoe@example.com" />
                            </div>
                            <div class="block w-117">
                                <flux:textarea wire:model.defer="address" rows="4" label="{{ __('Home address') }}" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-4">
                    <div class="bg-white dark:bg-[#0E1526] dark:border dark:border-neutral-700 p-6 rounded-xl">
                        <h2 class="text-xl font-semibold dark:text-slate-400 mb-4">
                            {{ __("Instructor since") }}
                        </h2>

                        <flux:input type="number" wire:model.defer="start_year" label="{{ __('Start year') }}"
                            min="1980" placeholder="Ex: 2008" class="mb-2" />

                        <flux:select label="{{ __('Role') }}" wire:model.defer="instructor_role_id" class="mb-2"
                            required>
                            <flux:select.option value="">{{ __('Select role') }}</flux:select.option>
                            @foreach ($instructor_roles as $item)
                                <flux:select.option value="{{ $item['id'] }}">
                                    {{ $item['name'] }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>

                    <div class="bg-white dark:bg-[#0E1526] dark:border dark:border-neutral-700 p-6 rounded-xl">
                        <h2 class="text-xl font-semibold dark:text-slate-400 mb-4">
                            {{ __("Refereeing career") }}
                        </h2>

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
                </div>
            </div>
        </section>
    </div>


    {{-- Footer fixe avec bouton Enregistrer --}}
    <div x-data="{ show: false }" x-init="
        const toggleFooter = () => { show = window.scrollY > 50 };
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
            <flux:button
                class="text-white font-medium border rounded-3xl text-sm px-4 py-3 focus:outline-none cursor-pointer"
                wire:click="save" wire:loading.attr="disabled">
                <span wire:loading.remove>{{ __('Save') }}</span>
                <span wire:loading>{{ __('Saving...') }}</span>
            </flux:button>
        </div>
    </div>
</div>