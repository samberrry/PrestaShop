<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */
declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\Form\IdentifiableObject\DataFormatter;

use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * This class transforms the data from bulk form into data adapted to the combination form structure,
 * since the forms are not constructed the same way the goal is to rebuild the same data values with the
 * right property path. When the data is not detected it is not included in the formatted data.
 */
class BulkCombinationFormDataFormatter
{
    /**
     * @param array<string, mixed> $formData
     *
     * @return array<string, mixed>
     */
    public function format(array $formData): array
    {
        $pathAssociations = [
            // References data follow same format so it is quite easy
            '[references][reference]' => '[references][reference]',
            '[references][mpn]' => '[references][mpn]',
            '[references][upc]' => '[references][upc]',
            '[references][ean_13]' => '[references][ean_13]',
            '[references][isbn]' => '[references][isbn]',
            // Prices data have almost the same format
            '[price][wholesale_price]' => '[price_impact][wholesale_price]',
            '[price][price_tax_excluded]' => '[price_impact][price_tax_excluded]',
            '[price][price_tax_included]' => '[price_impact][price_tax_included]',
            '[price][unit_price]' => '[price_impact][unit_price]',
            '[price][weight]' => '[price_impact][weight]',
            // Stock data are the trickiest
            '[stock][delta_quantity][delta]' => '[stock][quantities][delta_quantity][delta]',
            '[stock][minimal_quantity]' => '[stock][quantities][minimal_quantity]',
            '[stock][stock_location]' => '[stock][options][stock_location]',
            '[stock][low_stock_threshold]' => '[stock][options][low_stock_threshold]',
            '[stock][low_stock_alert]' => '[stock][options][low_stock_alert]',
            '[stock][available_date]' => '[stock][available_date]',
        ];
        $formattedData = [];

        $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()
            ->enableExceptionOnInvalidIndex()
            ->enableExceptionOnInvalidPropertyPath()
            ->disableMagicCall()
            ->getPropertyAccessor()
        ;
        foreach ($pathAssociations as $bulkFormPath => $editFormPath) {
            try {
                $bulkValue = $propertyAccessor->getValue($formData, $bulkFormPath);
                $propertyAccessor->setValue($formattedData, $editFormPath, $bulkValue);
            } catch (AccessException $e) {
                // When the bulk data is not found it means the field was disabled, which is the expected behaviour
                // as the bulk request is a partial request not every data is expected And when it's not present
                // it means there is no modification to do so this field is simply ignored
            }
        }

        return $formattedData;
    }
}
