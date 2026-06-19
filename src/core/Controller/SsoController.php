<?php
/**
 * SSO (Single Sign-On) Controller
 *
 * Allows external platforms to authenticate users into ownCloud
 * using a pre-shared Client ID and Client Secret.
 *
 * Endpoint: GET /api/sso/login?client_id=X&client_secret=Y&email=Z
 */

namespace OC\Core\Controller;

use OC\User\Session;
use OC_Util;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\IConfig;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUserManager;

class SsoController extends Controller {
	/** @var IUserManager */
	private $userManager;

	/** @var IConfig */
	private $config;

	/** @var ISession */
	private $session;

	/** @var Session */
	private $userSession;

	/** @var IURLGenerator */
	private $urlGenerator;

	public function __construct(
		$appName,
		IRequest $request,
		IUserManager $userManager,
		IConfig $config,
		ISession $session,
		Session $userSession,
		IURLGenerator $urlGenerator
	) {
		parent::__construct($appName, $request);
		$this->userManager = $userManager;
		$this->config = $config;
		$this->session = $session;
		$this->userSession = $userSession;
		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 * @UseSession
	 *
	 * @param string $client_id
	 * @param string $client_secret
	 * @param string $email
	 *
	 * @return RedirectResponse|JSONResponse
	 */
	public function login($client_id, $client_secret, $email) {
		// Validate required parameters
		if (empty($client_id) || empty($client_secret) || empty($email)) {
			return new JSONResponse(['error' => 'Missing required parameters: client_id, client_secret, email'], 400);
		}

		// Load registered SSO clients from system config
		// Configure in config/config.php:
		//   'sso_clients' => ['dann_hrm' => 'abc123xyz'],
		$ssoClients = $this->config->getSystemValue('sso_clients', []);

		// Validate client credentials
		if (!isset($ssoClients[$client_id]) || $ssoClients[$client_id] !== $client_secret) {
			return new JSONResponse(['error' => 'Invalid client credentials'], 401);
		}

		// Find user by email
		$users = $this->userManager->getByEmail($email);
		if (empty($users)) {
			return new JSONResponse(['error' => 'User not found'], 404);
		}

		// Use the first matched user (email should be unique)
		$user = $users[0];

		if (!$user->isEnabled()) {
			return new JSONResponse(['error' => 'User account is disabled'], 403);
		}

		// Create an authenticated session for the user bypassing password check
		$this->userSession->setUser($user);
		$this->userSession->createSessionToken($this->request, $user->getUID(), $user->getUID(), '');
		$this->session->set('user_id', $user->getUID());

		// Redirect to the main Files page
		return new RedirectResponse(OC_Util::getDefaultPageUrl());
	}
}
