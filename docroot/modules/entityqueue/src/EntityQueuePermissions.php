<?php

namespace Drupal\entityqueue;

use Drupal\entityqueue\Entity\EntityQueue;
use Drupal\Core\StringTranslation\StringTranslationTrait;

class EntityQueuePermissions {

  use StringTranslationTrait;

  /**
   * @return array
   */
  public function permissions() {
    $perms = [];
    // Generate queue permissions for all queues.
    foreach (EntityQueue::loadMultiple() as $queue) {
      $perms += $this->buildPermissions($queue);
    }

    return $perms;
  }

  /**
   * @param \Drupal\entityqueue\Entity\EntityQueue $queue
   *
   * @return array
   */
  public function buildPermissions(EntityQueue $queue) {
    $queue_id = $queue->id();

    if ($queue->getHandlerPlugin()->supportsMultipleSubqueues()) {
      $permissions["create $queue_id entityqueue"] = [
        'title' => $this->t('Add %queue subqueues', ['%queue' => $queue->label()]),
        'description' => $this->t('Access to create new subqueue to the %queue queue.', ['%queue' => $queue->label()]),
      ];
      $permissions["delete $queue_id entityqueue"] = [
        'title' => $this->t('Delete %queue subqueues', ['%queue' => $queue->label()]),
        'description' => $this->t('Access to delete subqueues of the %queue queue.', ['%queue' => $queue->label()]),
      ];
    }

    $permissions["update $queue_id entityqueue"] = [
      'title' => $this->t('Manipulate %queue queue', ['%queue' => $queue->label()]),
      'description' => $this->t('Access to update the %queue queue.', ['%queue' => $queue->label()]),
    ];

    return $permissions;
  }

}
