<?php

namespace Drupal\Tests\webform_encrypt\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Uninstall test for the webform_encrypt module.
 *
 * @group webform_encrypt
 */
class WebformEncryptUninstallTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'webform_encrypt',
    'webform_encrypt_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * Test webform encrypt uninstall hook.
   */
  public function testUninstall() {
    $assert_session = $this->assertSession();

    // Log in as normal user.
    $user = $this->drupalCreateUser(['view any webform submission']);
    $this->drupalLogin($user);

    // Make a submission.
    $edit = [
      'test_text_field' => 'Test text field value',
      'test_text_area' => 'Test text area value',
      'test_not_encrypted' => 'Test not encrypted value',
      'test_multiple_text_field[items][0][_item_]' => 'Test multiple text field value 1',
      'test_address_field[address]' => 'Test multiple address field address',
      'test_address_field[address_2]' => 'Test multiple address field address 2',
      'test_address_field[city]' => 'Test multiple address field city',
      'test_address_field[state_province]' => 'California',
      'test_address_field[postal_code]' => 'AA11AA',
      'test_address_field[country]' => 'United Kingdom',
      'test_multiple_address_field[items][0][_item_][address]' => 'Test multiple address field address',
      'test_multiple_address_field[items][0][_item_][address_2]' => 'Test multiple address field address 2',
      'test_multiple_address_field[items][0][_item_][city]' => 'Test multiple address field city',
      'test_multiple_address_field[items][0][_item_][state_province]' => 'California',
      'test_multiple_address_field[items][0][_item_][postal_code]' => 'AA11AA',
      'test_multiple_address_field[items][0][_item_][country]' => 'United Kingdom',
    ];
    $this->drupalPostForm('/webform/test_encryption', $edit, 'Submit');
    $assert_session->responseContains('New submission added to Test encryption.');

    // Uninstall the module.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/admin/modules/uninstall');
    $this->drupalPostForm('admin/modules/uninstall', ['uninstall[webform_encrypt_test]' => TRUE], 'Uninstall');
    $this->drupalPostForm(NULL, [], 'Uninstall');
    $assert_session->pageTextContains('The selected modules have been uninstalled.');
    $assert_session->pageTextNotContains('Webform Encrypt Test');
    $this->drupalPostForm('admin/modules/uninstall', ['uninstall[webform_encrypt]' => TRUE], 'Uninstall');
    $this->drupalPostForm(NULL, [], 'Uninstall');
    $assert_session->pageTextContains('The selected modules have been uninstalled.');
    $assert_session->pageTextNotContains('Webform Encrypt');

    // Ensure that all fields show unencrypted values for normal users.
    $this->drupalLogin($user);
    $this->drupalGet('admin/structure/webform/manage/test_encryption/results/submissions');
    $assert_session->responseContains($edit['test_text_field']);
    $assert_session->responseContains($edit['test_text_field']);
    $assert_session->responseContains($edit['test_not_encrypted']);
    $assert_session->responseContains($edit['test_multiple_text_field[items][0][_item_]']);
    $assert_session->responseContains($edit['test_address_field[address]']);
    $assert_session->responseContains($edit['test_address_field[address_2]']);
    $assert_session->responseContains($edit['test_address_field[city]']);
    $assert_session->responseContains($edit['test_address_field[state_province]']);
    $assert_session->responseContains($edit['test_address_field[postal_code]']);
    $assert_session->responseContains($edit['test_address_field[country]']);
    $assert_session->responseContains($edit['test_multiple_address_field[items][0][_item_][address]']);
    $assert_session->responseContains($edit['test_multiple_address_field[items][0][_item_][address_2]']);
    $assert_session->responseContains($edit['test_multiple_address_field[items][0][_item_][city]']);
    $assert_session->responseContains($edit['test_multiple_address_field[items][0][_item_][state_province]']);
    $assert_session->responseContains($edit['test_multiple_address_field[items][0][_item_][postal_code]']);
    $assert_session->responseContains($edit['test_multiple_address_field[items][0][_item_][country]']);
    $this->drupalGet('admin/structure/webform/manage/test_encryption/submission/1');
    $assert_session->elementTextContains('css', '.form-item-test-text-field', $edit['test_text_field']);
    $assert_session->elementTextContains('css', '.form-item-test-text-area', $edit['test_text_area']);
    $assert_session->elementTextContains('css', '.form-item-test-not-encrypted', $edit['test_not_encrypted']);
    $assert_session->elementTextContains('css', '.form-item-test-multiple-text-field', $edit['test_multiple_text_field[items][0][_item_]']);
    $assert_session->elementTextContains('css', '.form-item-test-address-field', $edit['test_address_field[address]']);
    $assert_session->elementTextContains('css', '.form-item-test-address-field', $edit['test_address_field[address_2]']);
    $assert_session->elementTextContains('css', '.form-item-test-address-field', $edit['test_address_field[city]']);
    $assert_session->elementTextContains('css', '.form-item-test-address-field', $edit['test_address_field[state_province]']);
    $assert_session->elementTextContains('css', '.form-item-test-address-field', $edit['test_address_field[postal_code]']);
    $assert_session->elementTextContains('css', '.form-item-test-address-field', $edit['test_address_field[country]']);
    $assert_session->elementTextContains('css', '.form-item-test-multiple-address-field', $edit['test_multiple_address_field[items][0][_item_][address]']);
    $assert_session->elementTextContains('css', '.form-item-test-multiple-address-field', $edit['test_multiple_address_field[items][0][_item_][address_2]']);
    $assert_session->elementTextContains('css', '.form-item-test-multiple-address-field', $edit['test_multiple_address_field[items][0][_item_][city]']);
    $assert_session->elementTextContains('css', '.form-item-test-multiple-address-field', $edit['test_multiple_address_field[items][0][_item_][state_province]']);
    $assert_session->elementTextContains('css', '.form-item-test-multiple-address-field', $edit['test_multiple_address_field[items][0][_item_][postal_code]']);
    $assert_session->elementTextContains('css', '.form-item-test-multiple-address-field', $edit['test_multiple_address_field[items][0][_item_][country]']);

    // Ensure we can make submissions after uninstalling.
    $edit = [
      'test_text_field' => 'Test text test_text_field value',
      'test_text_area' => 'Test text test_text_area value',
      'test_not_encrypted' => 'Test text test_not_encrypted value',
      'test_multiple_text_field[items][0][_item_]' => 'Test multiple text field value 1',
      'test_address_field[address]' => 'Test multiple address field address',
      'test_address_field[address_2]' => 'Test multiple address field address 2',
      'test_address_field[city]' => 'Test multiple address field city',
      'test_address_field[state_province]' => 'California',
      'test_address_field[postal_code]' => 'AA11AA',
      'test_address_field[country]' => 'United Kingdom',
      'test_multiple_address_field[items][0][_item_][address]' => 'Test multiple address field address',
      'test_multiple_address_field[items][0][_item_][address_2]' => 'Test multiple address field address 2',
      'test_multiple_address_field[items][0][_item_][city]' => 'Test multiple address field city',
      'test_multiple_address_field[items][0][_item_][state_province]' => 'California',
      'test_multiple_address_field[items][0][_item_][postal_code]' => 'AA11AA',
      'test_multiple_address_field[items][0][_item_][country]' => 'United Kingdom',
    ];
    $this->drupalPostForm('/webform/test_encryption', $edit, 'Submit');
    $assert_session->responseContains('New submission added to Test encryption.');
    $this->drupalGet('admin/structure/webform/manage/test_encryption/results/submissions');
    $assert_session->responseContains($edit['test_text_field']);
    $assert_session->responseContains($edit['test_not_encrypted']);
    $assert_session->responseContains($edit['test_multiple_text_field[items][0][_item_]']);
    $assert_session->responseContains($edit['test_address_field[address]']);
    $assert_session->responseContains($edit['test_address_field[address_2]']);
    $assert_session->responseContains($edit['test_address_field[city]']);
    $assert_session->responseContains($edit['test_address_field[state_province]']);
    $assert_session->responseContains($edit['test_address_field[postal_code]']);
    $assert_session->responseContains($edit['test_address_field[country]']);
    $assert_session->responseContains($edit['test_multiple_address_field[items][0][_item_][address]']);
    $assert_session->responseContains($edit['test_multiple_address_field[items][0][_item_][address_2]']);
    $assert_session->responseContains($edit['test_multiple_address_field[items][0][_item_][city]']);
    $assert_session->responseContains($edit['test_multiple_address_field[items][0][_item_][state_province]']);
    $assert_session->responseContains($edit['test_multiple_address_field[items][0][_item_][postal_code]']);
    $assert_session->responseContains($edit['test_multiple_address_field[items][0][_item_][country]']);
    $this->drupalGet('admin/structure/webform/manage/test_encryption/submission/2');
    $assert_session->elementTextContains('css', '.form-item-test-text-field', $edit['test_text_field']);
    $assert_session->elementTextContains('css', '.form-item-test-text-area', $edit['test_text_area']);
    $assert_session->elementTextContains('css', '.form-item-test-not-encrypted', $edit['test_not_encrypted']);
    $assert_session->elementTextContains('css', '.form-item-test-multiple-text-field', $edit['test_multiple_text_field[items][0][_item_]']);
    $assert_session->elementTextContains('css', '.form-item-test-address-field', $edit['test_address_field[address]']);
    $assert_session->elementTextContains('css', '.form-item-test-address-field', $edit['test_address_field[address_2]']);
    $assert_session->elementTextContains('css', '.form-item-test-address-field', $edit['test_address_field[city]']);
    $assert_session->elementTextContains('css', '.form-item-test-address-field', $edit['test_address_field[state_province]']);
    $assert_session->elementTextContains('css', '.form-item-test-address-field', $edit['test_address_field[postal_code]']);
    $assert_session->elementTextContains('css', '.form-item-test-address-field', $edit['test_address_field[country]']);
    $assert_session->elementTextContains('css', '.form-item-test-multiple-address-field', $edit['test_multiple_address_field[items][0][_item_][address]']);
    $assert_session->elementTextContains('css', '.form-item-test-multiple-address-field', $edit['test_multiple_address_field[items][0][_item_][address_2]']);
    $assert_session->elementTextContains('css', '.form-item-test-multiple-address-field', $edit['test_multiple_address_field[items][0][_item_][city]']);
    $assert_session->elementTextContains('css', '.form-item-test-multiple-address-field', $edit['test_multiple_address_field[items][0][_item_][state_province]']);
    $assert_session->elementTextContains('css', '.form-item-test-multiple-address-field', $edit['test_multiple_address_field[items][0][_item_][postal_code]']);
    $assert_session->elementTextContains('css', '.form-item-test-multiple-address-field', $edit['test_multiple_address_field[items][0][_item_][country]']);
  }

}
