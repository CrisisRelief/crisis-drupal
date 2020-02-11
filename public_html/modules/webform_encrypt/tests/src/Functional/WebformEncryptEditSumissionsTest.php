<?php

namespace Drupal\Tests\webform_encrypt\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests editing of encrypted webform submissions.
 *
 * @group webform_encrypt
 */
class WebformEncryptEditSumissionsTest extends BrowserTestBase {

  /**
   * The user that can not view encrypted webform submissions.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $notViewEncryptedUser;

  /**
   * The user that can view encrypted webform submissions.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $viewEncryptedUser;

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
  protected function setUp() {
    parent::setUp();
    $this->notViewEncryptedUser = $this->drupalCreateUser([
      'view any webform submission',
      'edit any webform submission',
    ]);
    $this->viewEncryptedUser = $this->drupalCreateUser([
      'view any webform submission',
      'edit any webform submission',
      'view encrypted values',
    ]);
  }

  /**
   * Test webform field encryption.
   */
  public function testEditSubmissions() {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->notViewEncryptedUser);
    // Make a submission.
    $edit = [
      'test_text_field' => 'Test text field encrypted value',
      'test_text_area' => 'Test text area encrypted value',
      'test_not_encrypted' => 'Test not encrypted value',
    ];
    $this->drupalPostForm('/webform/test_encryption', $edit, 'Submit');
    $assert_session->responseContains('New submission added to Test encryption.');

    // Ensure form is not accessible by user without the view encrypted values
    // permission.
    $edit_submission_path = 'admin/structure/webform/manage/test_encryption/submission/1/edit';
    $this->drupalGet($edit_submission_path);
    $assert_session->statusCodeEquals(403);
    $assert_session->responseContains('You are not authorized to access this page.');

    // Verify with the view encrypted values permission that form submission is
    // editable by user with the view encrypted values permission.
    $this->drupalLogin($this->viewEncryptedUser);
    $this->drupalGet($edit_submission_path);
    $assert_session->fieldValueEquals('test_text_field', $edit['test_text_field']);
    $assert_session->fieldValueEquals('test_text_area', $edit['test_text_area']);
    $assert_session->fieldValueEquals('test_not_encrypted', $edit['test_not_encrypted']);
    // Save the form without changing any values.
    $this->drupalPostForm($edit_submission_path, [], 'Save');
    // Check submission is still editeable and values are unchanged.
    $this->drupalGet($edit_submission_path);
    $assert_session->fieldValueEquals('test_text_field', $edit['test_text_field']);
    $assert_session->fieldValueEquals('test_text_area', $edit['test_text_area']);
    $assert_session->fieldValueEquals('test_not_encrypted', $edit['test_not_encrypted']);
  }

}
