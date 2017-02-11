<?php

namespace Drupal\dcc_bottom_widgets\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'BottomWidgetsFourthBlock' block.
 *
 * @Block(
 *  id = "bottom_widgets_fourth_block",
 *  admin_label = @Translation("Bottom widgets fourth block"),
 * )
 */
class BottomWidgetsFourthBlock extends BottomWidgetsBlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return $this->renderSubqueueItem(3);
  }

}
