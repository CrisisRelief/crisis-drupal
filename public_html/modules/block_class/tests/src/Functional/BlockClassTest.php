<?php

namespace Drupal\Tests\block_class\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the custom CSS classes for blocks.
 *
 * @group block_class
 */
class BlockClassTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['block', 'block_class'];

  /**
   * Tests the custom CSS classes for blocks.
   */
  public function testBlockClass() {

    $admin_user = $this->drupalCreateUser([
      'administer block classes',
      'administer blocks',
    ]);
    $this->drupalLogin($admin_user);

    // Add a content block with custom CSS class.
    $this->drupalGet('admin/structure/block/add/system_main_block/classy', ['query' => ['region' => 'content']]);
    $edit = [
      'region' => 'content',
      'third_party_settings[block_class][classes]' => 'TestClass_content',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save block'));

    // Add a user account menu with a custom CSS class.
    $this->drupalGet('admin/structure/block/add/system_menu_block:account/classy', ['query' => ['region' => 'content']]);
    $edit = [
      'region' => 'secondary_menu',
      'third_party_settings[block_class][classes]' => 'TestClass_menu',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save block'));

    // Go to the front page of the user.
    $this->drupalGet('<front>');
    // Assert the custom class in the content block.
    $this->assertRaw('<div id="block-mainpagecontent" class="TestClass_content block block-system block-system-main-block">');
    // Assert the custom class in user menu.
    $this->assertRaw('<nav role="navigation" aria-labelledby="block-useraccountmenu-menu" id="block-useraccountmenu" class="TestClass_menu block block-menu navigation menu--account">');
  }

}
