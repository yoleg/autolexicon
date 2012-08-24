<?php
/**
 * @snippet: AutoLexiconLink
 * @package: autolexicon
 * @author: Oleg Pryadko (oleg@websitezen.com)
 * @createdon: 8/15/12
 * @license: GPL v.3 or later
 */
/**
 * Params:
 *
 * - lang: the language of the link to generate (defaults to current lang)
 * - id: the ID of the resource to link to (defaults to current resource)
 * - scheme: the URL scheme (defaults to 'full')
 * - args: A JSON array of parameters to add to the URL
 * - options: A JSON array of system setting overrides to pass to the URL
 */
/**
 * @var AutoLexicon $autolexicon
 * @var modX $modx
 * @var array $scriptProperties
 */

$autolexicon = $modx->getService('autolexicon', 'AutoLexicon', $modx->getOption('autolexicon.core_path', null, $modx->getOption('core_path') . 'components/autolexicon/') . 'model/autolexicon/', $scriptProperties);
if (!($autolexicon instanceof AutoLexicon)) return;

$lang = $modx->getOption('lang',$scriptProperties,'');
if (empty($lang)) $lang = $modx->cultureKey;
$id = $modx->getOption('id',$scriptProperties,'');
if (empty($id)) $id = $modx->resource->get('id');
$scheme = $modx->getOption('scheme',$scriptProperties,'full');
$args = $modx->getOption('args',$scriptProperties,'[]');
$args = $modx->fromJSON($args);
$args = is_array($args) ? $args : array();
$options = $modx->getOption('options',$scriptProperties,'[]');
$options = $modx->fromJSON($options);
$options = is_array($options) ? $options : array();
if (in_array($lang, $autolexicon->config['langs'])) {
    $o = $autolexicon->makeUrl($id, $lang, $args, $scheme, $options);
} else {
    $args['cultureKey'] = $lang;
    $o = $modx->makeUrl($id,'',$args,$scheme,$options);
}
return $o;
