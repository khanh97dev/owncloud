<?php
/**
 * ownCloud
 *
 * @copyright (C) 2024 ownCloud GmbH
 * @license ownCloud Commercial License
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see
 * <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Files_Lifecycle\Policy;

use OCA\Files_Lifecycle\Application;
use OCA\Files_Lifecycle\Policy\IPolicy;
use OCA\Files_Lifecycle\Policy\SoftPolicy;
use OCP\IConfig;
use OCP\ILogger;

/**
 * Class PolicyManager
 *
 * @package OCA\Files_Lifecycle\Policy
 */
class PolicyManager {
	/** @var IConfig */
	private IConfig $config;

	/** @var ILogger */
	private ILogger $logger;

	/** @var IPolicy[] */
	private $policyList = [];

	/** @var string|null */
	private ?string $defaultPolicyName;

	/**
	 * @param IConfig $config
	 * @param ILogger $logger
	 */
	public function __construct(IConfig $config, ILogger $logger) {
		$this->config = $config;
		$this->logger = $logger;
	}

	/**
	 * Register the policy with the target name
	 *
	 * @param string $name the name to be used to register the policy
	 * @param IPolicy $policy the policy to be registered
	 */
	public function registerPolicy(string $name, IPolicy $policy) {
		$this->policyList[$name] = $policy;
	}

	/**
	 * Register the policy and set it as default policy
	 *
	 * @param string $name the name to be used to register the policy
	 * @param IPolicy $policy the policy to be registered
	 */
	public function registerDefaultPolicy(string $name, IPolicy $policy) {
		$this->policyList[$name] = $policy;
		$this->defaultPolicyName = $name;
	}

	/**
	 * Get the registered policy by name. If the policy isn't registered,
	 * return null
	 *
	 * @param string $name the name of the registered policy
	 * @return IPolicy|null the registered policy or null if it isn't registered
	 */
	public function getRegisteredPolicy(string $name): ?IPolicy {
		if (isset($this->policyList[$name])) {
			return $this->policyList[$name];
		}
		return null;
	}

	/**
	 * Get the names of the registered policies. The array could be empty if
	 * no policy is registered.
	 *
	 * @return array a list of the registered policy names
	 */
	public function getRegisteredNames(): array {
		return \array_keys($this->policyList);
	}

	/**
	 * Get the default registered policy name.
	 * If no policy is registered as default, the first registered name will
	 * be returned.
	 * If there are no policies registered, null will be returned.
	 *
	 * @return string|null the default policy name or null
	 */
	public function getDefaultName(): ?string {
		if (isset($this->defaultPolicyName, $this->policyList[$this->defaultPolicyName])) {
			return $this->defaultPolicyName;
		}

		return \array_key_first($this->policyList);  // null will be returned if array is empty
	}

	/**
	 * Try to get the configured policy. The policy returned will be according
	 * to the algorithm described in the `getRegisteredPolicyOrDefault` method.
	 * Note that this method might return null, although it would be weird because
	 * the policy manager should have at least one policy configured.
	 *
	 * @return IPolicy|null the configured policy or null
	 */
	public function getConfiguredPolicy(): ?IPolicy {
		$policyName = $this->config->getAppValue(Application::APPID, 'policy', SoftPolicy::POLICY_NAME);
		$policy = $this->getRegisteredPolicy($policyName);

		if ($policy === null) {
			$this->logger->info("files_lifecycle policy '{$policyName}' not registered. Trying default policy...", ['app' => 'files_lifecycle']);

			$defaultName = $this->getDefaultName();
			if ($defaultName !== null) {
				$policy = $this->getRegisteredPolicy($defaultName);
				if ($policy === null) {
					// we shouldn't reach this point
					$this->logger->error("files_lifecycle default policy '{$defaultName}' not registered.", ['app' => 'files_lifecycle']);
				}
			} else {
				$this->logger->error("files_lifecycle no default policy", ['app' => 'files_lifecycle']);
			}
		}

		return $policy;
	}
}
