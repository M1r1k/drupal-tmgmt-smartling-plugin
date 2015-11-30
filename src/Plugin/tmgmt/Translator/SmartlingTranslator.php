<?php

/**
 * @file
 * Contains \Drupal\tmgmt_smartling\Plugin\tmgmt\Translator\SmartlingTranslator.
 */

namespace Drupal\tmgmt_smartling\Plugin\tmgmt\Translator;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\tmgmt\Entity\Translator;
use Drupal\tmgmt\Translator\TranslatableResult;
use Drupal\tmgmt\TranslatorPluginBase;
use Drupal\tmgmt\TranslatorInterface;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt_smartling\Smartling\SmartlingApi;
use GuzzleHttp\ClientInterface;
use Masterminds\HTML5\Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\tmgmt\Translator\AvailableResult;

/**
 * Smartling translator plugin.
 *
 * @TranslatorPlugin(
 *   id = "smartling",
 *   label = @Translation("Smargling translator"),
 *   description = @Translation("Smartling Translator service."),
 *   ui = "Drupal\tmgmt_smartling\SmartlingTranslatorUi"
 * )
 */
class SmartlingTranslator extends TranslatorPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Guzzle HTTP client.
   *
   * @var \Drupal\tmgmt_smartling\Smartling\SmartlingApi
   */
  protected $smartlingApi;

  /**
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * Constructs a LocalActionBase object.
   *
   * @param \GuzzleHttp\ClientInterface $client
   *   The Guzzle HTTP client.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(ClientInterface $client, array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->client = $client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('http_client'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function checkAvailable(TranslatorInterface $translator) {
    if ($translator->getSetting('api_url') && $translator->getSetting('project_id') && $translator->getSetting('key')) {
      return AvailableResult::yes();
    }
    return AvailableResult::no(t('@translator is not available. Make sure it is properly <a href=:configured>configured</a>.', [
      '@translator' => $translator->label(),
      ':configured' => $translator->url(),
     ]));
  }

  /**
   * {@inheritdoc}
   */
  public function checkTranslatable(TranslatorInterface $translator, JobInterface $job) {
    // Anything can be exported.
    return TranslatableResult::yes();
  }

  /**
   * {@inheritdoc}
   */
  public function requestTranslation(JobInterface $job) {
    $name = $this->getFileName($job);

    $export = \Drupal::service('plugin.manager.tmgmt_file.format')->createInstance($job->getSetting('export_format'));

    $path = $job->getSetting('scheme') . '://tmgmt_sources/' . $name;
    $dirname = dirname($path);
    if (file_prepare_directory($dirname, FILE_CREATE_DIRECTORY)) {
      $file = file_save_data($export->export($job), $path, FILE_EXISTS_REPLACE);
      \Drupal::service('file.usage')->add($file, 'tmgmt_file', 'tmgmt_job', $job->id());
      $job->submitted('Exported file can be downloaded <a href="@link">here</a>.', array('@link' => file_create_url($path)));
    }
    else {
      $e = new \Exception('It is not possible to create a directory ' . $dirname);
      watchdog_exception('tmgmt_smartling', $e);
      $job->rejected('Job has been rejected with following error: @error',
        array('@error' => $e->getMessage()), 'error');
    }

    try {
      if ($job->getTranslator()->getSetting('callback_url_use')) {
        $url = Url::fromRoute('tmgmt_smartling.push_callback', ['job' => $job->id()])->setOptions(array('absolute' => TRUE))->toString();
      }
      $this->getSmartlingApi($job->getTranslator())->uploadFile(\Drupal::service('file_system')->realpath($file->getFileUri()), $file->getFilename(), 'xliff', ['callbackUrl' => $url]);
    }
    catch (\Exception $e) {
      watchdog_exception('tmgmt_smartling', $e);
      $job->rejected('Job has been rejected with following error: @error uploading @file',
        array('@error' => $e->getMessage(), '@file' => $file->getFileUri()), 'error');
    }
    // @todo disallow to submit translation to unsupported language.
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedRemoteLanguages(TranslatorInterface $translator) {
    $languages = [];
    // Prevent access if the translator isn't configured yet.
    if (!$translator->getSetting('project_id')) {
      // @todo should be implemented by an Exception.
      return $languages;
    }
    try {
      $languages = $this->getSmartlingApi($translator)->getLocaleList();
    }
    catch (\Exception $e) {
      drupal_set_message($e->getMessage(),
        'Cannot get languages from the translator');
      return $languages;
    }

    return $languages;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultRemoteLanguagesMappings() {
    return array(
      'zh-hans' => 'zh-CHS',
      'zh-hant' => 'zh-CHT',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTargetLanguages(TranslatorInterface $translator, $source_language) {
    $remote_languages = $this->getSupportedRemoteLanguages($translator);
    // There are no language pairs, any supported language can be translated
    // into the others. If the source language is part of the languages,
    // then return them all, just remove the source language.
    if (array_key_exists($source_language, $remote_languages)) {
      unset($remote_languages[$source_language]);
      return $remote_languages;
    }

    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function hasCheckoutSettings(JobInterface $job) {
    return FALSE;
  }

  /**
   * Execute a request against the Smartling API.
   *
   * @param Translator $translator
   *   The translator entity to get the settings from.
   * @param $path
   *   The path that should be appended to the base uri, e.g. Translate or
   *   GetLanguagesForTranslate.
   * @param $query
   *   (Optional) Array of GET query arguments.
   * @param $headers
   *   (Optional) Array of additional HTTP headers.
   *
   * @return array
   *   The HTTP response.
   */
  protected function doRequest(Translator $translator, $path, array $query = array(), array $headers = array()) {

    // @todo Implement it in a proper way.
    $response = $this->smartlingApi->uploadFile($path);
    return $response;
  }

  /**
   * @return \Drupal\tmgmt_smartling\Smartling\SmartlingApi
   */
  public function getSmartlingApi(TranslatorInterface $translator) {
    if (empty($this->smartlingApi)) {
      $this->smartlingApi = new SmartlingApi($translator->getSetting('api_url'), $translator->getSetting('key'), $translator->getSetting('project_id'), $this->client, SmartlingApi::PRODUCTION_MODE);
    }

    return $this->smartlingApi;
  }

  public function getFileName(JobInterface $job) {
    return  "JobID" . $job->id() . '_' . $job->getSourceLangcode() . '_' . $job->getTargetLangcode() . '.' . $job->getSetting('export_format');
  }

  /**
   * {@inheritdoc}
   */
  public function defaultSettings() {
    return array(
      'export_format' => 'xlf',
      'allow_override' => TRUE,
      'scheme' => 'public',
      'retrieval_type' => 'published',
      'callback_url_use' => FALSE,
    );
  }

}
