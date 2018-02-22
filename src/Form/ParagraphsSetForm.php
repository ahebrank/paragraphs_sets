<?php

namespace Drupal\paragraphs_sets\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for paragraph set forms.
 */
class ParagraphsSetForm extends EntityForm {

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\paragraphs\ParagraphsTypeInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $paragraphs_set = $this->entity;

    if (!$paragraphs_set->isNew()) {
      $form['#title'] = $this->t('Edit %title paragraph set', [
        '%title' => $paragraphs_set->label(),
      ]);
    }

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $paragraphs_set->label(),
      '#description' => $this->t("Label for the Paragraphs set."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $paragraphs_set->id(),
      '#machine_name' => [
        'exists' => 'paragraphs_set_load',
      ],
      '#maxlength' => 32,
      '#disabled' => !$paragraphs_set->isNew(),
    ];

    $form['description'] = [
      '#title' => $this->t('Description'),
      '#type' => 'textarea',
      '#default_value' => $paragraphs_set->getDescription(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $paragraphs_set = $this->entity;
    $paragraphs_set->save();

    drupal_set_message($this->t('Saved the %label Paragraphs set.', [
      '%label' => $paragraphs_set->label(),
    ]));
    $form_state->setRedirect('entity.paragraphs_set.collection');
  }

}
