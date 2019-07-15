<?php
/**
 *    This file is part of Magento Store Manager Connector.
 *
 *   Magento Store Manager Connector is free software: you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation, either version 3 of the License, or
 *   (at your option) any later version.
 *
 *   Magento Store Manager Connector is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with Magento Store Manager Connector.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Emagicone\Connector\Block\Adminhtml\Settings;

/**
 * Class Edit
 * @package Emagicone\Connector\Block\Adminhtml\Settings
 */
class Edit extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * Initialize settings edit block
     *
     * @return void
     */
    public function _construct()
    {
        $this->_blockGroup = 'Emagicone_Connector';
        $this->_controller = 'adminhtml_settings';

        parent::_construct();

        $this->buttonList->remove('back');
        if ($this->_isAllowedAction('Emagicone_Connector::settings_edit')) {
            $this->buttonList->add(
                'save-and-continue',
                [
                    'label' => __('Save and Continue Edit'),
                    'class' => 'save',
                    'data_attribute' => [
                        'mage-init' => [
                            'button' => [
                                'event' => 'saveAndContinueEdit',
                                'target' => '#edit_form'
                            ]
                        ]
                    ]
                ],
                -100
            );
        } else {
            $this->buttonList->remove('save');
        }
    }

    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    public function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }

    /**
     * Prepare layout
     *
     * @return \Magento\Framework\View\Element\AbstractBlock
     */
    public function _prepareLayout()
    {
        $this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('user_devices') == null) {
                    tinyMCE.execCommand('mceAddControl', false, 'user_devices');
                } else {
                    tinyMCE.execCommand('mceRemoveControl', false, 'user_devices');
                }
            };
        ";

        return parent::_prepareLayout();
    }
}
