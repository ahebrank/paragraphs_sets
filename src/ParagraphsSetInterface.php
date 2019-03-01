<?php

namespace Drupal\paragraphs_sets;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a ParagraphsSet entity.
 */
interface ParagraphsSetInterface extends ConfigEntityInterface {

  /**
   * Returns the icon file entity.
   *
   * @return \Drupal\file\FileInterface|bool
   *   The icon's file entity or FALSE if icon does not exist.
   */
  public function getIconFile();

  /**
   * Returns the icon's URL.
   *
   * @return string|bool
   *   The icon's URL or FALSE if icon does not exits.
   */
  public function getIconUrl();

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
