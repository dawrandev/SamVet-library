<?php

use App\Models\DepartmentCoverage;

beforeEach(fn () => actingAsAdmin());

it('lists department coverages on the index page', function () {
    DepartmentCoverage::factory()->create(['name' => ['uz' => 'Zooinjeneriya kafedrasi', 'ru' => 'x', 'kk' => 'x'], 'percentage' => 42]);

    $this->get(route('admin.lookups.department-coverages.index'))
        ->assertOk()
        ->assertSee('Zooinjeneriya kafedrasi')
        ->assertSee('42%', false);
});

it('creates a department coverage with a translated name and a percentage', function () {
    $this->post(route('admin.lookups.department-coverages.store'), [
        'name' => ['uz' => 'Yangi kafedra', 'ru' => 'Новая кафедра', 'kk' => 'Jaña kafedra'],
        'percentage' => 65,
    ])->assertRedirect(route('admin.lookups.department-coverages.index'));

    $department = DepartmentCoverage::firstWhere('name->uz', 'Yangi kafedra');
    expect($department)->not->toBeNull()
        ->and($department->percentage)->toBe(65)
        ->and($department->getTranslation('name', 'ru'))->toBe('Новая кафедра');
});

it('requires all 3 language names and a percentage between 0 and 100', function () {
    $this->from(route('admin.lookups.department-coverages.index'))
        ->post(route('admin.lookups.department-coverages.store'), [
            'name' => ['uz' => 'Faqat uz'],
            'percentage' => 150,
        ])
        ->assertSessionHasErrors(['name.ru', 'name.kk', 'percentage']);
});

it('updates a department coverage\'s percentage', function () {
    $department = DepartmentCoverage::factory()->create(['percentage' => 10]);

    $this->put(route('admin.lookups.department-coverages.update', $department), [
        'name' => $department->getTranslations('name'),
        'percentage' => 88,
    ])->assertRedirect(route('admin.lookups.department-coverages.index'));

    expect($department->fresh()->percentage)->toBe(88);
});

it('deletes a department coverage', function () {
    $department = DepartmentCoverage::factory()->create();

    $this->delete(route('admin.lookups.department-coverages.destroy', $department))
        ->assertRedirect(route('admin.lookups.department-coverages.index'));

    expect(DepartmentCoverage::find($department->id))->toBeNull();
});

it('blocks guests', function () {
    auth()->logout();

    $this->get(route('admin.lookups.department-coverages.index'))->assertRedirect(route('login'));
});
