<?php

namespace Drupal\dcc_bottom_widgets\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'BottomWidgetsSecondBlock' block.
 *
 * @Block(
 *  id = "bottom_widgets_second_block",
 *  admin_label = @Translation("Bottom widgets second block"),
 * )
 */
class BottomWidgetsSecondBlock extends BottomWidgetsBlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return $this->renderSubqueueItem(1);
  }

}
