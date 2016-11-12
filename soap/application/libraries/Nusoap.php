<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Nusoap class for CI
 * Load nusoap in CI
 *
 * @author    Edward Allan Florindo
 */
class Nusoap {
    public function __construct() {
        require APPPATH . '/libraries/nusoap/nusoap.php';
    }
}
