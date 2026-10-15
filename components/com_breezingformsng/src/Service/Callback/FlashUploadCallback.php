<?php

declare(strict_types=1);

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Site\Service\Callback;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\ParameterType;
use Joomla\Database\DatabaseInterface;
use Joomla\Filesystem\File;
use Vcmb\Component\BreezingformsNG\Site\Service\Upload\TemporaryUploadDirectory;
use Vcmb\Component\BreezingformsNG\Site\Service\Upload\FlashChunkAssembler;
use Vcmb\Component\BreezingformsNG\Site\Service\Upload\FlashUploadSizeValidator;

/**
 * Chunked (legacy flash) upload endpoint (flashUpload=1).
 */
final class FlashUploadCallback
{
    private ?FlashChunkAssembler $chunkAssemblerService = null;

    public function __construct(
        private readonly CMSApplication $application,
        private readonly DatabaseInterface $database,
        private readonly FlashUploadSizeValidator $uploadSizeValidator,
    ) {
    }

    public function handle(): void
    {
        $db = $this->database;

    if (ob_get_level() > 0) {
        ob_end_clean();
    }
    $input = $this->application->getInput();
    $uploadedFile = $input->files->get('Filedata', [], 'array');
    if (is_numeric($input->getString('form', '')) && $uploadedFile !== [] && $input->getString('bfFlashUploadTicket', '') != '') {

        $formId = $input->getInt('form', -1);
        $itemName = $input->getString('itemName', '');
        $query = $db->createQuery()
            ->select(['form.id', 'form.template_code_processed', 'form.template_code'])
            ->from($db->quoteName('#__facileforms_forms', 'form'))
            ->from($db->quoteName('#__facileforms_elements', 'element'))
            ->where('form.id = :formId')
            ->where('element.name = :itemName')
            ->where('element.form = :formId2')
            ->bind(':formId', $formId, ParameterType::INTEGER)
            ->bind(':itemName', $itemName, ParameterType::STRING)
            ->bind(':formId2', $formId, ParameterType::INTEGER);
        $db->setQuery($query);
        $objectList = $db->loadObjectList();
        $formIdCount = count($objectList);
        if ($formIdCount > 0) {
            $tempFile = (string) ($uploadedFile['tmp_name'] ?? '');
            $temporaryUploads = new TemporaryUploadDirectory((string) $this->application->get('tmp_path'));
            $targetPath = $temporaryUploads->path();
            if ($temporaryUploads->ensure()) {
                $secureTicket = $this->application->getSession()->get('secure_ticket', '');
                if ($secureTicket == '') {
                    mt_srand();
                    $secureTicket = md5(strtotime('now') . mt_rand(0, mt_getrandmax()));
                    $this->application->getSession()->set('secure_ticket', $secureTicket);
                }

                $targetFile = str_replace('//', '/', $targetPath) . 'chunks/' . $input->getInt('offset', 0) . '_' . bf_sanitizeFilename($input->getString('name', 'unknown')) . '_' . $input->getString('itemName', '') . '_' . $input->getString('bfFlashUploadTicket', '') . '_' . $secureTicket . '_chunktmp';
                $finaltargetFile = str_replace('//', '/', $targetPath) . bf_sanitizeFilename($input->getString('name', 'unknown')) . '_' . $input->getString('itemName', '') . '_' . $input->getString('bfFlashUploadTicket', '') . '_' . $secureTicket . '_flashtmp';

                if (File::upload($tempFile, $targetFile)
                    && $this->chunkAssembler()->append($targetFile, $finaltargetFile, $targetPath)
                ) {

                    $dataObject = json_decode(bf_b64dec($objectList[0]->template_code), true);

                    $validationLabel = $this->uploadSizeValidator->findOversizedLabel(
                        $dataObject,
                        $finaltargetFile,
                        $itemName
                    );

                    if ($validationLabel !== null) {
                        File::delete($finaltargetFile);
                        echo $validationLabel . ': '
                            . Text::_('COM_BREEZINGFORMSNG_FLASH_UPLOADER_TOO_LARGE');
                        $this->application->close();
                    }

                    File::delete($targetFile);
                } else {
                    echo Text::_('COM_BREEZINGFORMSNG_PROCESS_UPLOADFAILED');
                }
            } else {
                echo Text::_('COM_BREEZINGFORMSNG_PROCESS_UPLOADFAILED');
            }
        } else {
            echo Text::_('JERROR_AN_ERROR_HAS_OCCURRED');
        }
    }
    $this->application->close();
    }

    private function chunkAssembler(): FlashChunkAssembler
    {
        return $this->chunkAssemblerService ??= new FlashChunkAssembler();
    }

}
