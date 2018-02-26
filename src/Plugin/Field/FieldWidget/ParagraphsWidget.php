<?php

namespace Drupal\paragraphs_sets\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Plugin\Field\FieldWidget\ParagraphsWidget as ParagraphsParagraphsWidget;

/**
 * Plugin implementation of 'entity_reference_revisions paragraphs sets' widget.
 *
 * @FieldWidget(
 *   id = "paragraphs_sets",
 *   label = @Translation("Paragraphs sets EXPERIMENTAL"),
 *   description = @Translation("An experimental paragraphs inline form widget with sets."),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class ParagraphsWidget extends ParagraphsParagraphsWidget {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $options = [];
    foreach (static::getSets() as $key => $set) {
      $options[$key] = $set['label'];
    }

    $elements['default_paragraph_type']['#title'] = $this->t('Default paragraph set');
    $elements['default_paragraph_type']['#description'] = $this->t('When creating a new host entity, the selected set of paragraphs are added.');
    $elements['default_paragraph_type']['#options'] = $options;

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    if ($this->getDefaultParagraphTypeLabelName() !== NULL) {
      array_pop($summary);
      $summary[] = $this->t('Default paragraphs set: @default_paragraph_set', [
        '@default_paragraph_set' => $this->getDefaultParagraphTypeLabelName(),
      ]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $host = $items->getEntity();
    $field_name = $this->fieldDefinition->getName();
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    $this->fieldParents = $form['#parents'];
    $field_state = static::getWidgetState($this->fieldParents, $field_name, $form_state);
    $user_input = &$form_state->getUserInput();

    $max = $field_state['items_count'];
    $entity_type_manager = \Drupal::entityTypeManager();

    $sets = static::getSets();
    $set = isset($field_state['selected_set']) ? $field_state['selected_set'] : NULL;

    // Consider adding a default paragraph for new host entities.
    if ($max == 0 && $items->getEntity()->isNew() && empty($set)) {
      $set = $this->getDefaultParagraphTypeMachineName();
    }

    if ($set) {
      if (isset($field_state['button_type']) && ('set_selection_button' === $field_state['button_type'])) {
        // Clear all items.
        $items->filter(function () {
          return FALSE;
        });
        // Clear field state.
        $field_state['paragraphs'] = [];
        // Clear user input.
        foreach ($user_input[$field_name] as $key => $value) {
          if (!is_numeric($key) || empty($value['subform'])) {
            continue;
          }
          unset($user_input[$field_name][$key]);
        }
        $this->realItemCount = 0;
        $max = 0;
      }
      $target_type = $this->getFieldSetting('target_type');
      $context = [
        'set' => $set,
        'field' => $this->fieldDefinition,
        'form' => $form,
        'entity' => $host,
      ];
      foreach ($sets[$set]['paragraphs'] as $key => $info) {
        $alter_hooks = [
          'paragraphs_set_data',
          'paragraphs_set_' . $set . '_data',
          'paragraphs_set_' . $set . '_' . $field_name . '_data',
        ];

        $context['key'] = $key;
        $context['paragraphs_bundle'] = $info['type'];
        $data = $info['data'];
        \Drupal::moduleHandler()->alter($alter_hooks, $data, $context);

        $item_values = [
          'type' => $info['type'],
        ] + $data;

        $max++;
        $paragraphs_entity = $entity_type_manager->getStorage($target_type)->create($item_values);
        $display = EntityFormDisplay::collectRenderDisplay($paragraphs_entity, $this->getSetting('form_display_mode'));
        $field_state['paragraphs'][$max - 1] = [
          'entity' => $paragraphs_entity,
          'display' => $display,
          'mode' => 'edit',
          'original_delta' => $max,
        ];
        $field_state['items_count'] = $max;
      }
    }

    $this->realItemCount = $max;
    $is_multiple = $this->fieldDefinition->getFieldStorageDefinition()->isMultiple();

    $field_title = $this->fieldDefinition->getLabel();
    $description = FieldFilteredMarkup::create(\Drupal::token()->replace($this->fieldDefinition->getDescription()));

    $elements = [];
    $tabs = '';
    $this->fieldIdPrefix = implode('-', array_merge($this->fieldParents, [$field_name]));
    $this->fieldWrapperId = Html::getUniqueId($this->fieldIdPrefix . '-add-more-wrapper');

    // If the parent entity is paragraph add the nested class if not then add
    // the perspective tabs.
    $field_prefix = strtr($this->fieldIdPrefix, '_', '-');
    if (count($this->fieldParents) == 0) {
      if ($items->getEntity()->getEntityTypeId() != 'paragraph') {
        $tabs = '<ul class="paragraphs-tabs tabs primary clearfix"><li id="content" class="tabs__tab"><a href="#' . $field_prefix . '-values">Content</a></li><li id="behavior" class="tabs__tab"><a href="#' . $field_prefix . '-values">Behavior</a></li></ul>';
      }
    }
    if (count($this->fieldParents) > 0) {
      if ($items->getEntity()->getEntityTypeId() === 'paragraph') {
        $form['#attributes']['class'][] = 'paragraphs-nested';
      }
    }
    $elements['#prefix'] = '<div class="is-horizontal paragraphs-tabs-wrapper" id="' . $this->fieldWrapperId . '">' . $tabs;
    $elements['#suffix'] = '</div>';

    $field_state['ajax_wrapper_id'] = $this->fieldWrapperId;
    // Persist the widget state so formElement() can access it.
    static::setWidgetState($this->fieldParents, $field_name, $form_state, $field_state);

    $header_actions = $this->buildHeaderActions($field_state, $form_state);
    if ($header_actions) {
      $elements['header_actions'] = $header_actions;
      // Add a weight element so we guaranty that header actions will stay in
      // first row. We will use this later in
      // paragraphs_preprocess_field_multiple_value_form().
      $elements['header_actions']['_weight'] = [
        '#type' => 'weight',
        '#default_value' => -100,
      ];
    }

    if (!empty($field_state['dragdrop'])) {
      $elements['#attached']['library'][] = 'paragraphs/paragraphs-dragdrop';
      $elements['dragdrop'] = $this->buildNestedParagraphsFoDragDrop($form_state, NULL, []);
      return $elements;
    }

    if ($max > 0) {
      for ($delta = 0; $delta < $max; $delta++) {
        // Add a new empty item if it doesn't exist yet at this delta.
        if (!isset($items[$delta])) {
          $items->appendItem();
        }

        // For multiple fields, title and description are handled by the
        // wrapping table.
        $element = [
          '#title' => $is_multiple ? '' : $field_title,
          '#description' => $is_multiple ? '' : $description,
          '#paragraphs_bundle' => '',
        ];
        $element = $this->formSingleElement($items, $delta, $element, $form, $form_state);

        if ($element) {
          $widget_state = static::getWidgetState($element['#field_parents'], $field_name, $form_state);
          $element['#paragraphs_bundle'] = $widget_state['paragraphs'][$delta]['entity']->bundle();
          // Input field for the delta (drag-n-drop reordering).
          if ($is_multiple) {
            // We name the element '_weight' to avoid clashing with elements
            // defined by widget.
            $element['_weight'] = [
              '#type' => 'weight',
              '#title' => $this->t('Weight for row @number', ['@number' => $delta + 1]),
              '#title_display' => 'invisible',
              // This 'delta' is the FAPI #type 'weight' element's property.
              '#delta' => $max,
              '#default_value' => $items[$delta]->_weight ?: $delta,
              '#weight' => 100,
            ];
          }

          // Access for the top element is set to FALSE only when the paragraph
          // was removed. A paragraphs that a user can not edit has access on
          // lower level.
          if (isset($element['#access']) && !$element['#access']) {
            $this->realItemCount--;
          }
          else {
            $elements[$delta] = $element;
          }
        }
      }
    }

    $field_state = static::getWidgetState($this->fieldParents, $field_name, $form_state);
    $field_state['real_item_count'] = $this->realItemCount;
    $field_state['add_mode'] = $this->getSetting('add_mode');
    static::setWidgetState($this->fieldParents, $field_name, $form_state, $field_state);

    $elements += [
      '#element_validate' => [[$this, 'multipleElementValidate']],
      '#required' => $this->fieldDefinition->isRequired(),
      '#field_name' => $field_name,
      '#cardinality' => $cardinality,
      '#max_delta' => $max - 1,
    ];

    if ($this->realItemCount > 0) {
      $elements += [
        '#theme' => 'field_multiple_value_form__paragraphs_sets',
        '#cardinality_multiple' => $is_multiple,
        '#title' => $field_title,
        '#description' => $description,
      ];
    }
    else {
      $classes = $this->fieldDefinition->isRequired() ? ['form-required'] : [];
      $elements += [
        '#type' => 'container',
        '#theme_wrappers' => ['container'],
        '#cardinality_multiple' => TRUE,
        'title' => [
          '#type' => 'html_tag',
          '#tag' => 'strong',
          '#value' => $field_title,
          '#attributes' => ['class' => $classes],
        ],
        'text' => [
          '#type' => 'container',
          'value' => [
            '#markup' => $this->t('No @title added yet.', ['@title' => $this->getSetting('title')]),
            '#prefix' => '<em>',
            '#suffix' => '</em>',
          ],
        ],
      ];

      if ($description) {
        $elements['description'] = [
          '#type' => 'container',
          'value' => ['#markup' => $description],
          '#attributes' => ['class' => ['description']],
        ];
      }
    }

    $this->initIsTranslating($form_state, $host);

    $elements['set_selection'] = $this->buildSelectSetSelection($form_state, $set);

    if (($this->realItemCount < $cardinality || $cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) && !$form_state->isProgrammed() && !$this->isTranslating) {
      $elements['add_more'] = $this->buildAddActions();
    }

    $elements['#attached']['library'][] = 'paragraphs/drupal.paragraphs.widget';
    $elements['#attached']['library'][] = 'paragraphs_sets/drupal.paragraphs_sets.admin';

    return $elements;
  }

  /**
   * Builds select element for set selection.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $default
   *   Current selected set.
   *
   * @return array
   *   The form element array.
   */
  protected function buildSelectSetSelection(FormStateInterface $form_state, $default = NULL) {
    $field_name = $this->fieldDefinition->getName();
    $title = $this->fieldDefinition->getLabel();
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();

    $options = [
      '_none' => $this->t('- None -'),
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
      '#title' => $this->t('@title set', ['@title' => $this->getSetting('title')]),
      '#label_display' => 'hidden',
    ];

    $selection_elements['set_selection_button'] = [
      '#type' => 'submit',
      '#name' => strtr($this->fieldIdPrefix, '-', '_') . '_set_selection',
      '#value' => $this->t('Select set'),
      '#attributes' => ['class' => ['field-set-selection-submit']],
      '#limit_validation_errors' => [
        array_merge($this->fieldParents, [$field_name, 'set_selection']),
      ],
      '#submit' => [[get_class($this), 'setSetSubmit']],
      '#ajax' => [
        'callback' => [get_class($this), 'setSetAjax'],
        'wrapper' => $this->fieldWrapperId,
        'effect' => 'fade',
      ],
    ];
    $selection_elements['set_selection_button']['#suffix'] = $this->t('for %type', ['%type' => $title]);

    if ($this->realItemCount && ($this->realItemCount < $cardinality || $cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) && !$form_state->isProgrammed() && !$this->isTranslating) {
      $selection_elements['append_selection_button'] = [
        '#type' => 'submit',
        '#name' => strtr($this->fieldIdPrefix, '-', '_') . '_append_selection',
        '#value' => $this->t('Append set'),
        '#attributes' => ['class' => ['field-append-selection-submit']],
        '#limit_validation_errors' => [
          array_merge($this->fieldParents, [$field_name, 'append_selection']),
        ],
        '#submit' => [[get_class($this), 'setSetSubmit']],
        '#ajax' => [
          'callback' => [get_class($this), 'setSetAjax'],
          'wrapper' => $this->fieldWrapperId,
          'effect' => 'fade',
        ],
      ];
      $selection_elements['append_selection_button']['#suffix'] = $this->t('to %type', ['%type' => $title]);
    }

    return $selection_elements;
  }

  /**
   * Builds an add paragraph button for opening of modal form.
   *
   * @param array $element
   *   Render element.
   */
  protected function buildModalAddForm(array &$element) {
    // Attach the theme for the dialog template.
    $element['#theme'] = 'paragraphs_sets_add_dialog';
    $element['#buttons_title'] = $this->getSetting('title');

    $element['add_modal_form_area'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'paragraph-type-add-modal',
          'first-button',
        ],
      ],
      '#access' => !$this->isTranslating,
      '#weight' => -2000,
    ];

    $element['add_modal_form_area']['add_more'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add @title or Set', ['@title' => $this->getSetting('title')]),
      '#name' => 'button_add_modal',
      '#attributes' => [
        'class' => [
          'paragraph-type-add-modal-button',
          'js-show',
        ],
      ],
    ];

    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();

    foreach (static::getSets() as $machine_name => $set) {
      if (($cardinality !== FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) && (count($set['paragraphs']) > $cardinality)) {
        // Do not add sets having more paragraphs than allowed.
        continue;
      }
      $button_key = 'append_selection_button_' . $machine_name;
      $element[$button_key] = $this->expandButton([
        '#type' => 'submit',
        '#name' => $this->fieldIdPrefix . '_' . $machine_name . '_set_set',
        '#value' => $set['label'],
        '#attributes' => [
          'class' => [
            'field-add-more-submit',
            'field-append-set-submit',
          ],
        ],
        '#limit_validation_errors' => [
          array_merge($this->fieldParents, [
            $this->fieldDefinition->getName(), 'set_set',
          ]),
        ],
        '#submit' => [[get_class($this), 'setSetSubmit']],
        '#ajax' => [
          'callback' => [get_class($this), 'setSetAjax'],
          'wrapper' => $this->fieldWrapperId,
        ],
        '#set_machine_name' => $machine_name,
      ]);
    }

    $element['#attached']['library'][] = 'paragraphs/drupal.paragraphs.modal';
    $element['#attached']['library'][] = 'paragraphs_sets/drupal.paragraphs_sets.modal';
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

  /**
   * Get the list of all defined sets.
   *
   * @return array
   *   List of sets keyed by set ID.
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
   * Returns the default paragraph type.
   *
   * @return string
   *   Label name for default paragraph type.
   */
  protected function getDefaultParagraphTypeLabelName() {
    if ($this->getDefaultParagraphTypeMachineName() !== NULL) {
      $allowed_types = static::getSets();
      return $allowed_types[$this->getDefaultParagraphTypeMachineName()]['label'];
    }

    return NULL;
  }

  /**
   * Returns the machine name for default paragraph set.
   *
   * @return string
   *   Machine name for default paragraph set.
   */
  protected function getDefaultParagraphTypeMachineName() {
    $default_type = $this->getSetting('default_paragraph_type');
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

}
