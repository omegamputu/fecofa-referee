<?php 
use Livewire\Volt\Component;
use App\Models\Referees\Referee;
use App\Models\Referees\IdentityDocument;

new class extends Component {

    public function with(): array
    {
        // 1. Statistiques simples
        $totalReferees = Referee::count();

        $international = DB::table('referee_categories')
            // 1. On sélectionne les colonnes de base de la catégorie
            ->select('id', 'name')
            // 2. On injecte le compteur via une sous-requête
            ->addSelect([
                'nombre_arbitres' => function ($query) {
                    $query->selectRaw('COUNT(*)')
                        ->from('referees')
                        ->whereColumn('referees.referee_category_id', 'referee_categories.id');
                }
            ])
            // 2. On filtre pour ne récupérer que la catégorie "Internationale"
            ->where('name', 'Internationale')
            ->get();

        $national = DB::table('referee_categories')
            // 1. On sélectionne les colonnes de base de la catégorie
            ->select('id', 'name')
            // 2. On injecte le compteur via une sous-requête
            ->addSelect([
                'nombre_arbitres' => function ($query) {
                    $query->selectRaw('COUNT(*)')
                        ->from('referees')
                        ->whereColumn('referees.referee_category_id', 'referee_categories.id');
                }
            ])
            // 2. On filtre pour ne récupérer que la catégorie "Internationale"
            ->where('name', 'Nationale')
            ->get();

        $newThisMonth = Referee::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        // 2. Derniers arbitres ajoutés
        $lastReferees = Referee::with('league', 'refereeRole')
            ->latest()
            ->take(5)
            ->get();

        // 3. Documents qui expirent bientôt (par ex. dans les 3 prochains mois)
        $soonExpiringDocs = IdentityDocument::with('referee')
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '>=', now())
            ->whereDate('expiry_date', '<=', now()->addMonths(3))
            ->orderBy('expiry_date')
            ->take(5)
            ->get();

        // 4. Répartition par catégorie (version texte)
        $byCategory = Referee::selectRaw('referee_category_id, COUNT(*) as total')
            ->groupBy('referee_category_id')
            ->orderBy('total', 'desc')
            ->with('refereeCategory')
            ->get();

        $byLeague = Referee::selectRaw('league_id, COUNT(*) as total')
            ->whereNotNull('league_id')
            ->groupBy('league_id')
            ->with('league')
            ->orderBy('total', 'desc')
            ->get();


        return compact(
            'totalReferees',
            'international',
            'national',
            'newThisMonth',
            'lastReferees',
            'soonExpiringDocs',
            'byCategory',
            'byLeague'
        );
    }
}
?>

<div>
    <div class="container mx-auto h-full w-full max-w-7xl px-6">
        {{-- Titre --}}
        <header class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold dark:text-neutral-400 text-neutral-900">
                    {{ __('Dashboard') }}
                </h1>
                <p class="mt-1 text-sm text-neutral-500">
                    {{ __("Overview of FECOFA refereeing.") }}
                </p>
            </div>

            {{-- Actions rapides --}}
            <div class="flex gap-2">
                @can('create_referee')
                    <flux:button variant="primary" color="green" href="{{ route('referees.create') }}" wire:navigate>
                        {{ __('Add referee') }}
                    </flux:button>
                @endcan

                @can('export_referee_data')
                    <flux:button variant="outline" href="{{ route('referees.export') }}">
                        {{ __('Export PDF') }}
                    </flux:button>
                @endcan
            </div>
        </header>

        {{-- 1. Cartes statistiques --}}
        <section class="grid gap-4 md:grid-cols-4 mb-8">
            <div class="bg-white dark:bg-[#0E1526] dark:border dark:border-neutral-600 rounded-xl p-4">
                <p class="text-xs uppercase text-neutral-500">{{ __('Total referees') }}</p>
                <p class="mt-2 text-2xl font-semibold">{{ $totalReferees }}</p>
            </div>

            <div class="bg-white dark:bg-[#0E1526] dark:border dark:border-neutral-600 rounded-xl p-4">
                <p class="text-xs uppercase text-neutral-500">{{ __('International') }}</p>
                <p class="mt-2 text-2xl font-semibold">{{ $international->first()->nombre_arbitres }}</p>
            </div>

            <div class="bg-white dark:bg-[#0E1526] dark:border dark:border-neutral-600 rounded-xl p-4">
                <p class="text-xs uppercase text-neutral-500">{{ __('National') }}</p>
                <p class="mt-2 text-2xl font-semibold">{{ $national->first()->nombre_arbitres }}</p>
            </div>

            <div class="bg-white dark:bg-[#0E1526] dark:border dark:border-neutral-600 rounded-xl p-4">
                <p class="text-xs uppercase text-neutral-500">{{ __('New this month') }}</p>
                <p class="mt-2 text-2xl font-semibold">{{ $newThisMonth }}</p>
            </div>
        </section>

        <div class="grid gap-6 md:grid-cols-2">
            {{-- 2. Derniers arbitres ajoutés --}}
            <section class="bg-white dark:bg-[#0E1526] dark:border dark:border-neutral-600 rounded-xl p-4">
                <h2 class="text-base font-semibold dark:text-neutral-500 mb-3">
                    {{ __('Latest registered referees') }}
                </h2>

                @if ($lastReferees->isEmpty())
                    <p class="text-sm text-neutral-500">
                        {{ __("No referees have been registered yet.") }}
                    </p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="border-b dark:border-neutral-600 text-neutral-500 text-xs">
                                <tr>
                                    <th class="py-2 text-left">Person ID</th>
                                    <th class="py-2 text-left">{{ __('Name') }}</th>
                                    <th class="py-2 text-left">{{ __('League') }}</th>
                                    <th class="py-2 text-left">{{ __('Category') }}</th>
                                    <th class="py-2 text-left">{{ __('Added on') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @foreach($lastReferees as $referee)
                                    <tr>
                                        <td class="py-2">{{ $referee->person_id }}</td>
                                        <td class="py-2">
                                            {{ $referee->last_name }} {{ $referee->first_name }}
                                        </td>
                                        <td class="py-2">
                                            {{ $referee->league?->code ?? '—' }}
                                        </td>
                                        <td class="py-2">
                                            {{ ucfirst($referee->refereeCategory?->name) }}
                                        </td>
                                        <td class="py-2">
                                            {{ $referee->created_at->format('d/m/Y') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3 text-right">
                        <a href="{{ route('referees.index') }}" wire:navigate
                            class="text-xs text-primary-600 dark:text-[#0080C0] hover:underline">
                            {{ __('View all referees') }} →
                        </a>
                    </div>
                @endif
            </section>

            {{-- 3. Documents à surveiller --}}
            <section class="bg-white dark:bg-[#0E1526] dark:border dark:border-neutral-600 rounded-xl p-4">
                <h2 class="text-base font-semibold dark:text-neutral-500 mb-3">
                    {{ __('Documents expiring soon') }}
                </h2>

                @if ($soonExpiringDocs->isEmpty())
                    <p class="text-sm text-neutral-500">
                        {{ __("No documents are due to expire soon.") }}
                    </p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="border-b dark:border-neutral-600 text-neutral-500 text-xs uppercase">
                                <tr>
                                    <th class="py-2 text-left">{{ __('Referee') }}</th>
                                    <th class="py-2 text-left">{{ __('Type') }}</th>
                                    <th class="py-2 text-left">{{ __('Expiry date') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @foreach($soonExpiringDocs as $doc)
                                    <tr>
                                        <td class="py-2 text-xs">
                                            {{ $doc->referee?->last_name }}
                                            {{ $doc->referee?->first_name }}
                                        </td>
                                        <td class="py-2 text-xs">
                                            {{ ucfirst(str_replace('_', ' ', $doc->type)) }}
                                        </td>
                                        <td class="py-2 text-xs">
                                            {{ \Illuminate\Support\Carbon::parse($doc->expiry_date)->format('d/m/Y') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            {{-- 4. Répartition par catégorie --}}
            <section class="bg-white dark:bg-[#0E1526] dark:border dark:border-neutral-600 rounded-xl p-4 mt-6">
                <h2 class="text-base font-semibold dark:text-neutral-500 mb-3">
                    {{ __('Breakdown by category') }}
                </h2>

                @if ($byCategory->isEmpty())
                    <p class="text-sm text-neutral-500">
                        {{ __("No data available at this time.") }}
                    </p>
                @else
                    <table class="min-w-[250px] text-sm">
                        <thead class="border-b dark:border-neutral-600 text-neutral-500 text-xs">
                            <tr>
                                <th class="py-2 text-left">{{ __('Category') }}</th>
                                <th class="py-2 text-left">{{ __('Number of referees') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach($byCategory as $row)
                                <tr>
                                    <td class="py-2 text-xs">{{ ucfirst($row->refereeCategory?->name) }}</td>
                                    <td class="py-2 text-xs">{{ $row->total }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </section>

            {{-- Répartition par ligue --}}
            <section class="bg-white dark:bg-[#0E1526] dark:border dark:border-neutral-600 rounded-xl p-4 mt-6">
                <h2 class="text-base font-semibold dark:text-neutral-500 mb-3">
                    {{ __('Breakdown by league') }}
                </h2>

                @if ($byLeague->isEmpty())
                    <p class="text-sm text-neutral-500">
                        {{ __("No data available at this time.") }}
                    </p>
                @else
                    <table class="min-w-[300px] text-sm">
                        <thead class="border-b dark:border-neutral-600 text-neutral-500 text-xs">
                            <tr>
                                <th class="py-2 text-left">{{ __('League') }}</th>
                                <th class="py-2 text-left">{{ __('Number of referees') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach($byLeague as $row)
                                <tr>
                                    <td class="py-2 text-xs">
                                        {{ $row->league?->code }}
                                    </td>
                                    <td class="py-2 text-xs">
                                        {{ $row->total }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </section>
        </div>
    </div>
</div>