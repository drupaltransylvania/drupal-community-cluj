<?php

namespace Drupal\dcc_bottom_widgets\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BottomWidgetsBlockBase.
 *
 * Provides common functionality for the blocks displayed in the bottom widget
 * region.
 *
 * @package Drupal\dcc_bottom_widgets\Plugin\Block
 */
abstract class BottomWidgetsBlockBase extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity_type.manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The render controller of an entity type.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $renderController;

  /**
   * An array of queue items.
   *
   * The blocks extending this class all need to display their content from
   * the same queue, therefore we make this a static property, so that we only
   * load them once.
   *
   * @var null|array
   */
  protected static $subQueueItems = NULL;

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * BottomWidgetsBlockBase constructor.
   *
   * @param array $configuration
   *   The configuration of the plugin.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity_type.manager service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entityTypeManager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->renderController = $this->entityTypeManager->getViewBuilder('node');
  }

  /**
   * Returns an array of items from the subqueue.
   *
   * @return null|array
   *   An array of subqueue items.
   */
  public function getSubqueueItems() {
    // Because all the bottom widget blocks display content from the same
    // subqueue, we use the items from the subqueue as static property, so that
    // they are only loaded once, when the first block loads them.
    if (!self::$subQueueItems) {
      $subqueue = $this->entityTypeManager
        ->getStorage('entity_subqueue')
        ->load('homepage_bottom_widgets');
      self::$subQueueItems = $subqueue->get('items')->referencedEntities();;
    }

    return self::$subQueueItems;
  }

  /**
   * Renders a specific item from the subqueue.
   *
   * @param int $subqueueWeight
   *   The weight of the item in the subqueue.
   *
   * @return array
   *   A render array.
   */
  protected function renderSubqueueItem($subqueueWeight) {
    $output = [];
    $items = $this->getSubqueueItems();
    if (array_key_exists($subqueueWeight, $items)) {
      $output = $this->renderNode($items[$subqueueWeight]);
    }

    return $output;
  }

  /**
   * Creates the render array for a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   * @param string $viewMode
   *   The view mode in which we render the node.
   * @param mixed $langCode
   *   The language code.
   *
   * @return array
   *   A render array.
   */
  protected function renderNode(NodeInterface $node, $viewMode = 'bottom_widget', $langCode = NULL) {
    return $this->renderController->view($node, $viewMode, $langCode);
  }

}
