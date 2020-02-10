<?php

namespace Drupal\webform_content_creator\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

class WebformContentCreatorDeleteForm extends EntityConfirmFormBase {

  /**
   * Return the question shown when deleting a Webform content creator entity.
   *
   * @return type
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name?', array('%name' => $this->entity->getTitle()));
  }

  /**
   * Get url used when canceling an entity deletion.
   *
   * @return Url
   */
  public function getCancelUrl() {
    return new Url('entity.webform_content_creator.collection');
  }

  /**
   * Get confirmation text.
   *
   * @return string Confirmation text
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * Get submission form when deleting a Webform content creator entity.
   *
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();
    $this->messenger()->addMessage($this->t('Entity %label has been deleted.', array('%label' => $this->entity->getTitle())));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
