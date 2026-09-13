<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Liste des arbitres éligibles {{ $seasonYear }}-{{ $seasonYear + 1 }}</title>
    <style>
        @page { margin: 80px 25px 55px; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 10px; color: #111827; }
        header { position: fixed; top: -60px; left: 0; right: 0; height: 55px; }
        .header-table, .footer-table { width: 100%; border-collapse: collapse; }
        .header-table td, .footer-table td { border: 0; }
        .logo { height: 42px; }
        .center { text-align: center; }
        .right { text-align: right; }
        .title { font-size: 13px; font-weight: bold; text-transform: uppercase; }
        .subtitle { margin-top: 3px; font-size: 10px; }
        .summary { margin: 5px 0 10px; padding: 7px; background: #f3f4f6; }
        table.list { width: 100%; border-collapse: collapse; }
        table.list thead { display: table-header-group; }
        table.list tr { page-break-inside: avoid; }
        table.list th, table.list td { border: 0.5px solid #374151; padding: 4px 3px; }
        table.list th { background: #e5e7eb; font-size: 9px; text-align: center; }
        table.list td { font-size: 8.5px; vertical-align: middle; }
        footer { position: fixed; bottom: -35px; left: 0; right: 0; font-size: 8px; color: #4b5563; }
    </style>
</head>

<body>
    <header>
        <table class="header-table">
            <tr>
                <td style="width: 15%" class="center">
                    <img src="{{ public_path('images/fecofa-logo.png') }}" alt="FECOFA" class="logo">
                </td>
                <td class="center">
                    <div class="title">Fédération Congolaise de Football Association (FECOFA)</div>
                    <div class="subtitle">Département de l’Arbitrage</div>
                    <div class="subtitle"><strong>Liste des arbitres éligibles — Saison {{ $seasonYear }}–{{ $seasonYear + 1 }}</strong></div>
                </td>
                <td style="width: 15%" class="center">
                    <img src="{{ public_path('images/fecofa-logo.png') }}" alt="FECOFA" class="logo">
                </td>
            </tr>
        </table>
    </header>

    <footer>
        <table class="footer-table">
            <tr>
                <td>Document destiné au Département des Compétitions</td>
                <td class="right">Généré le {{ $generatedAt->format('d/m/Y à H:i') }}</td>
            </tr>
        </table>
    </footer>

    <main>
        <div class="summary">
            Total : <strong>{{ $referees->count() }}</strong> —
            Arbitres : <strong>{{ $centralCount }}</strong> —
            Arbitres assistants : <strong>{{ $assistantCount }}</strong>
        </div>

        <table class="list">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Matricule</th>
                    <th>Nom complet</th>
                    <th>Ligue</th>
                    <th>Catégorie</th>
                    <th>Fonction</th>
                    <th>Examen médical</th>
                    <th>Test physique</th>
                    <th>Niveau du test</th>
                </tr>
            </thead>
            <tbody>
                @forelse($referees as $index => $referee)
                    @php
                        $medicalExam = $referee->medicalExams->first();
                        $physicalTest = $referee->physicalTests->first();
                    @endphp
                    <tr>
                        <td class="center">{{ $index + 1 }}</td>
                        <td>{{ $referee->person_id }}</td>
                        <td>{{ mb_strtoupper($referee->last_name) }} {{ $referee->first_name }}</td>
                        <td class="center">{{ $referee->league?->code }}</td>
                        <td>{{ $referee->refereeCategory?->name }}</td>
                        <td>{{ $referee->refereeRole?->name }}</td>
                        <td class="center">{{ $medicalExam?->exam_date?->format('d/m/Y') }}</td>
                        <td class="center">{{ $physicalTest?->test_date?->format('d/m/Y') }}</td>
                        <td>{{ $physicalTest?->level ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="center">Aucun arbitre n’a satisfait aux deux tests pour cette saison.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </main>
</body>

</html>
