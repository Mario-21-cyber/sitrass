<?php

class Validator {
    protected $errors = [];
    protected $data = [];

    public function __construct($data) {
        $this->data = $data;
    }

    public function required($field, $label) {
        if (empty(trim($this->data[$field] ?? ''))) {
            $this->errors[$field] = "$label ay kinakailangan.";
        }
        return $this;
    }

    public function email($field, $label) {
        if (!empty($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "$label ay hindi wastong email format.";
        }
        return $this;
    }

    public function minLength($field, $label, $length) {
        if (!empty($this->data[$field]) && strlen($this->data[$field]) < $length) {
            $this->errors[$field] = "$label ay dapat hindi bababa sa $length na characters.";
        }
        return $this;
    }

    public function matches($field, $matchField, $label) {
        if (($this->data[$field] ?? '') !== ($this->data[$matchField] ?? '')) {
            $this->errors[$field] = "$label ay hindi magkatugma.";
        }
        return $this;
    }

    public function phone($field, $label) {
        if (!empty($this->data[$field]) && !preg_match('/^\+63\d{10}$/', $this->data[$field])) {
            $this->errors[$field] = "$label ay dapat nasa format na +639XXXXXXXXX.";
        }
        return $this;
    }

    public function passes() {
        return empty($this->errors);
    }

    public function getErrors() {
        return $this->errors;
    }

    public function firstError() {
        return reset($this->errors) ?: null;
    }
}