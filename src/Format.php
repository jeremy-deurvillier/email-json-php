<?php
/* ** La classe Format, sert simplement à formater en JSON la réponse renvoyé au script appelant.
* Par convention et par soucis d'uniformité, toutes les classes publiques de la bibliothèque doivent utilisée Format.
* */

namespace JeremyD\EmailJSON;

class Format {

	/* ** */
	public function __construct() {}

	/* ** Utilise json_encode pour formater la données en argument.
	* Le deuxième paramètre "status", doit-être une chaîne avec :
	* 	- soit "OK" si aucune erreur
	* 	- soit "KO" si erreur
	* La valeur par défaut est "OK".
	*
	* @param Any data Une donnée à formater en JSON.
	* @param String status Une chaîne qui décrit le statut à renvoyé.
	* */
	static public function json($data, $status = 'OK') {
		return json_encode([
			'status' => $status,
			'data' => $data
		]);
	}
}

?>
