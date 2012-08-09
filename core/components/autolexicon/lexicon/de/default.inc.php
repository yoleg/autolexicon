<?php
/**
 * AutoLexicon
 *
 * From Babel copyright 2010 by Jakob Class <jakob.class@class-zec.de>, adapted by Oleg Pryadko <oleg@websitezen.com> for use with AutoLexicon
 *
 * This file is part of AutoLexicon.
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
 * AutoLexicon German language file
 * 
 * @author Jakob Class <jakob.class@class-zec.de>
 *
 * @package autolexicon
 * @subpackage lexicon
 * 
 * @todo complete autolexicon.language_xx entries for every language
 */

$_lang['autolexicon.tv_caption'] = 'AutoLexicon-Übersetzungslinks';
$_lang['autolexicon.tv_description'] = 'Wird über das AutoLexicon-Plugin verwaltet. Bitte nicht ändern';
$_lang['autolexicon.create_translation'] = 'Übersetzung anlegen';
$_lang['autolexicon.unlink_translation'] = 'Verknüpfung aufheben';
$_lang['autolexicon.link_translation_manually'] = 'oder <strong>Übersetzung manuell verknüpfen</strong>:';
$_lang['autolexicon.id_of_target'] = 'Ziel-ID:';
$_lang['autolexicon.copy_tv_values'] = 'Synchronisierte TVs zum Ziel kopieren';
$_lang['autolexicon.save'] = 'Speichern';
$_lang['autolexicon.translation_pending'] = '[Übersetzung ausstehend]';

/* language names */
$_lang['autolexicon.language_ar'] = 'Arabisch';
$_lang['autolexicon.language_bg'] = 'Bulgarisch';
$_lang['autolexicon.language_ca'] = 'Katalanisch';
$_lang['autolexicon.language_cs'] = 'Tschechisch';
$_lang['autolexicon.language_da'] = 'Dänisch';
$_lang['autolexicon.language_de'] = 'Deutsch';
$_lang['autolexicon.language_en'] = 'Englisch';
$_lang['autolexicon.language_es'] = 'Spanisch';
$_lang['autolexicon.language_fa'] = 'Persisch';
$_lang['autolexicon.language_fi'] = 'Finnisch';
$_lang['autolexicon.language_fr'] = 'Französisch';
$_lang['autolexicon.language_he'] = 'Hebräisch';
$_lang['autolexicon.language_hu'] = 'Ungarisch';
$_lang['autolexicon.language_id'] = 'Indonesisch';
$_lang['autolexicon.language_it'] = 'Italienisch';
$_lang['autolexicon.language_ja'] = 'Japanisch';
$_lang['autolexicon.language_ko'] = 'Koreanisch';
$_lang['autolexicon.language_lt'] = 'Litauisch';
$_lang['autolexicon.language_ms'] = 'Malaiisch';
$_lang['autolexicon.language_nl'] = 'Niederländisch';
$_lang['autolexicon.language_no'] = 'Norwegisch';
$_lang['autolexicon.language_pl'] = 'Polnisch';
$_lang['autolexicon.language_pt'] = 'Portugiesisch';
$_lang['autolexicon.language_ro'] = 'Rumänisch';
$_lang['autolexicon.language_ru'] = 'Russisch';
$_lang['autolexicon.language_sk'] = 'Slowakisch';
$_lang['autolexicon.language_sl'] = 'Slowenisch';
$_lang['autolexicon.language_sr'] = 'Serbisch';
$_lang['autolexicon.language_sv'] = 'Schwedisch';
$_lang['autolexicon.language_tr'] = 'Türkisch';
$_lang['autolexicon.language_uk'] = 'Ukrainisch';
$_lang['autolexicon.language_vi'] = 'Vietnamesisch';
$_lang['autolexicon.language_zh'] = 'Chinesisch';

/* error messages */
$_lang['error.invalid_context_key'] = '[[+context]] ist kein gültiger Kontext-Schlüssel.';
$_lang['error.invalid_resource_id'] = '[[+resource]] ist keine gültige Ressourcen-ID.';
$_lang['error.resource_from_other_context'] = 'Ressource [[+resource]] befindet sich nicht im Kontext [[+context]].';
$_lang['error.resource_already_linked'] = 'Ressource [[+resource]] ist bereits mit anderen Ressourcen verknüpft.';
$_lang['error.no_link_to_context'] = 'Für den Kontext [[+context]] existiert noch keine Verknüpfung.';
$_lang['error.unlink_of_selflink_not_possible'] = 'Die Verknüpfung einer Ressource auf sich selbst kann nciht entfernt werden.';
$_lang['error.translation_in_same_context'] = 'Eine Übersetzung kann nicht im gleichen Kontext angelegt werden.';
$_lang['error.translation_already_exists'] = 'Es existiert bereits eine Übersetzung im Kontext [[+context]].';
$_lang['error.could_not_create_translation'] = 'Beim Erstellen der Übersetzung im Kontext [[+context]] ist ein Fehler aufgetreten.';
