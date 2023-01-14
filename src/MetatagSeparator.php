<?php

namespace Drupal\metatag;

/**
 * Separator logic used elsewhere.
 */
trait MetatagSeparator {

  /**
   * The default separator to use when one is not defined through configuration.
   *
   * @var string
   */
  public static $default_separator = ',';

  /**
   * Returns the multiple value separator for this site.
   *
   * This is the character used to explode multiple values. It defaults to a
   * comma but can be set to any other character or string.
   *
   * @return string
   *   The correct separator.
   */
  public function getSeparator() {
    $config = $this->configFactory->get('metatag.settings');
    $separator = trim($config->get('separator') ?? '');
    if ($separator === '') {
      $separator = $this::$default_separator;
    }
    return $separator;
  }

}
