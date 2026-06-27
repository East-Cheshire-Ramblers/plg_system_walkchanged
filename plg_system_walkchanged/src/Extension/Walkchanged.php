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
 * Colours changed Ramblers walk titles in the final rendered page output.
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
		$removeMarker = (bool) $this->params->get('remove_marker', 1);
		$selectors = $this->normaliseList((string) $this->params->get('selectors', "a\nstrong\nb\n.walk-title\n.event-title\n.rwalk-title"));
		$cancelTerms = $this->normaliseList((string) $this->params->get('cancel_terms', 'cancelled,canceled'));

		if ($selectors === []) {
			return;
		}

		$dom = new \DOMDocument('1.0', 'UTF-8');
		$previous = libxml_use_internal_errors(true);
		$loaded = $dom->loadHTML('<?xml encoding="UTF-8">' . $body, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
		libxml_clear_errors();
		libxml_use_internal_errors($previous);

		if (!$loaded) {
			return;
		}

		$xpath = new \DOMXPath($dom);
		$changed = false;

		foreach ($selectors as $selector) {
			$query = $this->selectorToXPath($selector);

			if ($query === null) {
				continue;
			}

			$nodes = $xpath->query($query);

			if (!$nodes instanceof \DOMNodeList) {
				continue;
			}

			foreach ($nodes as $node) {
				if (!$node instanceof \DOMElement) {
					continue;
				}

				$text = $node->textContent ?? '';

				if ($text === '' || strpos($text, $marker) === false || $this->isCancelled($node, $cancelTerms)) {
					continue;
				}

				$this->applyColour($node, $colour);

				if ($removeMarker) {
					$this->removeMarkerFromTextNodes($node, $marker);
				}

				$changed = true;
			}
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

	private function selectorToXPath(string $selector): ?string
	{
		$selector = trim($selector);

		if ($selector === '') {
			return null;
		}

		if ($selector[0] === '.') {
			$class = substr($selector, 1);

			if (!$this->isSafeCssIdentifier($class)) {
				return null;
			}

			return "//*[contains(concat(' ', normalize-space(@class), ' '), ' " . $class . " ')]";
		}

		if (!$this->isSafeCssIdentifier($selector)) {
			return null;
		}

		return '//' . strtolower($selector);
	}

	private function isSafeCssIdentifier(string $value): bool
	{
		return preg_match('/^[A-Za-z][A-Za-z0-9_-]*$/', $value) === 1;
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

			if (in_array($name, ['li', 'tr', 'article', 'section', 'div'], true)) {
				return $current;
			}
		}

		return $node;
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

	private function removeMarkerFromTextNodes(\DOMNode $node, string $marker): void
	{
		if ($node instanceof \DOMText) {
			$node->nodeValue = str_replace($marker, '', $node->nodeValue);

			return;
		}

		foreach (iterator_to_array($node->childNodes) as $child) {
			$this->removeMarkerFromTextNodes($child, $marker);
		}
	}

	private function stripInjectedXmlDeclaration(string $html): string
	{
		return preg_replace('/^<\?xml encoding="UTF-8"\?>\s*/', '', $html) ?? $html;
	}
}
