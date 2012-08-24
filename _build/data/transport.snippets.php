<?php
/**
 * @var MODx $modx
 * @var array $sources
 * @var array $scriptProperties
 */
$snippets= array();
$snippets['AutoLexiconLinks'] = array(
    'description' => 'Iterates through a template chunk of each configured language.',
    'properties' => array(
        'tpl' => 'AutoLexiconLinkTpl',
        'langs' => '',
        'active_class' => 'active',
    )
);
$snippets['AutoLexiconLink'] = array(
    'description' => 'Produces the URL for one configured language.',
    'properties' => array(
        'lang' => '',
        'id' => '',
        'scheme' => 'full',
        'args' => '[]',
        'options' => '[]',
    ),
);
return $snippets;
