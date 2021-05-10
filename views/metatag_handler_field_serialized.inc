<?php

class metatag_handler_field_serialized extends metatag_handler_field_entity {

  var $bundle_field;
  var $uses_default;
  var $raw_value;

  function option_definition() {
    $options = parent::option_definition();
    $options['metatag'] = array(
      'contains' => array(
        'use_default' => array('default' => FALSE, 'bool' => TRUE),
        'replace_tokens' => array('default' => FALSE, 'bool' => TRUE),
      ),
    );
    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['metatag'] = array(
      '#type' => 'fieldset',
      '#title' => 'Metatag',
      '#collapsible' => TRUE,
    );
    $form['metatag']['use_default'] = array(
      '#type' => 'checkbox',
      '#title' => 'Use default metatag value',
      '#description' => 'Display default value if no value has been defined on entity.',
      '#default_value' => $this->options['metatag']['use_default']
    );

    $form['metatag']['replace_tokens'] = array(
      '#type' => 'checkbox',
      '#title' => 'Replace tokens',
      '#description' => 'Attempt to replace all tokens with their (entity) values. Note: Tokens in metatags will be replaced before Views tokens.',
      '#default_value' => $this->options['metatag']['replace_tokens']
    );
  }

  function get_value($values, $field = NULL) {
    $value = parent::get_value($values);
    if (!is_null($value)) {
      $value = @unserialize($value);
      $value = $this->get_nested_value($value, $this->definition['path']);
    }
    $this->uses_default = FALSE;
    if (is_null($value) && !empty($this->options['metatag']['use_default'])) {
      $value = $this->get_default_value($values);
      $this->uses_default = TRUE;
    }
    $this->raw_value = $value;
    return $value;
  }

  function get_default_value($values) {
    $entity_type = $this->get_entity_type($values);
    $instance = $entity_type;
    $entity = $this->get_entity($values);
    list(, , $bundle) = entity_extract_ids($entity_type, $entity);
    if (!is_null($bundle)) {
      $instance .= ":$bundle";
    }
    $defaults = metatag_config_load_with_defaults($instance);
    return $this->get_nested_value($defaults, $this->definition['path']);
  }

  function replace_tokens($value, $values) {
    $entity_type = $this->get_entity_type($values);
    $entity = $this->get_entity($values);
    // Reload the entity object from cache as it may have been altered.
    $token_type = token_get_entity_mapping('entity', $entity_type);
    $options['token data'][$token_type] = $entity;
    $options['entity'] = $entity;

    $value = metatag_get_value($this->definition['metatag'], array('value' => $value), $options);
    return $value;
  }

  /**
   * Retrieves a nested value from an array.
   */
  function get_nested_value($value, $path) {
    foreach ($path as $index) {
      if (!isset($value[$index])) {
        return NULL;
      }
      $value = $value[$index];
    }
    return $value;
  }

  function render($values) {
    $value = (string) $this->get_value($values);
    if (strlen($value) && !empty($this->options['metatag']['replace_tokens'])) {
      $value = $this->replace_tokens($value, $values);
    }
    return $this->sanitize_value($value);
  }

}
