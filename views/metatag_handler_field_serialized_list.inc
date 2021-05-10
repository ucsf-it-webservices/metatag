<?php

class metatag_handler_field_serialized_list extends metatag_handler_field_serialized {

  function option_definition() {
    $options = parent::option_definition();
    unset($options['metatags']['contains']['replace_tokens']);
    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    unset($form['metatag']['replace_tokens']);
  }

  function render($values) {
    $value = (array) $this->get_value($values);
    return $this->sanitize_value(implode(',', $value));
  }

}
