<?php

use Illuminate\Support\Facades\DB;

it('queries the database on the health check', function () {
    DB::enableQueryLog();

    $this->getJson('/up')->assertOk()->assertExactJson(['status' => 'up']);

    expect(array_column(DB::getQueryLog(), 'query'))->toContain('select 1');
});

it('does not start a session on the health check', function () {
    $this->get('/up')->assertOk()->assertCookieMissing(config('session.cookie'));
});

it('fails the health check when the database is unreachable', function () {
    config([
        'app.debug' => false,
        'database.connections.unreachable' => ['driver' => 'sqlite', 'database' => '/nonexistent/database.sqlite'],
        'database.default' => 'unreachable',
    ]);

    $this->getJson('/up')->assertServerError()->assertExactJson(['status' => 'down']);

    config(['database.default' => 'sqlite']);
});
