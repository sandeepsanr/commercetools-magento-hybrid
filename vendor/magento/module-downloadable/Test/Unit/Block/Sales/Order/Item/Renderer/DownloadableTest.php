<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Downloadable\Test\Unit\Block\Sales\Order\Item\Renderer;

use Magento\Downloadable\Model\ResourceModel\Link\Purchased\Item\CollectionFactory;

/**
 * Tests Magento\Downloadable\Test\Unit\Block\Sales\Order\Item\Renderer\Downloadable
 */
class DownloadableTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Magento\Downloadable\Block\Sales\Order\Item\Renderer\Downloadable
     */
    protected $block;

    /**
     * @var \Magento\Downloadable\Model\Link\PurchasedFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $purchasedFactory;

    /**
     * @var CollectionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $itemsFactory;

    public function setUp()
    {
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $contextMock = $this->getMockBuilder('Magento\Backend\Block\Template\Context')
            ->disableOriginalConstructor()
            ->getMock();
        $this->purchasedFactory = $this->getMockBuilder('Magento\Downloadable\Model\Link\PurchasedFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->itemsFactory = $this->getMockBuilder(
            'Magento\Downloadable\Model\ResourceModel\Link\Purchased\Item\CollectionFactory'
        )
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->block = $objectManager->getObject(
            'Magento\Downloadable\Block\Sales\Order\Item\Renderer\Downloadable',
            [
                'context' => $contextMock,
                'purchasedFactory' => $this->purchasedFactory,
                'itemsFactory' => $this->itemsFactory
            ]
        );
    }

    public function testGetLinks()
    {
        $item = $this->getMockBuilder('\Magento\Sales\Model\Order\Item')
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();
        $linkPurchased = $this->getMockBuilder('Magento\Downloadable\Model\Link\Purchased')
            ->disableOriginalConstructor()
            ->setMethods(['load'])
            ->getMock();
        $itemCollection =
            $this->getMockBuilder('Magento\Downloadable\Model\ResourceModel\Link\Purchased\Item\Collection')
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter'])
            ->getMock();

        $this->block->setData('item', $item);
        $this->purchasedFactory->expects($this->once())->method('create')->willReturn($linkPurchased);
        $linkPurchased->expects($this->once())->method('load')->with('itemId', 'order_item_id')->willReturnSelf();
        $item->expects($this->any())->method('getId')->willReturn('itemId');
        $this->itemsFactory->expects($this->once())->method('create')->willReturn($itemCollection);
        $itemCollection->expects($this->once())
            ->method('addFieldToFilter')
            ->with('order_item_id', 'itemId')
            ->willReturnSelf();

        $this->assertEquals($linkPurchased, $this->block->getLinks());
    }
}
