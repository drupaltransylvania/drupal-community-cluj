<?php

namespace Drupal\dcc_bottom_widgets\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'BottomWidgetsThirdBlock' block.
 *
 * @Block(
 *  id = "bottom_widgets_third_block",
 *  admin_label = @Translation("Bottom widgets third block"),
 * )
 */
class BottomWidgetsThirdBlock extends BottomWidgetsBlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return $this->renderSubqueueItem(2);
  }

}
