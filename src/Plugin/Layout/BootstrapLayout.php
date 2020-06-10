<?php

namespace Drupal\bootstrap_layout_builder\Plugin\Layout;

use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;

/**
 * A layout from our bootstrap layout builder.
 *
 * @Layout(
 *   id = "bootstrap_layout_builder",
 *   deriver = "Drupal\bootstrap_layout_builder\Plugin\Deriver\BootstrapLayoutDeriver"
 * )
 */
class BootstrapLayout extends LayoutDefault implements ContainerFactoryPluginInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new class instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $configFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $regions) {
    $build = parent::build($regions);

    // Flag for local video.
    $has_background_local_video = FALSE;

    // Container.
    if ($this->configuration['container']) {
      $build['container']['#attributes']['class'] = $this->configuration['container'];

      if ($media_id = $this->configuration['container_wrapper_bg_media']) {
        $media_entity = Media::load($media_id);
        $bundle = $media_entity->bundle();
        if ($bundle == 'image') {
          $build['container_wrapper']['#attributes']['style'] = $this->buildBackgroundMediaImage($media_entity);
        }
        elseif ($bundle == 'video_file') {
          $has_background_local_video = TRUE;
          $build['container_wrapper']['#video_wrapper_classes'] = $this->configuration['container_wrapper_bg_color_class'];
          $build['container_wrapper']['#video_background_url'] = $this->buildBackgroundMediaLocalVideo($media_entity);
        }
      }

      if ($this->configuration['container_wrapper_bg_color_class'] || $this->configuration['container_wrapper_classes']) {
        $container_wrapper_classes = '';
        if ($this->configuration['container_wrapper_bg_color_class'] && !$has_background_local_video) {
          $container_wrapper_classes .= $this->configuration['container_wrapper_bg_color_class'];
        }

        if ($this->configuration['container_wrapper_classes']) {
          // Add space after the last class.
          if ($container_wrapper_classes) {
            $container_wrapper_classes = $container_wrapper_classes . ' ';
          }
          $container_wrapper_classes .= $this->configuration['container_wrapper_classes'];
        }
        $build['container_wrapper']['#attributes']['class'] = $container_wrapper_classes;
      }

    }

    // Section Classes.
    $section_classes = [];
    if ($this->configuration['section_classes']) {
      $section_classes = explode(' ', $this->configuration['section_classes']);
      $build['#attributes']['class'] = $section_classes;
    }

    // Regions classes.
    if ($this->configuration['regions_classes']) {
      foreach ($this->getPluginDefinition()->getRegionNames() as $region_name) {
        $region_classes = explode(' ', $this->configuration['regions_classes'][$region_name]);
        $build[$region_name]['#attributes']['class'] = $region_classes;
      }
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $default_configuration = parent::defaultConfiguration();

    $regions_classes = [];
    foreach ($this->getPluginDefinition()->getRegionNames() as $region_name) {
      $regions_classes[$region_name] = '';
    }

    return $default_configuration + [
      // Container wrapper commonly used on container background and minor styling.
      'container_wrapper_classes' => '',
      // Add background color to container wrapper.
      'container_wrapper_bg_color_class' => '',
      // Add background media to container wrapper.
      'container_wrapper_bg_media' => NULL,
      // Container is the section wrapper.
      // Empty means no container else it reflect container type.
      // In bootstrap it will be 'container' or 'container-fluid'.
      'container' => '',
      // Section refer to the div that contains row in bootstrap.
      'section_classes' => '',
      // Region refer to the div that contains Col in bootstrap.
      'regions_classes' => $regions_classes,
    ];
  }

  /**
   * Helper function to the background media image style.
   *
   * @return string
   *   Background media image style.
   */
  public function buildBackgroundMediaImage($media_entity) {
    // @TODO make this dynamic by configuration
    $fid = $media_entity->get('image')->target_id;
    $file = File::load($fid);
    $background_url = $file->url();

    $style = 'background-image: url(' . $background_url . '); background-repeat: no-repeat; background-size: cover;';
    return $style;
  }

  /**
   * Helper function to the background media local video style.
   *
   * @return string
   *   Background media local video style.
   */
  public function buildBackgroundMediaLocalVideo($media_entity) {
    // @TODO make this dynamic by configuration
    $fid = $media_entity->get('field_media_video_file')->target_id;
    $file = File::load($fid);
    return $file->url();
  }

  /**
   * Helper function to get section settings show/hide status.
   *
   * @return bool
   *   Section settings status.
   */
  public function sectionSettingsIsHidden() {
    $config = $this->configFactory->get('bootstrap_layout_builder.settings');
    $hide_section_settings = FALSE;
    if ($config->get('hide_section_settings')) {
      $hide_section_settings = (bool) $config->get('hide_section_settings');
    }
    return $hide_section_settings;
  }

  /**
   * Helper function to get the options of given style name.
   *
   * @param string $name
   *   A config style name like background_color.
   *
   * @return array
   *   Array of key => value of style name options.
   */
  public function getStyleOptions(string $name) {
    $config = $this->configFactory->get('bootstrap_layout_builder.settings');
    $options = [];
    $config_options = $config->get($name);

    $options = ['_none' => t('N/A')];
    $lines = explode(PHP_EOL, $config_options);
    foreach ($lines as $line) {
      $line = explode('|', $line);
      $options[$line[0]] = $line[1];
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Check if section settings visible.
    if (!$this->sectionSettingsIsHidden()) {
      $form['has_container'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Add Container'),
        '#default_value' => (int) !empty($this->configuration['container']) ? TRUE : FALSE,
      ];

      $container_types = [
        'container' => $this->t('Container'),
        'container-fluid' => $this->t('Container fluid'),
      ];

      $form['container_type'] = [
        '#type' => 'select',
        '#title' => $this->t('Container type'),
        '#options' => $container_types,
        '#default_value' => !empty($this->configuration['container']) ? $this->configuration['container'] : 'container',
        '#states' => [
          'visible' => [
            ':input[name="layout_settings[has_container]"]' => ['checked' => TRUE],
          ],
        ],
      ];

      $form['background'] = [
        '#type' => 'details',
        '#title' => $this->t('Background'),
        '#open' => FALSE,
        '#states' => [
          'visible' => [
            ':input[name="layout_settings[has_container]"]' => ['checked' => TRUE],
          ],
        ],
      ];

      $form['background']['container_wrapper_bg_color_class'] = [
        '#type' => 'radios',
        '#options' => $this->getStyleOptions('background_colors'),
        '#title' => $this->t('Background color'),
        '#default_value' => $this->configuration['container_wrapper_bg_color_class'],
        '#attributes' => [
          'class' => ['bootstrap_layout_builder_bg_color'],
        ],
        '#states' => [
          'visible' => [
            ':input[name="layout_settings[has_container_wrapper]"]' => ['checked' => TRUE],
          ],
        ],
      ];

      $form['background']['container_wrapper_bg_media'] = [
        '#type' => 'media_library',
        '#title' => $this->t('Background media'),
        '#description' => $this->t('Background media'),
        '#allowed_bundles' => ['image', 'video_file'],
        '#default_value' => $this->configuration['container_wrapper_bg_media'],
        '#prefix' => '<hr />',
      ];

      $form['container_wrapper_classes'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Container wrapper classes'),
        '#description' => $this->t('Add classes separated by space. Ex: bg-warning py-5.'),
        '#default_value' => $this->configuration['container_wrapper_classes'],
      ];

      $form['section_classes'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Row classes'),
        '#description' => $this->t('Row has "row" class, you can add more classes separated by space. Ex: no-gutters py-3.'),
        '#default_value' => $this->configuration['section_classes'],
      ];

      $form['regions'] = [
        '#type' => 'details',
        '#title' => $this->t('Columns Settings'),
        '#description' => $this->t('Add classes separated by space. Ex: col mb-5 py-3.'),
        '#open' => TRUE,
      ];

      foreach ($this->getPluginDefinition()->getRegionNames() as $region_name) {
        $form['regions'][$region_name . '_classes'] = [
          '#type' => 'textfield',
          '#title' => $this->getPluginDefinition()->getRegionLabels()[$region_name] . ' ' . $this->t('classes'),
          '#default_value' => $this->configuration['regions_classes'][$region_name],
        ];
      }
    }

    // Attach the Bootstrap Layout Builder base libraray.
    $form['#attached']['library'][] = 'bootstrap_layout_builder/base';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    // Check if section settings visible.
    if (!$this->sectionSettingsIsHidden()) {
      // Container type.
      $this->configuration['container'] = '';
      if ($form_state->getValue('has_container')) {
        $this->configuration['container'] = $form_state->getValue('container_type');
        // Container wrapper.
        $this->configuration['container_wrapper_bg_color_class'] = $form_state->getValue('background')['container_wrapper_bg_color_class'];
        $this->configuration['container_wrapper_bg_media'] = $form_state->getValue('background')['container_wrapper_bg_media'];
        $this->configuration['container_wrapper_classes'] = $form_state->getValue('container_wrapper_classes');
      }

      $this->configuration['section_classes'] = $form_state->getValue('section_classes');
      foreach ($this->getPluginDefinition()->getRegionNames() as $region_name) {
        $this->configuration['regions_classes'][$region_name] = $form_state->getValue('regions')[$region_name . '_classes'];
      }
    }
  }

}
