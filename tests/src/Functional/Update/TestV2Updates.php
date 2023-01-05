<?php

namespace Drupal\Tests\metatag\Functional\Update;

use Drupal\Component\Serialization\Json;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\node\Entity\Node;

/**
 * Tests all of the v2 updates.
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

    // Verify that the expected post-update script(s) are available.
    $this->assertSession()->responseContains('Convert all fields to use JSON storage.');
  }


  /**
   * Tests whether search_api_db_update_8102() works correctly.
   *
   * @see https://www.drupal.org/node/2884451
   */
  public function testPostUpdate() {
    $expected_value = 'This is a Metatag v1 meta tag.';

    // Confirm the data started as a serialized array.
    $query = \Drupal::database()->select('node__field_meta_tags');
    $query->addField('node__field_meta_tags', 'field_meta_tags_value');
    $result = $query->execute();
    $records = $result->fetchAll();
    $this->assertTrue(count($records) === 1);
    $this->assertTrue(strpos($records[0]->field_meta_tags_value, 'a:') === 0);
    $data = unserialize($records[0]->field_meta_tags_value, ['allowed_classes' => FALSE]);
    $this->assertTrue($data['description'] === $expected_value);

    $this->runUpdates();

    // Confirm the data was converted to JSON format.
    $query = \Drupal::database()->select('node__field_meta_tags');
    $query->addField('node__field_meta_tags', 'field_meta_tags_value');
    $result = $query->execute();
    $records = $result->fetchAll();
    $this->assertTrue(count($records) === 1);
    $this->assertTrue(strpos($records[0]->field_meta_tags_value, '{"') === 0);
    $data = Json::decode($records[0]->field_meta_tags_value);
    $this->assertTrue($data['description'] === $expected_value);
  }

}
