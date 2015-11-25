<?php

/**
 * @file
 * Contains \Drupal\tmgmt_smartling\Plugin\tmgmt\Translator\SmartlingTranslator.
 */

namespace Drupal\tmgmt_smartling\Plugin\tmgmt\Translator;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\tmgmt\Entity\Translator;
use Drupal\tmgmt\TranslatorPluginBase;
use Drupal\tmgmt\TranslatorInterface;
use Drupal\tmgmt\JobInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
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
    // @todo check is selected language is valid.
    return parent::checkTranslatable($translator, $job);
  }

  /**
   * {@inheritdoc}
   */
  public function requestTranslation(JobInterface $job) {
    // Pull the source data array through the job and flatten it.
    $data = \Drupal::service('tmgmt.data')->filterTranslatable($job->getData());
    $translation = array();
    foreach ($data as $key => $value) {
      // Query the translator API.
      try {
        $result = $this->doRequest($job->getTranslator(), 'Translate', array(
          'from' => $job->getRemoteSourceLanguage(),
          'to' => $job->getRemoteTargetLanguage(),
          'contentType' => 'text/plain',
          'text' => $value['#text'],
        ), array(
          'Content-Type' => 'text/plain',
        ));

        // Lets use DOMDocument for now because this service enables us to
        // send an array of translation sources, and we will probably use
        // this soon.
        $dom = new \DOMDocument;
        $dom->loadXML($result->getBody()->getContents());
        $items = $dom->getElementsByTagName('string');
        $translation[$key]['#text'] = $items->item(0)->nodeValue;

        // The translation job has been successfully submitted.
        $job->submitted('The translation job has been submitted.');
        // Save the translated data through the job.
        $job->addTranslatedData(\Drupal::service('tmgmt.data')->unflatten($translation));

      }
      catch (RequestException $e) {
        $job->rejected('Rejected by Smartling Translator: !error', array('!error' => $e->getResponse()->getBody()->getContents()), 'error');
      }
    }
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
      $request = $this->doRequest($translator, 'GetLanguagesForTranslate');
      if ($request) {
        $dom = new \DOMDocument;
        $dom->loadXML($request->getBody()->getContents());
        foreach ($dom->getElementsByTagName('string') as $item) {
          $languages[$item->nodeValue] = $item->nodeValue;
        }
      }
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

}
