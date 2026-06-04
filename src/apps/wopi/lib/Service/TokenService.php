<?php /** @noinspection HtmlUnknownTag */

/**
 * ownCloud Wopi
 *
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @copyright 2018 ownCloud GmbH.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\WOPI\Service;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use InvalidArgumentException;
use OC\AppFramework\Middleware\Security\Exceptions\SecurityException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\IUser;
use RuntimeException;
use UnexpectedValueException;

class TokenService {
	private IConfig $config;
	private ITimeFactory $timeFactory;
	private IURLGenerator $generator;

	public const MIN_KEY_LENGTH_BYTES = 32;

	public function __construct(
		IConfig $config,
		ITimeFactory $timeFactory,
		IURLGenerator $generator
	) {
		$this->config = $config;
		$this->timeFactory = $timeFactory;
		$this->generator = $generator;
	}

	/**
	 * @param string $fileId
	 * @param string $folderUrl
	 * @param IUser $user
	 * @return array
	 * @throws Exception
	 */
	public function GenerateNewAuthUserAccessToken(string $fileId, string $folderUrl, IUser $user): array {
		if ($fileId === '' || $folderUrl === '') {
			throw new InvalidArgumentException();
		}
		$key = $this->getTokenKey();

		$expiry = $this->timeFactory->getTime() + (int)$this->config->getAppValue('wopi', 'access-token.validity', (string)(4 * 60 * 60));
		$token = JWT::encode([
			'uid' => $user->getUID(),
			'fid' => $fileId,
			'furl' => $folderUrl,
			'exp' => $expiry
		], $key->getKeyMaterial(), $key->getAlgorithm());

		$wopiSrc = $this->getWopiSrc($fileId);

		return [
			'token' => $token,
			'expires' => $expiry * 1000,
			'wopi_src' => $wopiSrc
		];
	}

	/**
	 * @param string $fileId
	 * @param string $folderUrl
	 * @param string $shareToken
	 * @return array
	 * @throws Exception
	 */
	public function GenerateNewPublicLinkAccessToken(string $fileId, string $folderUrl, string $shareToken): array {
		if ($fileId === '' || $folderUrl === '') {
			throw new InvalidArgumentException();
		}
		$key = $this->getTokenKey();

		$expiry = $this->timeFactory->getTime() + (int)$this->config->getAppValue('wopi', 'access-token.validity', (string)(4 * 60 * 60));
		$token = JWT::encode([
			'uid' => '',
			'st' => $shareToken,
			'fid' => $fileId,
			'furl' => $folderUrl,
			'exp' => $expiry
		], $key->getKeyMaterial(), $key->getAlgorithm());

		$wopiSrc = $this->getWopiSrc($fileId);

		return [
			'token' => $token,
			'expires' => $expiry * 1000,
			'wopi_src' => $wopiSrc
		];
	}

	/**
	 * @param string $access_token
	 * @param string $fileId
	 * @return array
	 * @throws SecurityException
	 */
	public function verifyToken(string $access_token, string $fileId): array {
		try {
			$fileId = $this->extractFileId($fileId);

			$token = JWT::decode($access_token, $this->getTokenKey());
			if ($token->fid !== $fileId) {
				throw new SecurityException('Token not for the given fileId', 401);
			}

			if ($token->uid !== '') {
				return [
					'UserId' => $token->uid,
					'FolderUrl' => $token->furl,
					'FileId' => $token->fid,
				];
			}

			return [
				'ShareToken' => $token->st,
				'FolderUrl' => $token->furl,
				'FileId' => $token->fid,
			];
		} catch (UnexpectedValueException $ex) {
			throw new SecurityException($ex->getMessage(), 401);
		}
	}

	private function extractFileId(string $fileId): string {
		# not a JWT - just return
		$tks = \explode('.', $fileId);
		if (\count($tks) !== 3) {
			return $fileId;
		}
		return JWT::decode($fileId, $this->getProxyKey())->f;
	}

	/**
	 * Reads the jwt secret key which is used for jwt computation
	 */
	public function getTokenKey() : Key {
		return $this->loadKey('wopi.token.key');
	}

	public function getProxyKey() : Key {
		$key = $this->loadKey('wopi.proxy.key');

		# ensure that the keys are not identical
		$tokenKey = $this->getTokenKey();
		if ($tokenKey->getKeyMaterial() === $key->getKeyMaterial()) {
			throw new RuntimeException('System configuration <wopi.proxy.key> and <wopi.token.key> are identical.');
		}
		return $key;
	}

	private function getWopiSrc(string $fileId) {
		$wopiSrc = $this->config->getSystemValue('wopi.proxy.url', null);
		if ($wopiSrc) {
			$key = $this->getProxyKey();
			$url = $this->generator->linkToRouteAbsolute('wopi.discovery.index');
			$url = substr($url, 0, -14) . 'files/';

			# assumption: proxy url holds e.g. https://office.owncloud.com/wopi/files/
			$token = JWT::encode([
				'u' => $url,
				'f' => $fileId
			], $key->getKeyMaterial(), $key->getAlgorithm());
			$wopiSrc .= $token;
		}
		return $wopiSrc;
	}

	private function loadKey(string $configKey): Key {
		$key = $this->config->getSystemValue($configKey, null);
		if ($key === null) {
			throw new RuntimeException("System configuration <$configKey> is missing.");
		}
		if (\strlen($key) < self::MIN_KEY_LENGTH_BYTES) {
			throw new RuntimeException("System configuration <$configKey> is too short. " . self::MIN_KEY_LENGTH_BYTES . " bytes is the minimum.");
		}
		return new Key($key, 'HS256');
	}
}
