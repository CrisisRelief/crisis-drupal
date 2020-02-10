<?php

namespace Drupal\Tests\webform_content_creator\Unit\Entity;

use Drupal\Tests\UnitTestCase;
use Drupal\webform_content_creator\Entity\WebformContentCreatorEntity;

/**
 * Unit tests for WebformContentCreator class.
 *
 * @ingroup webform_content_creator
 *
 * @group webform_content_creator
 *
 * @coversDefaultClass \Drupal\webform_content_creator\Entity\WebformContentCreatorEntity
 */
class WebformContentCreatorEntityTest extends UnitTestCase {


  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Tests the Webform Content Creator entity functions.
   *
   */
  public function testEntity() {
    // Mock a Webform Content Creator entity.
    $entity = new WebformContentCreatorEntity([], 'webform_content_creator');
    $this->assertTrue($entity instanceof WebformContentCreatorEntity);

    // Test entity methods
    $entity->setTitle('testTitle');
    $this->assertEquals($entity->getTitle(), 'testTitle');
    $entity->setContentType('ct1');
    $this->assertEquals($entity->getContentType(), 'ct1');
    $entity->setWebform('webform_entity1');
    $this->assertEquals($entity->getWebform(), 'webform_entity1');
  }

}
