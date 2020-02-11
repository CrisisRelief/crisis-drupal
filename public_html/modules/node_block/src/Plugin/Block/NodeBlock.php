<?php

namespace Drupal\node_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\node\Entity\Node;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @Block(
 *   id = "node_block",
 *   admin_label = @Translation("Node Block")
 * )
 */
class NodeBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /** @var EntityFieldManagerInterface $entityManager */
  protected $entityManager;

  /** @var EntityViewBuilderInterface $viewBuilder */
  protected $viewBuilder;

  protected $view_mode = 'block';

  public function __construct(EntityFieldManagerInterface $entityManager, EntityViewBuilderInterface $viewBuilder, array $configuration, $plugin_id, $plugin_definition) {
    $this->entityManager = $entityManager;
    $this->viewBuilder = $viewBuilder;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $entityManager = \Drupal::service('entity_field.manager');
    $viewBuilder = \Drupal::entityTypeManager()->getViewBuilder('node');
    return new static($entityManager, $viewBuilder, $configuration, $plugin_id, $plugin_definition);
  }

  public function build() {
    $request = \Drupal::request();
    $node = $request->attributes->get('node');
    if ($node) {
      if (!is_object($node)) {
        $node = Node::load($node);
      }
    }

    $build = [];
    if ($node) {
      $fields = $this->entityManager->getFieldDefinitions('node', $node->bundle());
      foreach ($fields as $name => $definition) {
        if (!$node->get($name)->isEmpty()) {
          $build[$name] = $this->viewBuilder->viewField($node->get($name), $this->view_mode);
        }
      }
    }

    return $build;
  }

  public function defaultConfiguration() {
    return [
      'theme_suggestion' => '',
    ];
  }

  public function blockForm($form, FormStateInterface $form_state) {
    return [
      'theme_suggestion' => [
        '#type' => 'textfield',
        '#title' => $this->t('Theme Suggestion'),
        '#default_value' => $this->configuration['theme_suggestion'],
        '#description' => 'Creates a theme suggestion for block--node-block--&lt;theme suggestion&gt;.html.twig',
      ],
    ];
  }

  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['theme_suggestion'] = $form_state->getValue('theme_suggestion');
  }

  /**
   * @return \string[]
   */
  public function getCacheContexts() {
    return Cache::mergeContexts([
      'url.path',
    ], parent::getCacheContexts());
  }

  /**
   * @return array|\string[]
   */
  public function getCacheTags() {
    if (!$this->node) {
      return parent::getCacheTags();
    }
    return Cache::mergeTags([
      'node:' . $this->node->id(),
    ], parent::getCacheTags());
  }

}
