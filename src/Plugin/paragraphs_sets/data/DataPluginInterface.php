<?php

namespace Drupal\paragraphs_sets\Plugin\paragraphs_sets\data;

use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Data plugin specification.
 */
interface DataPluginInterface extends PluginInspectionInterface, DerivativeInspectionInterface {

  /**
   * Return ID.
   */
  public function getId();

  /**
   * Return label.
   */
  public function getLabel();

  /**
   * Retrieve default data.
   *
   * @param mixed $source
   *   Source data from sets config for a single field.
   * @param string $fieldname
   *   Field name on the paragraph.
   * @param array $context
   *   Contextual data.
   */
  public function transform($source, string $fieldname, array $context);

}
