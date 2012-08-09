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
 * AutoLexicon French language file
 * 
 * @author Jakob Class <jakob.class@class-zec.de>
 *
 * @package autolexicon
 * @subpackage lexicon
 * 
 * @todo complete autolexicon.language_xx entries for every language
 */

$_lang['autolexicon.tv_caption'] = 'Liens de traduction de AutoLexicon';
$_lang['autolexicon.tv_description'] = 'Mis à jour par le plugin AutoLexicon. Veuillez ne pas modifier!';
$_lang['autolexicon.create_translation'] = 'Créer une traduction';
$_lang['autolexicon.unlink_translation'] = 'Délier la traduction';
$_lang['autolexicon.link_translation_manually'] = 'ou <strong>lier manuellement une traduction</strong>:';
$_lang['autolexicon.id_of_target'] = 'ID of target:';
$_lang['autolexicon.copy_tv_values'] = 'Copy synchronized TVs to target';
$_lang['autolexicon.save'] = 'Sauvegarder';
$_lang['autolexicon.translation_pending'] = '[traduction en attente]';

/* language names */
$_lang['autolexicon.language_ar'] = 'Arabe';
$_lang['autolexicon.language_bg'] = 'Bulgare';
$_lang['autolexicon.language_ca'] = 'Catalan';
$_lang['autolexicon.language_cs'] = 'Tchèque';
$_lang['autolexicon.language_da'] = 'Danois';
$_lang['autolexicon.language_de'] = 'Allemand';
$_lang['autolexicon.language_en'] = 'Anglais';
$_lang['autolexicon.language_es'] = 'Espagnol';
$_lang['autolexicon.language_fa'] = 'Perse';
$_lang['autolexicon.language_fi'] = 'Finnois';
$_lang['autolexicon.language_fr'] = 'Français';
$_lang['autolexicon.language_he'] = 'Hébreu';
$_lang['autolexicon.language_hu'] = 'Hongrois';
$_lang['autolexicon.language_id'] = 'Indonésien';
$_lang['autolexicon.language_it'] = 'Italien';
$_lang['autolexicon.language_ja'] = 'Japonais';
$_lang['autolexicon.language_ko'] = 'Coréen';
$_lang['autolexicon.language_lt'] = 'Lituanien';
$_lang['autolexicon.language_ms'] = 'Malais';
$_lang['autolexicon.language_nl'] = 'Néerlandais';
$_lang['autolexicon.language_no'] = 'Norvégien';
$_lang['autolexicon.language_pl'] = 'Polonais';
$_lang['autolexicon.language_pt'] = 'Portugais';
$_lang['autolexicon.language_ro'] = 'Roumain';
$_lang['autolexicon.language_ru'] = 'Russe';
$_lang['autolexicon.language_sk'] = 'Slovaque';
$_lang['autolexicon.language_sl'] = 'Slovène';
$_lang['autolexicon.language_sr'] = 'Serbe';
$_lang['autolexicon.language_sv'] = 'Suédois';
$_lang['autolexicon.language_tr'] = 'Turc';
$_lang['autolexicon.language_uk'] = 'Ukrainien';
$_lang['autolexicon.language_vi'] = 'Vietnamien';
$_lang['autolexicon.language_zh'] = 'Chinois';

/* error messages */
$_lang['error.invalid_context_key'] = '[[+context]] n\'est pas une clé de context valide.';
$_lang['error.invalid_resource_id'] = '[[+resource]] n\'est pas un id valide de ressource.';
$_lang['error.resource_from_other_context'] = 'La ressource [[+resource]] n\'existe pas dans le context [[+context]].';
$_lang['error.resource_already_linked'] = 'La ressource [[+resource]] est déjà liée à d\'autres ressources.';
$_lang['error.no_link_to_context'] = 'Il n\'existe aucun lien vers le contexte [[+context]].';
$_lang['error.unlink_of_selflink_not_possible'] = 'Un lien vers une « même ressource » ne être défait.';
$_lang['error.translation_in_same_context'] = 'Une traduction ne peut être créée au sein d\'un même contexte.';
$_lang['error.translation_already_exists'] = 'Il y a déjà une traduction dans le contexte [[+context]].';
$_lang['error.could_not_create_translation'] = 'Une erreur est survenue lors de la création de traduction dans le contexte [[+context]].';
