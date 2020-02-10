<?php

namespace Drupal\webform_encrypt;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\webform\WebformSubmissionAccessControlHandler;

/**
 * {@inheritdoc}
 */
class WebformEncryptSubmissionAccessControlHandler extends WebformSubmissionAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // Disallow access to update if the user cannot view encrypted values and
    // any of the elements are encrypted.
    if ($operation === 'update') {
      $config = $entity->getWebform()
        ->getThirdPartySetting('webform_encrypt', 'element');
      $data = $entity->getData();
      foreach ($data as $element_name => $value) {
        if (isset($config[$element_name]['encrypt']) && $config[$element_name]['encrypt'] && $account->hasPermission('view encrypted values') === FALSE) {
          return AccessResult::forbidden();
        }
      }
    }

    return parent::checkAccess($entity, $operation, $account);
  }

}
