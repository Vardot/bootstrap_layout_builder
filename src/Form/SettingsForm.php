<?php

namespace Drupal\bootstrap_layout_builder\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Bootstrap Layout Builder settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'bootstrap_layout_builder.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bootstrap_layout_builder_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $form['hide_section_settings'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide "Advanced Settings"'),
      '#description' => $this->t('<img src="/' . drupal_get_path('module', 'bootstrap_layout_builder') . '/images/drupal-ui/toggle-advanced-settings.png" alt="Toggle Advanced Settings Tab Visibility" title="Toggle Advanced Settings Tab Visibility">'),
      '#default_value' => $config->get('hide_section_settings'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable(static::SETTINGS)
      ->set('hide_section_settings', $form_state->getValue('hide_section_settings'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
