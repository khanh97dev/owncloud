<?php
/**
 * ownCloud - Ransomware Protection
 *
 * @copyright 2022 ownCloud GmbH
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Ransomware_Protection\BlacklistFetcher;

class FileFetcher implements IBlacklistFetcher {
	/**
	 * Fetch the blacklist from the target file. The file must contain
	 * one pattern per line. Empty black lines will be ignored.
	 *
	 * @param string $location the path where the file is.
	 * @return array the patterns read from the file
	 * @throws BlacklistFetcherException if the content can't be read
	 */
	public function fetchBlacklist(string $location): array {
		$scheme = parse_url($location, PHP_URL_SCHEME);
		if ($scheme !== null) {
			throw new BlacklistFetcherException("$location not located on the local file system. PHP stream wrappers are not supported.");
		}
		if (!\is_readable($location)) {
			throw new BlacklistFetcherException("$location not found or not readable");
		}

		$handle = \fopen($location, 'rb');
		if ($handle === false) {
			throw new BlacklistFetcherException("An error happened while opening the blacklist in $location");
		}

		$blacklist = [];
		while (!\feof($handle)) {
			$line = \fgets($handle);
			if (\trim($line) !== '') {
				$blacklist[] = $line;
			}
		}
		\fclose($handle);

		return $blacklist;
	}
}
