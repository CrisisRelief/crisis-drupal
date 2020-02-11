<?php

namespace Drupal\webform_content_creator\Entity;

use Drupal\webform\WebformSubmissionInterface;
use Drupal\node\NodeInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\webform_content_creator\WebformContentCreatorInterface;
use Drupal\Core\StringTranslation;
use Drupal\webform_content_creator\WebformContentCreatorUtilities;

/**
 * Defines the Webform Content creator entity.
 *
 * @ConfigEntityType(
 *   id = "webform_content_creator",
 *   label = @Translation("Webform Content creator"),
 *   handlers = {
 *     "list_builder" = "Drupal\webform_content_creator\Controller\WebformContentCreatorListBuilder",
 *     "form" = {
 *       "add" = "Drupal\webform_content_creator\Form\WebformContentCreatorForm",
 *       "edit" = "Drupal\webform_content_creator\Form\WebformContentCreatorForm",
 *       "delete" = "Drupal\webform_content_creator\Form\WebformContentCreatorDeleteForm",
 *       "manage_fields" = "Drupal\webform_content_creator\Form\WebformContentCreatorManageFieldsForm",
 *     }
 *   },
 *   config_prefix = "webform_content_creator",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "title" = "title",
 *     "webform" = "webform",
 *     "content_type" = "content_type",
 *   },
 *   links = {
 *     "manage-fields-form" = "/admin/config/webform_content_creator/manage/{webform_content_creator}/fields",
 *     "edit-form" = "/admin/config/webform_content_creator/{webform_content_creator}",
 *     "delete-form" = "/admin/config/webform_content_creator/{webform_content_creator}/delete",
 *   }
 * )
 */
class WebformContentCreatorEntity extends ConfigEntityBase implements WebformContentCreatorInterface {

  use StringTranslation\StringTranslationTrait;

  protected $id;
  protected $title;
  protected $field_title;
  protected $webform;
  protected $content_type;
  protected $elements;
  protected $use_encrypt;
  protected $encryption_profile;

  /**
   * Returns the entity title.
   *
   * @return string
   *   The entity title.
   */
  public function getTitle() {
    return $this->get('title');
  }

  /**
   * Sets the entity title.
   *
   * @param string $title Node title
   * @return $this
   *   The Webform Content Creator entity.
   */
  public function setTitle($title) {
    $this->set('title', $title);
    return $this;
  }

  /**
   * Returns the entity content type id.
   *
   * @return string
   *   The entity content type.
   */
  public function getContentType() {
    return $this->get('content_type');
  }

  /**
   * Sets the content type entity.
   *
   * @param string $contentType Content type entity
   * @return $this
   *   The Webform Content Creator entity.
   */
  public function setContentType($contentType) {
    $this->set('content_type', $contentType);
    return $this;
  }

  /**
   * Returns the entity webform id.
   *
   * @return string
   *   The entity webform.
   */
  public function getWebform() {
    return $this->get('webform');
  }

  /**
   * Sets the entity webform id.
   *
   * @param string $webform Webform id
   * @return $this
   *   The Webform Content Creator entity.
   */
  public function setWebform($webform) {
    $this->set('webform', $webform);
    return $this;
  }

  /**
   * Returns the entity attributes as an associative array.
   *
   * @return array
   *   The entity attributes mapping.
   */
  public function getAttributes() {
    return $this->get(WebformContentCreatorInterface::ELEMENTS);
  }

  /**
   * Check if synchronization between nodes and webform submissions is used.
   *
   * @return boolean
   *   true, when the synchronization is used. Otherwise, returns false.
   */
  public function getSyncEditContentCheck() {
    return $this->get(WebformContentCreatorInterface::SYNC_CONTENT);
  }

  /**
   * Check if synchronization between nodes and webform submissions is used in deletion.
   *
   * @return boolean
   *   true, when the synchronization is used. Otherwise, returns false.
   */
  public function getSyncDeleteContentCheck() {
    return $this->get(WebformContentCreatorInterface::SYNC_CONTENT_DELETE);
  }

  /**
   * Get node field in which the webform submission id will be stored, to perform synchronization between nodes and webform submissions.
   *
   * @return string
   *   Field machine name.
   */
  public function getSyncContentField() {
    return $this->get(WebformContentCreatorInterface::SYNC_CONTENT_FIELD);
  }

  /**
   * Returns the encryption method.
   *
   * @return boolean
   *   true, when an encryption profile is used. Otherwise, returns false.
   */
  public function getEncryptionCheck() {
    return $this->get(WebformContentCreatorInterface::USE_ENCRYPT);
  }

  /**
   * Returns the encryption profile.
   *
   * @return string
   *   The encryption profile name.
   */
  public function getEncryptionProfile() {
    return $this->get(WebformContentCreatorInterface::ENCRYPTION_PROFILE);
  }

  /**
   * Get node title.
   *
   * @return string Node title
   */
  private function getNodeTitle() {
    // get title
    if ($this->get(WebformContentCreatorInterface::FIELD_TITLE) !== null && $this->get(WebformContentCreatorInterface::FIELD_TITLE) !== '') {
      $nodeTitle = $this->get(WebformContentCreatorInterface::FIELD_TITLE);
    } else {
      $nodeTitle = \Drupal::entityTypeManager()->getStorage(WebformContentCreatorInterface::WEBFORM)->load($this->get(WebformContentCreatorInterface::WEBFORM))->label();
    }

    return $nodeTitle;
  }

  /**
   * Get encryption profile name.
   *
   * @return string Encryption profile name.
   */
  private function getProfileName() {
    $encryptionProfile = '';
    $useEncrypt = $this->get(WebformContentCreatorInterface::USE_ENCRYPT);
    if ($useEncrypt) {
      $encryptionProfile = \Drupal::service('entity.manager')->getStorage(WebformContentCreatorInterface::ENCRYPTION_PROFILE)->load($this->getEncryptionProfile());
    }

    return $encryptionProfile;
  }

  /**
   * Get decrypted value with encryption profile associated with the Webform Content Creator entity.
   *
   * @param string $value Encrypted value
   * @return string $encryptionProfile Encryption profile used to encrypt/decrypt $value
   */
  private function getDecryptionFromProfile($value, $encryptionProfile = '') {
    if ($this->getEncryptionCheck()) {
      $decValue = WebformContentCreatorUtilities::getDecryptedValue($value, $encryptionProfile);
    } else {
      $decValue = $value;
    }
    return $decValue;
  }

  /**
   * Use a single mapping (node field-webform submission value) to set a Node field value.
   *
   * @param NodeInterface $initialContent Content being mapped with a webform submission
   * @param WebformSubmissionInterface $webform_submission Webform submission entity
   * @param array $fields Node fields
   * @param array $data Webform submission data
   * @param string $encryptionProfile Encryption profile used in Webform encrypt module
   * @param string $fieldId Node field id
   * @param array $mapping Webform Content Creator attribute with the mapping Node field - Webform submission value
   * @param array $attributes Webform Content Creator attributes with all mappings between Node fields and Webform submission values
   * @return NodeInterface Node
   */
  private function mapNodeField(NodeInterface $initialContent, $webform_submission = [], $fields = [], $data = [], $encryptionProfile = '', $fieldId = '', $mapping = [], $attributes = []) {
    $content = $initialContent;
    if (!$content->hasField($fieldId) || !is_array($mapping)) {
      return $content;
    }
    if ($attributes[$fieldId][WebformContentCreatorInterface::CUSTOM_CHECK]) { // custom text
      // use Drupal tokens to fill the field
      $decValue = WebformContentCreatorUtilities::getDecryptedTokenValue($mapping[WebformContentCreatorInterface::CUSTOM_VALUE], $encryptionProfile, $webform_submission);
      if($decValue === 'true' || $decValue === 'TRUE') {
        $decValue = TRUE;
      }
    } else {
      if (!$attributes[$fieldId][WebformContentCreatorInterface::TYPE]) { // webform element
        if (!array_key_exists(WebformContentCreatorInterface::WEBFORM_FIELD, $mapping) || !array_key_exists($mapping[WebformContentCreatorInterface::WEBFORM_FIELD], $data)) {
          return $content;
        }
        $decValue = $this->getDecryptionFromProfile($data[$mapping[WebformContentCreatorInterface::WEBFORM_FIELD]], $encryptionProfile);
      } else { // webform basic property
        $decValue = $webform_submission->{$mapping[WebformContentCreatorInterface::WEBFORM_FIELD]}->value;
      }
    }

    // check if field's max length is exceeded
    $maxLength = $this->checkMaxFieldSizeExceeded($fields, $fieldId, $decValue);
    if ($maxLength === 0) {
      $content->set($fieldId, $decValue);
    } else {
      $content->set($fieldId, substr($decValue, 0, $maxLength));
    }

    return $content;
  }

  /**
   * Create node from webform submission.
   *
   * @param WebformSubmission entity $webform_submission Webform submission
   */
  public function createNode($webform_submission) {
    $nodeTitle = $this->getNodeTitle();

    // get webform submission data
    $data = $webform_submission->getData();
    if (empty($data)) {
      return 0;
    }

    $encryptionProfile = $this->getProfileName();

    // decrypt title
    $decrypted_title = WebformContentCreatorUtilities::getDecryptedTokenValue($nodeTitle, $encryptionProfile, $webform_submission);

    //create new node
    $content = Node::create([
      WebformContentCreatorInterface::TYPE => $this->getContentType(),
          'title' => $decrypted_title
    ]);

    // set node fields values
    $attributes = $this->get(WebformContentCreatorInterface::ELEMENTS);

    $contentType = \Drupal::entityTypeManager()->getStorage('node_type')->load($this->getContentType());
    if (empty($contentType)) {
      return false;
    }

    $fields = WebformContentCreatorUtilities::contentTypeFields($contentType);
    if (empty($fields)) {
      return false;
    }
    foreach ($attributes as $k2 => $v2) {
      $content = $this->mapNodeField($content, $webform_submission, $fields, $data, $encryptionProfile, $k2, $v2, $attributes);
    }

    $result = false;

    // save node
    try {
      $result = $content->save();
    } catch (\Exception $e) {
      \Drupal::logger(WebformContentCreatorInterface::WEBFORM_CONTENT_CREATOR)->error(t('A problem occurred when creating a new node.'));
      \Drupal::logger(WebformContentCreatorInterface::WEBFORM_CONTENT_CREATOR)->error($e->getMessage());
    }
    return $result;
  }

  /**
   * Update node from webform submission.
   *
   * @param WebformSubmission entity $webform_submission Webform submission
   * @param string $op Operation
   * @return boolean true, if succeeded. Otherwise, return false.
   */
  public function updateNode($webform_submission, $op = 'edit') {
    if (empty($this->getSyncContentField())) {
      return false;
    }

    $contentType = \Drupal::entityTypeManager()->getStorage('node_type')->load($this->getContentType());
    if (empty($contentType)) {
      return false;
    }

    $fields = WebformContentCreatorUtilities::contentTypeFields($contentType);
    if (empty($fields)) {
      return false;
    }

    if (!array_key_exists($this->getSyncContentField(), $fields)) {
      return false;
    }

    $nodeTitle = $this->getNodeTitle();

    // get webform submission data
    $data = $webform_submission->getData();
    if (empty($data)) {
      return false;
    }

    $encryptionProfile = $this->getProfileName();

    // decrypt title
    $decrypted_title = WebformContentCreatorUtilities::getDecryptedTokenValue($nodeTitle, $encryptionProfile, $webform_submission);

    // get nodes created from this webform submission
    $nodes = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties([$this->getSyncContentField() => $webform_submission->id()]);

    // use only first result, if exists
    if (!($content = reset($nodes))) {
      return false;
    }

    if ($op === 'delete' && !empty($this->getSyncDeleteContentCheck())) {
      $result = $content->delete();
      return $result;
    }

    if (empty($this->getSyncEditContentCheck())) {
      return false;
    }

	// set title
	$content->setTitle($decrypted_title);

    // set node fields values
    $attributes = $this->get(WebformContentCreatorInterface::ELEMENTS);

    foreach ($attributes as $k2 => $v2) {
      $content = $this->mapNodeField($content, $webform_submission, $fields, $data, $encryptionProfile, $k2, $v2, $attributes);
    }

    $result = false;

    // save node
    try {
      $result = $content->save();
    } catch (\Exception $e) {
      \Drupal::logger(WebformContentCreatorInterface::WEBFORM_CONTENT_CREATOR)->error(t('A problem occurred while updating node.'));
      \Drupal::logger(WebformContentCreatorInterface::WEBFORM_CONTENT_CREATOR)->error($e->getMessage());
    }

    return $result;

  }

  /**
   * Check if a content type entity associated with the Webform content creator entity exists.
   *
   * @return boolean true, if content type entity exists. Otherwise, returns false.
   */
  public function existsContentType() {
    $content_type_id = $this->getContentType(); // get content type id
    $content_type_entity = \Drupal::entityTypeManager()->getStorage('node_type')->load($content_type_id); // get content type entity
    if (!empty($content_type_entity)) {
      return true;
    }
    return false;
  }

  /**
   * Check if the content type id (parameter) is equal to the content type id of Webform content creator entity
   *
   * @param string $ct Content type id
   * @return boolean true, if the parameter is equal to the content type id of Webform content creator entity. Otherwise, returns false.
   */
  public function equalsContentType($ct) {
    if ($ct === $this->getContentType()) {
      return true;
    }
    return false;
  }

  /**
   * Check if the webform id (parameter) is equal to the webform id of Webform content creator entity
   *
   * @param string $webform Webform id
   * @return boolean true, if the parameter is equal to the webform id of Webform content creator entity. Otherwise, returns false.
   */
  public function equalsWebform($webform) {
    if ($webform === $this->getWebform()) {
      return true;
    }
    return false;
  }

  /**
   * Show a message accordingly to status value, after creating/updating an entity.
   *
   * @param int $status Status int, returned after creating/updating an entity.
   */
  public function statusMessage($status) {
    if ($status) {
      \Drupal::messenger()->addMessage($this->t('Saved the %label entity.', ['%label' => $this->getTitle(),]));
    } else {
      \Drupal::messenger()->addMessage($this->t('The %label entity was not saved.', ['%label' => $this->getTitle(),]));
    }
  }

  /**
   * Check if field maximum size is exceeded.
   *
   * @param array $fields Content type fields
   * @param string $k Field machine name
   * @param string $decValue Decrypted value
   * @return int 1 if maximum size is exceeded, otherwise return 0.
   */
  public function checkMaxFieldSizeExceeded($fields, $k, $decValue = "") {
    if (!array_key_exists($k, $fields) || empty($fields[$k])) {
      return 0;
    }
    $fieldSettings = $fields[$k]->getSettings();
    if (empty($fieldSettings) || !array_key_exists('max_length', $fieldSettings)) {
      return 0;
    }

    $maxLength = $fieldSettings['max_length'];
    if (empty($maxLength)) {
      return 0;
    }
    if ($maxLength < strlen($decValue)) {
      \Drupal::logger(WebformContentCreatorInterface::WEBFORM_CONTENT_CREATOR)->notice(t('Problem: Field\'s max length exceeded (truncated).'));
      return $maxLength;
    }
    return strlen($decValue);
  }
}
