<?php

namespace Drupal\Tests\webform_encrypt\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests encrypted multistep wizard forms.
 *
 * @group webform_encrypt
 */
class WebformEncryptWizardTest extends BrowserTestBase {

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
  ];

  /**
   * Sets the test up.
   */
  protected function setUp() {
    parent::setUp();
    // Test admin user.
    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'view any webform submission',
      'edit any webform',
    ]);
  }

  /**
   * Verifies multipage (wizard) forms work correctly with encryption.
   */
  public function testWizardEncrypted() {
    $assert_session = $this->assertSession();
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('webform/test_wizard_encryption');

    // Enter values on the first page.
    // The Last Name field is encrypted.
    $edit = [
      'test_first_name_field' => 'FirstNameTest',
      'test_last_name_field' => 'LastNameTest',
      'test_gender_field' => 'Male',

    ];

    // Move to next page.
    $this->drupalPostForm(NULL, $edit, 'Next Page >');

    // Return to the first page and check the plain text value is still there.
    $this->drupalPostForm(NULL, NULL, '< Previous Page');
    $assert_session->fieldValueEquals('test_last_name_field', $edit['test_last_name_field']);

    // Providing the above assertion is correct move back to the second page.
    $this->drupalPostForm(NULL, NULL, 'Next Page >');

    // Enter a value on the second page.
    $edit = [
      'test_email_field' => 'testsubmission@test.test',
      'test_phone_field' => '+3333333333',
      'test_contact_via_phone_field' => 'Yes',

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

    // Post and move to third page.
    $this->drupalPostForm(NULL, $edit, 'Next Page >');

    // Return to the second page and check previously entered values.
    $this->drupalPostForm(NULL, NULL, '< Previous Page');
    $assert_session->fieldValueEquals('test_email_field', $edit['test_email_field']);
    $assert_session->fieldValueEquals('test_phone_field', $edit['test_phone_field']);
    $assert_session->fieldValueEquals('test_contact_via_phone_field', $edit['test_contact_via_phone_field']);

    $assert_session->fieldValueEquals('test_address_field[address]', $edit['test_address_field[address]']);
    $assert_session->fieldValueEquals('test_address_field[address_2]', $edit['test_address_field[address_2]']);
    $assert_session->fieldValueEquals('test_address_field[city]', $edit['test_address_field[city]']);
    $assert_session->fieldValueEquals('test_address_field[state_province]', $edit['test_address_field[state_province]']);
    $assert_session->fieldValueEquals('test_address_field[postal_code]', $edit['test_address_field[postal_code]']);
    $assert_session->fieldValueEquals('test_address_field[country]', $edit['test_address_field[country]']);

    $assert_session->fieldValueEquals('test_multiple_address_field[items][0][_item_][address]', $edit['test_multiple_address_field[items][0][_item_][address]']);
    $assert_session->fieldValueEquals('test_multiple_address_field[items][0][_item_][address_2]', $edit['test_multiple_address_field[items][0][_item_][address_2]']);
    $assert_session->fieldValueEquals('test_multiple_address_field[items][0][_item_][city]', $edit['test_multiple_address_field[items][0][_item_][city]']);
    $assert_session->fieldValueEquals('test_multiple_address_field[items][0][_item_][state_province]', $edit['test_multiple_address_field[items][0][_item_][state_province]']);
    $assert_session->fieldValueEquals('test_multiple_address_field[items][0][_item_][postal_code]', $edit['test_multiple_address_field[items][0][_item_][postal_code]']);
    $assert_session->fieldValueEquals('test_multiple_address_field[items][0][_item_][country]', $edit['test_multiple_address_field[items][0][_item_][country]']);

  }

}
