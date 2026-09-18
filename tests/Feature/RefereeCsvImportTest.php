<?php

use App\Models\League;
use App\Models\Referees\Referee;
use App\Models\Referees\RefereeCategory;
use App\Models\Referees\RefereeRole;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->league = League::create([
        'name' => 'Ligue test',
        'slug' => 'ligue-test',
        'code' => 'TEST',
        'province' => 'Kinshasa',
    ]);
    $this->category = RefereeCategory::create([
        'name' => 'Élite',
        'slug' => 'elite',
    ]);
    $this->centralRole = RefereeRole::create([
        'name' => 'Arbitre central',
        'slug' => RefereeRole::CENTRAL_SLUG,
    ]);
    $this->assistantRole = RefereeRole::create([
        'name' => 'Arbitre assistant',
        'slug' => RefereeRole::ASSISTANT_SLUG,
    ]);

    Permission::findOrCreate('view_referee');
    Permission::findOrCreate('import_referee_data');
});

test('an authorized user can import referees from a semicolon CSV file', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['view_referee', 'import_referee_data']);
    Referee::create([
        'league_id' => $this->league->id,
        'referee_category_id' => $this->category->id,
        'referee_role_id' => $this->centralRole->id,
        'person_id' => 'TEST-000004',
        'last_name' => 'EXISTANT',
        'first_name' => 'Arbitre',
        'gender' => 'male',
    ]);
    $csv = "\xEF\xBB\xBF".implode("\n", [
        'nom;prenoms;sexe;date_naissance;telephone;email;adresse;niveau_etudes;profession;annee_debut;code_ligue;categorie;fonction;matricule;actif;liste_fifa',
        'NDALA;Jean;M;12/03/1990;+243810000001;jean@example.test;Kinshasa;L2;Enseignant;2010;test;Elite;Arbitre central;;oui;non',
        'MUKENDI;Aline;female;1992-04-10;+243810000002;aline@example.test;G3;G3;Juriste;2012;TEST;élite;arbitre-assistant;CUSTOM-A;1;yes',
    ]);

    $this->actingAs($user);

    $component = Volt::test('referees.index')
        ->set('csvFile', UploadedFile::fake()->createWithContent('arbitres.csv', $csv))
        ->call('importCsv')
        ->assertHasNoErrors();

    expect($component->get('csvImportSummary'))->toBe(['imported' => 2, 'rejected' => 0]);
    expect($component->get('csvImportErrors'))->toBe([]);

    $this->assertDatabaseHas(Referee::class, [
        'person_id' => 'TEST-000005',
        'last_name' => 'NDALA',
        'first_name' => 'Jean',
        'gender' => 'male',
        'start_year' => 2010,
        'is_active' => true,
        'is_fifa_listed' => false,
        'has_medical_clearance' => false,
        'has_physical_clearance' => false,
    ]);
    expect(Referee::where('last_name', 'NDALA')->firstOrFail()->date_of_birth->toDateString())
        ->toBe('1990-03-12');
    $this->assertDatabaseHas(Referee::class, [
        'person_id' => 'CUSTOM-A',
        'last_name' => 'MUKENDI',
        'referee_role_id' => $this->assistantRole->id,
        'is_fifa_listed' => true,
    ]);
});

test('valid CSV rows are imported while invalid rows are reported', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['view_referee', 'import_referee_data']);
    $csv = implode("\n", [
        'nom,prenoms,sexe,code_ligue,categorie,fonction',
        'KABONGO,Paul,homme,TEST,Elite,Arbitre central',
        'ILUNGA,Marie,femme,TEST,Categorie inconnue,Arbitre assistant',
    ]);

    $this->actingAs($user);

    $component = Volt::test('referees.index')
        ->set('csvFile', UploadedFile::fake()->createWithContent('arbitres.csv', $csv))
        ->call('importCsv')
        ->assertHasNoErrors();

    expect($component->get('csvImportSummary'))->toBe(['imported' => 1, 'rejected' => 1]);
    expect($component->get('csvImportErrors'))->toHaveCount(1);
    expect($component->get('csvImportErrors')[0])->toContain('Categorie inconnue');
    $this->assertDatabaseHas(Referee::class, ['last_name' => 'KABONGO']);
    $this->assertDatabaseMissing(Referee::class, ['last_name' => 'ILUNGA']);
});

test('a user without import permission cannot import a CSV file', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('view_referee');
    $csv = "nom,prenoms,sexe,code_ligue,categorie,fonction\nKABONGO,Paul,male,TEST,Elite,Arbitre central";

    $this->actingAs($user);

    Volt::test('referees.index')
        ->set('csvFile', UploadedFile::fake()->createWithContent('arbitres.csv', $csv))
        ->call('importCsv')
        ->assertForbidden();

    $this->assertDatabaseMissing(Referee::class, ['last_name' => 'KABONGO']);
});

test('the CSV template download requires import permission', function () {
    $authorized = User::factory()->create();
    $authorized->givePermissionTo('import_referee_data');

    $response = $this->actingAs($authorized)
        ->get(route('referees.import.template'))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');

    expect($response->streamedContent())->toContain('nom;prenoms;sexe');

    $unauthorized = User::factory()->create();

    $this->actingAs($unauthorized)
        ->get(route('referees.import.template'))
        ->assertForbidden();
});
