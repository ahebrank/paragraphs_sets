<?php

namespace Drupal\paragraphs_sets\Plugin\paragraphs_sets\process;

/**
 * Default no-transform from source data.
 *
 * @ParagraphsSetsProcess(
 *  id = "default",
 *  label = @Translation("Default single-value field behavior"),
 * )
 */
class Simple extends ProcessPluginBase implements ProcessPluginInterface {}
