<?php

namespace Drupal\base;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Template\Attribute;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;

class Theme {

  /**
   * @var \Drupal\base\Theme
   */
  protected static $_instance;

  /**
   * @return \Drupal\base\Theme
   */
  public static function getInstance() {
    if (!self::$_instance) {
      self::$_instance = new self();
    }
    return self::$_instance;
  }

  /**
   * @return \Drupal\Core\Template\Attribute
   */
  public static function getSiteWrapClasses(&$vars) {
    $wrap = new Attribute();
    $wrap->addClass('site-wrap');
    if ($vars['is_front']) {
      $wrap->addClass('home-wrap');
    }
    $vars['wrap'] = $wrap;
  }

  public static function getThemeSuggestionsForBlocks(array &$suggestions, array $vars) {
    if (isset($vars['elements']['content']['#block_content'])) {
      $suggestions[] = 'block__custom__' . $vars['elements']['content']['#block_content']->bundle();
    }
    if (isset($vars['elements']['content']['#form_id'])) {
      $suggestions[] = 'block__' . $vars['elements']['content']['#form_id'];
    }
  }

  public static function getThemeSuggestionsForParagraphs(array &$suggestions, array $vars) {

  }

}
