<?php

namespace Tests\Unit\Ai;

use App\Services\Ai\SearchIntent;
use Tests\TestCase;

class SearchIntentTest extends TestCase
{
    public function test_an_intent_becomes_classic_search_parameters(): void
    {
        $intent = new SearchIntent(
            categoryId: 7,
            categoryName: 'Plomberie',
            city: 'Douala',
            quarter: 'Bonamoussadi',
            keywords: 'fuite évier',
            urgent: true,
        );

        $this->assertSame([
            'category_id' => '7',
            'city' => 'Douala',
            'quarter' => 'Bonamoussadi',
            'term' => 'fuite évier',
            'available_only' => '1',
        ], $intent->toQueryParameters());
    }

    public function test_empty_fields_are_left_out_of_the_url(): void
    {
        $intent = new SearchIntent(categoryId: 3, categoryName: 'Ménage');

        $this->assertSame(['category_id' => '3'], $intent->toQueryParameters());
    }

    public function test_an_intent_that_understood_nothing_is_reported_as_empty(): void
    {
        $this->assertTrue((new SearchIntent)->isEmpty());
        $this->assertTrue((new SearchIntent(keywords: ''))->isEmpty());
        $this->assertFalse((new SearchIntent(urgent: true))->isEmpty());
        $this->assertFalse((new SearchIntent(quarter: 'Akwa'))->isEmpty());
    }

    public function test_the_summary_lists_only_what_was_understood(): void
    {
        $intent = new SearchIntent(
            categoryId: 7,
            categoryName: 'Plomberie',
            city: 'Douala',
            urgent: true,
        );

        $this->assertSame(
            ['Plomberie', 'Douala', __('ui.search.understood_urgent')],
            $intent->summary(),
        );
    }

    public function test_a_category_that_could_not_be_matched_is_not_advertised(): void
    {
        // categoryName sans categoryId ne doit jamais arriver depuis
        // l'extracteur, mais l'objet ne doit pas mentir pour autant.
        $intent = new SearchIntent(categoryId: null, categoryName: null, city: 'Kribi');

        $this->assertSame(['Kribi'], $intent->summary());
        $this->assertSame(['city' => 'Kribi'], $intent->toQueryParameters());
    }
}
