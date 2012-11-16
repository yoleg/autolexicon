<?php
/**
 * General resolver.
 *
 * @package AutoLexicon
 * @subpackage build
 */
/**
 * @var xPDOObject $object
 * @var MODx $modx
 * @var array $options // install/user options
 */
if ($object->xpdo) {
    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
        case xPDOTransport::ACTION_UPGRADE:
            // always make sure log level set to INFO for proper installation feedback
            $modx =& $object->xpdo;
//            $modx->setLogLevel(modX::LOG_LEVEL_INFO);
//            $modelPath = $modx->getOption(PKG_NAME_LOWER.'.core_path',null,$modx->getOption('core_path').'components/'.PKG_NAME_LOWER.'/').'model/';
//            $modx->addPackage(PKG_NAME,$modelPath);
            $modx->cacheManager->refresh();
        break;
    }
}
return true;
