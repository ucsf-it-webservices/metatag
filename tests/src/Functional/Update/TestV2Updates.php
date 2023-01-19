<?php

namespace Drupal\Tests\metatag\Functional\Update;

use Drupal\Component\Serialization\Json;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests all of the v2 updates.
 *
 * This is a complicated task as the update script needs to accommodate both
 * field changes from serialized arrays to JSON encoded arrays, and deletion of
 * various meta tag plugins and submodule(s).
 *
 * @group metatag
 */
class TestV2Updates extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    // Drupal 9.5+ uses the 9.4.0 data dump, 9.4 uses the 9.3.0 data dump.
    $core93 = static::getDrupalRoot() . '/core/modules/system/tests/fixtures/update/drupal-9.3.0.bare.standard.php.gz';
    $core94 = static::getDrupalRoot() . '/core/modules/system/tests/fixtures/update/drupal-9.4.0.bare.standard.php.gz';
    if (file_exists($core94)) {
      $this->databaseDumpFiles = [
        $core94,
      ];
    }
    else {
      $this->databaseDumpFiles = [
        $core93,
      ];
    }

    // Load the Metatag v1 data dump on top of the core data dump.
    $this->databaseDumpFiles[] = __DIR__ . '/../../../fixtures/d8_metatag_v1.php';
  }

  /**
   * {@inheritdoc}
   */
  protected function doSelectionTest() {
    parent::doSelectionTest();

    // Verify that the v2 post post-update script is present.
    $this->assertSession()->responseContains('Convert all fields to use JSON storage.');

    // Verify that the GooglePlus-removal post-update scripts are present.
    $this->assertSession()->responseContains('Remove meta tags entity values that were removed in v2.');
    $this->assertSession()->responseContains('Remove meta tags from default configurations that were removed in v2.');
    $this->assertSession()->responseContains('Uninstall submodule(s) deprecated in v2: GooglePlus.');
  }

  /**
   * Tests whether the post-update scripts works correctly.
   */
  public function testPostUpdates() {
    $expected_value = 'This is a Metatag v1 meta tag.';

    // The meta tags to test.
    $entity_tags = [
      // For #3065441.
      'google_plus_name' => "GooglePlus Name tag test value for #3065441.",
      'google_plus_publisher' => "GooglePlus Publisher tag test value for #3065441.",
      // For #2973351.
      'news_keywords' => "News Keywords tag test value for #2973351.",
      'standout' => "Standout tag test value for #2973351.",
      // For #3132065.
      'twitter_cards_data1' => 'Data1 tag test for #3132065.',
      'twitter_cards_data2' => 'Data2 tag test for #3132065.',
      'twitter_cards_dnt' => 'Do Not Track tag test for #3132065.',
      'twitter_cards_gallery_image0' => 'Gallery Image0 tag test for #3132065.',
      'twitter_cards_gallery_image1' => 'Gallery Image1 tag test for #3132065.',
      'twitter_cards_gallery_image2' => 'Gallery Image2 tag test for #3132065.',
      'twitter_cards_gallery_image3' => 'Gallery Image3 tag test for #3132065.',
      'twitter_cards_image_height' => 'Image Height tag test for #3132065.',
      'twitter_cards_image_width' => 'Image Width tag test for #3132065.',
      'twitter_cards_label1' => 'Label1 tag test for #3132065.',
      'twitter_cards_label2' => 'Label2 tag test for #3132065.',
      'twitter_cards_page_url' => 'Page URL tag test for #3132065.',
    ];
    $global_tags = [
      // For #3065441.
      'google_plus_name' => "Global GooglePlus Name test value for #3065441.",
      'google_plus_publisher' => "Global GooglePlus Publisher test value for #3065441.",
      // For #2973351.
      'news_keywords' => "Global News Keywords test value for #2973351.",
      'standout' => "Global Standout test value for #2973351.",
      // For #3132065.
      'twitter_cards_data1' => 'Global Data1 tag test for #3132065.',
      'twitter_cards_data2' => 'Global Data2 tag test for #3132065.',
      'twitter_cards_dnt' => 'Global Do Not Track tag test for #3132065.',
      'twitter_cards_gallery_image0' => 'Global Gallery Image0 tag test for #3132065.',
      'twitter_cards_gallery_image1' => 'Global Gallery Image1 tag test for #3132065.',
      'twitter_cards_gallery_image2' => 'Global Gallery Image2 tag test for #3132065.',
      'twitter_cards_gallery_image3' => 'Global Gallery Image3 tag test for #3132065.',
      'twitter_cards_image_height' => 'Global Image Height tag test for #3132065.',
      'twitter_cards_image_width' => 'Global Image Width tag test for #3132065.',
      'twitter_cards_label1' => 'Global Label1 tag test for #3132065.',
      'twitter_cards_label2' => 'Global Label2 tag test for #3132065.',
      'twitter_cards_page_url' => 'Global Page URL tag test for #3132065.',
    ];

    // Confirm the data started as a serialized array.
    $query = \Drupal::database()->select('node__field_meta_tags');
    $query->addField('node__field_meta_tags', 'field_meta_tags_value');
    $result = $query->execute();
    $records = $result->fetchAll();
    $this->assertTrue(count($records) === 1);
    $this->assertTrue(strpos($records[0]->field_meta_tags_value, 'a:') === 0);
    $data = unserialize($records[0]->field_meta_tags_value, ['allowed_classes' => FALSE]);

    // For metatag_post_update_change_fields_to_json().
    $this->assertTrue(isset($data['description']));
    $this->assertTrue($data['description'] === $expected_value);

    // For metatag_post_update_v2_remove_entity_values().
    foreach ($entity_tags as $tag_name => $tag_value) {
      $this->assertTrue(isset($data[$tag_name]));
      $this->assertEquals($data[$tag_name], $tag_value);
    }

    // For metatag_post_update_v2_remove_config_values().
    $config = $this->config('metatag.metatag_defaults.global');
    $tags = $config->get('tags');

    // Set the global configuration.
    foreach ($global_tags as $tag_name => $tag_value) {
      $tags[$tag_name] = $tag_value;
    }
    $config->set('tags', $tags);

    $config = $this->config('metatag.metatag_defaults.global');
    $tags = $config->get('tags');

    // Verify each of the global tags is present.
    foreach ($global_tags as $tag_name => $tag_value) {
      $this->assertTrue(isset($tags[$tag_name]));
      $this->assertEquals($tags[$tag_name], $tag_value);
    }

    $this->runUpdates();

    // Confirm the data was converted to JSON format.
    $query = \Drupal::database()->select('node__field_meta_tags');
    $query->addField('node__field_meta_tags', 'field_meta_tags_value');
    $result = $query->execute();
    $records = $result->fetchAll();
    $this->assertTrue(count($records) === 1);
    $this->assertTrue(strpos($records[0]->field_meta_tags_value, '{"') === 0);
    $data = Json::decode($records[0]->field_meta_tags_value);

    // For metatag_post_update_change_fields_to_json().
    $this->assertTrue(isset($data['description']));
    $this->assertTrue($data['description'] === $expected_value);

    // For metatag_post_update_v2_remove_entity_values().
    foreach ($entity_tags as $tag_name => $tag_value) {
      $this->assertTrue(!isset($data[$tag_name]));
    }

    // For metatag_post_update_v2_remove_config_values().
    $config = $this->config('metatag.metatag_defaults.global');
    $tags = $config->get('tags');

    // Verify each of the global tags is no longer present.
    foreach ($global_tags as $tag_name => $tag_value) {
      $this->assertTrue(!isset($tags[$tag_name]));
    }
  }

}
