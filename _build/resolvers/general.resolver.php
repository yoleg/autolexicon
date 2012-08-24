<?php
/**
 * Just a simple resolver that does nothing.
 *
 * @package AutoLexicon
 * @subpackage build
 */
if ($object->xpdo) {
    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
        case xPDOTransport::ACTION_UPGRADE:
            $modx =& $object->xpdo;
            $modelPath = $modx->getOption(PKG_NAME_LOWER.'.core_path',null,$modx->getOption('core_path').'components/'.PKG_NAME_LOWER.'/').'model/';
            $modx->addPackage(PKG_NAME,$modelPath);

            $modx->setLogLevel(modX::LOG_LEVEL_ERROR);
        break;
    }
}
return true;
