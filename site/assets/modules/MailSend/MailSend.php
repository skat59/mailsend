<?php
/**
 * Просто класс для подключения плагина вывода меню
 */
class MailSend
{
	const VERSION = '1.0.0';

	public  $corePath;
	private $params  = [];
	private $lang    = [];
	private $manager = [];
	private $evo     = null;

	public function __construct($params = [])
	{
		$this->params = $params;
		$this->params['menu'] = isset($_GET['type']) && is_string($_GET['type']) ? $_GET['type'] : 'default';

		$this->evo = EvolutionCMS();

		$this->corePath = str_replace('\\','/',dirname(__FILE__)) . '/';

		$manager_id = $this->evo->getLoginUserID('mgr');

		$this->manager = $this->evo->getUserInfo($manager_id);

		$this->loadLang();
	}

	public function getModuleId()
	{
		return $this->evo->db->getValue($this->evo->db->select('id', $this->evo->getFullTablename('site_modules'), "name = 'MailSend'"));
	}

	public function loadLang()
	{
		$_MailSendLang = [];

		$userlang = $this->evo->getConfig('manager_language');
		if (is_file($this->corePath . 'lang/' . $userlang . '.php')) {
			include $this->corePath . 'lang/' . $userlang . '.php';
		}
		$this->lang = $_MailSendLang;
		return $this->lang;
	}

}
