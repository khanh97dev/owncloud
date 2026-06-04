<?php
/**
 *
 * @copyright Copyright (c) 2022, ownCloud GmbH
 * @license OCL
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\FilesClassifier;

use Laminas\Xml\Security;
use OCP\Files\File;
use OCP\Files\NotPermittedException;
use OCP\ILogger;
use OCP\ITempManager;
use Symfony\Component\Process\Process;
use ZipArchive;
use SimpleXMLElement;

class DocumentProperties {
	private ITempManager $tempManager;
	private ILogger $logger;

	public function __construct(ITempManager $tempManager, ILogger $logger) {
		$this->tempManager = $tempManager;
		$this->logger = $logger;
	}

	/**
	 * Check whether document is supported for scanning of properties
	 */
	public function isDocumentSupported(string $fileName): bool {
		$extension = \pathinfo($fileName, PATHINFO_EXTENSION);

		return \in_array(strtolower($extension), ['docx','dotx','xlsx','xltx','pptx','ppsx','potx','pdf', 'jpg', 'jpeg', 'png', 'heic', 'tiff']);
	}

	/**
	 * @throws NotPermittedException
	 */
	public function scanFile(File $file): ?SimpleXMLElement {
		$tempFile = $this->tempManager->getTemporaryFile($file->getName());
		\file_put_contents($tempFile, $file->fopen('rb'));

		return $this->scan($tempFile);
	}

	/**
	 * Scan for document properties
	 *
	 * @param string $path the path to the file
	 * @param string|null $fileName the name of the file. Has to be set in case $path is a temporary file which holds no extension
	 * @return SimpleXMLElement|null
	 */
	public function scan(string $path, string $fileName = null): ?SimpleXMLElement {
		$this->logger->debug("[files_classifier] DocumentProperties:scan: $path, $fileName");
		if (!\file_exists($path)) {
			$this->logger->warning("[files_classifier] File not found: $path, $fileName");
			return null;
		}
		$fileName = $fileName ?? $path;
		$extension = \pathinfo($fileName, PATHINFO_EXTENSION);
		if (\in_array(strtolower($extension), ['pdf', 'jpg', 'jpeg', 'png', 'heic', 'tiff'])) {
			$customXml = $this->loadPDF($path);
		} else {
			$zip = new ZipArchive();
			if ($zip->open($path) === true) {
				// get the custom.xml file from the office document
				$customXml = $zip->getFromName('docProps/custom.xml');
				$zip->close();
			} else {
				// Not a valid zip file
				$customXml = '';
			}
			$customXml = \str_replace('xmlns="', 'ns="', $customXml);
		}

		if (empty($customXml)) {
			return null;
		}
		/** @var false|SimpleXMLElement $xml */
		$xml = Security::scan($customXml);
		if ($xml === false) {
			return null;
		}

		$ns = $xml->getNamespaces(true);
		foreach ($ns as $k => $n) {
			$xml->registerXPathNamespace($k, $n);
		}

		return $xml;
	}

	private function loadPDF(string $path): string {
		$exiftoolBinary = \OC_Helper::findBinaryPath('exiftool');
		if ($exiftoolBinary === null) {
			throw new \RuntimeException("Command 'exiftool' not found.");
		}

		$process = new Process([
			$exiftoolBinary,
			'-n',
			'-q',
			'-b',
			'-X',
			'-charset',
			'UTF8',
			realpath($path)
		]);
		$process->setTimeout(60);

		$process->run();

		if (! $process->isSuccessful()) {
			throw new \RuntimeException(sprintf('Command %s failed : %s, exitcode %s', $process->getCommandLine(), $process->getErrorOutput(), $process->getExitCode()));
		}

		$output = $process->getOutput();

		$this->logger->debug("exiftool($path): $output");

		return $output;
	}
}
