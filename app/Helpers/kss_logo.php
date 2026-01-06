<?php
/**
 * Helper para exibir a logo da KSS
 * 
 * @param string $class Classes CSS adicionais
 * @param string $alt Texto alternativo
 * @param int $height Altura da logo em pixels
 * @return string HTML da logo ou texto fallback
 */
function kss_logo(string $class = '', string $alt = 'KSS ASSISTÊNCIA 360°', int $height = 40): string
{
    $logoUrl = \App\Core\Url::kssLogo();
    
    if (!empty($logoUrl)) {
        return sprintf(
            '<img src="%s" alt="%s" class="%s" style="height: %dpx; width: auto;" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'flex\';">' .
            '<div class="flex items-center space-x-2 %s" style="display: none;">' .
            '<span class="text-green-600 font-bold text-lg">KSS</span>' .
            '<span class="text-gray-600 text-sm">ASSISTÊNCIA 360°</span>' .
            '</div>',
            htmlspecialchars($logoUrl),
            htmlspecialchars($alt),
            htmlspecialchars($class),
            $height,
            htmlspecialchars($class)
        );
    }
    
    // Fallback: texto estilizado
    return sprintf(
        '<div class="flex items-center space-x-2 %s">' .
        '<span class="text-green-600 font-bold text-lg">KSS</span>' .
        '<span class="text-gray-600 text-sm">ASSISTÊNCIA 360°</span>' .
        '</div>',
        htmlspecialchars($class)
    );
}

/**
 * Retorna apenas a URL da logo da KSS
 */
function kss_logo_url(): string
{
    return \App\Core\Url::kssLogo();
}

