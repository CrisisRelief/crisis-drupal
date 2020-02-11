<?php

namespace Drupal\Tests\key\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Key\KeyInterface;

/**
 * Tests the key.repository service.
 *
 * @group key
 */
class KeyRepositoryServiceTest extends BrowserTestBase {

  use KeyTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['key'];

  /**
   * Test key provider methods.
   */
  public function testKeyRepositoryService() {

    $this->createTestKey('testing_key_0');

    // Test getKey.
    $targetKey = \Drupal::service('key.repository')->getKey('testing_key_0');

    $this->assertInstanceOf(KeyInterface::class, $targetKey);

    $this->createTestKey('test_provider_0');
    $this->createTestKey('test_provider_1', 'authentication', 'file');

    // Test getKeysByProvider.
    $keys = \Drupal::service('key.repository')->getKeysByProvider('config');
    $this->assertEquals('2', count($keys), "The getKeysByProvider function is not returning 2 config keys");
    foreach ($keys as $key) {
      $this->assertInstanceOf(KeyInterface::class, $key);
      $this->assertEquals('config', $key->getKeyProvider()->getPluginId());
    }

    $this->createTestKey('test_type', 'encryption', 'config');

    // Test getKeysByType.
    $keys = \Drupal::service('key.repository')->getKeysByType('encryption');
    $this->assertEquals('1', count($keys), "Found " . count($keys) . " keys with type 'encryption' instead of 1.");
    foreach ($keys as $key) {
      $this->assertInstanceOf(KeyInterface::class, $key);
      $this->assertEquals('encryption', $key->getKeyType()->getPluginId());
    }

    // Test getKeys.
    $keys = \Drupal::service('key.repository')->getKeys();
    $this->assertEquals(4, count($keys), "Only found " . count($keys) . " of 4 keys.");

    $keys = \Drupal::service('key.repository')->getKeys(['test_type', 'testing_key_0']);
    $this->assertEquals(2, count($keys), "Couldn't find 2 keys by ID.");

    // Test getKeysByTypeGroup.
    $this->createTestKey('test_type_group', 'authentication_multivalue', 'config');
    $keys = \Drupal::service('key.repository')->getKeysByTypeGroup('authentication');
    $this->assertEquals(4, count($keys), "Only found " . count($keys) . " of 4 'authentication' group keys.");

    // Test getKeyNamesAsOptions.
    $keys = \Drupal::service('key.repository')->getKeyNamesAsOptions();
    $this->assertEquals(5, count($keys), "Only found " . count($keys) . " of 5 key names.");

    $filter = [
      'type' => 'authentication',
      'provider' => 'file',
    ];
    $keys = \Drupal::service('key.repository')->getKeyNamesAsOptions($filter);
    $this->assertEquals(1, count($keys), "Found " . count($keys) . " key names instead of 1.");
  }

}
