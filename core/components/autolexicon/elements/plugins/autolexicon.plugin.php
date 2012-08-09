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
 * Web Events: OnHandleRequest,OnLoadWebDocument
 * Mgr Events: OnDocFormPrerender,OnDocFormRender,OnDocFormSave,
 *             OnEmptyTrash,OnContextRemove,OnResourceDuplicate
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
// todo-important: remove debug req. here and in Babel MODX plugin
if(!$modx->user->get('id') == 1) return;

$autolexicon = $modx->getService('autolexicon','AutoLexicon',$modx->getOption('autolexicon.core_path',null,$modx->getOption('core_path').'components/autolexicon/').'model/autolexicon/',$scriptProperties);
if (!($autolexicon instanceof AutoLexicon)) return;

switch ($modx->event->name) {
// front-end events
case 'OnHandleRequest':
    if ($modx->context->get('key') == 'mgr') continue;
    $autolexicon->OnHandleRequest();
    break;
case 'OnLoadWebDocument':
    if ($modx->context->get('key') == 'mgr') continue;
    $resource =& $modx->resource;
    if(!$resource) {break;}
    $autolexicon->OnLoadWebDocument($resource);
    break;
// back-end events
case 'OnDocFormPrerender':
case 'OnDocFormRender':
case 'OnDocFormSave':
    $resource =& $modx->event->params['resource'];
    if(!$resource) {break;}
    $autolexicon->{$modx->event->name}($resource);
    break;

// unfinished front-end events
case 'OnLoadWebPageCache':
    if ($modx->context->get('key') == 'mgr') continue;
     break;
case 'OnWebPageInit':
    if ($modx->context->get('key') == 'mgr') continue;
    break;

// unfinished/unused back-end events
case 'OnManagerPageAfterRender':
    $controller =& $modx->event->params['controller'];
    if(!$controller) {break;}
//    $autolexicon->OnManagerPageAfterRender($controller);
    break;
case 'OnResourceTVFormRender':
    if($modx->event->params['hidden']) break;
    $resource_id =& $modx->event->params['resource'];
    if(!$resource_id) {break;}
    break;
case 'OnBeforeDocFormSave':
    $resource =& $modx->event->params['resource'];
    if(!$resource) {break;}
    $data =& $modx->event->params['data'];
    break;
case 'OnResourceTVFormPrerender':
    break;
case 'OnResourceDuplicate':
    /* init AutoLexicon TV of duplicated resources */
    $resource =& $modx->event->params['newResource'];
    if(!$resource) {break;}
    break;
case 'OnEmptyTrash':
    /* remove translation links to non-existing resources */
    $deletedResourceIds =& $modx->event->params['ids'];
    if (empty($deletedResourceIds)) break;
    if(is_array($deletedResourceIds)) {
        foreach ($deletedResourceIds as $deletedResourceId) {
            continue;
        }
    }
    break;
case 'OnContextRemove':
    /* remove translation links to non-existing contexts */
    $context =& $modx->event->params['context'];
    if(!$context) {break;}
    break;
}
return;
