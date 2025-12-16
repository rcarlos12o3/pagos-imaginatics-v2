<?php

if (!function_exists('clean_text')) {
    /**
     * Limpiar texto de HTML entities y caracteres mal codificados
     */
    function clean_text(?string $text): string
    {
        if (empty($text)) {
            return '';
        }

        // Decodificar HTML entities (&amp; -> &)
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return $text;
    }
}
