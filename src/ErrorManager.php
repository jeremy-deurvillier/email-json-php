<?php
/* ** 
* La classe ErrorManager s'occupe de gérer et renvoyer les erreurs rencontrées à la classe EmailJSON.
* Il utilise des "clés" pour décrire les erreurs, laissant la traduction des-dites erreurs à l'utilisateur de la bibliothèque.
* Le dernier message d'erreur rencontrée par IMAP est toujours renvoyé, en plus de la "clé" décrivant l'erreur.
* */

namespace JeremyD\EmailJSON;

class ErrorManager {

	/* ** DESCRIPTION DES CLÉS D'ERREUR :
	* 	- unknown_error = Une erreur inconue est survenue
	* 	- no_ready = Erreurs dans les paramètres de connexion (vérifiez l'e-mail, le mot de passe ...)
	* 	- connect_fail = Impossible de se connecter au serveur de mails
	* 	- disconnect_fail = imap_close() n'a pas réussie à fermer la boîte mails
	* 	- get_folders_error = Impossible de récupérer les dossiers du serveur de mails
	* 	- check_mailbox_fail = Impossible de vérifier la boîte mails courante
	* 	- ping_mailbox_fail = Impossible de vérifier si la connexion au serveur est toujours active
	* 	- get_quota_fail = Impossible de récupérer les quotas de la boîte mails
	* 	- get_mails_fail = Impossible de récupérer la liste des entêtes de message
	* 	- get_message_fail = Impossible de récupérer le message spécifié
	* */

	/* ** */
	public function __construct() {}
	
	/* ** Permet d'envoyer l'erreur à EmailJSON pour un traitement futur dans le script appelant.
	*
	* @param String error Une chaîne représentant l'erreur rencontrée.
	* */
	public static function response(String $error = 'unknown_error') {
		$struct = self::_createStructure($error);

		return Format::json($struct, 'KO');
	}

	/* ** Créer la structure à renvoyée au script appelant.
	*
	* @param String error L'erreur personnalisée à ajouter à la structure.
	* */
	private function _createStructure($error) {
		return [
			'error_key' => $error,
			'imap_last_error' => imap_last_error()
		];
	}

}

?>
