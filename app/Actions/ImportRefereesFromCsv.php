<?php

namespace App\Actions;

use App\Models\League;
use App\Models\Referees\Referee;
use App\Models\Referees\RefereeCategory;
use App\Models\Referees\RefereeRole;
use DateTimeImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ImportRefereesFromCsv
{
    public const MAX_ROWS = 1000;

    public const MAX_REPORTED_ERRORS = 50;

    /** @var array<int, string> */
    public const TEMPLATE_HEADERS = [
        'nom', 'prenoms', 'sexe', 'date_naissance', 'telephone', 'email',
        'adresse', 'niveau_etudes', 'profession', 'annee_debut', 'code_ligue',
        'categorie', 'fonction', 'matricule', 'actif', 'liste_fifa',
    ];

    /** @var array<string, string> */
    private const HEADER_ALIASES = [
        'nom' => 'last_name',
        'nom_de_famille' => 'last_name',
        'last_name' => 'last_name',
        'prenom' => 'first_name',
        'prenoms' => 'first_name',
        'first_name' => 'first_name',
        'sexe' => 'gender',
        'gender' => 'gender',
        'date_naissance' => 'date_of_birth',
        'date_de_naissance' => 'date_of_birth',
        'date_of_birth' => 'date_of_birth',
        'telephone' => 'phone',
        'phone' => 'phone',
        'email' => 'email',
        'e_mail' => 'email',
        'courriel' => 'email',
        'adresse' => 'address',
        'address' => 'address',
        'niveau_etudes' => 'education_level',
        'niveau_d_etudes' => 'education_level',
        'education_level' => 'education_level',
        'profession' => 'profession',
        'annee_debut' => 'start_year',
        'annee_debut_arbitrage' => 'start_year',
        'start_year' => 'start_year',
        'ligue' => 'league_code',
        'code_ligue' => 'league_code',
        'league_code' => 'league_code',
        'categorie' => 'category',
        'category' => 'category',
        'fonction' => 'role',
        'role' => 'role',
        'function' => 'role',
        'matricule' => 'person_id',
        'person_id' => 'person_id',
        'actif' => 'is_active',
        'statut' => 'is_active',
        'is_active' => 'is_active',
        'liste_fifa' => 'is_fifa_listed',
        'fifa' => 'is_fifa_listed',
        'is_fifa_listed' => 'is_fifa_listed',
    ];

    /** @var array<int, string> */
    private const REQUIRED_HEADERS = [
        'last_name', 'first_name', 'gender', 'league_code', 'category', 'role',
    ];

    /**
     * @return array{imported: int, rejected: int, errors: array<int, string>}
     */
    public function handle(UploadedFile $file): array
    {
        try {
            $rows = $this->readRows($file);
        } catch (RuntimeException $exception) {
            return ['imported' => 0, 'rejected' => 0, 'errors' => [$exception->getMessage()]];
        }

        if ($rows === []) {
            return [
                'imported' => 0,
                'rejected' => 0,
                'errors' => [__('The CSV file does not contain any referee.')],
            ];
        }

        $providedIds = collect($rows)
            ->map(fn (array $row): ?string => $this->normalizePersonId($row['values']['person_id'] ?? null))
            ->filter()
            ->values();
        $idCounts = $providedIds->map(fn (string $id): string => Str::upper($id))->countBy()->all();
        $existingIds = Referee::query()
            ->whereIn('person_id', $providedIds->all())
            ->pluck('person_id')
            ->mapWithKeys(fn (string $id): array => [Str::upper($id) => true])
            ->all();
        $lookups = $this->referenceLookups();
        $validRows = [];
        $errors = [];
        $rejected = 0;

        foreach ($rows as $row) {
            [$data, $rowErrors] = $this->validateRow($row, $lookups, $idCounts, $existingIds);

            if ($rowErrors !== []) {
                $rejected++;
                $this->addError($errors, __('Line :line: :message', [
                    'line' => $row['line'],
                    'message' => implode(' ', $rowErrors),
                ]));
            } else {
                $validRows[] = $data;
            }
        }

        if ($validRows === []) {
            return ['imported' => 0, 'rejected' => $rejected, 'errors' => $errors];
        }

        try {
            $imported = DB::transaction(fn (): int => $this->createReferees($validRows));
        } catch (Throwable $exception) {
            report($exception);
            $this->addError($errors, __('The CSV import could not be completed. No row was saved.'));

            return ['imported' => 0, 'rejected' => count($rows), 'errors' => $errors];
        }

        return ['imported' => $imported, 'rejected' => $rejected, 'errors' => $errors];
    }

    /**
     * @return array<int, array{line: int, values: array<string, string|null>, valid_columns: bool}>
     */
    private function readRows(UploadedFile $file): array
    {
        $contents = file_get_contents($file->getRealPath());

        if ($contents === false || trim($contents) === '') {
            throw new RuntimeException(__('The CSV file is empty.'));
        }

        if (str_starts_with($contents, "\xEF\xBB\xBF")) {
            $contents = substr($contents, 3);
        } elseif (str_starts_with($contents, "\xFF\xFE")) {
            $contents = mb_convert_encoding(substr($contents, 2), 'UTF-8', 'UTF-16LE');
        } elseif (str_starts_with($contents, "\xFE\xFF")) {
            $contents = mb_convert_encoding(substr($contents, 2), 'UTF-8', 'UTF-16BE');
        }

        if (! mb_check_encoding($contents, 'UTF-8')) {
            $contents = mb_convert_encoding($contents, 'UTF-8', 'Windows-1252');
        }

        $contents = str_replace("\0", '', $contents);
        $contents = preg_replace('/^(?:\r\n|\r|\n)+/', '', $contents) ?? $contents;
        $delimiter = $this->detectDelimiter(strtok($contents, "\r\n") ?: '');
        $stream = fopen('php://temp', 'r+');

        if ($stream === false) {
            throw new RuntimeException(__('The CSV file could not be read.'));
        }

        fwrite($stream, $contents);
        rewind($stream);
        $rawHeaders = fgetcsv($stream, null, $delimiter, '"', '');

        if (! is_array($rawHeaders)) {
            fclose($stream);
            throw new RuntimeException(__('The CSV header is invalid or missing.'));
        }

        $headers = [];
        $mappedHeaders = [];

        foreach ($rawHeaders as $rawHeader) {
            $header = self::HEADER_ALIASES[$this->normalizeHeader((string) $rawHeader)] ?? null;

            if ($header !== null && in_array($header, $mappedHeaders, true)) {
                fclose($stream);
                throw new RuntimeException(__('The CSV file contains duplicate columns.'));
            }

            $headers[] = $header;

            if ($header !== null) {
                $mappedHeaders[] = $header;
            }
        }

        $missing = array_diff(self::REQUIRED_HEADERS, $mappedHeaders);

        if ($missing !== []) {
            fclose($stream);
            throw new RuntimeException(__('Missing required CSV columns: :columns.', [
                'columns' => implode(', ', array_map($this->fieldLabel(...), $missing)),
            ]));
        }

        $rows = [];
        $line = 1;

        while (($rawValues = fgetcsv($stream, null, $delimiter, '"', '')) !== false) {
            $line++;

            if (collect($rawValues)->every(fn (?string $value): bool => blank($value))) {
                continue;
            }

            if (count($rows) >= self::MAX_ROWS) {
                fclose($stream);
                throw new RuntimeException(__('The CSV file cannot contain more than :max referees.', [
                    'max' => self::MAX_ROWS,
                ]));
            }

            $values = [];

            foreach ($headers as $index => $header) {
                if ($header !== null) {
                    $value = isset($rawValues[$index]) ? trim((string) $rawValues[$index]) : '';
                    $values[$header] = $value === '' ? null : $value;
                }
            }

            $rows[] = [
                'line' => $line,
                'values' => $values,
                'valid_columns' => count($rawValues) <= count($headers),
            ];
        }

        fclose($stream);

        return $rows;
    }

    private function detectDelimiter(string $firstLine): string
    {
        $scores = [];

        foreach ([';', ',', "\t"] as $delimiter) {
            $scores[$delimiter] = count(str_getcsv($firstLine, $delimiter, '"', ''));
        }

        arsort($scores);

        return (string) array_key_first($scores);
    }

    /**
     * @param  array{line: int, values: array<string, string|null>, valid_columns: bool}  $row
     * @param  array{leagues: array<string, League>, categories: array<string, RefereeCategory>, roles: array<string, RefereeRole>}  $lookups
     * @param  array<string, int>  $idCounts
     * @param  array<string, bool>  $existingIds
     * @return array{0: array<string, mixed>, 1: array<int, string>}
     */
    private function validateRow(array $row, array $lookups, array $idCounts, array $existingIds): array
    {
        $values = $row['values'];
        $errors = [];

        if (! $row['valid_columns']) {
            $errors[] = __('The row contains more values than the CSV header.');
        }

        $missing = collect(self::REQUIRED_HEADERS)
            ->filter(fn (string $field): bool => blank($values[$field] ?? null))
            ->map($this->fieldLabel(...))
            ->all();

        if ($missing !== []) {
            $errors[] = __('Required values are missing: :fields.', ['fields' => implode(', ', $missing)]);
        }

        foreach (['last_name', 'first_name', 'phone', 'email', 'address', 'education_level', 'profession', 'person_id'] as $field) {
            if (mb_strlen((string) ($values[$field] ?? '')) > 255) {
                $errors[] = __('The value for :field is too long.', ['field' => $this->fieldLabel($field)]);
            }
        }

        $league = $lookups['leagues'][$this->lookupKey($values['league_code'] ?? '')] ?? null;
        $category = $lookups['categories'][$this->lookupKey($values['category'] ?? '')] ?? null;
        $role = $lookups['roles'][$this->lookupKey($values['role'] ?? '')] ?? null;

        if (filled($values['league_code'] ?? null) && $league === null) {
            $errors[] = __('Unknown league code ":value".', ['value' => $values['league_code']]);
        }

        if (filled($values['category'] ?? null) && $category === null) {
            $errors[] = __('Unknown referee category ":value".', ['value' => $values['category']]);
        }

        if (filled($values['role'] ?? null) && $role === null) {
            $errors[] = __('Unknown referee function ":value".', ['value' => $values['role']]);
        }

        $gender = $this->normalizeGender($values['gender'] ?? null);

        if (filled($values['gender'] ?? null) && $gender === null) {
            $errors[] = __('Invalid gender ":value".', ['value' => $values['gender']]);
        }

        $dateOfBirth = $this->normalizeDate($values['date_of_birth'] ?? null);

        if (filled($values['date_of_birth'] ?? null) && $dateOfBirth === null) {
            $errors[] = __('Invalid date ":value". Use YYYY-MM-DD or DD/MM/YYYY.', [
                'value' => $values['date_of_birth'],
            ]);
        }

        $startYear = $this->normalizeYear($values['start_year'] ?? null);

        if (filled($values['start_year'] ?? null) && $startYear === null) {
            $errors[] = __('Invalid start year ":value".', ['value' => $values['start_year']]);
        }

        if (filled($values['email'] ?? null) && filter_var($values['email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = __('Invalid email address ":value".', ['value' => $values['email']]);
        }

        [$activeIsValid, $isActive] = $this->normalizeBoolean($values['is_active'] ?? null, true);
        [$fifaIsValid, $isFifaListed] = $this->normalizeBoolean($values['is_fifa_listed'] ?? null, false);

        if (! $activeIsValid) {
            $errors[] = __('Invalid active status ":value".', ['value' => $values['is_active']]);
        }

        if (! $fifaIsValid) {
            $errors[] = __('Invalid FIFA status ":value".', ['value' => $values['is_fifa_listed']]);
        }

        $personId = $this->normalizePersonId($values['person_id'] ?? null);

        if ($personId !== null) {
            $idKey = Str::upper($personId);

            if (($idCounts[$idKey] ?? 0) > 1) {
                $errors[] = __('Person ID ":value" is duplicated in the CSV file.', ['value' => $personId]);
            } elseif (isset($existingIds[$idKey])) {
                $errors[] = __('Person ID ":value" already exists.', ['value' => $personId]);
            }
        }

        return [[
            'league_id' => $league?->id,
            'referee_category_id' => $category?->id,
            'referee_role_id' => $role?->id,
            'person_id' => $personId,
            'last_name' => $values['last_name'] ?? null,
            'first_name' => $values['first_name'] ?? null,
            'date_of_birth' => $dateOfBirth,
            'gender' => $gender,
            'phone' => $values['phone'] ?? null,
            'email' => $values['email'] ?? null,
            'address' => $values['address'] ?? null,
            'education_level' => $values['education_level'] ?? null,
            'profession' => $values['profession'] ?? null,
            'start_year' => $startYear,
            'is_active' => $isActive,
            'is_fifa_listed' => $isFifaListed,
        ], $errors];
    }

    /** @param array<int, array<string, mixed>> $rows */
    private function createReferees(array $rows): int
    {
        $leagueIds = collect($rows)->pluck('league_id')->unique()->sort()->values();
        $lockedLeagues = League::query()
            ->whereKey($leagueIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
        $existing = Referee::query()
            ->whereIn('league_id', $leagueIds)
            ->whereNotNull('person_id')
            ->get(['league_id', 'person_id']);
        $reservedIds = $existing->pluck('person_id')
            ->mapWithKeys(fn (string $id): array => [Str::upper($id) => true])
            ->all();
        $nextNumbers = [];

        foreach ($leagueIds as $leagueId) {
            $nextNumbers[$leagueId] = $existing
                ->where('league_id', $leagueId)
                ->pluck('person_id')
                ->reduce(fn (int $max, string $id): int => $this->maximumSuffix($max, $id), 0);
        }

        foreach ($rows as $row) {
            if ($row['person_id'] !== null) {
                $reservedIds[Str::upper($row['person_id'])] = true;
                $nextNumbers[$row['league_id']] = $this->maximumSuffix(
                    $nextNumbers[$row['league_id']],
                    $row['person_id'],
                );
            }
        }

        foreach ($rows as $row) {
            if ($row['person_id'] === null) {
                $league = $lockedLeagues->get($row['league_id']);

                do {
                    $nextNumbers[$row['league_id']]++;
                    $row['person_id'] = sprintf('%s-%06d', $league->code, $nextNumbers[$row['league_id']]);
                } while (isset($reservedIds[Str::upper($row['person_id'])]));
            }

            $reservedIds[Str::upper($row['person_id'])] = true;
            Referee::create($row);
        }

        return count($rows);
    }

    private function maximumSuffix(int $maximum, string $personId): int
    {
        return preg_match('/-(\d+)$/', $personId, $matches)
            ? max($maximum, (int) $matches[1])
            : $maximum;
    }

    /**
     * @return array{
     *     leagues: array<string, League>,
     *     categories: array<string, RefereeCategory>,
     *     roles: array<string, RefereeRole>
     * }
     */
    private function referenceLookups(): array
    {
        $lookups = ['leagues' => [], 'categories' => [], 'roles' => []];

        foreach (League::query()->get() as $league) {
            $lookups['leagues'][$this->lookupKey($league->code)] = $league;
        }

        foreach (RefereeCategory::query()->get() as $category) {
            $lookups['categories'][$this->lookupKey($category->name)] = $category;
            $lookups['categories'][$this->lookupKey($category->slug)] = $category;
        }

        foreach (RefereeRole::query()->get() as $role) {
            $lookups['roles'][$this->lookupKey($role->name)] = $role;
            $lookups['roles'][$this->lookupKey($role->slug)] = $role;
        }

        return $lookups;
    }

    private function lookupKey(?string $value): string
    {
        return Str::lower(preg_replace('/[^a-zA-Z0-9]+/', '', Str::ascii(trim((string) $value))) ?? '');
    }

    private function normalizeHeader(string $header): string
    {
        return Str::of(Str::ascii(trim($header)))
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->toString();
    }

    private function normalizePersonId(?string $personId): ?string
    {
        $personId = trim((string) $personId);

        return $personId === '' ? null : Str::upper($personId);
    }

    private function normalizeGender(?string $gender): ?string
    {
        return match ($this->lookupKey($gender)) {
            'm', 'male', 'masculin', 'homme' => 'male',
            'f', 'female', 'feminin', 'femme' => 'female',
            default => null,
        };
    }

    private function normalizeDate(?string $date): ?string
    {
        $date = trim((string) $date);

        if ($date === '') {
            return null;
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y'] as $format) {
            $parsed = DateTimeImmutable::createFromFormat('!'.$format, $date);

            if ($parsed !== false && $parsed->format($format) === $date) {
                return $parsed->format('Y-m-d');
            }
        }

        return null;
    }

    private function normalizeYear(?string $year): ?int
    {
        $year = trim((string) $year);

        if ($year === '' || ! ctype_digit($year)) {
            return null;
        }

        $year = (int) $year;

        return $year >= 1980 && $year <= (int) date('Y') ? $year : null;
    }

    /** @return array{0: bool, 1: bool} */
    private function normalizeBoolean(?string $value, bool $default): array
    {
        if (blank($value)) {
            return [true, $default];
        }

        return match ($this->lookupKey($value)) {
            '1', 'true', 'yes', 'oui', 'actif', 'active' => [true, true],
            '0', 'false', 'no', 'non', 'inactif', 'inactive' => [true, false],
            default => [false, $default],
        };
    }

    private function fieldLabel(string $field): string
    {
        return __([
            'last_name' => 'Last name',
            'first_name' => 'First name',
            'gender' => 'Gender',
            'league_code' => 'League code',
            'category' => 'Category',
            'role' => 'Function',
            'phone' => 'Phone number',
            'email' => 'Email',
            'address' => 'Address',
            'education_level' => 'Education level',
            'profession' => 'Profession',
            'person_id' => 'Person ID',
        ][$field] ?? $field);
    }

    /** @param array<int, string> $errors */
    private function addError(array &$errors, string $error): void
    {
        if (count($errors) < self::MAX_REPORTED_ERRORS) {
            $errors[] = $error;
        }
    }
}
