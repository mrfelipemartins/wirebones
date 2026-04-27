<?php

use MrFelipeMartins\Wirebones\Support\Names;

it('generates default names from livewire namespace prefixes', function (): void {
    expect(Names::defaultWireboneName(
        'App\\Livewire\\Dashboard\\RevenueCard',
        ['App\\Livewire\\'],
    ))->toBe('dashboard.revenue-card');
});

it('sanitizes names for filesystem paths', function (string $name, string $expected): void {
    expect(Names::safeFileName($name))->toBe($expected);
})->with([
    ['reports.revenue-card', 'reports.revenue-card'],
    ['../outside', 'outside'],
    ['admin/users table', 'admin_users_table'],
    ['...', 'wirebone'],
    ['////', 'wirebone'],
]);
