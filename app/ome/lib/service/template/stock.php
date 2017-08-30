<?php

/**
 * 备货单
 *
 */
class ome_service_template_stock {
    public function getElements() {
        return kernel::single('ome_delivery_template_stock')->defaultElements();
    }
}