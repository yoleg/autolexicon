<?php
/**
 * @snippet: AutoLexiconLinks
 * @package: autolexicon
 * @author: Oleg Pryadko (oleg@websitezen.com)
 * @createdon: 8/15/12
 * @license: GPL v.3 or later
 */
/**
 * @var AutoLexicon $autolexicon
 * @var MODx $modx
 * @var array $scriptProperties
 */
/**
 * Params:
 *
 * - active: the name of the class to use if the language is active
 * - tpl: the name of the chunk to use as a link template (defaults to 'AutoLexiconLinkTpl')
 * - langs: The languages to use (defaults to all configured languages)
 * - id: the ID of the resource to link to (defaults to current resource)
 * - scheme: the URL scheme (defaults to 'full')
 * - args: A JSON array of parameters to add to the URL
 * - options: A JSON array of system setting overrides to pass to the URL
 */

if (!$modx->getOption('autolexicon.enabled',null,false)) return '';

$autolexicon = $modx->getService('autolexicon', 'AutoLexicon', $modx->getOption('autolexicon.core_path', null, $modx->getOption('core_path') . 'components/autolexicon/') . 'model/autolexicon/', $scriptProperties);
if (!($autolexicon instanceof AutoLexicon)) return;
$tpl = $modx->getOption('tpl',$scriptProperties,'AutoLexiconLinkTpl');
$langs = $modx->getOption('langs',$scriptProperties,'');
$active = $modx->getOption('active_class',$scriptProperties,'active');
if(!$langs) {
    $langs = $autolexicon->config['langs'];
} else {
    $langs = explode(',',$langs);
    foreach($langs as $i => $lang) $langs[$i] = trim($lang);
}
$output = '';
foreach($langs as $lang) {
    $props = $scriptProperties;
    $props['lang'] = $lang;
    $props['url'] = $modx->runSnippet('AutoLexiconLink',$props);
    $props['active'] = ($lang == $modx->cultureKey) ? $active : '';
    $props['cultureKey'] = $lang;
    $o = $autolexicon->getChunk($tpl,$props);
    $output .= $o;
}

return $output;
