<?php

namespace Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class DashboardPaginationTest extends TestCase
{
    public function test_pagination_helper_renders_navigation_links(): void
    {
        $items = collect(range(1, 25));

        $paginator = new LengthAwarePaginator(
            $items->forPage(1, 10),
            $items->count(),
            10,
            1,
            ['path' => '/']
        );

        $html = get_paginate($paginator);

        $this->assertNotEmpty($html);
        $this->assertStringContainsString('Pagination Navigation', (string) $html);
    }
}
