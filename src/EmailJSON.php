<?php
/* ** La classe EmailToJSON récupère les e-mails d'un utilisateur en utilisant l'extension IMAP de PHP.
* La valeur de retour des méthodes est toujours au format JSON. Cela permet une inter-connexion plus simple avec différents clients.
* La valeur de retour contient toujours deux (2) clés : "status" et "data".
* La propriété privée host est récupérer implicitement via l'adresse e-mail fournit en argument au constructeur.
* */

namespace JeremyD\EmailJSON;

class EmailJSON {

	private String $email; // Stocke l'adresse e-mail
	private String $password; // Stocke le mot de passe
	private Array $options; // Stocke les différentes options de connexion au serveur de mails.

	private String $host; // Stocke le nom du serveur de mails.
	private String $_target; // Stocke l'adresse du serveur de mails.

	private $mailbox; // Stocke la connexion IMAP issue de imap_open()

	/* ** Constructeur
	* 
	* @param String email L'adresse e-mail dont on veut obtenir les e-mails.
	* @param String password Le mot de passe associé à l'adresse e-mail.
	* @param Array options Un tableau contenant les options de connexion.
	* */
	public function __construct(String $email, String $password, Array $options) {
		$isEmail = $this->_checkEmail($email);

		if ($isEmail !== false) {
			$this->_setEmail($isEmail);
			$this->_setPassword($password);
			$this->_setOptions($options);
			$this->_setHost($this->getEmail());
			$this->_setTarget();
			$this->connect();

			return $this->_JSONResponse('Instance : email instance created successfully');
		}

		return $this->_lastError('Instance : email is not valid');
	}

	/* ** Vérifie la présence du module IMAP.
	* NOTE : NON IMPLEMENTÉE !
	* */
	private function _checkIMAPModule() {
		return 0;
	}
	
	/* ** Vérifie la validité d'une adresse e-mail.
	*
	* @param String email Une adresse e-mail.
	*
	* @return String/Boolean Retourne la valeur de la fonction filter_var() : l'adresse e-mail filtrée si ce dernier est valide, false sinon.
	* */
	private function _checkEmail(String $email) {
		return filter_var($email, FILTER_VALIDATE_EMAIL);
	}
	
	/* ** Vérifie qu'une boîte mail s'est bien ouverte sans erreur.
	*
	* @param IMAP mailbox Une ressource IMAP
	*
	* @return Boolean Retourne true si la boîte mail est ouverte sans erreur, false sinon.
	* */
	private function _mboxIsOpen($mailbox) {
		if ($mailbox !== false) return true;

		return false;
	}

	/* ** _JSONResponse est utilisée comme valeur de retour de chaque méthode publique, si aucune erreur n'est rencontrée.
	*
	* @param Any data Une donnée qu'on souhaite retourner à l'utilisateur.
	*
	* @return JSON Une représentation JSON encodée dans une chaîne.
	*  */
	private function _JSONResponse($data) {
		return json_encode([
			'status' => 'ok',
			'data' => $data
		]);
	}

	/* ** _lastError est utilisée comme valeur de retour de chaque méthode publique, si une erreur est rencontrée.
	* La fonction renvoie toujours la dernière erreur rencontrée par les fonctions de l'extension IMAP de PHP.
	*
	* @param String msg Une clé sous forme de chaîne permettant de mieux comprendre l'erreur.
	*
	* @return JSON Une représentation JSON encodée dans une chaîne.
	* */
	private function _lastError(String $msg) {
		return json_encode([
			'status' => 'error',
			'data' => [
				'message' => $msg,
				'last_error' => imap_last_error()
			]
		]);
	}

	/* ** Définit la propriété email.
	*
	* @param String email L'adresse e-mail de l'utilisateur.
	* */
	private function _setEmail(String $email) {
		$this->email = $email;
	}

	/* ** Retourne la valeur de la propiété email.
	* 
	* @return String L'adresse e-mail stockée dans la propriété email.
	* */
	public function getEmail() {return $this->email;}

	/* ** Définit la propriété password.
	*
	* @param String password Le mot de passe de l'utilisateur. 
	* */
	private function _setPassword(String $password) {
		$this->password = $password;
	}

	/* ** Définit la propriété options.
	* 
	* @param Array options Un tableau contenant les options de connexion au serveur de mails.
	* */
	private function _setOptions(Array $options) {
		$this->options = $options;
	}

	/* ** retourne la valeur de la propriété options.
	*
	* @return Array Un tableau contenant les options de connexion au serveur de mails. 
	* */
	public function getOptions() {return $this->options;}
	
	/* ** Définit la propriété host, à partir d'une adresse e-mail.
	*
	* @param String email Une adresse e-mail.
	* */
	private function _setHost(String $email) {
		list($name, $host) = explode('@', $email);

		$this->host = $host;
	}

	/* ** Définit la cible de la fonction imap_open().
	* */
	private function _setTarget() {
		$this->_target = '{' . $this->host . ':' . $this->options['port'] . '}';
	}

	/* ** Ouvre une boîte mail.
	* 
	* @param String target La cible à ouvrir.
	*
	* @return IMAP/Boolean Retourne une ressource IMAP si l'ouverture de la boîte mail à réussie, false sinon.
	* */
	private function _openBox(String $target) {
		return imap_open($target, $this->getEmail(), $this->password);
	}

	/* ** Ouvre une boîte mail pour test.
	* Cette fonction est uniquement utilisée dans le constructeur.
	* 
	* @return Function La fonction _JSONResponse() si la boîte mail est ouverte sans erreur, la fonction _lastError() sinon.
	* */
	public function connect() {
		$mailbox = $this->_openBox($this->_target);

		if ($this->_mboxIsOpen($mailbox)) {
			imap_close($mailbox);

			return $this->_JSONResponse('Connect : logged in');
		}

		return $this->_lastError('Connect : logged in failed');
	}

	/* ** Récupère les dossiers présents dans la boîte mails.
	* 
	* imap_getmailboxes() renvoie un tableau d'objets (les dossiers). Chaque objet (dossier) possède une clé "attributes".
	* La clé "attributes" est un masque de bits dont voici les valeurs :
	* 	- LATT_NOINFERIORS = 1
	*	- LATT_NOSELECT = 2
	*	- LATT_MARKED = 4
	*	- LATT_UNMARKED = 8
	*	- LATT_REFERRAL = 16
	*	- LATT_HASCHILDREN = 32
	*	- LATT_HASNOCHILDREN = 64
	* Par exemple un dossier avec une clé atrributes avec la valeur 72 équivaut à un dossier qui 
	* 	- n'a pas d'enfant sélectionnable (LATT_HASNOCHILDREN)
	* 	- est non marqué et ne contient pas de nouveau message (LATT_UNMARKED)
	*
	* @return Function La fonction _JSONResponse() si on récupère le tableau de dossier(s) sans erreur, la fonction _lastError() sinon.
	* */
	public function getFolders() {
		$mailbox = $this->_openBox($this->_target);

		if ($this->_mboxIsOpen($mailbox)) {
			$folders = imap_getmailboxes($mailbox, $this->_target, '*');

			imap_close($mailbox);

			return $this->_JSONResponse($folders);
		}

		return $this->_lastError('Folders : no logged in');
	}
	
	/* ** Vérifie les informations de la boîte mail courante.
	* 
	* @param String box Le nom de la boîte mail courante.
	* */
	public function check($box) {
		$mailbox = $this->_openBox($this->_target . $box);

		if ($this->_mboxIsOpen($mailbox)) {
			$check = imap_check($mailbox);

			imap_close($mailbox);

			return $this->_JSONResponse($check);
		}

		return $this->_lastError('Check : no logged in');
	}

	/* ** Vérifie que la connexion à la boîte mail est toujours active.
	* 
	* @return Function La fonction _JSONResponse() si la connexion est active, la fonction _lastError() dans tous les autres cas.
	* */
	public function ping() {
		$mailbox = $this->_openBox($this->_target);

		if ($this->_mboxIsOpen($mailbox)) {
			$ping = imap_ping($mailbox);

			imap_close($mailbox);

			return $this->_JSONResponse($ping);
		}

		return $this->_lastError('Ping : no logged in');
	}

	/* ** Récupère le quota d'une boîte mail.
	* 
	* @param String box Le nom d'une boîte mail (par exemple INBOX).
	*
	* @return Function La fonction _JSONResponse() si on récupère le quota sans erreur, la fonction _lastError() sinon.
	* */
	public function getQuota(String $box) {
		$mailbox = $this->_openBox($this->_target);

		if ($this->_mboxIsOpen($mailbox)) {
			$quota = imap_get_quotaroot($mailbox, $box);

			imap_close($mailbox);

			return $this->_JSONResponse($quota);
		}

		return $this->_lastError('Quota : no logged in');
	}

	/* ** Récupère les entêtes des e-mails dans une intervalle.
	* 
	* @param String box Le nom du dossier dans lequel récupérer les entêtes.
	* @param Interger start Le début de l'intervalle.
	* @param Integer max Le nombre maximum d'entêtes à récupérer.
	*
	* @return Function La fonction _JSONResponse() si on récupère les entêtes sans erreur, la fonction _lastError() sinon.
	* */
	public function getMails(String $box, Int $start, Int $max) {
		$mailbox = $this->_openBox($this->_target . $box);

		if ($this->_mboxIsOpen($mailbox)) {
			$check = imap_check($mailbox);

			$end = $max + $start;

			if ($end > $check->Nmsgs) $end = $check->Nmsgs; 

			$sequence = $start . ':' . $end;

			$headers = imap_fetch_overview($mailbox, $sequence, 0);

			imap_close($mailbox);

			return $this->_JSONResponse($headers);
		}

		return $this->_lastError('Get mails : no logged in');
	}
	
	/* ** Récupère un message dans une boîte mail, à partir de son uid.
	* 
	* @param String box La boîte mail dans laquelle récupéré le message.
	* @param Integer uid L'uid du message.
	*
	* @return Function La fonction _JSONResponse() si on récupère le message sans erreur, la fonction _lastError() sinon.
	* */
	public function getMessage(String $box, Int $uid) {
		$mailbox = $this->_openBox($this->_target . $box);

		if ($this->_mboxIsOpen($mailbox)) {
			$bodyMsg = imap_body($mailbox, $uid);

			imap_close($mailbox);

			return $this->_JSONResponse($bodyMsg);
		}

		return $this->_lastError('Get message : no logged in');
	}
}

?>
