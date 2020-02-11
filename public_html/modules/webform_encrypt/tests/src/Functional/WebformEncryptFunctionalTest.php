<?php

namespace Drupal\Tests\webform_encrypt\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\Role;

/**
 * Functional tests for the webform_encrypt module.
 *
 * @group webform_encrypt
 */
class WebformEncryptFunctionalTest extends BrowserTestBase {

  /**
   * The admin user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'webform_encrypt',
    'webform_encrypt_test',
    'webform_ui',
    'block',
  ];

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = [
    'access administration pages',
    'view any webform submission',
    'edit any webform',
  ];

  /**
   * Sets the test up.
   */
  protected function setUp() {
    parent::setUp();
    // Test admin user.
    $this->adminUser = $this->drupalCreateUser($this->permissions);
    $this->drupalPlaceBlock('local_actions_block');
  }

  /**
   * Test webform field encryption.
   */
  public function testFieldEncryption() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalLogin($this->adminUser);
    $encrypted_value = '[Value Encrypted]';

    // Test admin functionality.
    $this->drupalGet('admin/structure/webform/manage/test_encryption');

    // Add a new element and set encryption on it.
    $page->clickLink('Add element');
    $page->clickLink('Date');
    $edit = [
      'key' => 'test_date',
      'title' => 'Test date',
      'encrypt' => TRUE,
      'encrypt_profile' => 'test_encryption_profile',
    ];
    $this->submitForm($edit, 'Save');
    $assert_session->responseContains('<em class="placeholder">Test date</em> has been created');

    // Verify settings are saved and displayed in the edit element form.
    $this->drupalGet('admin/structure/webform/manage/test_encryption/element/test_date/edit');
    $assert_session->checkboxChecked('encrypt');
    $assert_session->fieldValueEquals('encrypt_profile', $edit['encrypt_profile']);

    // Make a submission.
    $edit = [
      'test_text_field' => 'Test text field value',
      'test_multiple_text_field[items][0][_item_]' => 'Test multiple text field value 1',
      'test_text_area' => 'Test text area value',
      'test_not_encrypted' => 'Test not encrypted value',

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

      'test_date' => '2019-09-15',
    ];
    $this->drupalPostForm('/webform/test_encryption', $edit, 'Submit');
    $assert_session->responseContains('New submission added to Test encryption.');

    // Ensure encrypted fields do not show values.
    $this->drupalGet('admin/structure/webform/manage/test_encryption/results/submissions');
    $assert_session->responseNotContains($edit['test_text_field']);
    $assert_session->responseNotContains($edit['test_multiple_text_field[items][0][_item_]']);
    $assert_session->responseNotContains($edit['test_text_area']);
    $assert_session->responseContains($edit['test_not_encrypted']);
    $assert_session->responseNotContains($edit['test_address_field[address]']);
    $assert_session->responseNotContains($edit['test_address_field[address_2]']);
    $assert_session->responseNotContains($edit['test_address_field[city]']);
    $assert_session->responseNotContains($edit['test_address_field[state_province]']);
    $assert_session->responseNotContains($edit['test_address_field[postal_code]']);
    $assert_session->responseNotContains($edit['test_address_field[country]']);

    $assert_session->responseNotContains($edit['test_multiple_address_field[items][0][_item_][address]']);
    $assert_session->responseNotContains($edit['test_multiple_address_field[items][0][_item_][address_2]']);
    $assert_session->responseNotContains($edit['test_multiple_address_field[items][0][_item_][city]']);
    $assert_session->responseNotContains($edit['test_multiple_address_field[items][0][_item_][state_province]']);
    $assert_session->responseNotContains($edit['test_multiple_address_field[items][0][_item_][postal_code]']);
    $assert_session->responseNotContains($edit['test_multiple_address_field[items][0][_item_][country]']);

    $assert_session->responseNotContains($edit['test_date']);

    $submission_path = 'admin/structure/webform/manage/test_encryption/submission/1';
    $this->drupalGet($submission_path);

    $text_selector = '.form-item-test-text-field';
    $text_multiple_selector = '.form-item-test-multiple-text-field';
    $area_selector = '.form-item-test-text-area';
    $not_encrypted_selector = '.form-item-test-not-encrypted';
    $address_field_address_selector = '.form-item-test-address-field';
    $multiple_address_field_address_selector = '.form-item-test-multiple-address-field';
    $date_selector = '.form-item-test-date';

    $assert_session->elementTextContains('css', $text_selector, $encrypted_value);
    $assert_session->elementTextNotContains('css', $text_selector, $edit['test_text_field']);
    $assert_session->elementTextContains('css', $text_multiple_selector, $encrypted_value);
    $assert_session->elementTextNotContains('css', $text_multiple_selector, $edit['test_multiple_text_field[items][0][_item_]']);
    $assert_session->elementTextContains('css', $area_selector, $encrypted_value);
    $assert_session->elementTextNotContains('css', $area_selector, $edit['test_text_area']);
    $assert_session->elementTextContains('css', $not_encrypted_selector, $edit['test_not_encrypted']);
    $assert_session->elementTextNotContains('css', $not_encrypted_selector, $encrypted_value);

    $assert_session->elementTextNotContains('css', $address_field_address_selector, $edit['test_address_field[address]']);
    $assert_session->elementTextNotContains('css', $address_field_address_selector, $edit['test_address_field[address_2]']);
    $assert_session->elementTextNotContains('css', $address_field_address_selector, $edit['test_address_field[city]']);
    $assert_session->elementTextNotContains('css', $address_field_address_selector, $edit['test_address_field[state_province]']);
    $assert_session->elementTextNotContains('css', $address_field_address_selector, $edit['test_address_field[postal_code]']);
    $assert_session->elementTextNotContains('css', $address_field_address_selector, $edit['test_address_field[country]']);

    $assert_session->elementTextNotContains('css', $multiple_address_field_address_selector, $edit['test_multiple_address_field[items][0][_item_][address]']);
    $assert_session->elementTextNotContains('css', $multiple_address_field_address_selector, $edit['test_multiple_address_field[items][0][_item_][address_2]']);
    $assert_session->elementTextNotContains('css', $multiple_address_field_address_selector, $edit['test_multiple_address_field[items][0][_item_][city]']);
    $assert_session->elementTextNotContains('css', $multiple_address_field_address_selector, $edit['test_multiple_address_field[items][0][_item_][state_province]']);
    $assert_session->elementTextNotContains('css', $multiple_address_field_address_selector, $edit['test_multiple_address_field[items][0][_item_][postal_code]']);
    $assert_session->elementTextNotContains('css', $multiple_address_field_address_selector, $edit['test_multiple_address_field[items][0][_item_][country]']);

    $assert_session->elementTextNotContains('css', $date_selector, $edit['test_date']);

    // Grant user access to view encrypted values and check again.
    $this->grantPermissions(Role::load($this->adminUser->getRoles()[0]), ['view encrypted values']);
    $this->drupalGet($submission_path);
    $assert_session->responseNotContains($encrypted_value);
    $assert_session->responseContains($edit['test_text_field']);
    $assert_session->responseContains($edit['test_multiple_text_field[items][0][_item_]']);
    $assert_session->responseContains($edit['test_text_area']);
    $assert_session->responseContains($edit['test_not_encrypted']);
    $assert_session->responseContains($edit['test_address_field[address]']);
    $assert_session->responseContains($edit['test_multiple_address_field[items][0][_item_][address]']);
    $assert_session->responseContains($edit['test_date']);
  }

}
