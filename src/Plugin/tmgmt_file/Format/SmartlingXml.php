<?php

/**
 * @file
 * Contains Drupal\tmgmt_smartling\Format\Smartling.
 */

namespace Drupal\tmgmt_smartling\Plugin\tmgmt_file\Format;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\tmgmt\Entity\Job;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt_file\Format\DOMDocument;
use Drupal\tmgmt_file\Format\FormatInterface;
use Drupal\tmgmt_file\Format\type;
use Drupal\tmgmt_file\Annotation\FormatPlugin;
use Drupal\Core\Annotation\Translation;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * Export into HTML.
 *
 * @FormatPlugin(
 *   id = "smartling_xml",
 *   label = @Translation("Smartling XML")
 * )
 */
class SmartlingXml implements FormatInterface, ContainerFactoryPluginInterface {

  /**
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  public function __construct(Serializer $serializer, array $configuration, $plugin_id, array $plugin_definition) {
    $this->serializer = $serializer;
  }

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('serializer'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * Returns base64 encoded data that is safe for use in xml ids.
   */
  protected function encodeIdSafeBase64($data) {
    // Prefix with a b to enforce that the first character is a letter.
    return 'b' . rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
  }

  /**
   * Returns decoded id safe base64 data.
   */
  protected function decodeIdSafeBase64($data) {
    // Remove prefixed b.
    $data = substr($data, 1);
    return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
  }

  /**
   * Implements TMGMTFileExportInterface::export().
   */
  public function export(JobInterface $job) {
    $items = array();
    foreach ($job->getItems() as $item) {
      $data = \Drupal::service('tmgmt.data')->filterTranslatable($item->getData());
      foreach ($data as $key => $value) {
        unset($value['#parent_label']);
        $items[$item->id()][$this->encodeIdSafeBase64($item->id() . '][' . $key)] = $value;
      }
    }

    return $this->serializer->serialize($items, 'xml');
  }

  /**
   * Implements TMGMTFileExportInterface::import().
   */
  public function import($imported_file) {
    $file_data = file_get_contents($imported_file);

    $data = $this->serializer->deserialize($file_data, 'array', 'xml');
    return \Drupal::service('tmgmt.data')->unflatten($data);
  }

  /**
   * {@inheritdoc}
   */
  public function validateImport($imported_file) {
    $dom = new \DOMDocument();
    if (!$dom->loadHTMLFile($imported_file)) {
      return FALSE;
    }
    $xml = simplexml_import_dom($dom);

    // Collect meta information.
    $meta_tags = $xml->xpath('//meta');
    $meta = array();
    foreach ($meta_tags as $meta_tag) {
      $meta[(string) $meta_tag['name']] = (string) $meta_tag['content'];
    }

    // Check required meta tags.
    foreach (array('JobID', 'languageSource', 'languageTarget') as $name) {
      if (!isset($meta[$name])) {
        return FALSE;
      }
    }

    // Attempt to load the job.
    if (!$job = Job::load($meta['JobID'])) {
      drupal_set_message(t('The imported file job id @file_id is not available.', array(
        '@file_id' => $job->id(),
      )), 'error');
      return FALSE;
    }

    // Check language.
    if ($meta['languageSource'] != $job->getRemoteSourceLanguage() ||
        $meta['languageTarget'] != $job->getRemoteTargetLanguage()) {
      return FALSE;
    }

    // Validation successful.
    return $job;
  }

}
