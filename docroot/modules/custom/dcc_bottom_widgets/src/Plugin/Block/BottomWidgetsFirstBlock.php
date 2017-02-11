<?php

namespace Drupal\dcc_bottom_widgets\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\entityqueue\Entity\EntityQueue;
use Drupal\entityqueue\Entity\EntitySubqueue;

/**
 * Provides a 'BottomWidgetsFirstBlock' block.
 *
 * @Block(
 *  id = "bottom_widgets_first_block",
 *  admin_label = @Translation("Bottom widgets first block"),
 * )
 */
class BottomWidgetsFirstBlock extends BottomWidgetsBlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return $this->renderSubqueueItem(0);
  }

}
