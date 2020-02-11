<?php

namespace Drupal\webform_content_creator;

/**
 * Provides useful functions required in Webform content creator module.
 */
class WebformContentCreatorUtilities {

  const WEBFORM = 'webform';

  const WEBFORM_SUBMISSION = 'webform_submission';

  const ENTITY_MANAGER = 'entity.manager';

  const CONTENT_BASIC_FIELDS = ['body', 'status', 'uid'];

  /**
   * Contructs a tree with webform elements which can be used in Selects.
   *
   * @param array $elements Webform elements
   * @return array Tree with webform elements
   */
  private static function buildTree($elements) {
    $elementsDefinitions = \Drupal::service('plugin.manager.webform.element')->getDefinitions();
    $result = array();
    $webformFieldIds = array_keys($elements);
    $wizardPage = t('Webform elements'); //default value, only used if there are no wizard pages in webform
    $flag = 0; // check which element is the first wizard page (in case it exists)
    $aux = array();
    foreach ($webformFieldIds as $v) {
      $title = isset($elements[$v]['#title']) ? $elements[$v]['#title'] : $elements[$v]['#markup'];
      if ($elements[$v]["#type"] === "webform_wizard_page") {
        if ($flag === 0) { //executes only for the first wizard page (first optgroup in select)
          $wizardPage = html_entity_decode($title);
          unset($aux);
          $flag ++;
          continue;
        }

        foreach ($aux as $k2 => $v2) {
          $result[$wizardPage][$k2] = $v2;
        }
        $wizardPage = html_entity_decode($title);
        unset($aux);
      } elseif ($elements[$v]["#webform_parent_key"] === '') { // check if element has not parents    
        $result['0,' . $v] = html_entity_decode($title) . ' (' . $v . ') - ' . $elementsDefinitions[$elements[$v]["#type"]]['label'];
      } elseif ($elements[$v]["#type"] !== "webform_section") { // skip webform sections (not shown in selects)
        $aux['0,' . $v] = html_entity_decode($title) . ' (' . $v . ') - ' . $elementsDefinitions[$elements[$v]["#type"]]['label'];
      }
    }

    // organize webform elements as a tree (wizard pages as optgroups)
    foreach ($aux as $k2 => $v2) {
      $result[$wizardPage][$k2] = $v2;
    }
    return $result;
  }

  /**
   * Get webform elements and properties, organized in a tree, which can be used in Selects.
   *
   * @param string $webform_id Webform id
   * @return array Tree with webform elements and basic attributes.
   */
  public static function getWebformElements($webform_id) {
    $webform = \Drupal::entityTypeManager()->getStorage(self::WEBFORM)->load($webform_id);
    $options = [];
    $submission_storage = \Drupal::entityTypeManager()->getStorage(self::WEBFORM_SUBMISSION);
    $field_definitions = $submission_storage->checkFieldDefinitionAccess($webform, $submission_storage->getFieldDefinitions());
    foreach ($field_definitions as $key => $field_definition) { //basic webform properties (sid, token, serial number ..)
      if (isset($field_definition['type']) && !empty($field_definition['type'])) {
        $options['1,' . $key] = $field_definition['title'] . ' (' . $key . ') - ' . $field_definition['type'];
      }
    }
    $elements = $webform->getElementsInitializedAndFlattened(); // webform elements
    $webformOptions = self::buildTree($elements); // webform elements organized in a structured tree
    $webformOptions[t('Webform properties')->render()] = $options; // join with basic webform properties
    return $webformOptions;
  }

  /**
   * Return array with all webform elements types.
   *
   * @param type $webform_id Webform id
   * @return array Webform basic attributes and element types
   */
  public static function getWebformElementsTypes($webform_id) {
    if (!isset($webform_id) || empty($webform_id)) {
      return null;
    }

    // get webform entity
    $webform = \Drupal::entityTypeManager()->getStorage(self::WEBFORM)->load($webform_id);
    if (empty($webform)) {
      return null;
    }

    // get webform submission storage
    $submissionStorage = \Drupal::entityTypeManager()->getStorage(self::WEBFORM_SUBMISSION);
    $submissionStorageDefinitions = $submissionStorage->getFieldDefinitions();
    if (empty($submissionStorageDefinitions)) {
      return null;
    }

    // get webform basic attributes definitions
    $fieldDefinitions = $submissionStorage->checkFieldDefinitionAccess($webform, $submissionStorageDefinitions);
    if (empty($fieldDefinitions)) {
      return null;
    }

    //get webform elements and join the result with the webform basic attributes
    $elements = $webform->getElementsInitializedAndFlattened();
    if (is_array($elements)) {
      $webformFieldIds = array_keys($elements);
      foreach ($webformFieldIds as $v) {
        if (!isset($elements[$v]) || empty($elements[$v])) {
          continue;
        }
        $fieldDefinitions[$v] = $elements[$v]['#type'];
      }
    }
    return $fieldDefinitions;
  }

  /**
   * Return the content type fields.
   *
   * @param Drupal\node\Entity\NodeType $contentType
   * @return array Content type fields
   */
  public static function contentTypeFields($contentType) {
    $entityManager = \Drupal::service(self::ENTITY_MANAGER);
    $fields = [];

    if (!empty($contentType)) {
      $fields = $entityManager->getFieldDefinitions('node', $contentType->getOriginalId());
    }
    return $fields;
  }

  /**
   * Get content type fields, except the basic fields inherited from node type entity.
   *
   * @param Drupal\node\Entity\NodeType $contentType Content type entity
   * @return array Associative array Content type fields
   */
  public static function getContentFieldsIds($contentType) {
    $nodeFields = self::contentTypeFields($contentType);
    $nodeFieldIds = array_keys($nodeFields);
    return array_filter($nodeFieldIds, function ($fid) {
      return strpos($fid, 'field_') === 0 || in_array($fid, self::CONTENT_BASIC_FIELDS);
    });
  }

  /**
   * Get all content type ids.
   *
   * @return array Array with all content type ids.
   */
  public static function getAllContentTypeIds() {
    return \Drupal::service(self::ENTITY_MANAGER)->getStorage('node_type')->getQuery()->execute();
  }

  /**
   * Get all content type entities.
   *
   * @return array All content type entities.
   */
  public static function getAllContentTypes() {
    $ids = self::getAllContentTypeIds();
    return \Drupal::entityTypeManager()->getStorage('node_type')->loadMultiple(array_keys($ids));
  }

  /**
   * Get an formatted and associative array with content type ids and respective labels.
   *
   * @return array Associative array with content type ids and labels.
   */
  public static function getFormattedContentTypes() {
    $contentTypes = self::getAllContentTypes();
    $contentTypes_formatted = array();
    foreach ($contentTypes as $k => $v) {
      $contentTypes_formatted[$k] = $v->label();
    }
    return $contentTypes_formatted;
  }

  /**
   * Get all webform ids.
   *
   * @return array Array with all webform ids.
   */
  public static function getAllWebformIds() {
    $ids = \Drupal::entityTypeManager()->getStorage(self::WEBFORM)->getQuery()->condition('template', FALSE)->execute();
    return $ids;
  }

  /**
   * Get all webform entities.
   *
   * @return array All webform entities.
   */
  public static function getAllWebforms() {
    $ids = self::getAllWebformIds();
    $webforms = \Drupal::entityTypeManager()->getStorage(self::WEBFORM)->loadMultiple(array_keys($ids));
    return $webforms;
  }

  /**
   * Get an formatted and associative array with webform ids and respective labels.
   *
   * @return array Associative array with webform ids and labels.
   */
  public static function getFormattedWebforms() {
    $webforms = self::getAllWebforms();
    $webforms_formatted = array();
    foreach ($webforms as $k => $v) {
      $category = $v->get('category');
      if (empty($category)) {
        $webforms_formatted[$k] = $v->label();
      } else {
        $webforms_formatted[$category][$k] = $v->label();
      }
    }

    return $webforms_formatted;
  }

  /**
   * Get an formatted and associative array with encryption profiles and respective labels.
   *
   * @return array Associative array with encryption profiles ids and labels.
   */
  public static function getFormattedEncryptionProfiles() {
    $encryption_profiles = \Drupal::service(self::ENTITY_MANAGER)->getStorage('encryption_profile')->loadMultiple();
    $encryption_profiles_formatted = array();
    foreach ($encryption_profiles as $k => $v) {
      $encryption_profiles_formatted[$k] = $v->label();
    }
    return $encryption_profiles_formatted;
  }

  /**
   * Get decrypted value.
   *
   * @param string $value Encrypted value
   * @param EncryptionProfile entity $encryption_profile Encryption profile
   * @return string Decrypted value
   */
  public static function getDecryptedValue($value, $encryption_profile) {
    if (empty($value) || empty($encryption_profile)) {
      return '';
    }
    $dec_value = \Drupal::service('encryption')->decrypt($value, $encryption_profile);
    if ($dec_value === false) {
      $dec_value = $value;
    }
    return $dec_value;
  }

  /**
   * Get decrypted values inside text with tokens.
   *
   * @param string $value String with tokens
   * @param EncryptionProfile entity $encryption_profile Encryption profile
   * @param WebformSubmission entity $webform_submission Webform submission
   * @return string Tokens type
   */
  public static function getDecryptedTokenValue($value, $encryption_profile, $webform_submission, $type = self::WEBFORM_SUBMISSION) {
    if (empty($value) || empty($webform_submission)) {
      return '';
    }
    $tokens = \Drupal::token()->scan($value); // get tokens in string
    $token_keys = [];
    $token_values = [];
    if (empty($tokens)) {
      return $value;
    }
    foreach ($tokens[$type] as $key => $val) {
      $token_value = \Drupal::token()->replace($val, array(self::WEBFORM_SUBMISSION => $webform_submission));
      if (!empty($encryption_profile)) {
        $dec_token_value = self::getDecryptedValue($token_value, $encryption_profile);  // decrypt single token value
      } else {
        $dec_token_value = $token_value;
      }
      $token_keys[] = $val;
      $token_values[] = $dec_token_value;
    }
    if (empty($token_values)) {
      return $value;
    }
    $dec_value = str_replace($token_keys, $token_values, $value); // replace all token values in string
    return $dec_value;
  }

}
