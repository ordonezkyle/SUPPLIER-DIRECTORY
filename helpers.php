<?php
// reusable form helpers

function inputField($name, $value = '', $placeholder = '', $type = 'text', $class = 'form-control', $autocomplete = 'off') {
    return "<input autocomplete=\"" . htmlspecialchars($autocomplete) . "\" type=\"" . htmlspecialchars($type) . "\" name=\"" . htmlspecialchars($name) . "\" class=\"" . htmlspecialchars($class) . "\" placeholder=\"" . htmlspecialchars($placeholder) . "\" value=\"" . htmlspecialchars($value) . "\">";
}

function selectField($name, $options, $selected = null, $class = 'form-select') {
    $html = "<select name=\"" . htmlspecialchars($name) . "\" class=\"" . htmlspecialchars($class) . "\">";
    foreach ($options as $val => $label) {
        $sel = ((string)$val === (string)$selected) ? ' selected' : '';
        $html .= "<option value=\"" . htmlspecialchars($val) . "\"{$sel}>" . htmlspecialchars($label) . "</option>";
    }
    $html .= "</select>";
    return $html;
}

function datalistField($name, $options, $value = '', $placeholder = '', $class = 'form-control', $autocomplete = 'off') {
    $listId = $name . '_list';
    $html = "<input autocomplete=\"" . htmlspecialchars($autocomplete) . "\" type=\"text\" name=\"" . htmlspecialchars($name) . "\" class=\"" . htmlspecialchars($class) . "\" placeholder=\"" . htmlspecialchars($placeholder) . "\" value=\"" . htmlspecialchars($value) . "\" list=\"" . htmlspecialchars($listId) . "\">";
    $html .= "<datalist id=\"" . htmlspecialchars($listId) . "\">";
    foreach ($options as $opt) {
        $html .= "<option value=\"" . htmlspecialchars($opt) . "\">";
    }
    $html .= "</datalist>";
    return $html;
}
