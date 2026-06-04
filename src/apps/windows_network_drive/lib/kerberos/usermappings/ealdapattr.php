<?php
/**
 * ownCloud
 *
 * @author Juan Pablo Villafañez Ramos <jvillafanez@owncloud.com>
 * @copyright Copyright (c) 2023, ownCloud GmbH
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\windows_network_drive\lib\kerberos\usermappings;

use OCP\IUserManager;

/**
 * This mapping will get the extended attributes of the ownCloud user in order
 * to get the LDAP attribute requested. Note that the LDAP attribute must be
 * exposed by the user_ldap app
 */
class EALdapAttr implements IMapping {
	/** @var IUserManager */
	private IUserManager $userManager;
	/** @var string */
	private string $attr;
	/** @var array */
	private array $nomap;

	public function __construct(IUserManager $manager, array $params) {
		$this->userManager = $manager;
		$this->attr = $params['attr'] ?? 'userPrincipalName';
		$this->nomap = $params['nomap'] ?? [];
	}

	/**
	 * @inheritdoc
	 */
	public function mapOcToWindows(string $uid): string {
		$isInNoMap = \in_array($uid, $this->nomap, true);

		$userObj = $this->userManager->get($uid);
		if ($userObj === null) {
			if ($isInNoMap) {
				// if it's in the list, return it without change
				return $uid;
			}
			throw new MappingException("User {$uid} not found");
		}

		if ($isInNoMap) {
			throw new MappingException("User {$uid} exists in ownCloud but it is configured not to be mapped");
		}

		if ($userObj->getBackendClassName() !== 'LDAP') {
			throw new MappingException("User {$uid} not in LDAP");
		}

		$eas = $userObj->getExtendedAttributes();
		if (!isset($eas['user_ldap_state']) || $eas['user_ldap_state'] !== 'OK') {
			throw new MappingException("Cannot ensure the LDAP state for user {$uid}");
		}

		if (!isset($eas["user_ldap_attr_{$this->attr}"])) {
			throw new MappingException("Requested attribute not found or not exposed for user {$uid}");
		}
		return $eas["user_ldap_attr_{$this->attr}"];
	}
}
