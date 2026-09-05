<?php

namespace App\Services\DynamicTable;

class DynamicSanitizer
{
  private $service; // //

  public function __construct($service) // //
  {
    $this->service = $service; // //
  }

  public function applySanitization(string $table, array $data): array // //
  {
    $profile = $this->service->getProfileByTable($table); // //

    foreach ($data as $field => $value) {

      if (!is_string($value)) {
        continue;
      }

      $rules = $profile['sanitize'][$field] ?? null; // //

      $data[$field] = $this->sanitizeValue($value, $rules); // //
    }

    return $data; // //
  }

  public function sanitizeValue(?string $value, ?array $rules = null): string // //
  {
    if ($value === null) {
      return ''; // //
    }

    $value = trim((string)$value); // //

    // Hapus control characters
    $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value); // //

    // Field editor dokumen yang ditandai eksplisit mempertahankan struktur
    // HTML, tetapi tetap melalui whitelist tag, atribut, URL, dan CSS.
    if (!empty($rules['html'])) {
      return $this->sanitizeHtml($value, $rules);
    }

    // Normalize multi space
    $value = $this->normalizeSpaces($value); // //

    // Strip HTML
    $value = strip_tags($value); // //

    if (!empty($rules['case'])) {
      switch ($rules['case']) {
        case 'upper':
          $value = mb_strtoupper($value);
          break;
        case 'lower':
          $value = mb_strtolower($value);
          break;
        case 'title':
          $value = mb_convert_case($value, MB_CASE_TITLE);
          break;
      }
    }

    return $value; // //
  }

  private function sanitizeHtml(string $html, array $rules): string
  {
    $allowedTags = $rules['tags'] ?? [
      'p','br','div','span','strong','b','em','i','u','s','strike',
      'ul','ol','li','h1','h2','h3','h4','blockquote','a',
      'table','thead','tbody','tfoot','tr','th','td',
      'figure','figcaption','img','small'
    ];
    $allowedAttributes = $rules['attributes'] ?? [
      'href','src','alt','title','class','data-rde-type','style',
      'colspan','rowspan','width','height','target','rel'
    ];

    if (!class_exists(\DOMDocument::class)) {
      $tags = '<'.implode('><', $allowedTags).'>';
      $clean = strip_tags($html, $tags);
      $clean = preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/iu', '', $clean);
      return preg_replace('/(href|src)\s*=\s*(["\'])\s*(?:javascript|vbscript):.*?\2/iu', '$1="#"', $clean);
    }

    $document = new \DOMDocument('1.0', 'UTF-8');
    $previous = libxml_use_internal_errors(true);
    $document->loadHTML('<?xml encoding="UTF-8"><div id="sesendok-html-root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    $root = $document->getElementById('sesendok-html-root');
    if (!$root) return '';

    $xpath = new \DOMXPath($document);
    foreach (iterator_to_array($xpath->query('.//comment()', $root)) as $comment) {
      $comment->parentNode?->removeChild($comment);
    }
    foreach (array_reverse(iterator_to_array($xpath->query('.//*', $root))) as $element) {
      $tag = strtolower($element->nodeName);
      if (!in_array($tag, $allowedTags, true)) {
        if (in_array($tag, ['script','style','iframe','object','embed'], true)) {
          $element->parentNode?->removeChild($element);
          continue;
        }
        $parent = $element->parentNode;
        if ($parent) {
          while ($element->firstChild) $parent->insertBefore($element->firstChild, $element);
          $parent->removeChild($element);
        }
        continue;
      }

      foreach (iterator_to_array($element->attributes ?? []) as $attribute) {
        $name = strtolower($attribute->nodeName);
        $value = trim($attribute->nodeValue ?? '');
        if (!in_array($name, $allowedAttributes, true) || str_starts_with($name, 'on')) {
          $element->removeAttribute($name);
          continue;
        }
        if (in_array($name, ['href','src'], true) && !$this->safeUrl($value, $name === 'src')) {
          $element->removeAttribute($name);
        } elseif ($name === 'style') {
          $safeStyle = $this->sanitizeStyle($value);
          $safeStyle === '' ? $element->removeAttribute('style') : $element->setAttribute('style', $safeStyle);
        } elseif ($name === 'class') {
          $element->setAttribute('class', preg_replace('/[^a-z0-9 _-]/i', '', $value));
        } elseif ($name === 'target' && !in_array($value, ['_blank','_self'], true)) {
          $element->removeAttribute('target');
        }
      }
      if ($tag === 'a' && $element->getAttribute('target') === '_blank') {
        $element->setAttribute('rel', 'noopener noreferrer');
      }
    }

    $result = '';
    foreach ($root->childNodes as $child) $result .= $document->saveHTML($child);
    return trim($result);
  }

  private function safeUrl(string $url, bool $image): bool
  {
    if ($url === '' || str_starts_with($url, '/') || str_starts_with($url, '#')) return true;
    $decoded = html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $scheme = strtolower((string)parse_url($decoded, PHP_URL_SCHEME));
    return in_array($scheme, $image ? ['http','https'] : ['http','https','mailto','tel'], true);
  }

  private function sanitizeStyle(string $style): string
  {
    $allowed = [
      'text-align','font-family','font-size','font-weight','font-style','text-decoration',
      'color','background-color','column-count','text-indent','margin','margin-left',
      'margin-right','margin-top','margin-bottom','line-height','float','max-width',
      'width','height','border-style','border-color','border-width','border-radius',
      'opacity','list-style-type','z-index','display','align-items','justify-content',
      'gap','padding','position','bottom','left','right','top','flex','min-width'
    ];
    $clean = [];
    foreach (explode(';', $style) as $declaration) {
      if (!str_contains($declaration, ':')) continue;
      [$property, $value] = array_map('trim', explode(':', $declaration, 2));
      $property = strtolower($property);
      if (!in_array($property, $allowed, true)) continue;
      if ($value === '' || preg_match('/(?:url\s*\(|expression\s*\(|javascript:|vbscript:|[<>"\'])/iu', $value)) continue;
      $clean[] = $property.':'.$value;
    }
    return implode(';', $clean);
  }

  public function normalizeSpaces(string $value): string // //
  {
    $value = trim((string)$value); // //
    return preg_replace('/\s+/u', ' ', $value); // //
  }
}
