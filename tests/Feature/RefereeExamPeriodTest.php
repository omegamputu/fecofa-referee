<?php

use App\Models\Referees\RefereeExamPeriod;
use App\Models\User;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Permission;

function examinationPeriodAdministrator(): User
{
    $user = User::factory()->create();
    Permission::findOrCreate('admin_access', 'web');
    $user->givePermissionTo('admin_access');

    return $user;
}

test('an administrator can schedule a pre-season examination period', function () {
    $user = examinationPeriodAdministrator();
    $this->actingAs($user);

    Volt::test('admin.exam-periods.index')
        ->set('seasonYear', 2026)
        ->set('opensAt', today()->addWeek()->format('Y-m-d'))
        ->set('closesAt', today()->addWeeks(2)->format('Y-m-d'))
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas(RefereeExamPeriod::class, [
        'season_year' => 2026,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $period = RefereeExamPeriod::query()->sole();
    expect($period->opens_at->isSameDay(today()->addWeek()))->toBeTrue()
        ->and($period->closes_at->isSameDay(today()->addWeeks(2)))->toBeTrue();
});

test('a closed examination period cannot be rescheduled', function () {
    RefereeExamPeriod::create([
        'season_year' => 2026,
        'opens_at' => today()->subMonth(),
        'closes_at' => today()->subDay(),
    ]);
    $this->actingAs(examinationPeriodAdministrator());

    Volt::test('admin.exam-periods.index')
        ->set('seasonYear', 2026)
        ->set('opensAt', today()->format('Y-m-d'))
        ->set('closesAt', today()->addWeek()->format('Y-m-d'))
        ->call('save')
        ->assertHasErrors('seasonYear');

    expect(RefereeExamPeriod::query()->first()->closes_at->isYesterday())->toBeTrue();
});

test('a user without administration permission cannot schedule periods', function () {
    $this->actingAs(User::factory()->create());

    Volt::test('admin.exam-periods.index')->assertForbidden();
});
