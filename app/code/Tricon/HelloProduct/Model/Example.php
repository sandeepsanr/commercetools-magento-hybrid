<?php
namespace Tricon\HelloProduct\Model;
use Magento\Framework\Model\AbstractModel;
class Example extends AbstractModel
{
    /**
     * Define resource model
     */
    protected function _construct()
    {
    $this->_init('Tricon\HelloProduct\Model\Resource\Example');
    }
}