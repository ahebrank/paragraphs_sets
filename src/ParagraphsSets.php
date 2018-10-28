<?php

namespace Drupal\paragraphs_sets;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\WidgetBaseInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Plugin\Field\FieldWidget\ParagraphsWidget;

/**
 * Utitlity class for paragraphs_sets.
 */
class ParagraphsSets {

  /**
   * Get a list of all available sets.
   *
   * @return array
   *   List of all paragraphs sets.
   */
  public static function getSets() {
    $query = \Drupal::entityQuery('paragraphs_set');
    $config_factory = \Drupal::configFactory();
    $results = $query->execute();
    $sets = [];
    foreach ($results as $id) {
      /** @var \Drupal\Core\Config\ImmutableConfig $config */
      if (($config = $config_factory->get("paragraphs_sets.set.{$id}"))) {
        $sets[$id] = $config->getRawData();
      }
    }

    return $sets;
  }

  /**
   * Returns the machine name for default paragraph set.
   *
   * @param \Drupal\Core\Field\WidgetBaseInterface $widget
   *   The widget to operate on.
   *
   * @return string
   *   Machine name for default paragraph set.
   */
  public static function getDefaultParagraphTypeMachineName(WidgetBaseInterface $widget) {
    $default_type = $widget->getSetting('default_paragraph_type');
    $allowed_types = static::getSets();
    if ($default_type && isset($allowed_types[$default_type])) {
      return $default_type;
    }
    // Check if the user explicitly selected not to have any default Paragraph
    // set. Otherwise, if there is only one set available, that one is the
    // default.
    if ($default_type === '_none') {
      return NULL;
    }
    if (count($allowed_types) === 1) {
      return key($allowed_types);
    }

    return NULL;
  }

  /**
   * Builds select element for set selection.
   *
   * @param array $elements
   *   Form elements to build the selection for.
   * @param array $context
   *   Required context for the set selection.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $default
   *   Current selected set.
   *
   * @return array
   *   The form element array.
   */
  public static function buildSelectSetSelection(array $elements, array $context, FormStateInterface $form_state, $default = NULL) {
    /** @var \Drupal\paragraphs\Plugin\Field\FieldWidget\ParagraphsWidget $widget */
    $widget = $context['widget'];
    if (!($widget instanceof ParagraphsWidget)) {
      return [];
    }

    $items = $context['items'];
    $field_definition = $items->getFieldDefinition();
    $field_name = $field_definition->getName();
    $title = $field_definition->getLabel();
    $cardinality = $field_definition->getFieldStorageDefinition()->getCardinality();
    $field_parents = $context['form']['#parents'];
    $field_id_prefix = implode('-', array_merge($field_parents, [$field_name]));
    $field_wrapper_id = Html::getId($field_id_prefix . '-add-more-wrapper');
    $field_state = static::getWidgetState($field_parents, $field_name, $form_state);

    $options = [
      '_none' => t('- None -'),
    ];
    foreach (static::getSets() as $key => $set) {
      if (($cardinality !== FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) && (count($set['paragraphs']) > $cardinality)) {
        // Do not add sets having more paragraphs than allowed.
        continue;
      }
      $options[$key] = $set['label'];
    }
    $selection_elements = [
      '#type' => 'container',
      '#theme_wrappers' => ['container'],
      '#attributes' => [
        'class' => ['set-selection-wrapper'],
      ],
    ];
    $selection_elements['set_selection_select'] = [
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $default,
      '#title' => t('@title set', ['@title' => $widget->getSetting('title')]),
      '#label_display' => 'hidden',
    ];

    $selection_elements['set_selection_button'] = [
      '#type' => 'submit',
      '#name' => strtr($field_id_prefix, '-', '_') . '_set_selection',
      '#value' => t('Select set'),
      '#attributes' => ['class' => ['field-set-selection-submit']],
      '#limit_validation_errors' => [
        array_merge($field_parents, [$field_name, 'set_selection']),
      ],
      '#submit' => [['\Drupal\paragraphs_sets\ParagraphsSets', 'setSetSubmit']],
      '#ajax' => [
        'callback' => ['\Drupal\paragraphs_sets\ParagraphsSets', 'setSetAjax'],
        'wrapper' => $field_wrapper_id,
        'effect' => 'fade',
      ],
    ];
    $selection_elements['set_selection_button']['#prefix'] = '<div class="paragraphs-set-button paragraphs-set-button-set">';
    $selection_elements['set_selection_button']['#suffix'] = t('for %type', ['%type' => $title]) . '</div>';

    if ($field_state['items_count'] && ($field_state['items_count'] < $cardinality || $cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) && !$form_state->isProgrammed() && $elements['#allow_reference_changes']) {
      $selection_elements['append_selection_button'] = [
        '#type' => 'submit',
        '#name' => strtr($field_id_prefix, '-', '_') . '_append_selection',
        '#value' => t('Append set'),
        '#attributes' => ['class' => ['field-append-selection-submit']],
        '#limit_validation_errors' => [
          array_merge($field_parents, [$field_name, 'append_selection']),
        ],
        '#submit' => [['\Drupal\paragraphs_sets\ParagraphsSets', 'setSetSubmit']],
        '#ajax' => [
          'callback' => ['\Drupal\paragraphs_sets\ParagraphsSets', 'setSetAjax'],
          'wrapper' => $field_wrapper_id,
          'effect' => 'fade',
        ],
      ];
      $selection_elements['append_selection_button']['#prefix'] = '<div class="paragraphs-set-button paragraphs-set-button-append">';
      $selection_elements['append_selection_button']['#suffix'] = t('to %type', ['%type' => $title]) . '</div>';
    }

    return $selection_elements;
  }

  /**
   * Retrieves processing information about the widget from $form_state.
   *
   * This method is static so that it can be used in static Form API callbacks.
   *
   * @param array $parents
   *   The array of #parents where the field lives in the form.
   * @param string $field_name
   *   The field name.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   An array with the following key/value pairs:
   *   - items_count: The number of widgets to display for the field.
   *   - array_parents: The location of the field's widgets within the $form
   *     structure. This entry is populated at '#after_build' time.
   */
  public static function getWidgetState(array $parents, $field_name, FormStateInterface $form_state) {
    return NestedArray::getValue($form_state->getStorage(), static::getWidgetStateParents($parents, $field_name));
  }

  /**
   * Stores processing information about the widget in $form_state.
   *
   * This method is static so that it can be used in static Form API #callbacks.
   *
   * @param array $parents
   *   The array of #parents where the widget lives in the form.
   * @param string $field_name
   *   The field name.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $field_state
   *   The array of data to store. See getWidgetState() for the structure and
   *   content of the array.
   */
  public static function setWidgetState(array $parents, $field_name, FormStateInterface $form_state, array $field_state) {
    NestedArray::setValue($form_state->getStorage(), static::getWidgetStateParents($parents, $field_name), $field_state);
  }

  /**
   * Returns the location of processing information within $form_state.
   *
   * @param array $parents
   *   The array of #parents where the widget lives in the form.
   * @param string $field_name
   *   The field name.
   *
   * @return array
   *   The location of processing information within $form_state.
   */
  public static function getWidgetStateParents(array $parents, $field_name) {
    // Field processing data is placed at
    // $form_state->get(['field_storage', '#parents', ...$parents..., '#fields',
    // $field_name]), to avoid clashes between field names and $parents parts.
    return array_merge(['field_storage', '#parents'], $parents, ['#fields', $field_name]);
  }

  /**
   * {@inheritdoc}
   */
  public static function setSetAjax(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -2));

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function setSetSubmit(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -2));
    $field_name = $element['#field_name'];
    $parents = $element['#field_parents'];
    $button_type = end($button['#array_parents']);

    // Increment the items count.
    $widget_state = static::getWidgetState($parents, $field_name, $form_state);
    $widget_state['button_type'] = $button_type;

    if (isset($button['#set_machine_name'])) {
      $widget_state['selected_set'] = $button['#set_machine_name'];
    }
    else {
      $widget_state['selected_set'] = $element['set_selection']['set_selection_select']['#value'];
    }

    static::setWidgetState($parents, $field_name, $form_state, $widget_state);

    $form_state->setRebuild();
  }

}
