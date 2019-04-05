<?php

namespace Drupal\config_patch\Plugin\config_patch\output;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Simple text output of the patches.
 *
 * For sets config entries adding content to reference fields.
 *
 * Example:
 *
 *  data:
 *    ...
 *    field_ENTITY_REFERENCE_FIELD:
 *      plugin: create_entity
 *      bundle: BUNDLE_TYPE
 *      data:
 *        title: ENTITY_TITLE
 *        field_SOME_OTHER_FIELD: SIMPLE_VALUE
 *    ...
 *
 * Recursive entity-in-entity creation is not supported.
 *
 * @ParagraphsSetsData(
 *  id = "create_entity",
 *  label = @Translation("Create a new referenced entity"),
 * )
 */
class CreateEntity extends DataPluginBase implements DataPluginInterface, ContainerFactoryPluginInterface {

  /**
   * The entity manager.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * Inject dependencies.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($source, string $fieldname, array $context) {
    // Require some config.
    if (!isset($source['bundle'])) {
      return $source;
    }
    if (!isset($source['data'])) {
      return $source;
    }
    // Figure out the target type and the bundle key for this entity.
    $paragraph_type = $this->entityManager->getDefinition('paragraphs_item');

    $data = $source['data'];
    $data[$bundle_key] = $source['bundle'];

    $entity_storage = $this->entityManager->getStorage($entity_type);
    $entity = $entity_storage->create($data);
    $entity->save();

    return $entity->id();
  }

}
