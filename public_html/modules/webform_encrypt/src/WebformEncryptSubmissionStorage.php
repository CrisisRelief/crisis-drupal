<?php

namespace Drupal\webform_encrypt;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\encrypt\EncryptionProfileInterface;
use Drupal\encrypt\EncryptServiceInterface;
use Drupal\webform\WebformAccessRulesManagerInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformSubmissionStorage;
use Drupal\encrypt\Entity\EncryptionProfile;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Alter webform submission storage definitions.
 */
class WebformEncryptSubmissionStorage extends WebformSubmissionStorage {

  /**
   * The encryption Service.
   *
   * @var \Drupal\encrypt\EncryptServiceInterface
   */
  protected $encryptionService;

  /**
   * WebformEncryptSubmissionStorage constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection to be used.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend to be used.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   Proxied implementation of AccountInterface, to access current user data.
   * @param \Drupal\webform\WebformAccessRulesManagerInterface $access_rules_manager
   *   The webform access rules manager.
   * @param \Drupal\encrypt\EncryptServiceInterface $encryptService
   *   The encryption Service.
   */
  public function __construct(EntityTypeInterface $entity_type, Connection $database, EntityManagerInterface $entity_manager, CacheBackendInterface $cache, LanguageManagerInterface $language_manager, AccountProxyInterface $current_user, WebformAccessRulesManagerInterface $access_rules_manager, EncryptServiceInterface $encryptService) {
    parent::__construct($entity_type, $database, $entity_manager, $cache, $language_manager, $current_user, $access_rules_manager);

    $this->encryptionService = $encryptService;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('database'),
      $container->get('entity.manager'),
      $container->get('cache.entity'),
      $container->get('language_manager'),
      $container->get('current_user'),
      $container->get('webform.access_rules_manager'),
      $container->get('encryption')
    );
  }

  /**
   * Helper function to recursively encrypt fields.
   *
   * @param array $data
   *   The current form data array.
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform we are encrypting.
   *
   * @return array
   *   Array of form data with the value encrypted for those elements setup
   *   for being processed by an encryption profile.
   *
   * @throws \Drupal\encrypt\Exception\EncryptException
   */
  public function encryptElements(array $data, WebformInterface $webform) {
    // Load the configuration.
    $config = $webform->getThirdPartySetting('webform_encrypt', 'element');

    foreach ($data as $element_name => $value) {
      $encryption_profile = isset($config[$element_name]) ? EncryptionProfile::load($config[$element_name]['encrypt_profile']) : FALSE;
      // If the value is an array and we have a encryption profile.
      if ($encryption_profile) {
        if (is_array($value)) {
          $this->encryptChildren($data[$element_name], $encryption_profile);
        }
        else {
          $encrypted_value = $this->encrypt($value, $encryption_profile);
          // Save the encrypted data value.
          $data[$element_name] = $encrypted_value;
        }
      }
    }
    return $data;
  }

  /**
   * Helper function to recursively encrypt children of fields.
   *
   * @param array $data
   *   Element data by reference.
   * @param \Drupal\encrypt\EncryptionProfileInterface $encryption_profile
   *   The encryption profile to be used on this element.
   *
   * @throws \Drupal\encrypt\Exception\EncryptException
   */
  public function encryptChildren(array &$data, EncryptionProfileInterface $encryption_profile) {
    foreach ($data as $key => $value) {
      if (is_array($value)) {
        $this->encryptChildren($data[$key], $encryption_profile);
      }
      else {
        $encrypted_value = $this->encrypt($value, $encryption_profile);
        $data[$key] = $encrypted_value;
      }
    }
  }

  /**
   * Encrypts a string.
   *
   * @param string $string
   *   The string to be decrypted.
   * @param \Drupal\encrypt\EncryptionProfileInterface $encryption_profile
   *   The encryption profile to be used to encrypt the string.
   *
   * @return string
   *   The serialized encrypted value.
   *
   * @throws \Drupal\encrypt\Exception\EncryptException
   */
  protected function encrypt($string, EncryptionProfileInterface $encryption_profile) {
    // Serialize the data to include the encryption profile.
    // This is used later to check for changes in the profile.
    $encrypted_data = [
      'data' => $this->encryptionService->encrypt($string, $encryption_profile),
      'encrypt_profile' => $encryption_profile->id(),
    ];
    return serialize($encrypted_data);
  }

  /**
   * Decrypts a string.
   *
   * @param string $data
   *   The serialized submission data to be decrypted.
   * @param bool $check_permissions
   *   Flag that controls permissions check.
   *
   * @return string
   *   The decrypted value.
   *
   * @throws \Drupal\encrypt\Exception\EncryptException
   */
  protected function decrypt($data, $check_permissions = TRUE) {
    if ($check_permissions && !$this->currentUser->hasPermission('view encrypted values')) {
      return '[Value Encrypted]';
    }
    $unserialized = unserialize($data);
    if (isset($unserialized['data']) && isset($unserialized['encrypt_profile'])) {
      $encryption_profile = EncryptionProfile::load($unserialized['encrypt_profile']);
      return $this->encryptionService->decrypt($unserialized['data'], $encryption_profile);
    }

    return $data;
  }

  /**
   * Helper function to recursively decrypt fields.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   The webform submission to work on.
   * @param bool $check_permissions
   *   Flag that controls permissions check.
   *
   * @return array
   *   Array of form data with the value now decrypted for those elements setup
   *   for being processed by an encryption profile.
   *
   * @throws \Drupal\encrypt\Exception\EncryptException
   */
  public function decryptElements(WebformSubmissionInterface $webform_submission, $check_permissions = TRUE) {
    // Load webform.
    $webform = $webform_submission->getWebform();
    // Load submission data.
    $data = $webform_submission->getData();
    // Load the configuration.
    $config = $webform->getThirdPartySetting('webform_encrypt', 'element');
    foreach ($data as $element_name => $value) {
      if (isset($config[$element_name]) && $config[$element_name]['encrypt']) {
        if (is_array($value)) {
          $this->decryptChildren($data[$element_name], $check_permissions);
        }
        else {
          $decrypted_value = $this->decrypt($value, $check_permissions);
          // Save the decrypted data value.
          $data[$element_name] = $decrypted_value;
        }
      }
    }
    return $data;
  }

  /**
   * Helper function to recursively decrypt children of fields.
   *
   * @param array $data
   *   Element data by reference.
   * @param bool $check_permissions
   *   Flag that controls permissions check.
   *
   * @throws \Drupal\encrypt\Exception\EncryptException
   */
  public function decryptChildren(array &$data, $check_permissions = TRUE) {
    foreach ($data as $key => $value) {
      if (is_array($value)) {
        $this->decryptChildren($data[$key], $check_permissions);
      }
      else {
        $decrypted_value = $this->decrypt($value, $check_permissions);
        $data[$key] = $decrypted_value;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doPreSave(EntityInterface $entity) {
    /** @var \Drupal\webform\WebformSubmissionInterface $entity */
    $id = parent::doPreSave($entity);

    $data_original = $entity->getData();

    $webform = $entity->getWebform();

    $encrypted_data = $this->encryptElements($data_original, $webform);
    $entity->setData($encrypted_data);

    return $id;
  }

  /**
   * {@inheritdoc}
   */
  protected function doPostSave(EntityInterface $entity, $update) {
    /** @var \Drupal\webform\WebformSubmissionInterface $entity */

    // Decrypt and set data post save so it remains readable for multistep
    // webforms and/or any other process that may take place after saving.
    $data = $this->decryptElements($entity, FALSE);
    $entity->setData($data);

    parent::doPostSave($entity, $update);
  }

  /**
   * {@inheritdoc}
   */
  protected function loadData(array &$webform_submissions) {
    parent::loadData($webform_submissions);

    foreach ($webform_submissions as &$webform_submission) {
      $data = $this->decryptElements($webform_submission);
      $webform_submission->setData($data);
      $webform_submission->setOriginalData($data);
    }
  }

}
