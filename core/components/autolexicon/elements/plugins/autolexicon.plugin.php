<?php
/**
 * AutoLexicon
 *
 * Copyright 2012 Oleg Pryadko <oleg@websitezen.com>
 * Based on Babel 2.2.5 by Jakob Class <jakob.class@class-zec.de>
 *
 * This file is part of AutoLexicon for MODX Revolution.
 *
 * AutoLexicon is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * AutoLexicon is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * AutoLexicon; if not, write to the Free Software Foundation, Inc., 59 Temple Place,
 * Suite 330, Boston, MA 02111-1307 USA
 *
 * @package autolexicon
 */
/**
 * AutoLexicon Plugin to synchronize resources and lexicon entries.
 *
 */
/**
 * @var MODx $modx
 * @var array $scriptProperties
 * @var AutoLexicon $autolexicon
 * @var modResource $resource
 * @var modResource $unlinkedResource
 * @var modResource $newResource
 * @var modResource $targetResource
 * @var modContext $context
 */
/*
$events = array(
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
);
*/
// todo-important: remove debug req.
if (!$modx->user->get('id') == 1) return;

if (!$modx->getOption('autolexicon.enabled',null,false)) return;

$autolexicon = $modx->getService('autolexicon', 'AutoLexicon', $modx->getOption('autolexicon.core_path', null, $modx->getOption('core_path') . 'components/autolexicon/') . 'model/autolexicon/', $scriptProperties);
if (!($autolexicon instanceof AutoLexicon)) return;

$class = $modx->context->get('key') == 'mgr' ? 'AutoLexiconEventHandlerManager' : 'AutoLexiconEventHandlerWeb';
$handler = $autolexicon->getEventHandler($class);
$o = $handler->handleEvent($modx->event->name, $modx->event->params);
if ($o) return $o;

return;
