<?php
/**
 * @package AutoLexicon
 * @var MODx $modx
 * @var array $sources
 * @var array $scriptProperties
 */
$plugins= array();
$plugins['AutoLexicon'] = array(
    'description' => 'Iterates through a template chunk of each configured language.',
    'properties' => array(
    ),
    'events' => array(
        'OnInitCulture' => array('priority' => 10,),
        'OnHandleRequest' => array('priority' => 10,),
        'OnLoadWebDocument' => array('priority' => 10,),
        'OnDocFormPrerender' => array('priority' => 10,),
        'OnDocFormRender' => array('priority' => 10,),
        'OnDocFormSave' => array('priority' => 10,),
        'OnSiteRefresh' => array('priority' => 10,),
        'OnEmptyTrash' => array('priority' => 10,),
        'OnContextBeforeRemove' => array('priority' => 10,),
        'OnContextRemove' => array('priority' => 10,),
        'OnResourceDuplicate' => array('priority' => 10,),
    ),
);
return $plugins;
