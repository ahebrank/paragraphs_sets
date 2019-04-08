<?php

namespace Drupal\paragraphs_sets\Plugin\paragraphs_sets\process;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base process plugin.
 */
abstract class ProcessPluginBase extends PluginBase implements ProcessPluginInterface {

  /**
   * Return the id.
   */
  public function getId() {
    return $this->pluginId;
  }

  /**
   * Return the label.
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * Data to return to the paragraph.
   *
   * Probably override this.
   *
   * @param mixed $source
   *   Source data from sets config for a single field.
   * @param string $fieldname
   *   Field name on the paragraph.
   * @param array $context
   *   Contextual data.
   */
  public function transform($source, string $fieldname, array $context) {
    return $source;
  }

}
