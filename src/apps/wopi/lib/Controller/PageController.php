<?php /** @noinspection HtmlUnknownTag */

/**
 * ownCloud Wopi
 *
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Piotr Mrowczynski <piotr@owncloud.com>
 * @copyright 2021 ownCloud GmbH.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\WOPI\Controller;

use OC\HintException;
use OCA\WOPI\Service\DiscoveryService;
use OCA\WOPI\Service\FileService;
use OCA\WOPI\Service\TokenService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Files\InvalidPathException;
use OCP\Files\NotFoundException;
use OCP\ILogger;
use OCP\IRequest;
use OCP\Files\Node;
use OCP\Util;

class PageController extends Controller {
	private ILogger $logger;
	private DiscoveryService $discoveryService;
	private FileService $fileService;
	private TokenService $tokenService;

	/**
	 * PageController constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param ILogger $logger
	 * @param DiscoveryService $discoveryService
	 * @param FileService $fileService
	 * @param TokenService $tokenService
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		ILogger $logger,
		DiscoveryService $discoveryService,
		FileService $fileService,
		TokenService $tokenService
	) {
		parent::__construct($appName, $request);
		$this->logger = $logger;
		$this->discoveryService = $discoveryService;
		$this->fileService = $fileService;
		$this->tokenService = $tokenService;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 *
	 * @param string $_action
	 * @param string $shareToken
	 * @param int|null $fileId
	 * @return TemplateResponse
	 * @throws HintException
	 */
	public function OfficePublicLink($_action, $shareToken, $fileId): TemplateResponse {
		$this->logger->debug("ShareFileIndex $_action for $shareToken/$fileId", ['app' => 'wopi']);
		$file = $this->fileService->getByShareToken($shareToken, $fileId);

		return $this->getTemplateResponse($_action, $file, $shareToken);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $_action
	 * @param int $fileId
	 * @return TemplateResponse
	 * @throws HintException
	 */
	public function Office($_action, $fileId): TemplateResponse {
		$this->logger->debug("FileIndex $_action for $fileId", ['app' => 'wopi']);
		$file = $this->fileService->getByFileId($fileId);
		
		return $this->getTemplateResponse($_action, $file, null);
	}

	/**
	 * @throws HintException
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	private function getTemplateResponse(string $_action, Node $file, ?string $shareToken): TemplateResponse {
		try {
			$this->tokenService->getTokenKey();
		} catch (\RuntimeException $ex) {
			throw new HintException($ex);
		}

		$info = new \SplFileInfo($file->getName());
		$data = [
			'key' => 'wopi',
			'data-id' => $file->getId(),
			'data-mime' => $file->getMimetype(),
			'data-ext' => $info->getExtension(),
			'data-fileName' => $info->getBasename(),
			'data-shareToken' => $shareToken,
			'data-action' => $_action
		];
		
		Util::addHeader('data', $data);

		$resp = new TemplateResponse('wopi', 'main', [], 'base');

		$policy = $this->discoveryService->getContentSecurityPolicy();
		$resp->setContentSecurityPolicy($policy);

		return $resp;
	}
}
