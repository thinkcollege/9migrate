<?php

namespace Drupal\surveynodeform\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller routines for page example routes.
 */
class SurveynodeformController extends ControllerBase {


  /**
   * {@inheritdoc}
   */
  protected function getModuleName() {
    return 'surveynodeform';
  }

  /**
   * Constructs a simple page.
   *
   * The router _controller callback, maps the path
   * 'examples/surveynodeform/simple' to this method.
   *
   * _controller callbacks return a renderable array for the content area of the
   * page. The theme system will later render and surround the content with the
   * appropriate blocks, navigation, and styling.
   */
  public function providersummary() {
    $userinfo = \Drupal::service('surveynodeform.providersummary')->surveynodeform_prov_summary($provid);

  }


  /**
   * A more complex _controller callback that takes arguments.
   *
   * This callback is mapped to the path
   * 'examples/surveynodeform/arguments/{first}/{second}'.
   *
   * The arguments in brackets are passed to this callback from the page URL.
   * The placeholder names "first" and "second" can have any value but should
   * match the callback method variable names; i.e. $first and $second.
   *
   * This function also demonstrates a more complex render array in the returned
   * values. Instead of rendering the HTML with theme('item_list'), content is
   * left un-rendered, and the theme function name is set using #theme. This
   * content will now be rendered as late as possible, giving more parts of the
   * system a chance to change it if necessary.
   *
   * Consult @link http://drupal.org/node/930760 Render Arrays documentation
   * @endlink for details.
   *
   * @param string $first
   *   A string to use, should be a number.
   * @param string $second
   *   Another string to use, should be a number.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   If the parameters are invalid.
   */
  public function arguments($first, $second) {
    // Make sure you don't trust the URL to be safe! Always check for exploits.
    if (!is_numeric($first) || !is_numeric($second)) {
      // We will just show a standard "access denied" page in this case.
      throw new AccessDeniedHttpException();
    }

    $list[] = $this->t("First number was @number.", ['@number' => $first]);
    $list[] = $this->t("Second number was @number.", ['@number' => $second]);
    $list[] = $this->t('The total was @number.', ['@number' => $first + $second]);

    $render_array['surveynodeform_arguments'] = [
      // The theme function to apply to the #items.
      '#theme' => 'item_list',
      // The list itself.
      '#items' => $list,
      '#title' => $this->t('Argument Information'),
    ];
    return $render_array;
  }

}
