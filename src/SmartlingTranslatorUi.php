<?php
/**
 * @file
 * Contains \Drupal\tmgmt_smartling\SmartlingTranslatorUi.
 */

namespace Drupal\tmgmt_smartling;

use Drupal\tmgmt\TranslatorPluginUiBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Smartling translator UI.
 */
class SmartlingTranslatorUi extends TranslatorPluginUiBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\tmgmt\TranslatorInterface $translator */
    $translator = $form_state->getFormObject()->getEntity();

    $form['api_url'] = [
      '#type' => 'textfield',
      '#title' => t('API URL'),
      '#default_value' => $translator->getSetting('api_url'),
      '#size' => 25,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#description' => t('Set api url. Default: @api_url', ['@api_url' => $translator->getSetting('api_url')]),
    ];

    $form['project_id'] = [
      '#type' => 'textfield',
      '#title' => t('Project Id'),
      '#default_value' => $translator->getSetting('.project_id'),
      '#size' => 25,
      '#maxlength' => 25,
      '#required' => TRUE,
    ];

    $form['key'] = [
      '#type' => 'textfield',
      '#title' => t('Key'),
      '#default_value' => '',
      '#description' => $this->t('Current key: @key', ['@key' => $this->hideKey($translator->getSetting('.key'))]),
      '#size' => 40,
      '#maxlength' => 40,
      '#required' => FALSE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    /** @var \Drupal\tmgmt\TranslatorInterface $translator */
    $translator = $form_state->getFormObject()->getEntity();
    $supported_remote_languages = $translator->getPlugin()->getSupportedRemoteLanguages($translator);
    if (empty($supported_remote_languages)) {
      $form_state->setErrorByName('settings][project_id', t('The "Project ID", the "Client key" or both are not correct.'));
      $form_state->setErrorByName('settings][key', t('The "Project ID", the "Client key" or both are not correct.'));
    }
  }

  /**
   * Hide last 10 characters in string.
   *
   * @param string $key
   *   Smartling key.
   *
   * @return string
   *   Return smartling key without 10 last characters.
   */
  protected function hideKey($key = '') {
    return substr($key, 0, -10) . str_repeat("*", 10);
  }

}
