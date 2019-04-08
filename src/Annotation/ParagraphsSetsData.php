<?php

namespace Drupal\paragraphs_sets\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a paragraphs_sets Data annotation object.
 *
 * These plugins provide the default values for new paragraph entities.
 *
 * @Annotation
 *
 * @ingroup paragraphs_sets
 */
class ParagraphsSetsData extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the output type.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The name of the class.
   *
   * @var string
   *
   * This is not provided manually, it will be added by the discovery mechanism.
   */
  public $class;

}
