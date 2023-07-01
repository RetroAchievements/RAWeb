<?php

declare(strict_types=1);

/**
 * @file Converter.php
 * @brief This file contains the Converter class.
 * @details
 *
 * @author Filippo F. Fadda
 */

namespace App\Support\Shortcode\Converter;

/**
 * @deprecated Only used for news HTML sync. TODO: Remove when done
 */
abstract class Converter
{
    public function __construct(protected string $text, protected string $id = '')
    {
    }
}
