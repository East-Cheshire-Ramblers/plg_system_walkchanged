<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  System.walkchanged
 */

namespace Ramblerseastcheshire\Plugin\System\Walkchanged\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;

/**
 * Colours changed Ramblers walk items in the final rendered page output.
 */
final class Walkchanged extends CMSPlugin implements SubscriberInterface
{
	/**
	 * @var CMSApplicationInterface
	 */
	protected $app;

	public static function getSubscribedEvents(): array
	{
		return [
			'onAfterRender' => 'onAfterRender',
		];
	}

	public function onAfterRender(): void
	{
		if (!$this->app->isClient('site')) {
			return;
		}

		$body = $this->app->getBody();

		if ($body === '' || stripos($body, '<html') === false) {
			return;
		}

		$marker = (string) $this->params->get('marker', '***');

		if ($marker === '' || strpos($body, $marker) === false) {
			return;
		}

		$colour = $this->normaliseColour((string) $this->params->get('colour', '#F08050'));
		$cancelTerms = $this->normaliseList((string) $this->params->get('cancel_terms', 'cancelled,canceled'));

		$dom = new \DOMDocument('1.0', 'UTF-8');
		$previous = libxml_use_internal_errors(true);
		$loaded = $dom->loadHTML('<?xml encoding="UTF-8">' . $body, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
		libxml_clear_errors();
		libxml_use_internal_errors($previous);

		if (!$loaded) {
			return;
		}

		$xpath = new \DOMXPath($dom);
		$textNodes = $xpath->query('//text()[contains(., ' . $this->xpathLiteral($marker) . ')]');
		$changed = false;

		if (!$textNodes instanceof \DOMNodeList) {
			return;
		}

		foreach (iterator_to_array($textNodes) as $textNode) {
			if (!$textNode instanceof \DOMText || !$textNode->parentNode instanceof \DOMElement) {
				continue;
			}

			if ($this->isIgnoredTextNode($textNode)) {
				continue;
			}

			$container = $this->nearestContainer($textNode->parentNode);

			if ($this->isCancelled($container, $cancelTerms)) {
				continue;
			}

			$changedNode = $this->topLevelNodeWithin($textNode, $container);

			$this->removeMarkerFromTextNode($textNode, $marker);
			$this->prependMarker($container, $marker);
			$this->applyColourToLeadingWalkText($container, $changedNode, $colour);

			$changed = true;
		}

		if ($changed) {
			$output = $dom->saveHTML();

			if (is_string($output)) {
				$this->app->setBody($this->stripInjectedXmlDeclaration($output));
			}
		}
	}

	private function normaliseColour(string $colour): string
	{
		$colour = trim($colour);

		if (preg_match('/^#[0-9a-f]{3}([0-9a-f]{3})?$/i', $colour) === 1) {
			return $colour;
		}

		return '#F08050';
	}

	/**
	 * @return string[]
	 */
	private function normaliseList(string $value): array
	{
		$items = preg_split('/[\r\n,]+/', $value) ?: [];
		$items = array_map('trim', $items);
		$items = array_filter($items, static fn ($item) => $item !== '');

		return array_values(array_unique($items));
	}

	private function xpathLiteral(string $value): string
	{
		if (!str_contains($value, '"')) {
			return '"' . $value . '"';
		}

		if (!str_contains($value, "'")) {
			return "'" . $value . "'";
		}

		$parts = explode('"', $value);
		$quoted = array_map(static fn ($part) => '"' . $part . '"', $parts);

		return 'concat(' . implode(', \'"\', ', $quoted) . ')';
	}

	private function isIgnoredTextNode(\DOMText $node): bool
	{
		for ($current = $node->parentNode; $current instanceof \DOMElement; $current = $current->parentNode) {
			if (in_array(strtolower($current->nodeName), ['script', 'style', 'textarea', 'title'], true)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Skip walks already marked as cancelled, including struck-through output.
	 *
	 * @param string[] $cancelTerms
	 */
	private function isCancelled(\DOMElement $node, array $cancelTerms): bool
	{
		$container = $this->nearestContainer($node);
		$text = strtolower($container->textContent ?? '');

		foreach ($cancelTerms as $term) {
			if ($term !== '' && str_contains($text, strtolower($term))) {
				return true;
			}
		}

		for ($current = $node; $current instanceof \DOMElement; $current = $current->parentNode) {
			$style = strtolower($current->getAttribute('style'));

			if (str_contains($style, 'line-through') || strtolower($current->nodeName) === 's' || strtolower($current->nodeName) === 'strike') {
				return true;
			}
		}

		return false;
	}

	private function nearestContainer(\DOMElement $node): \DOMElement
	{
		for ($current = $node; $current instanceof \DOMElement; $current = $current->parentNode) {
			$name = strtolower($current->nodeName);

			if (in_array($name, ['li', 'tr', 'p', 'article', 'section', 'div'], true)) {
				return $current;
			}
		}

		return $node;
	}

	private function topLevelNodeWithin(\DOMNode $node, \DOMElement $container): \DOMNode
	{
		$current = $node;

		while ($current->parentNode instanceof \DOMNode && !$current->parentNode->isSameNode($container)) {
			$current = $current->parentNode;
		}

		return $current;
	}

	private function applyColour(\DOMElement $node, string $colour): void
	{
		$style = trim($node->getAttribute('style'));
		$style = preg_replace('/(^|;)\s*color\s*:[^;]*/i', '', $style) ?? '';
		$style = trim($style, " \t\n\r\0\x0B;");

		if ($style !== '') {
			$style .= '; ';
		}

		$node->setAttribute('style', $style . 'color: ' . $colour . ';');
	}

	private function removeMarkerFromTextNode(\DOMText $node, string $marker): void
	{
		$node->nodeValue = preg_replace('/\s*' . preg_quote($marker, '/') . '\s*/', ' ', $node->nodeValue) ?? $node->nodeValue;
		$node->nodeValue = preg_replace('/\s{2,}/', ' ', $node->nodeValue) ?? $node->nodeValue;
		$node->nodeValue = ltrim($node->nodeValue);
	}

	private function prependMarker(\DOMElement $container, string $marker): void
	{
		$firstTextNode = $this->firstMeaningfulTextNode($container);

		if ($firstTextNode instanceof \DOMText) {
			$value = ltrim($firstTextNode->nodeValue);

			if (!str_starts_with($value, $marker)) {
				$firstTextNode->nodeValue = $marker . ' ' . $value;
			}

			return;
		}

		$container->appendChild($container->ownerDocument->createTextNode($marker . ' '));
	}

	private function firstMeaningfulTextNode(\DOMNode $node): ?\DOMText
	{
		foreach ($node->childNodes as $child) {
			if ($child instanceof \DOMText && trim($child->nodeValue) !== '') {
				return $child;
			}

			if ($child instanceof \DOMElement && !in_array(strtolower($child->nodeName), ['script', 'style'], true)) {
				$match = $this->firstMeaningfulTextNode($child);

				if ($match instanceof \DOMText) {
					return $match;
				}
			}
		}

		return null;
	}

	private function applyColourToLeadingWalkText(\DOMElement $container, \DOMNode $changedNode, string $colour): void
	{
		foreach (iterator_to_array($container->childNodes) as $child) {
			$this->applyColourToNode($child, $colour);

			if ($child->isSameNode($changedNode)) {
				break;
			}
		}
	}

	private function applyColourToNode(\DOMNode $node, string $colour): void
	{
		if ($node instanceof \DOMElement) {
			$this->applyColour($node, $colour);

			return;
		}

		if (!$node instanceof \DOMText || trim($node->nodeValue) === '') {
			return;
		}

		$span = $node->ownerDocument->createElement('span');
		$span->setAttribute('style', 'color: ' . $colour . ';');
		$span->appendChild($node->ownerDocument->createTextNode($node->nodeValue));

		$node->parentNode?->replaceChild($span, $node);
	}

	private function stripInjectedXmlDeclaration(string $html): string
	{
		return preg_replace('/^<\?xml encoding="UTF-8"\?>\s*/', '', $html) ?? $html;
	}
}
