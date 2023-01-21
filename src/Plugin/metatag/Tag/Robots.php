<?php

namespace Drupal\metatag\Plugin\metatag\Tag;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * The basic "Robots" meta tag.
 *
 * @MetatagTag(
 *   id = "robots",
 *   label = @Translation("Robots"),
 *   description = @Translation("Provides search engines with specific directions for what to do when this page is indexed."),
 *   name = "robots",
 *   group = "advanced",
 *   weight = 1,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Robots extends MetaNameBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function setValue($value): void {
    if (is_array($value)) {
      $value = array_filter($value);
      $value = implode($this->getSeparator() . ' ', array_keys($value));
    }
    $this->value = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $element = []): array {
    // Prepare the default value as it is stored as a string.
    $default_value = [];
    if (!empty($this->value)) {
      $default_value = explode($this->getSeparator() . ' ', $this->value);
    }

    $form = [
      '#type' => 'checkboxes',
      '#title' => $this->label(),
      '#description' => $this->description(),
      '#options' => $this->formValues(),
      'index' => [
        '#states' => [
          'disabled' => [
            [':input[name="robots[noindex]"]' => ['checked' => TRUE]],
            'or',
            [':input[name*="[robots][noindex]"]' => ['checked' => TRUE]],
          ],
        ],
      ],
      'noindex' => [
        '#states' => [
          'disabled' => [
            [':input[name="robots[index]"]' => ['checked' => TRUE]],
            'or',
            [':input[name*="[robots][index]"]' => ['checked' => TRUE]],
          ],
        ],
      ],
      'follow' => [
        '#states' => [
          'disabled' => [
            [':input[name="robots[nofollow]"]' => ['checked' => TRUE]],
            'or',
            [':input[name*="[robots][nofollow]"]' => ['checked' => TRUE]],
          ],
        ],
      ],
      'nofollow' => [
        '#states' => [
          'disabled' => [
            [':input[name="robots[follow]"]' => ['checked' => TRUE]],
            'or',
            [':input[name*="[robots][follow]"]' => ['checked' => TRUE]],
          ],
        ],
      ],
      '#default_value' => $default_value,
      '#required' => $element['#required'] ?? FALSE,
      '#element_validate' => [[get_class($this), 'validateTag']],
    ];

    return $form;
  }

  /**
   * The list of select values.
   *
   * @return array
   *   A list of values available for this select tag.
   */
  protected function formValues(): array {
    return [
      'index' => $this->t('index - Allow search engines to index this page (assumed).'),
      'follow' => $this->t('follow - Allow search engines to follow links on this page (assumed).'),
      'noindex' => $this->t('noindex - Prevents search engines from indexing this page.'),
      'nofollow' => $this->t('nofollow - Prevents search engines from following links on this page.'),
      'noarchive' => $this->t('noarchive - Prevents cached copies of this page from appearing in search results.'),
      'nosnippet' => $this->t('nosnippet - Prevents descriptions from appearing in search results, and prevents page caching.'),
      'noodp' => $this->t('noodp - Blocks the <a href=":opendirectory">Open Directory Project</a> description from appearing in search results.', [':opendirectory' => 'http://www.dmoz.org/']),
      'noydir' => $this->t('noydir - Prevents Yahoo! from listing this page in the <a href=":ydir">Yahoo! Directory</a>.', [':ydir' => 'http://dir.yahoo.com/']),
      'noimageindex' => $this->t('noimageindex - Prevent search engines from indexing images on this page.'),
      'notranslate' => $this->t('notranslate - Prevent search engines from offering to translate this page in search results.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTestFormXpath(): array {
    $paths = [];
    foreach ($this->formValues() as $key => $value) {
      $paths[] = "//input[@name='robots[{$key}]' and @type='checkbox']";
    }
    return $paths;
  }

  /**
   * {@inheritdoc}
   */
  public function getTestFormData(): array {
    return [
      // @todo Expand this?
      'robots[index]' => TRUE,
      'robots[noydir]' => TRUE,
      // 'robots[follow]',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTestOutputValuesXpath(array $values): array {
    // This tag outputs its multiple possible values as a comma-separated string
    // so just use the standard test output once the values are joined together
    // as a single string.
    $new_values = [];
    foreach ($values as $form_field_name => $value) {
      // The strings are stored as e.g. "robots[index]", "robots[noydir]", etc.
      // So in order to get the value names we need to remove the first part
      // and the wrapping brackets.
      $new_values[] = substr($form_field_name, 7, -1);
    }
    return parent::getTestOutputValuesXpath([implode(', ', $new_values)]);
  }

}
