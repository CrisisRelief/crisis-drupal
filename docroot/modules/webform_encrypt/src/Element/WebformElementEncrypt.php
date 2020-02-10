<?php

namespace Drupal\webform_encrypt\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Url;

/**
 * Provides a webform element for element attributes.
 *
 * @FormElement("webform_element_encrypt")
 */
class WebformElementEncrypt extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processWebformElementEncrypt'],
      ],
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {

  }

  /**
   * Processes element attributes.
   */
  public static function processWebformElementEncrypt(&$element, FormStateInterface $form_state, &$complete_form) {
    $webform = $config = $form_state->getFormObject()->getWebform();
    $values = $form_state->getValues();
    $element_name = $values['key'];
    $config = $webform->getThirdPartySetting('webform_encrypt', 'element');
    $encryption_options = \Drupal::service('encrypt.encryption_profile.manager')
      ->getEncryptionProfileNamesAsOptions();

    if (count($encryption_options) > 0) {
      $element['element_encrypt']['encrypt'] = [
        '#type' => 'checkbox',
        '#title' => t("Encrypt this field's value"),
        '#description' => t('<a href=":link">Click here</a> to edit encryption settings.', [
          ':link' => Url::fromRoute('entity.encryption_profile.collection')
            ->toString(),
        ]),
        '#default_value' => isset($config[$element_name]['encrypt']) ? $config[$element_name]['encrypt'] : FALSE,
      ];

      $element['element_encrypt']['encrypt_profile'] = [
        '#type' => 'select',
        '#title' => t('Select Encryption Profile'),
        '#options' => $encryption_options,
        '#default_value' => isset($config[$element_name]['encrypt_profile']) ? $config[$element_name]['encrypt_profile'] : NULL,
        '#states' => [
          'visible' => [
            [':input[name="encrypt"]' => ['checked' => TRUE]],
          ],
        ],
      ];

      $element['#element_validate'] = [
        [
          get_called_class(),
          'validateWebformElementEncrypt',
        ],
      ];
    }
    else {
      $element['element_encrypt']['message'] = [
        '#markup' => t('Please configure the encryption profile to enable encryption for the element.'),
      ];
    }

    return $element;
  }

  /**
   * Validates element attributes.
   */
  public static function validateWebformElementEncrypt(&$element, FormStateInterface $form_state, &$complete_form) {
    $webform = $form_state->getFormObject()->getWebform();
    $values = $form_state->getValues();
    $element_name = $values['key'];
    $config = $webform->getThirdPartySetting('webform_encrypt', 'element');

    // To avoid generating an unnecessary dependencies on webform_encrypt:
    // 1. Only set our third party settings if we are encrypting the element.
    // 2. Unset our third party settings if not encrypting the element.
    if (isset($values['encrypt']) && $values['encrypt'] == 1) {
      $config[$element_name] = [
        'encrypt' => $values['encrypt'],
        'encrypt_profile' => $values['encrypt_profile'],
      ];
    }
    else {
      unset($config[$element_name]);
    }
    if (empty($config)) {
      $webform->unsetThirdPartySetting('webform_encrypt', 'element');
    }
    else {
      $webform->setThirdPartySetting('webform_encrypt', 'element', $config);
    }

  }

}
