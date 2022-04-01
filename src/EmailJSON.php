<?php
/* ** La classe EmailToJSON récupère les e-mails d'un utilisateur en utilisant l'extension IMAP de PHP.
* La valeur de retour des méthodes est toujours au format JSON. Cela permet une inter-connexion plus simple avec différents clients.
* La valeur de retour contient toujours deux (2) clés : "status" et "data".
* La propriété privée host est récupérer implicitement via l'adresse e-mail fournit en argument au constructeur.
* */

namespace JeremyD\EmailJSON;

require('Format.php');
require('ErrorManager.php');

class EmailJSON {

	private String $email; // Stocke l'adresse e-mail
	private String $password; // Stocke le mot de passe
	private Array $options; // Stocke les différentes options de connexion au serveur de mails

	private String $host; // Stocke le nom du serveur de mails
	private String $_target; // Stocke l'adresse du serveur de mails

	private Bool $_isReady; // Définit si le paramètres de connexion sont valides

	private $mailbox; // Stocke la connexion IMAP issue de imap_open()

	/* ** Constructeur
	* 
	* @param String email L'adresse e-mail dont on veut obtenir les e-mails.
	* @param String password Le mot de passe associé à l'adresse e-mail.
	* @param Array options Un tableau contenant les options de connexion.
	* */
	public function __construct(String $email, String $password, Array $options) {
		$isEmail = $this->_checkEmail($email);

		$this->_isReady = false;

		if ($isEmail !== false) {
			$this->_setEmail($isEmail);
			$this->_setPassword($password);
			$this->_setOptions($options);
			$this->_setHost($this->getEmail());
			$this->_setTarget();

			$this->_isReady = true;
		}
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
		if ($this->_isReady) {
			$mailbox = $this->_openBox($this->_target);

			if ($this->_mboxIsOpen($mailbox)) {
				imap_close($mailbox);

				return Format::json('Connect : logged in');
			}

			return ErrorManager::response('Connect : logged in failed');
		}

		return ErrorManager::response('no_ready');
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
		if ($this->_isReady) {
			$mailbox = $this->_openBox($this->_target);

			if ($this->_mboxIsOpen($mailbox)) {
				$folders = imap_getmailboxes($mailbox, $this->_target, '*');

				imap_close($mailbox);

				return Format::json($folders);
			}

			return ErrorManager::response('Folders : no logged in');
		}

		return ErrorManager::response('no_ready');
	}
	
	/* ** Vérifie les informations de la boîte mail courante.
	* 
	* @param String box Le nom de la boîte mail courante.
	* */
	public function check($box) {
		if ($this->_isReady) {
			$mailbox = $this->_openBox($this->_target . $box);

			if ($this->_mboxIsOpen($mailbox)) {
				$check = imap_check($mailbox);

				imap_close($mailbox);

				return Format::json($check);
			}

			
			return ErrorManager::response('Check : no logged in');
		}

		return ErrorManager::response('no_ready');
	}

	/* ** Vérifie que la connexion à la boîte mail est toujours active.
	* 
	* @return Function La fonction _JSONResponse() si la connexion est active, la fonction _lastError() dans tous les autres cas.
	* */
	public function ping() {
		if ($this->_isReady) {
			$mailbox = $this->_openBox($this->_target);

			if ($this->_mboxIsOpen($mailbox)) {
				$ping = imap_ping($mailbox);

				imap_close($mailbox);

				return Format::json($ping);
			}

			return ErrorManager::response('Ping : no logged in');
		}

		return ErrorManager::response('no_ready');
	}

	/* ** Récupère le quota d'une boîte mail.
	* 
	* @param String box Le nom d'une boîte mail (par exemple INBOX).
	*
	* @return Function La fonction _JSONResponse() si on récupère le quota sans erreur, la fonction _lastError() sinon.
	* */
	public function getQuota(String $box) {
		if ($this->_isReady) {
			$mailbox = $this->_openBox($this->_target);

			if ($this->_mboxIsOpen($mailbox)) {
				$quota = imap_get_quotaroot($mailbox, $box);

				imap_close($mailbox);

				return format::json($quota);
			}

			return ErrorManager::response('Quota : no logged in');
		}

		return ErrorManager::response('no_ready');
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
		if ($this->_isReady) {
			$mailbox = $this->_openBox($this->_target . $box);

			if ($this->_mboxIsOpen($mailbox)) {
				$check = imap_check($mailbox);

				$end = $max + $start;

				if ($end > $check->Nmsgs) $end = $check->Nmsgs; 

				$sequence = $start . ':' . $end;

				$headers = imap_fetch_overview($mailbox, $sequence, 0);

				imap_close($mailbox);

				return Format::json($headers);
			}

			return ErrorManager::response('Get mails : no logged in');
		}

		return ErrorManager::response('no_ready');
	}
	
	/* ** Récupère un message dans une boîte mail, à partir de son uid.
	* 
	* @param String box La boîte mail dans laquelle récupéré le message.
	* @param Integer uid L'uid du message.
	*
	* @return Function La fonction _JSONResponse() si on récupère le message sans erreur, la fonction _lastError() sinon.
	* */
	public function getMessage(String $box, Int $uid) {
		if ($this->_isReady) {
			$mailbox = $this->_openBox($this->_target . $box);

			if ($this->_mboxIsOpen($mailbox)) {
				$bodyMsg = imap_body($mailbox, $uid);

				imap_close($mailbox);

				return Format::json($bodyMsg);
			}

			return ErrorManager::response('Get message : no logged in');
		}

		return ErrorManager::response('no_ready');
	}
}

?>
