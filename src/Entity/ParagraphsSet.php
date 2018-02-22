<?php

namespace Drupal\paragraphs_sets\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\paragraphs_sets\ParagraphsSetInterface;

/**
 * Defines the ParagraphsSet entity.
 *
 * @ConfigEntityType(
 *   id = "paragraphs_set",
 *   label = @Translation("Paragraphs set"),
 *   config_prefix = "set",
 *   handlers = {
 *     "list_builder" = "Drupal\paragraphs_sets\Controller\ParagraphsSetListBuilder",
 *     "form" = {
 *       "add" = "Drupal\paragraphs_sets\Form\ParagraphsSetForm",
 *       "edit" = "Drupal\paragraphs_sets\Form\ParagraphsSetForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   admin_permission = "administer paragraphs sets",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "paragraphs",
 *   },
 *   links = {
 *     "edit-form" = "/admin/structure/paragraphs_set/{paragraphs_set}",
 *     "delete-form" = "/admin/structure/paragraphs_set/{paragraphs_set}/delete",
 *     "collection" = "/admin/structure/paragraphs_set",
 *   }
 * )
 */
class ParagraphsSet extends ConfigEntityBase implements ParagraphsSetInterface {

  /**
   * The ParagraphsType ID.
   *
   * @var string
   */
  public $id;

  /**
   * The ParagraphsType label.
   *
   * @var string
   */
  public $label;

  /**
   * A brief description of this paragraph type.
   *
   * @var string
   */
  public $description;

  /**
   * List of paragraphs in this set.
   *
   * @var array
   */
  public $paragraphs;

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function getParagraphs() {
    return $this->paragraphs;
  }

}
