<?php

/*
 *  HyperPalenor, a plugin for MODx Evolution
 *
 *  Redirects to the new URL of moved and renamed documents
 *
 *  v14
 *
 *  Events:
 *  - OnBeforeDocFormSave
 *  - OnPageNotFound
 *  - OnDocFormRender
 *
 *  Configuration:
 *  &lang=Plugin language;text;en
 *  &tablePrefix=Plugin table prefix;text;
 *
 *  Original idea by Matthieu Baudoux
 *  Created: June 20, 2007 by Benjamin Toussaint
 *  Maintained since February 2011 by Matthieu Baudoux 
 *
 */


// debugLog() : loggue les erreurs pour un debug facile

// mode DEBUG ?
define("DEBUG", false);

if (!function_exists('debugLog'))
{
function debugLog($errorMsg_t)
{   
	global $modx;
	// si mode DEBUG
	if (DEBUG === true) {
		
		// debug log
		
		// ancienne méthode : posait probléme hors du manager
		//trigger_error("MODx: plugin HyperPalenor - ".$errorMsg_t, E_USER_NOTICE);
		
		// inscrit l'entrée dans le log du manager MODx
		if (!isset($GLOBALS['log'])) {
			// on le déclare
			$GLOBALS['log'] = new logHandler;
		}
		// paramétres
		$msg_t = $modx->db->escape($errorMsg_t);
		$internalKey_i = "1";
		$username_t = "HyperPalenor plugin";
		$action_i = "Action";
		$itemname_t = "HyperPalenor - debug";
		// on écrit dans le log
		$GLOBALS['log']->initAndWriteLog($msg_t, $internalKey_i, $username_t, $action_i, "", $itemname_t);
	}
}
} // /function_exists()


/*************************
		Paramétres
**************************/

// on inclut la classe "logHandler" de MODx
include_once dirname(__FILE__).'/log.class.inc.php';

// si on est dans le manager
if (IN_MANAGER_MODE == true) {
	// on récupére l'ID du document
	$docID_i = $id;
}
// sinon
if (empty($docID_i)) {
	// on récupére l'ID du document
	$docID_i = $modx->documentIdentifier;
}
// si pas d'ID trouvé
if (empty($docID_i)) {
	// debug log
	debugLog("No document ID found");
	// on sort
	return;
}


// langue pour l'affichage
$GLOBALS['pluginLang'] = isset($lang) ? $lang : "en";

// nom de la table utilisée par le plugin
$GLOBALS['HyperPalenorTable'] = isset($tablePrefix) ? $tablePrefix."hyperpalenor" : "hyperpalenor";


/************************
		Langues
*************************/

$GLOBALS['lang'] = array();
$GLOBALS['lang']['pluginSectionTitle']['fr'] = 			"Gestion des redirections pour cette page";
$GLOBALS['lang']['pluginSectionTitle']['en'] = 			"Redirections management for this page";
$GLOBALS['lang']['redirectionUrl']['fr'] = 				"URL de redirection";
$GLOBALS['lang']['redirectionUrl']['en'] = 				"Redirection URL";
$GLOBALS['lang']['creationDate']['fr'] = 				"Date de création";
$GLOBALS['lang']['creationDate']['en'] = 				"Creation date";
$GLOBALS['lang']['usesNumber']['fr'] = 					"Utlisation";
$GLOBALS['lang']['usesNumber']['en'] = 					"Uses";
$GLOBALS['lang']['lastUse']['fr'] = 					"Derniére utilisation";
$GLOBALS['lang']['lastUse']['en'] = 					"Last use";
$GLOBALS['lang']['creator']['fr'] = 					"Créée par";
$GLOBALS['lang']['creator']['en'] = 					"Creator";
$GLOBALS['lang']['noRedirectionFound']['fr'] = 			"Il n'existe actuellement aucune redirection pour cette page.";
$GLOBALS['lang']['noRedirectionFound']['en'] = 			"There is no redirection for this page for the moment.";
$GLOBALS['lang']['untickedCheckboxDeleted']['fr'] = 	"Les redirections décochées seront supprimées lors du prochain enregistrement.";
$GLOBALS['lang']['untickedCheckboxDeleted']['en'] = 	"Unticked redirections will be deleted at the next saving.";
$GLOBALS['lang']['newRedirectionUrl']['fr'] = 			"Nouvelle URL de redirection";
$GLOBALS['lang']['newRedirectionUrl']['en'] = 			"New redirection URL";
$GLOBALS['lang']['newRedirectionInfo']['fr'] = 			"Chemin complet de la page depuis la racine.";
$GLOBALS['lang']['newRedirectionInfo']['en'] = 			"Complete page path from root.";
$GLOBALS['lang']['never']['en'] = 						"never";
$GLOBALS['lang']['never']['fr'] = 						"jamais";
$GLOBALS['lang']['times']['en'] = 						"times";
$GLOBALS['lang']['times']['fr'] = 						"fois";


/*************************
		Fonctions
**************************/


// checkHyperPalenorTableExistence() : vérifie la présence de la table utilisée par le plugin

// v12
if (!function_exists('checkHyperPalenorTableExistence'))
{
function checkHyperPalenorTableExistence()
{   
	// vérification de l'existence de la table
	$sql_checkTable_t = "SHOW TABLES;";
	$rs_checkTable_i = mysql_query($sql_checkTable_t);
	// liste des tables trouvées
	$listeDesTables_at = array();
	while ($result_at = mysql_fetch_array($rs_checkTable_i)) {
		$listeDesTables_at[] = $result_at[0]; // nom de la table
	}
	// si la table n'existe pas (si elle n'est pas dans la liste)
	if (!in_array($GLOBALS['HyperPalenorTable'], $listeDesTables_at)) {
		// on l'indique
		return false;
	}
	
	// si la table existe
	return true;
}
} // /function_exists()


// createHyperPalenorTable() : crée la table utilisée par le plugin

// v12
if (!function_exists('createHyperPalenorTable'))
{
function createHyperPalenorTable() 
{
	// requéte pour la création de la table
	$sql_createTable_t = "CREATE TABLE `".$GLOBALS['HyperPalenorTable']."` (";
	$sql_createTable_t .= "  `id` int(11) unsigned NOT NULL auto_increment,";
	$sql_createTable_t .= "  `doc_id` int(10) unsigned default NULL,";
	$sql_createTable_t .= "  `url` varchar(255) default NULL,";
	$sql_createTable_t .= "  `creation_date` date NOT NULL default '0000-00-00',";
	$sql_createTable_t .= "  `use_count` int(10) unsigned NOT NULL default '0',";
	$sql_createTable_t .= "  `last_use_date` date NOT NULL default '0000-00-00',";
	$sql_createTable_t .= "  `creator` varchar(255) default NULL,";
	$sql_createTable_t .= "  UNIQUE KEY `url` (`url`),";
	$sql_createTable_t .= "  KEY `id` (`id`),";
	$sql_createTable_t .= "  KEY `doc_id` (`doc_id`)";
	$sql_createTable_t .= ");";
		
	// création de la table
	$rs_createTable_i = mysql_query($sql_createTable_t);
	
	// on renvoit le résultat de l'opération
	return $rs_createTable_i;
}
} // /function_exists()


// splitUrl() : renvoit un tableau avec les différentes parties de l'url


// v12
if (!function_exists('splitUrl'))
{
function splitUrl()
{
	
	// récupération du chemin et des éventuels paramétres
	// ex. : /path/to/my/page.html?fruit=apple&color=white
	$request_uri_t = $_SERVER['REQUEST_URI'];

	// récupération du chemin, de l'extension et du "suffixe" de l'url
	// bto 20071119 (v8) : l'extension et le suffixe ne sont plus obligatoires
	//preg_match("`([^?]+)(\.[^/?]+)(.*)`", $request_uri_t, $matches_at);
	preg_match("`([^?]+)(\.[^/?]+)?(.*)?`", $request_uri_t, $matches_at);
	$urlPath_t = $matches_at[1];
	$urlExtension_t = $matches_at[2];
	$urlSuffix_t = $matches_at[3];
	unset($matches_at);
	// si vide, on sort
	if (empty($urlPath_t)) {
		// debug log
		debugLog("Error while attempting to retrieve url path");
		// on sort
		return false;
	}
		
	// on sauvegarde les valeurs dans un tableau
	$urlInfo_at = array();
	$urlInfo_at['urlPrefix'] = $urlPrefix_t;
	$urlInfo_at['urlPath'] = $urlPath_t;
	$urlInfo_at['urlExtension'] = $urlExtension_t;
	$urlInfo_at['urlSuffix'] = $urlSuffix_t;
	$urlInfo_at['originalUrl'] = $urlPrefix_t.$urlPath_t.$urlExtension_t.$urlSuffix_t;
	// v7 : on ajoute une variable avec le chemin sans le prefixe
	$urlInfo_at['originalUrlWithoutPrefix'] = $urlPath_t.$urlExtension_t.$urlSuffix_t;

	// debug log
	debugLog("URL path : ".$urlInfo_at['urlPath']);
	
	// on renvoit ce tableau
	return $urlInfo_at;
}
} // /function_exists()


// getCurrentUrlFromOldOne() : renvoit le nouvelle url é partir de l'ancienne


// v12
if (!function_exists('getCurrentUrlFromOldOne'))
{
function getCurrentUrlFromOldOne($url_t)
{
	global $modx;
	
	// on tente de récupérer l'ID du document correspondant é l'url
	$docIdFromUrl_at = searchHyperPalenorWithUrl($url_t, '', true);

	// si un ID est trouvé
	if ($docIdFromUrl_at != false) {
		// on crée l'url depuis cette ID
		$urlFromID_t = $modx->makeUrl($docIdFromUrl_at['doc_id']);
		// on sépare l'url et l'extension de la page
		preg_match("`(.+)(\.[^\.]+)$`", $urlFromID_t, $matches_at);
		// récupération url sans extension
		$urlSansExtension_t = $matches_at[1];
		// debug log
		debugLog("URL sans extension : ".$urlSansExtension_t);
		// récupération extension
		$extension_t = $matches_at[2];
		unset($matches_at);
		// on ajoute la partie ignorée de l'url lors de la recherche et l'extension
		$urlRedirection_t = $urlSansExtension_t.$docIdFromUrl_at['ignored_url_part'].$extension_t;
		// on ajoute le "préfixe" et le "suffixe" de l'url
		$urlRedirection_t = $urlInfo_at['urlPrefix'].$urlRedirection_t.$urlInfo_at['urlSuffix'];
		
		// bto 20071119 (v8) : on ajoute l'éventuel "query string"
		$urlRedirection_t .= $docIdFromUrl_at['query_string'];
		
		// si des parties ont été enlevées é l'url lors de la recherche
		// et donc si l'url recherchée n'existait pas en tant que redirection
		if (!empty($docIdFromUrl_at['ignored_url_part'])) {
			// on ajoute l'entrée dans la base de données
			$addEntry_b = addEntryInHyperPalenor($urlSansExtension_t, $docIdFromUrl_at['doc_id'], 1);
			// si l'ajout ne fonctionne pas
			if (!$addEntry_b) {
				// debug log
				debugLog("Error while attempting to add an entry for the specified URL");
			}
		}
		
		if($url_t == $urlRedirection_t)
		{
			return false;
		}
		// on renvoit l'url
		return $urlRedirection_t;
	}
	// sinon, si pas d'ID
	else {
		// debug log
		debugLog("No ID matching the specified URL was found");
		// on sort
		return false;
	}
}
} // /function_exists()


// searchHyperPalenorWithUrl() : cherche une redirection avec l'adresse spécifiée

// v12
if (!function_exists('searchHyperPalenorWithUrl'))
{
function searchHyperPalenorWithUrl($url_t, $ignoredUrlPart_t='', $recursive_b=false)
{
	global $modx;
	
	// on protége la valeur reéue
	$url_t = $modx->db->escape($url_t);
	// on cherche la valeur dans la table
	
	// bto 20071119 (v8) : on élargit la recherche avec "/" et ".html"
	
	//$sql_searchUrl_t = "SELECT `id`, `doc_id` FROM `".$GLOBALS['HyperPalenorTable']."` WHERE `url`='".$url_t."' AND `doc_id`>0;";
	
	// si l'url contient un "query string"
	if (strpos($url_t, "?") !== false) {
		// on sépare l'url_t du "query string"
		$queryString_t = substr($url_t, strpos($url_t, "?"), strlen($url_t));
		$url_t = substr($url_t, 0, strpos($url_t, "?"));
	}
	
	// si l'url se termine par un "/"
	if ($url_t{strlen($url_t)-1} == "/") {
		// on le supprime
		$url_t = substr($url_t, 0, -1);
	}
	
	// suffixe pour l'expression réguliére
	$urlSuffixRegexp_t = str_replace(".", "\.", $modx->config['friendly_url_suffix']);
	// si l'url se termine par le suffixe
	if (preg_match("`^(.*)".$urlSuffixRegexp_t."$`", $url_t, $matches_at)) {
		// on le supprime
		$url_t = $matches_at[1];
	}
		
	// on ajoute le préfixe et le suffixe é l'url pour la recherche
	$urlAvecPrefixeEtSuffixe_t = $modx->config['friendly_url_prefix'].$url_t.$modx->config['friendly_url_suffix'];
	
	// si on a un "query string"
	if (!empty($queryString_t))
	{
		// on ajoute le "query string" pour la recherche
		$urlAvecQueryString_t = $url_t.$queryString_t;
		
		// on ajoute le "query string" é l'url avec le préfixe et le suffixe pour la recherche
		$urlAvecPrefixeSuffixeEtQueryString_t = $urlAvecPrefixeEtSuffixe_t.$queryString_t;
	}
	
	// recherche dans la table du plugin
	$sql_searchUrl_t = "SELECT `id`, `doc_id`, `url`, CHAR_LENGTH(`url`) AS urlLength FROM `".$GLOBALS['HyperPalenorTable']."`";
	
	// on cherche l'url, l'url avec un "/" final et l'url avec préfixe et suffixe et avec "query string"
	$sql_searchUrl_t .= " WHERE (`url`='".$url_t."' OR `url`='".$url_t."/' OR `url`='".$urlAvecPrefixeEtSuffixe_t."'";
	$sql_searchUrl_t .= (!empty($queryString_t)) ? " OR `url`='".$urlAvecQueryString_t."' OR `url`='".$urlAvecPrefixeSuffixeEtQueryString_t."')" : ")";
	
	// on ne cherche évidemment que les redirections qui ménent quelque part
	$sql_searchUrl_t .= " AND `doc_id`>0";
	
	// on trie les résultats par longeur de l'url
	$sql_searchUrl_t .= " ORDER BY urlLength DESC;";
	
	// fin bto 20071119

	// debug log
	debugLog($sql_searchUrl_t);
	// requéte
	$rs_searchUrl_i = mysql_query($sql_searchUrl_t);
	// si la recherche fonctionne
	if ($rs_searchUrl_i !== false) {
		
		// bto 20071119 (v8) : on accepte plusieurs résultats, on prend juste le plus long (le premier d'aprés le tri)
		// si la recherche renvoit un seul résultat
		//if (mysql_num_rows($rs_searchUrl_i) == 1) {
			
		// on vérifie quand méme qu'on trouve quelque chose
		if (mysql_num_rows($rs_searchUrl_i) >= 1) {
				
			// récupération de l'entrée
			$result_searchUrl_at = mysql_fetch_assoc($rs_searchUrl_i);
			// on met é jour l'entrée pour indiquer son utilisation
			$updateEntryUsage_b = updateHyperPalenorEntryUsage($result_searchUrl_at['id']);
			// si la mise é jour échoue
			if (!$updateEntryUsage_b) {
				// debug log
				debugLog("Error while attempting to update entry usage");
			}
			// tableau qui contiendra l'ID et le reste de l'url
			$output_at = array();
			$output_at['doc_id'] = $result_searchUrl_at['doc_id'];
			$output_at['entry_id'] = $result_searchUrl_at['id'];
			$output_at['ignored_url_part'] = $ignoredUrlPart_t;
			
			// bto 20071119 (v8) : on ajoute l'éventuel "query string" sauf s'il est présent dans la redirection
			$output_at['query_string'] = (strpos($result_searchUrl_at['url'], "?") === false) ? $queryString_t : '';
			
			// on renvoit ces données
			return $output_at;
		}
		
		// sinon
		else {
			
			// si pas de récurssion
			if (!$recursive_b) {
				// debug log
				debugLog("URL was not found and recursion is off");
				// on renvoit false
				return false;
			}
			
			// on compte les niveaux restants dans l'url
			$urlLevels_i = mb_substr_count($url_t, "/");
			// s'il reste plus d'un niveau dans l'url
			if ($urlLevels_i > 1) {
				// on cherche la position du dernier "/"
				$lastSlash_i = strrpos($url_t, "/");
				// on enléve la derniére partie de l'url
				$shorterUrl_t = substr($url_t, 0, $lastSlash_i);
				// on récupére la partie enlevée
				$ignoredUrlPart_t = substr($url_t, $lastSlash_i, strlen($url_t)).$ignoredUrlPart_t;
				// on fait appel é la fonction é la méme fonction avec le reste de l'url
				return searchHyperPalenorWithUrl($shorterUrl_t, $ignoredUrlPart_t, $recursive_b);
			}	   
			
			// sinon
			else {
				// debug log
				debugLog("URL was not found, even after URL cuts");
				// on renvoit false
				return false;
			}
		}
	}
	// sinon, si la recherche ne fonctionne pas
	else {
		// debug log
		debugLog("Error while attempting to retrieve an entry matching with the URL (".mysql_error().")");
		// on sort
		return false;
	}
}
} // /function_exists()


// updateHyperPalenorEntryUsage() : met é jour les données d'une redirection pour indiquer son utilisation

// v12
if (!function_exists('updateHyperPalenorEntryUsage'))
{
function updateHyperPalenorEntryUsage($entryID_i)
{
	global $modx;
	
	// on protége la valeur reéue
	$entryID_i = (int) $modx->db->escape($entryID_i);
	// on incrémente le compteur de redirection pour cette url
	// on change la date de derniére redirection
	$sql_updateEntryUsage_t = "UPDATE `".$GLOBALS['HyperPalenorTable']."`";
	$sql_updateEntryUsage_t .= " SET `use_count`=`use_count`+1, `last_use_date`=NOW()";
	$sql_updateEntryUsage_t .= " WHERE `id`=".$entryID_i." LIMIT 1;";
	// debug log
	debugLog($sql_updateEntryUsage_t);
	// mise é jour de l'entrée
	$rs_updateEntryUsage_i = mysql_query($sql_updateEntryUsage_t);
	// si échec de la mise é jour
	if (!$rs_updateEntryUsage_i) {
		// debug log
		debugLog("Error while attempting to update entry for usage (".mysql_error().")");
	}
	
	// on renvoit le résultat de l'opération
	return $rs_updateEntryUsage_i;
}
} // /function_exists()


// updateHyperPalenorEntryDocId() : met é jour les données d'une redirection lors d'un changement d'ID de document

// v12
if (!function_exists('updateHyperPalenorEntryDocId'))
{
function updateHyperPalenorEntryDocId($entryID_i, $docID_i)
{
	global $modx;
	
	// créateur de la redirection
	$creator_t = $modx->getLoginUserName();
	
	// on protége les valeurs reéues
	$entryID_i = (int) $modx->db->escape($entryID_i);
	$docID_i = (int) $modx->db->escape($docID_i);
	$creator_t = $modx->db->escape($creator_t);
	// on met é jour l'ID du document pour cette adresse
	// on initialise le compteur de redirections
	// on met é jour la date de création de la redirection
	// on initialise la date de derniére utilisation de la redirection
	$sql_updateEntryDocID_t = "UPDATE `".$GLOBALS['HyperPalenorTable']."`";
	$sql_updateEntryDocID_t .= " SET `doc_id`=".$docID_i.", `creation_date`=NOW(), `use_count`=0,";
	$sql_updateEntryDocID_t .= " `last_use_date`='0000-00-00', `creator='".$creator_t."'`";
	$sql_updateEntryDocID_t .= " WHERE `id`=".$entryID_i." LIMIT 1;";
	// mise é jour de l'entrée
	$rs_updateEntryDocID_i = mysql_query($sql_updateEntryDocID_t);
	// si échec de la mise é jour
	if (!$updateEntryDocID_i) {
		// debug log
		debugLog("Error while attempting to update entry (".mysql_error().")");
	}
	
	// on renvoit le résultat de l'opération
	return $rs_updateEntryDocID_i;
}
} // /function_exists()


// addEntryInHyperPalenor() : ajout une entrée dans la tables redirections

// v12
if (!function_exists('addEntryInHyperPalenor'))
{
function addEntryInHyperPalenor($url_t, $docID_i, $addUseCount_b=0, $creator_t='')
{
	global $modx;
	
	// si les valeurs ne sont vides
	if (empty($url_t) || empty($docID_i)) {
		// debug log
		debugLog("No way to add an entry if either the URL or the document ID is empty");
		// on l'indique
		return false;
	}
		
	// créateur de la redirection
	if (empty($creator_t)) {
		$creator_t = $modx->getLoginUserName();
	}
	
	// on protége les valeurs reéues
	$url_t = $modx->db->escape($url_t);
	$docID_i = (int) $modx->db->escape($docID_i);
	$creator_t = $modx->db->escape($creator_t);
	// valeur du champ `use_count`
	$useCount_i = ($addUseCount_b) ? 1 : 0;
	$lastUseDate_t = ($addUseCount_b) ? "NOW()" : "'0000-00-00'"; //v6
	// création de la nouvelle entrée
	$sql_addEntry_t = "INSERT INTO `".$GLOBALS['HyperPalenorTable']."`";
	$sql_addEntry_t .= " (`id`, `doc_id`, `url`, `creation_date`, `use_count`, `last_use_date`, `creator`)";
	$sql_addEntry_t .= " VALUES ('', $docID_i, '".$url_t."', NOW(), ".$useCount_i.", ".$lastUseDate_t.", '".$creator_t."');";
	$rs_addEntry_i = mysql_query($sql_addEntry_t);
	// si probléme é l'insertion
	if (!$rs_addEntry_i) {
		// debug log
		debugLog("Error while attempting to add a new entry (".mysql_error().")");
	}
	
	// on renvoit le résultat de l'opération
	return $rs_addEntry_i;
}
} // /function_exists()


// docUrlHasChanged() : vérifie si l'url a changé (changement d'alias ou de parent)

// v12
if (!function_exists('docUrlHasChanged'))
{
function docUrlHasChanged($docID_i)
{
	global $modx;

	
	// v10 : si l'ID n'est pas valide
	if (empty($docID_i)) {
		// on tente de le récupérer en POST
		$docID_i = isset($_POST['id']) ? $_POST['id'] : 0;
	}
	
	// si l'ID n'est pas valide
	if (empty($docID_i)) {
		// on sort
		return false;
	}
	
	// si on n'est pas dans un contexte de manager (QuickEdit)
	// v10 : on ajoute un test pour savoir si c'est n'est pas dans le contexte d'un déplacement
	//if (!isset($_POST['ta'])) {
	if (!isset($_POST['ta']) && !isset($_POST['new_parent'])) {
		// on sort
		return false;
	}
	
	// on récupére les données actuelles du document
	$docObject_at = $modx->getDocumentObject('id', $docID_i);
	$currentAlias_t = $docObject_at['alias'];
	$currentParent_i = $docObject_at['parent'];
	
	// on récupére la nouvelle valeur de l'alias
	$newAlias_t = isset($_POST['alias']) ? $_POST['alias'] : "";
	
	// v10 : si on est en mode édition normale
	if (isset($_POST['ta'])) { // ta = le nom du textarea du contenu dans le mode édition
		// on récupére le nouveau parent		
		$newParent_i = isset($_POST['parent']) ? $_POST['parent'] : -1;
	}
	// sinon
	else {	
		// on récupére la nouvelle valeur du parent du document
		$newParent_i = isset($_POST['new_parent']) ? $_POST['new_parent'] : -1;
	}
	// fin v10
	
	// v10 : on ne teste pas l'alias si la valeur du nouveau est vide
	if (!empty($newAlias_t)) {
		// si la valeur a été changée
		if ($currentAlias_t != $newAlias_t) {
			// on renvoit true
			return true;
		}	
	}
	
	// v10 : on ne teste pas le parent si la valeur du nouveau est vide
	if (!empty($newParent_i)) {
		// si la valeur a été changée
		if ($currentParent_i != $newParent_i) {
			// on renvoit true
			return true;
		}
	}
	
	// v10 : si on arrive ici, en renvoit false (pas de changement)
	
	// debug log
	debugLog("Document's URL hasn't changed");
	// on renvoit false
	return false;

}
} // /function_exists()


// manageDocUrlChange() : gestion du changement d'url du document (alias ou parent modifié)

// v12
if (!function_exists('manageDocUrlChange'))
{
function manageDocUrlChange($docID_i)
{   
	global $modx;
	
	// si l'ID est vide
	if (empty($docID_i)) {
		// debug log
		debugLog("No way to manage an empty document ID");
		// on sort
		return false;
	}
	
	// on génére l'url depuis l'ID
	$url_t = $modx->makeUrl($docID_i);
	// on sépare l'url et l'extension de la page
	preg_match("`(.+)\.[^\.]+$`", $url_t, $matches_at);
	// récupération url sans extension
	$urlSansExtension_t = $matches_at[1];
	// debug log
	debugLog("URL sans extension (management) : ".$urlSansExtension_t);
	unset($matches_at);
	
	// on cherche dans la table des redirections si l'adresse existe
	$redirectInfo_at = searchHyperPalenorWithUrl($urlSansExtension_t);

	
	// si l'adresse existe
	if ($redirectInfo_at) {
		// on met é jour l'entrée
		$updateEntry_b = updateHyperPalenorEntryDocId($redirectInfo_at['entry_id'], $docID_i);
		// si la mise é jour échoue
		if (!$updateEntry_b) {
			// debug log
			debugLog("URL was found but the document ID update has failed");
		}
		// on renvoit le résultat de l'opération
		return $updateEntry_b;
	}			   
	// si l'adresse n'existe pas
	else {
		// on l'ajoute
		$addEntry_b = addEntryInHyperPalenor($urlSansExtension_t, $docID_i);
		// si la mise é jour échoue
		if (!$addEntry_b) {
			// debug log
			debugLog("URL was not found and its creation failed");
		}
		// on renvoit le résultat de l'opération
		return $addEntry_b;
	}
}
} // /function_exists()


// addSectionInEditionPage() : ajoute une section dans la page d'édition (manager)

// v12
if (!function_exists('addSectionInEditionPage'))
{
function addSectionInEditionPage($titre_t, $description_t, $content_t)
{
	// documents list presentation (default)
	$nouveauCadre_t = "<div class=\"sectionHeader\">\n";
	$nouveauCadre_t .= "\t".$titre_t."\n";
	$nouveauCadre_t .= "</div>\n";
	$nouveauCadre_t .= "<div class=\"sectionBody\">\n";
	$nouveauCadre_t .= "\t<p><span class=\"warning\">".$description_t."</span></p>\n";
	$nouveauCadre_t .= "\t".$content_t."\n";
	$nouveauCadre_t .= "</div>\n";
		
	return $nouveauCadre_t;
}
} // /function_exists()


// getCurrentDocumentRedirections() : récupére les redirections é afficher

// v12
if (!function_exists('getCurrentDocumentRedirections'))
{
function getCurrentDocumentRedirections($docID_i)
{
	global $modx;

	
	// si l'ID fourni est invalide
	if (empty($docID_i) || !is_numeric($docID_i)) {
		// debug log
		debugLog("Error while attempting to retrieve redirections : invalid document ID given");
		// on indique l'erreur
		return false;
	}
	
	// préparation de la valeur
	$docID_i = $modx->db->escape($docID_i);
		
	// récupération des entrées pour l'ID courant
	$sql_redirections_t = "SELECT `id`, `url`, `creation_date`, `use_count`, `last_use_date`, `creator`";
	$sql_redirections_t .= " FROM `".$GLOBALS['HyperPalenorTable']."`";
	$sql_redirections_t .= " WHERE `doc_id`=".$docID_i." ORDER BY `url` ASC;";
	$rs_redirections_i = mysql_query($sql_redirections_t);
	
	// si la requéte échoue
	if ($rs_redirections_i === false) {
		// debug log
		debugLog("Error while attempting to retrieve redirections (".mysql_error().")");
		// on indique l'erreur
		return false;
	}
		
	// si aucun résultat n'est trouvé
	if (mysql_num_rows($rs_redirections_i) < 1) {
		// debug log
		debugLog("No redirections found (".$sql_redirections_t.")");
		// on indique l'erreur
		return false;
	}
		
	// contiendra les infos sur les redirections
	$redirectionsInfo_at = array();
	// boucle sur les redirections trouvées
	while ($result_redirections_at = mysql_fetch_assoc($rs_redirections_i)) {
		// url
		$url_t = $result_redirections_at['url'];
				
		// créateur
		$createur_t = (!empty($result_redirections_at['creator'])) ? $result_redirections_at['creator'] : "-";
		// date de création
		list($y, $m, $d) = explode("-", $result_redirections_at['creation_date']);
		$dateCreation_t = $d."/".$m."/".$y;
		// nombre d'utilisation
		$nombreUtilisation_i = $result_redirections_at['use_count'];
		// si jamais utilisée
		if (!empty($nombreUtilisation_i)) {
			// date de derniére utilisation
			list($y, $m, $d) = explode("-", $result_redirections_at['last_use_date']);
			$dateDerniereUtilisation_t = $d."/".$m."/".$y;
			// nombre d'utilisation
			$nbUtilisation_t = $nombreUtilisation_i." ".$GLOBALS['lang']['times'][$GLOBALS['pluginLang']];			
		}
		// sinon
		else {
			// date de derniére utilisation
			$dateDerniereUtilisation_t = "-";
			// nombre d'utilisation
			$nbUtilisation_t = $GLOBALS['lang']['never'][$GLOBALS['pluginLang']];
		}
		// on récupéres les infos sur les redirections
		$redirectionsInfo_at[$result_redirections_at['id']]['url'] = $url_t;
		$redirectionsInfo_at[$result_redirections_at['id']]['dateCreation'] = $dateCreation_t;
		$redirectionsInfo_at[$result_redirections_at['id']]['dateDerniereUtilisation'] = $dateDerniereUtilisation_t;
		$redirectionsInfo_at[$result_redirections_at['id']]['nombreUtilisations'] = $nbUtilisation_t;
		$redirectionsInfo_at[$result_redirections_at['id']]['createur'] = $createur_t;
	}
	
	// si la liste est vide
	if (empty($redirectionsInfo_at)) {
		// on l'indique
		return false;
	}
	
	// on renvoit les redirections
	return $redirectionsInfo_at;
}
} // /function_exists()


// getHyperPalenorSectionContent() : récupére les champs é afficher dans la section

// v12
if (!function_exists('getHyperPalenorSectionContent'))
{
function getHyperPalenorSectionContent($docID_i)
{   
	// padding dans le tableau
	$padding_t = "padding: 2px 10px 0 10px;";
	
	// modéle pour les checkbox
	$checkboxCode_t = "<input type=\"checkbox\" name=\"hp_redir[]\" value=\"%d\" checked=\"checked\" class=\"checkbox\" />\n";
	
	// modéle pour la présentation des redirections
	$presentationRedirections_at = array();
	$presentationRedirections_at[] = "<table cellspacing=\"12\" style=\"margin-bottom: 3px; border-collapse: collapse;\">";
	$presentationRedirections_at[] = "<thead>";
	$presentationRedirections_at[] = "<tr style=\"padding-bottom: 4px;\">";
	$presentationRedirections_at[] = "<th style=\"".$padding_t."\">&nbsp;</th>";
	$presentationRedirections_at[] = "<th style=\"".$padding_t."\">".$GLOBALS['lang']['redirectionUrl'][$GLOBALS['pluginLang']]."</th>";
	$presentationRedirections_at[] = "<th style=\"".$padding_t."\">".$GLOBALS['lang']['creationDate'][$GLOBALS['pluginLang']]."</th>";
	$presentationRedirections_at[] = "<th style=\"".$padding_t."\">".$GLOBALS['lang']['usesNumber'][$GLOBALS['pluginLang']]."</th>";
	$presentationRedirections_at[] = "<th style=\"".$padding_t."\">".$GLOBALS['lang']['lastUse'][$GLOBALS['pluginLang']]."</th>";
	$presentationRedirections_at[] = "<th style=\"".$padding_t."\">".$GLOBALS['lang']['creator'][$GLOBALS['pluginLang']]."</th>";
	$presentationRedirections_at[] = "</tr>";
	$presentationRedirections_at[] = "</thead>";
	$presentationRedirections_at[] = "<tbody>";
	$presentationRedirections_at[] = "%s";
	$presentationRedirections_at[] = "</tbody>";
	$presentationRedirections_at[] = "</table>";
	$presentationRedirections_t = implode("\n", $presentationRedirections_at);
	
	// récupération des redirections
	$redirections_at = getCurrentDocumentRedirections($docID_i);
			
	// redirections
	$redirectionFields_at = array();

	// si aucune redirection n'est trouvée
	if (empty($redirections_at)) {
		$redirectionFields_at[] = "<p>".$GLOBALS['lang']['noRedirectionFound'][$GLOBALS['pluginLang']]."</p>";
	}
	// sinon
	else {
		
		// bto 20071123 (v9) : on teste le tableau avant la boucle
		if (count($redirections_at) >= 1)
		{
			// contenu du tableau
			$tableContent_at = array();
			// boucle sur les redirections
			foreach ($redirections_at as $valeur_t => $redirectInfo_at) {
				// ligne du tableau
				$tableContent_at[] = "<tr>";
				// checkbox
				$checkbox_t = sprintf($checkboxCode_t, $valeur_t);
				$tableContent_at[] = "<td>".$checkbox_t."</td>";
				$tableContent_at[] = "<td>".$redirectInfo_at['url']."</td>";
				$tableContent_at[] = "<td style=\"text-align: center; ".$padding_t."\">".$redirectInfo_at['dateCreation']."</td>";
				$tableContent_at[] = "<td style=\"text-align: center; ".$padding_t."\">".$redirectInfo_at['nombreUtilisations']."</td>";
				$tableContent_at[] = "<td style=\"text-align: center; ".$padding_t."\">".$redirectInfo_at['dateDerniereUtilisation']."</td>";
				$tableContent_at[] = "<td style=\"".$padding_t."\">".$redirectInfo_at['createur']."</td>";
				// fin de ligne du tableau
				$tableContent_at[] = "</tr>";
			}
			// on récupére le contenu du tableau
			$tableContent_t = implode("\n", $tableContent_at);
		}
		// sinon
		else
		{
			// on donne une chaine vide
			$tableContent_t = "";
		}
		// fin de la condition bto 20071123 (v9)
		
		// on place le contenu dans le tableau
		$redirectionFields_at[] = sprintf($presentationRedirections_t, $tableContent_t);
		
		// message info
		$redirectionFields_at[] = "<p>".$GLOBALS['lang']['untickedCheckboxDeleted'][$GLOBALS['pluginLang']]."</p>";
	}
	
	// on ajoute un champ libre
	$redirectionFields_at[] = "<p>".$GLOBALS['lang']['newRedirectionUrl'][$GLOBALS['pluginLang']]."&nbsp;:<br />";
	$redirectionFields_at[] = "<input type=\"text\" name=\"hp_new\" size=\"50\" />";
	$redirectionFields_at[] = "</p>";
	$redirectionFields_at[] = "<p>".$GLOBALS['lang']['newRedirectionInfo'][$GLOBALS['pluginLang']]."</p>";
	
	// on récupére la liste
	$redirectionFields_t = implode("\n", $redirectionFields_at);
	
	// on renvoit la liste
	return $redirectionFields_t;
}
} // /function_exists()


// deleteUnwantedRedirections() : efface les redirections non désirées (décochées)

// v12
if (!function_exists('deleteUnwantedRedirections'))
{
function deleteUnwantedRedirections($redirectionsToKeep_ai, $docID_i)
{
	global $modx;
	
	// on récupére les redirections actuelle de la page
	$redirections_at = getCurrentDocumentRedirections($docID_i);
	
	// bto 20071123 (v9) : test du tableau des redirections
	if ($redirections_at === false) {
		return false;
	}
		
	// boucle sur les redirections existantes
	foreach ($redirections_at as $redirectionID_i => $redirectionInfo_at) {
		// si l'ID de la redirection n'est pas dans le tableau é garder
		if (!in_array($redirectionID_i, $redirectionsToKeep_ai)) {
			// on supprime la redirection
			$suppression_b  = deleteRedirectionFromID($redirectionID_i);
			// si la suppression échoue
			if (!$suppression_b) {
				// debug log
				debugLog("Error while attempting to delete redirection #".$redirectionID_i." (document ID : ".$docID_i.")");
			}
		}
	}
}
} // /function_exists()


// deleteRedirectionFromID() : efface une redirection d'aprés son ID

// v12
if (!function_exists('deleteRedirectionFromID'))
{
function deleteRedirectionFromID($redirectionID_i)
{
	global $modx;
	
	// on prépare la valeur
	$redirectionID_i = $modx->db->escape($redirectionID_i);
	// on construit la requéte de suppression
	$sql_deleteRedir_t = "DELETE FROM `".$GLOBALS['HyperPalenorTable']."` WHERE `id`=".$redirectionID_i." LIMIT 1;";
	// on supprime la redirection
	$rs_deleteRedir_i = mysql_query($sql_deleteRedir_t);
	
	// on renvoit le résultat de l'opération
	return $rs_deleteRedir_i;
}
} // /function_exists()


// addNewRedirection() : ajoute une nouvelle redirection (manuelle)

// v12
if (!function_exists('addNewRedirection'))
{
function addNewRedirection($givenUrl_t, $docID_i)
{
	// si l'URL donnée ne commence pas par un "/"
	if ($givenUrl_t{0} != "/") {
		// on l'ajoute
		$givenUrl_t = "/".$givenUrl_t;
	}
	
	// on teste la valeur donnée pour la nouvelle redirection
	// v7 : changement du test de validite pour permettre les parametres
	//$urlIsValid_b = preg_match("`^(/[a-z0-9\._-]+)*(/[a-z0-9_-]+)+$`i", $givenUrl_t);
	$urlIsValid_b = preg_match("`^(/[a-z0-9\._-]+)*(/[a-z0-9_-]+)+(\.[a-z]+)?(\?.*)?$`i", $givenUrl_t);
	
	// si l'url est valide
	if ($urlIsValid_b) {
		// on ajoute la nouvelle entrée
		$addEntry_b = addEntryInHyperPalenor($givenUrl_t, $docID_i);
		// si l'ajout échoue
		if (!$addEntry_b) {
			// debug log
			debugLog("Error while attempting to add a new redirection with the given url (\"".$givenUrl_t."\")");
		}
	}
	// sinon
	else {
		// debug log
		debugLog("Error while attempting to add a new redirection : given url is invalid (\"".$givenUrl_t."\")");
	}
}
} // /function_exists()


/*************************
		Evénements
**************************/

// référence vers l'événement du plugin
$e = & $modx->Event;

// switch sur l'événement
switch ($e->name) {

	
	// é l'enregistrement d'un document
	case "OnBeforeDocFormSave":
   
		// si la table du plugin n'existe pas encore
		if (!checkHyperPalenorTableExistence()) {
			// on créé la table du plugin
			$createTable_b = createHyperPalenorTable();
			// si la création échoue
			if (!$createTable_b) {
				// debug log
				debugLog("Error while attempting to create table");
				// on sort
				break;
			}
		}
   		
   		// v10 : on vérifie qu'on est en mode édition et pas déplacement avant de supprimer
   		// en mode déplacement le tableau des redirections n'existe pas donc éa efface toutes
   		// les redirections existantes du document et éa, c'est pas top.
   		if (isset($_POST['ta']))
   		{
			// si des redirections sont encore cochées
			if (isset($_POST['hp_redir'])) {
				// redirections
				$redirectionsToKeep_ai = $_POST['hp_redir'];
			}
			// sinon
			else {
				// tableau vide
				$redirectionsToKeep_ai = array();
			}
			// suppression éventuelles des redirections décochées
			deleteUnwantedRedirections($redirectionsToKeep_ai, $docID_i);
   		}
   		// fin v10
		
		// si l'url du document a changé
		if (docUrlHasChanged($docID_i)) {
			// on gére le changement d'url
			$management_b = manageDocUrlChange($docID_i);
			// si la gestion échoue
			if (!$management_b) {
				// debug log
				debugLog("Error while attempting to manage document url change");
			}
		}
		
		// si une nouvelle redirection est spécifiée
		if (!empty($_POST['hp_new'])) {
			// ajout éventuel d'une redirection via le champ libre
			addNewRedirection($_POST['hp_new'], $docID_i);
		}
		
		break;

		
	// si la page 404 est appelée
	case "OnPageNotFound":
		
		// on récupére les infos de l'url appelée
		$urlInfo_at = splitUrl();
		
		// tentative de récupération d'une url valide depuis l'url appelée
		// v7 : on recherche une url complete pour permettre les parametres
		//$newUrl_t = getCurrentUrlFromOldOne($urlInfo_at['urlPath']);
		$newUrl_t = getCurrentUrlFromOldOne($urlInfo_at['originalUrlWithoutPrefix']);
				
		// si la nouvelle url est trouvée
		if (!empty($newUrl_t)) {
			// on redirige vers cette url
			header("HTTP/1.1 301 Moved Permanently"); // v5 : header 301
			header("Location: ".$newUrl_t); // on n'utilise plus la redirection MODx
			exit();
		}
		
		// si pas d'url trouvée, enregistrement de l'url puis gestion 404 par MODx
		addEntryInHyperPalenor($urlInfo_at['urlPath'], -1, 1, "404"); // v6
		
		//updateHyperPalenorEntryUsage()
		
		break;
		
	// lors de l'affichage du formulaire d'édition (manager)
	case "OnDocFormRender":
		
		// on récupére le contenu de la section é ajouter
		$sectionContent_t = getHyperPalenorSectionContent($docID_i);
		
		// titre et description de la section
		$titre_t = "HyperPalenor";
		$description_t = $GLOBALS['lang']['pluginSectionTitle'][$GLOBALS['pluginLang']];
		
		// on crée la zone qui liste les redirections pour la page courante
		$section_t = addSectionInEditionPage($titre_t, $description_t, $sectionContent_t);
				
		// on ajoute le tout é la sortie
		$e->output($section_t);
	
		break;

		
	// lors de la suppression d'un document (manager, MODx 0.9.6+)
	case "OnDocFormDelete":
	
		// on ne fait rien ici pour le moment
		// idéalement, il faudrait gérer l'événement
		// du vidage de la corbeille
	
		break;
			
	// si l'événement n'est pas une des événements du plugin
	default:
	
		// on ne fait rien, on s'arréte ici
	
		break;

}


// eof