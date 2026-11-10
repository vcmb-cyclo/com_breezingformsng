<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Site\Service\Rendering;

/**
 * Builds the client-side validator for configured file-upload extensions.
 */
final class FileExtensionsCheckBuilder
{
    /**
     * @param array<int, \stdClass> $rows
     * @return array{0: string, 1: int}
     */
    public function build(array $rows, int $rowCount, string $fileExtensionError, bool $templateConfigured): array
    {
        $count = 0;
        $script = 'function checkFileExtensions(){';

        for ($i = 0; $i < $rowCount; $i++) {
            $row = $rows[$i];

            if ($row->type != 'File Upload' || !$templateConfigured || trim($row->data2) == '') {
                continue;
            }

            $id = $row->id;
            $script .= 'var ff_elem' . $id . 'Exts = false;';
            $extensions = explode(',', $row->data2);

            foreach ($extensions as $extension) {
                $extensionLiteral = json_encode(
                    '.' . strtolower(trim($extension)),
                    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR
                );
                $script .= '
								if(!ff_elem' . $id
                    . 'Exts && document.getElementById("ff_elem' . $id
                    . '").value.toLowerCase().lastIndexOf(' . $extensionLiteral . ') != -1){
								ff_elem' . $id . 'Exts = true;
								}else if(!ff_elem' . $id
                    . 'Exts && document.getElementById("ff_elem' . $id
                    . '").value == ""){
								ff_elem' . $id . 'Exts = true;
							}';
            }

            $allowedLiteral = json_encode(
                implode(', ', array_map('trim', $extensions)),
                JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR
            );
            $script .= '
					if(!ff_elem' . $id . 'Exts){
						var uploadInput = document.getElementById("ff_elem' . $id . '");
						var uploadName = uploadInput.value.split(/[\\\\\/]/).pop();
						var uploadMessage = ' . $fileExtensionError . '.replace(/%([12])\$s/g, function(_, n) {
							return n == "1" ? uploadName : ' . $allowedLiteral . ';
						});
						if(typeof bfUseErrorAlerts == "undefined"){
							alert(uploadMessage);
						} else {
							var uploadText = document.createElement("span");
							uploadText.textContent = uploadMessage;
							bfShowErrors(uploadText.innerHTML);
						}
						if(ff_currentpage != ' . $row->page . ')ff_switchpage(' . $row->page . ');
                                                if(document.getElementById("bfSubmitButton")){
                                                    document.getElementById("bfSubmitButton").disabled = false;
                                                }
                                                if(typeof JQuery != "undefined"){'
                . 'JQuery(".bfCustomSubmitButton").prop("disabled", false);}
						return false;
					}
					';
            $count++;
        }

        $script .= '
			return true;
		}
		';

        return [$script, $count];
    }
}
