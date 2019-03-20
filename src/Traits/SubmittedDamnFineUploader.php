<?php
namespace Codem\DamnFineUploader;

use SilverStripe\Assets\File;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Control\Controller;

/**
 * Trait for submitted DFU field implementations
 */
trait SubmittedDamnFineUploader
{
    private static $security_token_name = "SecurityID";

    protected function getSecurityTokenValue()
    {
        $controller = Controller::curr();
        $request = $controller->getRequest();
        $token_name = $this->config()->get('security_token_name');
        $token_value = $request->postVar($token_name);
        return $token_value;
    }

    /**
     * Handle incoming uuids from the form, use the uuid and the form security token to retrieve the file
     * Note that this does not publish the file
     *
     * @return SubmittedFineUploaderField
     */
    public function setValue($uuids)
    {
        if (!empty($uuids) && is_array($uuids) && ($token_value = $this->getSecurityTokenValue())) {
            foreach ($uuids as $uuid) {
                $file = singleton(File::class);
                $record = $file->getByDfuToken($uuid, $token_value);
                if (!empty($record->ID)) {
                    $this->Files()->add($record);
                    $record->protectFile();
                }
            }
        }
        return $this;
    }

    /**
     * Return the value of this field for inclusion into things such as
     * reports.
     *
     * @return string
     */
    public function getFormattedValue()
    {
        $title = _t('DamnFineUploader.DOWNLOAD_FILE', 'Download file');
        $files = [];
        foreach ($this->Files() as $i => $file) {
            $files[] = sprintf(
                '%s - <a href="%s" target="_blank">%s</a>',
                $file->Name,
                $file->URL,
                $title
            );
        }
        return DBField::create_field('HTMLText', implode('<br/>', $files));
    }

    /**
     * Return the value for this field in the CSV export.
     *
     * @return string
     */
    public function getExportValue()
    {
        $links = [];
        foreach ($this->Files() as $file) {
            $links[] = $file->getAbsoluteURL();
        }
        return implode('|', $links);
    }
}
