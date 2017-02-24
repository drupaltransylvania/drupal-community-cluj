<?php

namespace Drupal\dcc_gtd_registration\Plugin\Block;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Block\Annotation\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\dcc_gtd_scheduler\Controller\ScheduleManager;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'GtdRegistrationBlock' block.
 *
 * @Block(
 *  id = "gtd_registration_block",
 *  admin_label = @Translation("Gtd registration block"),
 * )
 */
class GtdRegistrationBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The schedule manager service.
   *
   * @var \Drupal\dcc_gtd_scheduler\Controller\ScheduleManager
   */
  protected $scheduleManager;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * GtdRegistrationBlock constructor.
   *
   * @param array $configuration
   *   The block configuration.
   * @param string $plugin_id
   *   The block id.
   * @param mixed $plugin_definition
   *   The pluging definition of the block.
   * @param \Drupal\dcc_gtd_scheduler\Controller\ScheduleManager $scheduleManager
   *   The schedule manager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ScheduleManager $scheduleManager,
    EntityTypeManagerInterface $entityTypeManager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->scheduleManager = $scheduleManager;
    $this->entityTypeManager = $entityTypeManager;
  }

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
      $container->get('dcc_schedule.manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $nid = $this->scheduleManager->getActiveSchedulerId();

    // If the node doesn't exist, then the registration period is either over,
    // or there's no training node yet.
    if ($nid) {
      /** @var NodeInterface $node */
      $node = $this->entityTypeManager->getStorage('node')->load($nid);
      $build = $this->buildExistingRegistrationContent($node);
    }
    else {
      $build = $this->buildNonExistingRegistrationContent();
    }

    $build += [
      '#attached' => [
        'library' => [
            'dcc_gtd_registration/dcc_gtd_registration.block'
        ],
      ],
      '#cache' => array(
        'max-age' => 0
      ),
    ];
    return $build;
  }

  /**
   * Builds the content when there's an active scheduler.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The scheduler node.
   *
   * @return array
   *   Returns a render array.
   */
  private function buildExistingRegistrationContent(NodeInterface $node) {
    $renderController = \Drupal::entityTypeManager()->getViewBuilder('node');

    $registerUrl = Link::createFromRoute($this->t('Register here'), 'dcc_gtd_registration.form')->toRenderable();
    $registerUrl['#attributes']['class'] = ['big-btn', 'full-width'];

    return [
      'node' => $renderController->view($node, 'registration_block'),
      'url' => $registerUrl,
    ];
  }

  /**
   * Returns content, when there's no active scheduler.
   *
   * @return mixed
   *   The content.
   */
  private function buildNonExistingRegistrationContent() {
    return ['#markup' => $this->t('There are no trainings currently.')];
  }

}
