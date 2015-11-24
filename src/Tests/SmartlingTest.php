<?php
/**
 * @file
 * Contains \Drupal\tmgmt_smartling\Tests\SmartlingTest.
 */

namespace Drupal\tmgmt_smartling\Tests;
use Drupal\tmgmt\Entity\Translator;
use Drupal\tmgmt\Tests\TMGMTTestBase;
use Drupal\Core\Url;

/**
 * Basic tests for the Smartling translator.
 *
 * @group tmgmt_smartling
 */
class SmartlingTest extends TMGMTTestBase {

  /**
   * A tmgmt_translator with a server mock.
   *
   * @var Translator
   */
  protected $translator;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('tmgmt_smartling', 'tmgmt_smartling_test');

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->addLanguage('de');
    $this->translator = $this->createTranslator([
      'plugin' => 'smartling',
      'settings' => [
        'url' => URL::fromUri('base://tmgmt_smartling_mock/v2/Http.svc', array('absolute' => TRUE))->toString(),
      ],
    ]);
  }

  /**
   * Tests basic API methods of the plugin.
   */
  protected function testSmartling() {
    $job = $this->createJob();
    $job->translator = $this->translator->id();
    $item = $job->addItem('test_source', 'test', '1');
    $item->save();

    $this->assertFalse($job->isTranslatable(), 'Check if the translator is not available at this point because we did not define the API parameters.');

    // Save a wrong client ID key.
    $this->translator->setSetting('client_id', 'wrong client_id');
    $this->translator->setSetting('client_secret', 'wrong client_secret');
    $this->translator->save();

    $translator = $job->getTranslator();
    $languages = $translator->getSupportedTargetLanguages('en');
    $this->assertTrue(empty($languages), t('We can not get the languages using wrong api parameters.'));

    // Save a correct client ID.
    $translator->setSetting('client_id', 'correct client_id');
    $translator->setSetting('client_secret', 'correct client_secret');
    $translator->save();

    // Make sure the translator returns the correct supported target languages.
    $translator->clearLanguageCache();
    $languages = $translator->getSupportedTargetLanguages('en');
    $this->assertTrue(isset($languages['de']));
    $this->assertTrue(isset($languages['es']));
    $this->assertTrue(isset($languages['it']));
    $this->assertTrue(isset($languages['zh-hans']));
    $this->assertTrue(isset($languages['zh-hant']));
    $this->assertFalse(isset($languages['zh-CHS']));
    $this->assertFalse(isset($languages['zh-CHT']));
    $this->assertFalse(isset($languages['en']));

    $this->assertTrue($job->canRequestTranslation()->getSuccess());

    $job->requestTranslation();

    // Now it should be needs review.
    foreach ($job->getItems() as $item) {
      $this->assertTrue($item->isNeedsReview());
    }

    $items = $job->getItems();
    $item = end($items);
    $data = $item->getData();
    $this->assertEqual('Hallo Welt', $data['dummy']['deep_nesting']['#translation']['#text']);
  }

  /**
   * Tests the UI of the plugin.
   */
  protected function testSmartlingUi() {
    $this->loginAsAdmin();
    $edit = [
      'settings[client_id]' => 'wrong client_id',
      'settings[client_secret]' => 'wrong client_secret',
    ];
    $this->drupalPostForm('admin/config/regional/tmgmt_translator/manage/' . $this->translator->id(), $edit, t('Save'));
    $this->assertText(t('The "Client ID", the "Client secret" or both are not correct.'));
    $edit = [
      'settings[client_id]' => 'correct client_id',
      'settings[client_secret]' => 'correct client_secret',
    ];
    $this->drupalPostForm('admin/config/regional/tmgmt_translator/manage/' . $this->translator->id(), $edit, t('Save'));
    $this->assertText(t('@label configuration has been updated.', ['@label' => $this->translator->label()]));
  }

}
