<?php
/**
 * @package AutoLexicon
 * @var MODx $modx
 * @var array $sources
 * @var array $scriptProperties
 */
$plugins= array();
$plugins['AutoLexicon'] = array(
    'description' => 'Loads lexicon entries when resources are loaded, and saves lexicon entries when resources are saved .',
    'properties' => array(
    ),
    'events' => array(
        'OnInitCulture' => array('priority' => 20,),
        'OnHandleRequest' => array('priority' => 20,),
        'OnLoadWebDocument' => array('priority' => 20,),
        'OnManagerPageInit' => array('priority' => 20,),
        'OnDocFormPrerender' => array('priority' => 20,),
        'OnDocFormRender' => array('priority' => 20,),
        'OnDocFormSave' => array('priority' => 20,),
        'OnSiteRefresh' => array('priority' => 20,),
        'OnEmptyTrash' => array('priority' => 20,),
        'OnContextBeforeRemove' => array('priority' => 20,),
        'OnContextRemove' => array('priority' => 20,),
        'OnResourceDuplicate' => array('priority' => 20,),
    ),
);
return $plugins;
