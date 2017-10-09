<?php

namespace Drupal\Tests\facets_rest\Functional;

use Drupal\Tests\facets\Functional\FacetsTestBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tests the integration of REST-views and facets.
 *
 * @group facets
 */
class RestIntegrationTest extends FacetsTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'rest_view',
    'facets_rest',
    'rest',
    'hal',
    'serialization',
    'views_ui',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->drupalLogin($this->adminUser);
    $this->setUpExampleStructure();
    $this->insertExampleContent();
    $this->assertEquals(5, $this->indexItems($this->indexId), '5 items were indexed.');
  }

  /**
   * {@inheritdoc}
   */
  protected function installModulesFromClassProperty(ContainerInterface $container) {
    // This will just set the Drupal state to include the necessary bundles for
    // our test entity type. Otherwise, fields from those bundles won't be found
    // and thus removed from the test index. (We can't do it in setUp(), before
    // calling the parent method, since the container isn't set up at that
    // point.)
    $bundles = [
      'entity_test_mulrev_changed' => ['label' => 'Entity Test Bundle'],
      'item' => ['label' => 'item'],
      'article' => ['label' => 'article'],
    ];
    \Drupal::state()->set('entity_test_mulrev_changed.bundles', $bundles);

    parent::installModulesFromClassProperty($container);
  }

  /**
   * Tests that the facet results are correct.
   */
  public function testRestResults() {
    global $base_url;

    $name = 'Type';
    $id = 'type';

    // Add a new facet to filter by content type.
    $this->createFacet($name, $id, 'type', 'rest_export_1', 'views_rest__search_api_rest_test_view');

    // Use the array widget.
    $facet_edit_page = '/admin/config/search/facets/' . $id . '/edit';
    $this->drupalGet($facet_edit_page);
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalPostForm(NULL, ['widget' => 'array'], 'Configure widget');
    $values['widget'] = 'array';
    $values['widget_config[show_numbers]'] = TRUE;
    $values['facet_sorting[count_widget_order][status]'] = TRUE;
    $values['facet_sorting[count_widget_order][settings][sort]'] = 'ASC';
    $values['facet_sorting[display_value_widget_order][status]'] = FALSE;
    $values['facet_sorting[active_widget_order][status]'] = FALSE;
    $values['facet_settings[query_operator]'] = 'or';
    $values['facet_settings[only_visible_when_facet_source_is_visible]'] = TRUE;

    $this->drupalPostForm(NULL, $values, 'Save');

    drupal_flush_all_caches();

    $name = 'Keywords';
    $id = 'keywords';
    // Add a new facet to filter by keywords.
    $this->createFacet($name, $id, 'keywords', 'rest_export_1', 'views_rest__search_api_rest_test_view');

    // Use the array widget.
    $facet_edit_page = '/admin/config/search/facets/' . $id . '/edit';
    $this->drupalGet($facet_edit_page);
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalPostForm(NULL, ['widget' => 'array'], 'Configure widget');
    $values['widget'] = 'array';
    $values['widget_config[show_numbers]'] = TRUE;
    $values['facet_sorting[count_widget_order][status]'] = TRUE;
    $values['facet_sorting[count_widget_order][settings][sort]'] = 'ASC';
    $values['facet_sorting[display_value_widget_order][status]'] = FALSE;
    $values['facet_sorting[active_widget_order][status]'] = FALSE;
    $values['facet_settings[query_operator]'] = 'or';
    $values['facet_settings[only_visible_when_facet_source_is_visible]'] = TRUE;

    $this->drupalPostForm(NULL, $values, 'Save');

    // Get the output from the rest view and decode it into an array.
    $json = $this->drupalGet('facets-rest');
    $json_decoded = json_decode($json);

    $this->assertEquals(5, count($json_decoded->search_results));

    // Verify the facet "Type".
    $results = [
      'article' => [
        'url' => $base_url . '/facets-rest?f%5B0%5D=type%3Aarticle',
        'count' => 2,
      ],
      'item' => [
        'url' => $base_url . '/facets-rest?f%5B0%5D=type%3Aitem',
        'count' => 3,
      ],
    ];

    foreach ($json_decoded->facets[1][0]->type as $result) {
      $value = $result->values->value;
      $this->assertEquals($result->url, $results[$value]['url']);
      $this->assertEquals($result->values->count, $results[$value]['count']);
    }

    // Verify the facet "Keywords".
    $results = [
      'banana' => [
        'url' => $base_url . '/facets-rest?f%5B0%5D=keywords%3Abanana',
        'count' => 1,
      ],
      'strawberry' => [
        'url' => $base_url . '/facets-rest?f%5B0%5D=keywords%3Astrawberry',
        'count' => 2,
      ],
      'apple' => [
        'url' => $base_url . '/facets-rest?f%5B0%5D=keywords%3Aapple',
        'count' => 2,
      ],
      'orange' => [
        'url' => $base_url . '/facets-rest?f%5B0%5D=keywords%3Aorange',
        'count' => 3,
      ],
      'grape' => [
        'url' => $base_url . '/facets-rest?f%5B0%5D=keywords%3Agrape',
        'count' => 3,
      ],
    ];

    foreach ($json_decoded->facets[0][0]->keywords as $result) {
      $value = $result->values->value;
      $this->assertEquals($result->url, $results[$value]['url']);
      $this->assertEquals($result->values->count, $results[$value]['count']);
    }

    // Filter and verify that the results are correct.
    $json = $this->drupalGet($base_url . '/facets-rest?f%5B0%5D=type%3Aitem');
    $json_decoded = json_decode($json);

    $this->assertEquals(3, count($json_decoded->search_results));

    $results = [
      'article' => [
        'url' => $base_url . '/facets-rest?f%5B0%5D=type%3Aitem&f%5B1%5D=type%3Aarticle',
        'count' => 2,
      ],
      'item' => [
        'url' => $base_url . '/facets-rest',
        'count' => 3,
      ],
      'banana' => [
        'url' => $base_url . '/facets-rest?f%5B0%5D=type%3Aitem&f%5B1%5D=keywords%3Abanana',
        'count' => 0,
      ],
      'strawberry' => [
        'url' => $base_url . '/facets-rest?f%5B0%5D=type%3Aitem&f%5B1%5D=keywords%3Astrawberry',
        'count' => 0,
      ],
      'apple' => [
        'url' => $base_url . '/facets-rest?f%5B0%5D=type%3Aitem&f%5B1%5D=keywords%3Aapple',
        'count' => 1,
      ],
      'orange' => [
        'url' => $base_url . '/facets-rest?f%5B0%5D=type%3Aitem&f%5B1%5D=keywords%3Aorange',
        'count' => 2,
      ],
      'grape' => [
        'url' => $base_url . '/facets-rest?f%5B0%5D=type%3Aitem&f%5B1%5D=keywords%3Agrape',
        'count' => 1,
      ],
    ];

    foreach ($json_decoded->facets[1][0]->type as $result) {
      $value = $result->values->value;
      $this->assertEquals($result->url, $results[$value]['url']);
      $this->assertEquals($result->values->count, $results[$value]['count']);
    }

    foreach ($json_decoded->facets[0][0]->keywords as $result) {
      $value = $result->values->value;
      $this->assertEquals($result->url, $results[$value]['url']);
      $this->assertEquals($result->values->count, $results[$value]['count']);
    }

  }

  /**
   * Tests that the system raises an error when selecting the wrong widget.
   */
  public function testWidgetSelection() {
    $id = 'type';

    // Add a new facet to filter by content type.
    $this->createFacet('Type', $id, 'type', 'rest_export_1', 'views_rest__search_api_rest_test_view');

    // Use the array widget.
    $facet_edit_page = '/admin/config/search/facets/' . $id . '/edit';
    $this->drupalGet($facet_edit_page);
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalPostForm(NULL, ['widget' => 'checkbox'], 'Configure widget');
    $this->assertSession()->pageTextContains('The Facet source is a Rest export. Please select a raw widget.');

    $this->drupalPostForm(NULL, ['widget' => 'array'], 'Configure widget');
    $this->assertSession()->pageTextNotContains('The Facet source is a Rest export. Please select a raw widget.');
  }

}
