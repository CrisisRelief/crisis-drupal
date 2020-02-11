<?php

namespace Drupal\webform_content_creator\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Provides a list of Webform Content Creator entities.
 */
class WebformContentCreatorListBuilder extends ConfigEntityListBuilder {
  
  const TITLE = 'title';

  const WEBFORM = 'webform';

  const CONTENT_TYPE = 'content_type';

  /**
   * Constructs the table header.
   *
   * @return array Table header
   */
  public function buildHeader() {
    $header[self::TITLE] = $this->t('Title');
    $header[self::WEBFORM] = $this->t('Webform');
    $header[self::CONTENT_TYPE] = $this->t('Content type');
    return $header + parent::buildHeader();
  }

  /**
   * Constructs the table rows.
   *
   * @param EntityInterface $entity
   * @return \Drupal\Core\Entity\EntityListBuilder A render array structure of fields for this entity.
   */
  public function buildRow(EntityInterface $entity) {
    $webform = \Drupal::entityTypeManager()->getStorage(self::WEBFORM)->load($entity->getWebform());
    $content_type = \Drupal::entityTypeManager()->getStorage('node_type')->load($entity->getContentType());
    if (!empty($webform) && !empty($content_type)) {
      $row[self::TITLE] = $entity->get('title') . ' (' . $entity->id() . ')';
      $row[self::WEBFORM] = $webform->label() . ' (' . $entity->getWebform() . ')';
      $row[self::CONTENT_TYPE] = $content_type->label() . ' (' . $entity->getContentType() . ')';
      return $row + parent::buildRow($entity);
    }
    return parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity, $type = 'edit') {
    $operations = parent::getDefaultOperations($entity);
    $operations['manage_fields'] = [
      self::TITLE => $this->t('Manage fields'),
      'weight' => 0,
      'url' => Url::fromRoute('entity.webform_content_creator.manage_fields_form', ['webform_content_creator' => $entity->id()]),
    ];

    return $operations;
  }

}
