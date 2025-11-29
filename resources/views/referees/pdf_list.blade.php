<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Liste officielle des arbitres - FECOFA</title>

    <style>
        @page {
            margin: 70px 30px 50px 30px;
        }

        body {
            font-family: "Helvetica", "Open Sans", "Arial", sans-serif;
            font-size: 11px;
        }

        /* HEADER FIXE */
        header {
            position: fixed;
            top: -50px;
            left: 0;
            right: 0;
            height: 60px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: middle;
        }

        .logo-left {
            text-align: center;
        }

        .logo-right {
            text-align: center
        }

        .logo {
            height: 45px;
        }

        .header-center {
            text-align: center;
            font-size: 12px;
        }

        .header-title-main {
            font-weight: bold;
            text-transform: uppercase;
        }

        .header-title-sub {
            margin-top: 2px;
        }

        .header-title-small {
            margin-top: 4px;
            font-size: 10px;
        }

        /* FOOTER FIXE */
        .pdf-footer {
            position: fixed;
            bottom: 15px;
            left: 25px;
            right: 25px;
            font-size: 10px;
            font-family: Arial, Helvetica, sans-serif;
        }

        .pdf-footer-table {
            width: 100%;
            border: none;
        }

        .pdf-footer-table td {
            border: none !important;
            padding: 0;
            margin: 0;
            font-size: 10px;
            vertical-align: middle;
        }

        .pdf-footer-left {
            text-align: left;
        }

        .pdf-footer-right {
            text-align: right;
        }

        .text-left {
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        /* TABLEAU PRINCIPAL */
        main {
            margin-top: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        th,
        td {
            border: 0.5px solid #000;
            padding: 3px 2px;
        }

        th {
            font-weight: "Helvetica", "Open Sans", "Arial", sans-serif;
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center;
            font-size: 10px;
        }

        td {
            vertical-align: top;
            font-size: 9px;
        }

        .center {
            text-align: center;
        }

        .small {
            font-size: 9px;
        }
    </style>
</head>

<body>
    <header>
        <table class="header-table">
            <tr>
                <td class="logo-left">
                    <img src="{{ public_path('images/fecofa-logo.png') }}" alt="FECOFA" class="logo">
                </td>
                <td class="header-center">
                    <div class="header-title-main uppercase">
                        Fédération Congolaise de Football Association (FECOFA)
                    </div>
                    <div class="header-title-small">
                        Département de l'Arbitrage – Base de données des arbitres
                    </div>
                </td>
                <td class="logo-right">
                    <img src="{{ public_path('images/fecofa-logo.png') }}" alt="FECOFA" class="logo">
                </td>
            </tr>
        </table>
    </header>

    <div class="pdf-footer">
        <hr style="border: 0; border-top: 0.5px solid #999; margin-bottom: 4px;">

        <table class="pdf-footer-table">
            <tr>
                <td class="pdf-footer-left">FECOFA – Gestion des arbitres</td>
                <td class="pdf-footer-right">
                    {{ __("Printed at") }} {{ $generatedAt->format('d/m/Y') }} {{ $generatedAt->format('H:i') }}
                </td>
            </tr>
        </table>
    </div>



    <main>
        <table>
            <thead>
                <tr>
                    <th class="small">#</th>
                    <th class="small text-left">Person ID</th>
                    <th class="small text-left">Nom</th>
                    <th class="small">Ligue</th>
                    <th class="small">Date de naissance</th>
                    <th class="small">Catégorie</th>
                    <th class="small">Rôle</th>
                    <th class="small">Année début</th>
                    <th class="small">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($referees as $index => $ref)
                    @php
                        $status = $ref->status ?? 'active';
                    @endphp
                    <tr>
                        {{-- # --}}
                        <td class="center small">{{ $index + 1 }}</td>

                        {{-- Person ID --}}
                        <td class="small">{{ $ref->person_id }}</td>

                        {{-- Nom complet --}}
                        <td class="small">
                            {{ mb_strtoupper($ref->last_name) }}
                            {{ ' ' }}
                            {{ ucfirst(strtolower($ref->first_name)) }}
                        </td>

                        {{-- Ligue --}}
                        <td class="center small">
                            {{ $ref->league?->code ?? '' }}
                        </td>

                        {{-- Date de naissance --}}
                        <td class="center small">
                            {{ optional($ref->date_of_birth)->format('d.m.Y') }}
                        </td>

                        {{-- Catégorie --}}
                        <td class="center small">
                            {{ ucfirst($ref->category ?? '') }}
                        </td>

                        {{-- Rôle --}}
                        <td class="center small">
                            {{ $ref->refereeRole?->name ?? '' }}
                        </td>

                        {{-- Année début --}}
                        <td class="center small">
                            {{ $ref->start_year ?? '' }}
                        </td>

                        {{-- Status --}}
                        <td class="center small">
                            {{ ($status === 'active' || $status === 1) ? 'Active' : 'Inactive' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </main>
</body>

</html>