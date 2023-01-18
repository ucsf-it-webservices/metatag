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
    $this->assertSession()->responseContains('GooglePlus removal: Remove the Publisher and Name tags from defaults.');
    $this->assertSession()->responseContains('GooglePlus removal: Remove the Publisher and Name tags from entity values.');
    $this->assertSession()->responseContains('GooglePlus removal: Uninstall the module.');
  }

  /**
   * Tests whether the post-update scripts works correctly.
   */
  public function testPostUpdates() {
    $expected_value = 'This is a Metatag v1 meta tag.';

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

    // For metatag_post_update_remove_googleplus1().
    $this->assertTrue(isset($data['publisher']));
    $this->assertEquals($data['publisher'], 'Publisher tag test for #3065441');
    $this->assertTrue(isset($data['name']));
    $this->assertEquals($data['name'], 'Name tag test for #3065441');

    // For metatag_post_update_remove_googleplus2().
    $config = $this->config('metatag.metatag_defaults.global');
    $tags = $config->get('tags');
    // Set some specific values to test with.
    $tags['publisher'] = "Global Publisher test value for #3065441.";
    $tags['name'] = "Global Name test value for #3065441.";
    $config->set('tags', $tags);

    $config = $this->config('metatag.metatag_defaults.global');
    $tags = $config->get('tags');
    $this->assertTrue(isset($tags['publisher']));
    $this->assertEquals($tags['publisher'], 'Global Publisher test value for #3065441.');
    $this->assertTrue(isset($tags['name']));
    $this->assertEquals($tags['name'], 'Global Name test value for #3065441.');

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

    // For metatag_post_update_remove_googleplus1().
    $this->assertTrue(!isset($data['publisher']));
    $this->assertTrue(!isset($data['name']));

    // For metatag_post_update_remove_googleplus2().
    $config = $this->config('metatag.metatag_defaults.global');
    $tags = $config->get('tags');
    $this->assertTrue(!isset($tags['publisher']));
    $this->assertTrue(!isset($tags['name']));
  }

}
