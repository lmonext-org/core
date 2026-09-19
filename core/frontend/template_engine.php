<?php
/**
 * Project: LMOnext
 * Filename: template_engine.php
 * Fileversion: 3.0.0
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 * Kompatibilitätsschicht für die frühere monolithische frontend/template_engine.php.
 * Die eigentliche Implementierung liegt jetzt unter src/Template/TemplateEngine.php.
 * Diese Datei lädt die Klasse und stellt alle bisherigen globalen Funktionsnamen
 * unverändert als dünne Delegations-Wrapper bereit.
 */
declare(strict_types = 1);

require_once __DIR__ . '/../src/Template/TemplateEngine.php';

use LMOnext\Template\TemplateEngine;

const TEMPLATE_DIR         = __DIR__ . '/../template';
const TEMPLATE_SESSION_KEY = 'lmonext_template';
const DEFAULT_TEMPLATE     = 'default';

function getAvailableTemplates() : array           { return TemplateEngine::getAvailableTemplates(); }
function resolveActiveTemplate(string $configuredDefault, bool $allowSwitch) : string { return TemplateEngine::resolveActiveTemplate($configuredDefault, $allowSwitch); }
function setActiveTemplateName(string $name) : void { TemplateEngine::setActiveTemplateName($name); }
function getActiveTemplateName() : string          { return TemplateEngine::getActiveTemplateName(); }
function substitutePlaceholders(string $html, array $vars) : string { return TemplateEngine::substitutePlaceholders($html, $vars); }
function loadTemplateFile(string $relativePath, array $vars) : string { return TemplateEngine::loadTemplateFile($relativePath, $vars); }
function renderPartial(string $partialName, array $vars = []) : string { return TemplateEngine::renderPartial($partialName, $vars); }
function renderTemplateSwitcher(bool $allowSwitch) : string { return TemplateEngine::renderTemplateSwitcher($allowSwitch); }
function renderTemplate(string $activeTemplate, string $page, array $vars = []) : void { TemplateEngine::renderTemplate($activeTemplate, $page, $vars); }
