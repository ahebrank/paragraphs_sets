<?php

namespace Drupal\paragraphs_sets;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a ParagraphsSet entity.
 */
interface ParagraphsSetInterface extends ConfigEntityInterface {

  /**
   * Gets the description.
   *
   * @return string
   *   The description of this paragraph set.
   */
  public function getDescription();

  /**
   * Get the list of paragraphs in the set.
   *
   * @return array
   *   The paragraphs data of this paragraph set.
   */
  public function getParagraphs();

}
