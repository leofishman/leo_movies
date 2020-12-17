<?php

namespace Drupal\leo_movies\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for leo_movies routes.
 */
class LeoMoviesController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function build() {

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];

    return $build;
  }

}
