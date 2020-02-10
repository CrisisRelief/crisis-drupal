<?php

namespace Drupal\Tests\webform_encrypt\Kernel;

use Drupal\encrypt\Entity\EncryptionProfile;
use Drupal\KernelTests\KernelTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests webform submission storage.
 *
 * @group webform_encrypt
 */
class WebformEncryptSubmissionStorageTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'user',
    'path',
    'field',
    'webform',
    'webform_encrypt_test',
    'key',
    'encrypt',
    'encrypt_test',
    'webform_encrypt',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installSchema('webform', ['webform']);

    $this->installConfig('webform');
    $this->installConfig('encrypt');
    $this->installConfig('webform_encrypt_test');
    $this->installConfig('webform_encrypt');
    $this->installConfig('key');

    $this->installEntitySchema('key');
    $this->installEntitySchema('webform_submission');
    $this->installEntitySchema('user');
  }

  /**
   * Test encryption and decryption.
   */
  public function testEncryptDecrypt() {

    // Create a new webform.
    $webform = Webform::load('test_encryption');
    $values = [
      'id' => 'webform_submission_test',
      'webform_id' => $webform->id(),
      'data' => [
        'test_text_field' => 'Test text field value',
        'test_multiple_text_field' => [
          0 => 'Test multiple text field value 1',
        ],
        'test_text_area' => 'Test text area value',
        'test_not_encrypted' => 'Test not encrypted value',
        'test_address_field' => [
          'address' => 'Test address field address',
          'address_2' => 'Test address field address 2',
          'city' => 'Test address field city',
          'state_province' => 'California',
          'postal_code' => 'AA11AA',
          'country' => 'United Kingdom',
        ],
        'test_multiple_address_field' => [
          0 => [
            'address' => 'Test multiple address field address',
            'address_2' => 'Test multiple address field address 2',
            'city' => 'Test multiple address field city',
            'state_province' => 'California',
            'postal_code' => 'AA11AA',
            'country' => 'United Kingdom',
          ],
        ],
      ],
    ];
    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = WebformSubmission::create($values);
    $webform_submission->setData(\Drupal::entityTypeManager()
      ->getStorage("webform_submission")
      ->encryptElements($webform_submission->getData(), $webform));

    // Ensure UUIDs match.
    $this->assertEquals($webform->uuid(), $webform_submission->getWebform()
      ->uuid());

    $data = $webform_submission->getData();

    // Get the encryption profile.
    $config = $webform->getThirdPartySetting('webform_encrypt', 'element');

    $encryptionService = $this->container->get('encryption');

    // Test the encrypted is encrypted.
    $this->assertEquals(unserialize($data['test_text_field'])['data'], $encryptionService->encrypt('Test text field value', EncryptionProfile::load($config['test_text_field']['encrypt_profile'])));
    $this->assertEquals(unserialize($data['test_multiple_text_field'][0])['data'], $encryptionService->encrypt('Test multiple text field value 1', EncryptionProfile::load($config['test_multiple_text_field']['encrypt_profile'])));
    $this->assertEquals(unserialize($data['test_text_area'])['data'], $encryptionService->encrypt('Test text area value', EncryptionProfile::load($config['test_text_area']['encrypt_profile'])));

    $this->assertEquals(unserialize($data['test_address_field']['address'])['data'], $encryptionService->encrypt('Test address field address', EncryptionProfile::load($config['test_address_field']['encrypt_profile'])));
    $this->assertEquals(unserialize($data['test_address_field']['address_2'])['data'], $encryptionService->encrypt('Test address field address 2', EncryptionProfile::load($config['test_address_field']['encrypt_profile'])));
    $this->assertEquals(unserialize($data['test_address_field']['city'])['data'], $encryptionService->encrypt('Test address field city', EncryptionProfile::load($config['test_address_field']['encrypt_profile'])));
    $this->assertEquals(unserialize($data['test_address_field']['state_province'])['data'], $encryptionService->encrypt('California', EncryptionProfile::load($config['test_address_field']['encrypt_profile'])));
    $this->assertEquals(unserialize($data['test_address_field']['postal_code'])['data'], $encryptionService->encrypt('AA11AA', EncryptionProfile::load($config['test_address_field']['encrypt_profile'])));
    $this->assertEquals(unserialize($data['test_address_field']['country'])['data'], $encryptionService->encrypt('United Kingdom', EncryptionProfile::load($config['test_address_field']['encrypt_profile'])));

    $this->assertEquals(unserialize($data['test_multiple_address_field'][0]['address'])['data'], $encryptionService->encrypt('Test multiple address field address', EncryptionProfile::load($config['test_multiple_address_field']['encrypt_profile'])));
    $this->assertEquals(unserialize($data['test_multiple_address_field'][0]['address_2'])['data'], $encryptionService->encrypt('Test multiple address field address 2', EncryptionProfile::load($config['test_multiple_address_field']['encrypt_profile'])));
    $this->assertEquals(unserialize($data['test_multiple_address_field'][0]['city'])['data'], $encryptionService->encrypt('Test multiple address field city', EncryptionProfile::load($config['test_multiple_address_field']['encrypt_profile'])));
    $this->assertEquals(unserialize($data['test_multiple_address_field'][0]['state_province'])['data'], $encryptionService->encrypt('California', EncryptionProfile::load($config['test_multiple_address_field']['encrypt_profile'])));
    $this->assertEquals(unserialize($data['test_multiple_address_field'][0]['postal_code'])['data'], $encryptionService->encrypt('AA11AA', EncryptionProfile::load($config['test_multiple_address_field']['encrypt_profile'])));
    $this->assertEquals(unserialize($data['test_multiple_address_field'][0]['country'])['data'], $encryptionService->encrypt('United Kingdom', EncryptionProfile::load($config['test_multiple_address_field']['encrypt_profile'])));

  }

  /**
   * Tests 'preSave' handlers are called only once.
   */
  public function testPreSaveHandlersAreCalledOnce() {
    $this->assertEquals(NULL, $this->container->get('state')->get('test_webform_handler_counter'));

    $webform = Webform::load('test_encryption');
    $values = [
      'id' => 'webform_submission_test',
      'webform_id' => $webform->id(),
      'data' => [
        'test_text_field' => 'Test text field value',
        'test_multiple_text_field' => [
          0 => 'Test multiple text field value 1',
        ],
        'test_text_area' => 'Test text area value',
        'test_not_encrypted' => 'Test not encrypted value',
        'test_address_field' => [
          'address' => 'Test address field address',
          'address_2' => 'Test address field address 2',
          'city' => 'Test address field city',
          'state_province' => 'California',
          'postal_code' => 'AA11AA',
          'country' => 'United Kingdom',
        ],
        'test_multiple_address_field' => [
          0 => [
            'address' => 'Test multiple address field address',
            'address_2' => 'Test multiple address field address 2',
            'city' => 'Test multiple address field city',
            'state_province' => 'California',
            'postal_code' => 'AA11AA',
            'country' => 'United Kingdom',
          ],
        ],
      ],
    ];
    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = WebformSubmission::create($values);
    $webform_submission->save();

    $this->assertEquals(1, $this->container->get('state')->get('test_webform_handler_presave_counter'));
  }

  /**
   * Tests access to decrypted data after 'postSave'.
   */
  public function testAccessDecryptedDataPostSave() {
    $this->assertEquals(NULL, $this->container->get('state')->get('test_webform_handler_counter'));

    $webform = Webform::load('test_encryption');
    $values = [
      'id' => 'webform_submission_test',
      'webform_id' => $webform->id(),
      'data' => [
        'test_text_field' => 'Test text field value',
        'test_multiple_text_field' => [
          0 => 'Test multiple text field value 1',
        ],
        'test_text_area' => 'Test text area value',
        'test_not_encrypted' => 'Test not encrypted value',
        'test_address_field' => [
          'address' => 'Test address field address',
          'address_2' => 'Test address field address 2',
          'city' => 'Test address field city',
          'state_province' => 'California',
          'postal_code' => 'AA11AA',
          'country' => 'United Kingdom',
        ],
        'test_multiple_address_field' => [
          0 => [
            'address' => 'Test multiple address field address',
            'address_2' => 'Test multiple address field address 2',
            'city' => 'Test multiple address field city',
            'state_province' => 'California',
            'postal_code' => 'AA11AA',
            'country' => 'United Kingdom',
          ],
        ],
      ],
    ];
    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = WebformSubmission::create($values);
    $webform_submission->save();

    $data = $webform_submission->getData();

    $this->assertEquals('Test text field value', $data['test_text_field']);
  }

}
