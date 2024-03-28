<?php
/**
 * AtroCore Software
 *
 * This source file is available under GNU General Public License version 3 (GPLv3).
 * Full copyright and license information is available in LICENSE.txt, located in the root directory.
 *
 * @copyright  Copyright (c) AtroCore GmbH (https://www.atrocore.com)
 * @license    GPLv3 (https://www.gnu.org/licenses/)
 */

declare(strict_types=1);

namespace Atro\Controllers;

use Atro\Core\Templates\Controllers\Base;
use Atro\Core\Exceptions\BadRequest;
use Atro\Core\Exceptions\Forbidden;

class File extends Base
{
    public function actionUploadProxy($params, $data, $request)
    {
        if (!$request->isGet() || empty($request->get('url'))) {
            throw new BadRequest();
        }

        // Open stream to file URL
        $fileStream = fopen($request->get('url'), 'r');
        if (!$fileStream) {
            throw new \Exception('Failed to open file stream');
        }

        // Set content type to octet-stream for downloading large files
        header("Content-Type: application/octet-stream");

        // Stream the file content
        while (!feof($fileStream)) {
            echo fread($fileStream, 8192); // Read and output in 8KB chunks
            flush(); // Flush output buffer to ensure immediate output
        }

        // Close the file stream
        fclose($fileStream);

        exit(0);
    }

    public function actionCreate($params, $data, $request)
    {
        if (!$request->isPost()) {
            throw new BadRequest();
        }

        if (!$this->getAcl()->check($this->name, 'create')) {
            throw new Forbidden();
        }

        return $this->getRecordService()->createEntity($data);
    }
}
