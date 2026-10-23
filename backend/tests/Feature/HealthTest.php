<?php

it('reports the api as healthy', function () {
    $this->getJson('/api/health')
        ->assertOk()
        ->assertExactJson(['status' => 'ok']);
});
