<?php

namespace Shoplo;

class Product extends Resource
{
	public function retrieve($id = 0, $collection_id = 0, $category_id = 0, $params = array(), $cache = false)
	{
		if ($id == 0) {
			if (!$cache || !isset($this->bucket['product'])) {
				$params                  = $this->prepare_params($params);
				$result                  = empty($params) ? $this->send($this->prefix . "products") : $this->send($this->prefix . "products?" . $params);
				$this->bucket['product'] = $this->prepare_result($result);
			}
			return $this->bucket['product'];
		} else {
			if (!$cache || !isset($this->bucket['product'][$id])) {
				$result                       = $this->send($this->prefix . "/products/" . $id);
				$this->bucket['product'][$id] = $this->prepare_result($result);
			}
			return $this->bucket['product'][$id];
		}
	}

	public function count($collection_id = 0, $params = array())
	{
		$params = $this->prepare_params($params);
		return ($collection_id > 0) ? $this->send($this->prefix . "products/count?collection_id=" . $collection_id . "&" . $params) : $this->send($this->prefix . "products/count?" . $params);
	}

    public function create($fields, $validateSku = true, $apiV2 = false)
    {
        $fields = array('product' => $fields, 'validate_sku'=>$validateSku, 'apiV2'=>$apiV2);
		return $this->send("products", 'POST', $fields);
    }

	public function modify($id, $fields, $validateSku = true, $apiV2 = false)
	{
		$fields = array('product' => $fields, 'validate_sku'=>$validateSku);
		$apiV2Param = $apiV2 ? '&apiV2=1' : '';
		return $this->send($this->prefix . "products/" . $id . "?update_variants=1".$apiV2Param, 'PUT', $fields);
	}

	public function remove($id)
	{
		return $this->send("products/" . $id, 'DELETE');
	}

	public function createBulk($params)
	{
		$params = ['products' => $params];
		return $this->send($this->prefix . "products_bulk", 'POST', $params);
	}

	public function updateBulk($params)
	{
		$params = ['products' => $params];
		return $this->send($this->prefix . "products_bulk", 'PUT', $params);
	}
}