<?php

namespace Drupal\webform_content_creator\Form;

use Drupal\Core\Url;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\webform_content_creator\WebformContentCreatorUtilities;

/**
 * Form handler for the Webform content creator manage fields form.
 */
class WebformContentCreatorManageFieldsForm extends EntityForm {

  const CONTENT_TYPE_FIELD = 'content_type_field';

  const FIELD_TYPE = 'field_type';

  const WEBFORM_FIELD = 'webform_field';

  const CUSTOM_CHECK = 'custom_check';

  const CUSTOM_VALUE = 'custom_value';

  const FORM_TABLE = 'table';

  /**
   * Constructs an WebformContentCreatorForm object.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The entity query.
   */
  public function __construct(QueryFactory $entity_query) {
    $this->entityQuery = $entity_query;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('entity.query')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->get('field_title'),
      '#help' => $this->t('Title of content created after webform submission. You may use tokens.'),
      '#description' => $this->t("Default value: webform title."),
      '#weight' => 0,
    ];
    $form['intro_text'] = [
      '#markup' => '<p>' . $this->t('You can create nodes based on webform submission values. In this page, you can do mappings between content type fields and webform submission values. You may also use tokens in custom text.') . '</p>',
    ];
    $form['tokens'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => array('webform_submission'),
      '#global_types' => TRUE,
      '#click_insert' => TRUE,
      '#show_restricted' => FALSE,
      '#recursion_limit' => 3,
      '#text' => $this->t('Browse available tokens'),
    ];
    // construct table with mapping between content type fields and webform elements
    $this->constructTable($form);
    return $form;
  }

  /**
   * Constructs an administration table to configure the mapping between webform elements and content type fields.
   *
   * @param Drupal\Core\Entity\EntityForm $form
   */
  function constructTable(&$form) {
    $fieldTypesDefinitions = \Drupal::service('plugin.manager.field.field_type')->getDefinitions();
    $attributes = $this->entity->getAttributes();
    $ct = $this->entity->getContentType();
    $contentType = \Drupal::entityTypeManager()->getStorage('node_type')->load($ct);
    $nodeFilteredFieldIds = WebformContentCreatorUtilities::getContentFieldsIds($contentType);
    asort($nodeFilteredFieldIds);
    $nodeFields = WebformContentCreatorUtilities::contentTypeFields($contentType);
    $webform_id = $this->entity->getWebform();
    $webformOptions = WebformContentCreatorUtilities::getWebformElements($webform_id);

    // table header
    $header = array(
      self::CONTENT_TYPE_FIELD => $this->t('Content type field'),
      self::FIELD_TYPE => $this->t('Field type'),
      self::CUSTOM_CHECK => $this->t('Custom'),
      self::WEBFORM_FIELD => $this->t('Webform field'),
      self::CUSTOM_VALUE => $this->t('Custom text'),
    );
    $form[self::FORM_TABLE] = [
      '#type' => 'table',
      '#header' => $header,
    ];

    foreach ($nodeFilteredFieldIds as $fieldId) {
      $route_parameters = [
        'node_type' => $ct,
        'field_config' => 'node.' . $ct . '.' . $fieldId,
      ];

      //checkboxes with content type fields
      $form[self::FORM_TABLE][$fieldId][self::CONTENT_TYPE_FIELD] = [
        '#type' => 'checkbox',
        '#default_value' => array_key_exists($fieldId, $attributes),
        '#title' => $nodeFields[$fieldId]->getLabel() . ' (' . $fieldId . ')',
      ];

      //link to edit field settings
      $form[self::FORM_TABLE][$fieldId][self::FIELD_TYPE] = [
        '#type' => 'link',
        '#title' => $fieldTypesDefinitions[$nodeFields[$fieldId]->getType()]['label'],
        '#url' => Url::fromRoute("entity.field_config.node_storage_edit_form", $route_parameters),
        '#options' => ['attributes' => ['title' => $this->t('Edit field settings.')]],
      ];

      //checkbox to select between webform element/property or custom text
      $form[self::FORM_TABLE][$fieldId][self::CUSTOM_CHECK] = [
        '#type' => 'checkbox',
        '#default_value' => array_key_exists($fieldId, $attributes) ? $attributes[$fieldId][self::CUSTOM_CHECK] : '',
        '#states' => [
          'disabled' => [
            ':input[name="'. self::FORM_TABLE . '[' . $fieldId . '][' . self::CONTENT_TYPE_FIELD . ']"]' => ['checked' => false],
          ],
        ],
      ];

      $type = !empty($attributes[$fieldId]) && $attributes[$fieldId]['type'] ? '1' : '0';
      //select with webform elements and basic properties
      $form[self::FORM_TABLE][$fieldId][self::WEBFORM_FIELD] = [
        '#type' => 'select',
        '#options' => $webformOptions,
        '#states' => [
          'required' => [
            ':input[name="'. self::FORM_TABLE . '[' . $fieldId . '][' . self::CONTENT_TYPE_FIELD . ']"]' => ['checked' => true],
            ':input[name="'. self::FORM_TABLE . '[' . $fieldId . '][' . self::CUSTOM_CHECK . ']"]' => ['checked' => false],
          ],
        ],
      ];

      if (array_key_exists($fieldId, $attributes) && !$attributes[$fieldId][self::CUSTOM_CHECK]) {
        $form[self::FORM_TABLE][$fieldId][self::WEBFORM_FIELD]['#default_value'] = $type . ',' . $attributes[$fieldId][self::WEBFORM_FIELD];
      }

      // textarea with custom text (including tokens)
      $form[self::FORM_TABLE][$fieldId][self::CUSTOM_VALUE] = [
        '#type' => 'textarea',
        '#default_value' => array_key_exists($fieldId, $attributes) ? $attributes[$fieldId][self::CUSTOM_VALUE] : '',
      ];
    }

    // change table position in page
    $form[self::FORM_TABLE]['#weight'] = 1;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $ct = $this->entity->getContentType();
    $contentType = \Drupal::entityTypeManager()->getStorage('node_type')->load($ct);
    $nodeFields = WebformContentCreatorUtilities::contentTypeFields($contentType);
    $webform_id = $this->entity->getWebform();
    $webformElementTypes = WebformContentCreatorUtilities::getWebformElementsTypes($webform_id);
    foreach ($form_state->getValue(self::FORM_TABLE) as $k => $v) { // for each table row
      if (!$v[self::CONTENT_TYPE_FIELD]) { // check if a content type field is selected
        continue;
      }
      $args = explode(',', $v[self::WEBFORM_FIELD]);
      if (empty($args) || count($args) < 2) {
        continue;
      }

      $nodeFieldType = $nodeFields[$k]->getType();
      $webformOptionType = array_key_exists($args[1], $webformElementTypes) ? $webformElementTypes[$args[1]] : '';
      if ($nodeFieldType === $webformOptionType) {
        continue;
      }

      if ($nodeFieldType === 'email') {
        $form_state->setErrorByName(self::FORM_TABLE . '][' . $k . '][' . self::WEBFORM_FIELD, t('Incompatible type'));
      }

      if ($webformOptionType === 'email' && (strpos($nodeFieldType, 'text') === false) && (strpos($nodeFieldType, 'string') === false)) {
        $form_state->setErrorByName(self::FORM_TABLE . '][' . $k . '][' . self::WEBFORM_FIELD, t('Incompatible type'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $attributes = [];
    foreach ($form_state->getValue(self::FORM_TABLE) as $k => $v) { // for each table row
      if (!$v[self::CONTENT_TYPE_FIELD]) { // check if a content type field is selected
        continue;
      }
      $args = explode(',', $v[self::WEBFORM_FIELD]);
      if (empty($args) || count($args) < 1) {
        continue;
      }

      $attributes[$k] = [
        'type' => explode(',', $v[self::WEBFORM_FIELD])[0] === '1',
        self::WEBFORM_FIELD => !$v[self::CUSTOM_CHECK] ? explode(',', $v[self::WEBFORM_FIELD])[1] : '',
        self::CUSTOM_CHECK => $v[self::CUSTOM_CHECK],
        self::CUSTOM_VALUE => $v[self::CUSTOM_CHECK] ? $v[self::CUSTOM_VALUE] : '',
      ];
    }

    $this->entity->set('field_title', $form_state->getValue('title'));
    $this->entity->set('elements', $attributes);
    $status = $this->entity->save();
    $this->entity->statusMessage($status);
    $form_state->setRedirect('entity.webform_content_creator.collection');
  }

  /**
   * Helper function to check whether a Webform content type creator entity exists.
   *
   * @param type $id Entity id
   * @return boolean Return true if entity already exists
   */
  public function exist($id) {
    $entity = $this->entityQuery->get('webform_content_creator')
        ->condition('id', $id)
        ->execute();
    return (bool) $entity;
  }

}
