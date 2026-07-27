<?php

if (basename($_SERVER['PHP_SELF'] ?? '') === 'maple_html.php') {
    http_response_code(403);
    exit('403 - Access Forbidden');
}

/**
 * Clean user-authored HTML with a small allowlist that is compatible with PHP 8.
 */
function maple_clean_html(?string $html, bool $allowMedia = false): string
{
    if ($html === null || $html === '') {
        return '';
    }

    $allowedElements = [
        'p', 'br', 'b', 'strong', 'i', 'em', 'u', 's',
        'ol', 'ul', 'li', 'blockquote', 'pre', 'code',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'a',
    ];
    if ($allowMedia) {
        $allowedElements = array_merge(
            $allowedElements,
            ['div', 'span', 'img', 'iframe', 'table', 'thead', 'tbody', 'tr', 'th', 'td']
        );
    }

    $document = new DOMDocument('1.0', 'UTF-8');
    $previous = libxml_use_internal_errors(true);
    $document->loadHTML(
        '<!DOCTYPE html><html><body><div id="maple-clean-root">'
        . $html
        . '</div></body></html>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    $root = $document->getElementById('maple-clean-root');
    if (!$root) {
        return '';
    }

    $cleanNode = static function (DOMNode $node) use (&$cleanNode, $allowedElements, $allowMedia): void {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMComment) {
                $node->removeChild($child);
                continue;
            }
            if (!($child instanceof DOMElement)) {
                continue;
            }

            $tag = strtolower($child->tagName);
            if (!in_array($tag, $allowedElements, true)) {
                if (in_array($tag, ['script', 'style', 'object', 'embed'], true)) {
                    $node->removeChild($child);
                    continue;
                }
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }

            $allowedAttributes = [];
            if ($tag === 'a') {
                $allowedAttributes = ['href', 'title', 'target'];
            } elseif ($allowMedia && $tag === 'img') {
                $allowedAttributes = ['src', 'alt', 'title', 'width', 'height'];
            } elseif ($allowMedia && $tag === 'iframe') {
                $allowedAttributes = ['src', 'title', 'width', 'height', 'allowfullscreen'];
            }

            foreach (iterator_to_array($child->attributes) as $attribute) {
                if (!in_array(strtolower($attribute->name), $allowedAttributes, true)) {
                    $child->removeAttributeNode($attribute);
                }
            }

            if ($child->hasAttribute('href')
                && !preg_match('~^(?:https?://|mailto:|[/?#])~i', $child->getAttribute('href'))) {
                $child->removeAttribute('href');
            }
            if ($child->hasAttribute('src')) {
                $source = $child->getAttribute('src');
                $validSource = $tag === 'iframe'
                    ? preg_match('~^https://(?:www\.)?(?:youtube(?:-nocookie)?\.com/embed/|player\.vimeo\.com/video/)~i', $source)
                    : preg_match('~^(?:https?://|/)~i', $source);
                if (!$validSource) {
                    $child->removeAttribute('src');
                }
            }
            if ($child->getAttribute('target') === '_blank') {
                $child->setAttribute('rel', 'noopener noreferrer');
            }

            $cleanNode($child);
        }
    };
    $cleanNode($root);

    $output = '';
    foreach ($root->childNodes as $child) {
        $output .= $document->saveHTML($child);
    }
    return $output;
}
