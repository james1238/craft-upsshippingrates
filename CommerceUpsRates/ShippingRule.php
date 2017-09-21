<?php

namespace CommerceUpsRates;

use Ups\Rate;
use Commerce\Interfaces\ShippingRule as CommerceShippingRule;
use Craft\StringHelper;

class ShippingRule implements CommerceShippingRule
{
	private $_description;
	private $_price;
	private $_rate;
	private $_order;

	/**
	 * ShippingRule constructor.
	 *
	 * @param      $carrier
	 * @param      $service
	 * @param null $rate
	 */
	public function __construct($carrier, $service, $rate = null, $order = null)
	{
		$this->_description = $rate->Service->getDescription();
		$this->_rate = $rate;
		$this->_order = $order;

		if ($this->_rate)
		{

			$settings = \Craft\craft()->plugins->getPlugin('upsshippingrates')->getSettings();

			$rateType = \Craft\craft()->config->get('useRate', 'upsshippingrates');


			if ( $settings->showNegotiatedRates && isset( $rate->NegotiatedRates->NetSummaryCharges->GrandTotal->MonetaryValue ))
			{
				// Show Negotiated Rates is enabld and a negotiated rate exists.
 				$amount = $rate->NegotiatedRates->NetSummaryCharges->GrandTotal->MonetaryValue;
			}
			else
			{
				$amount = $rate->TotalCharges->MonetaryValue;
			}

			if ($settings->markup > 0 && $settings->markup <= 100)
			{
				$markupPercentage = $settings->markup / 100;
				$this->_price = $amount + ($amount * $markupPercentage);
			}
			else
			{
				$this->_price = $amount;
			}


			$modifyPrice = \Craft\craft()->config->get('modifyPrice', 'upsshippingrates');

			if ($modifyPrice)
			{
				if (is_callable($modifyPrice))
				{
					$this->_price = call_user_func_array($modifyPrice, [
						'shippingMethod' => $rate->Service->getCode().' - '.$rate->Service->getName(),
						'order'          => $this->_order,
						'price'          => $this->_price]);
				}
				else
				{
					$this->_price = $modifyPrice;
				}
			}
		}
	}

	/**
	 * Is this rule a match on the order? If false is returned, the shipping engine tries the next rule.
	 *
	 * @return bool
	 */
	public function matchOrder(\Craft\Commerce_OrderModel $order)
	{
		if ($this->_rate)
		{
			return true;
		}
	}

	/**
	 * Is this shipping rule enabled for listing and selection
	 *
	 * @return bool
	 */
	public function getIsEnabled()
	{
		return true;
	}

	/**
	 * Stores this data as json on the orders shipping adjustment.
	 *
	 * @return mixed
	 */
	public function getOptions()
	{
		return [];
	}

	/**
	 * Returns the percentage rate that is multiplied per line item subtotal.
	 * Zero will not make any changes.
	 *
	 * @return float
	 */
	public function getPercentageRate()
	{
		return 0.00;
	}

	/**
	 * Returns the flat rate that is multiplied per qty.
	 * Zero will not make any changes.
	 *
	 * @return float
	 */
	public function getPerItemRate()
	{
		return 0.00;
	}

	/**
	 * Returns the rate that is multiplied by the line item's weight.
	 * Zero will not make any changes.
	 *
	 * @return float
	 */
	public function getWeightRate()
	{
		return 0.00;
	}

	/**
	 * Returns a base shipping cost. This is added at the order level.
	 * Zero will not make any changes.
	 *
	 * @return float
	 */
	public function getBaseRate()
	{
		return $this->_price;
	}

	/**
	 * Returns a max cost this rule should ever apply.
	 * If the total of your rates as applied to the order are greater than this, the baseShippingCost
	 * on the order is modified to meet this max rate.
	 *
	 * @return float
	 */
	public function getMaxRate()
	{
		return 0.00;
	}

	/**
	 * Returns a min cost this rule should have applied.
	 * If the total of your rates as applied to the order are less than this, the baseShippingCost
	 * on the order is modified to meet this min rate.
	 * Zero will not make any changes.
	 *
	 * @return float
	 */
	public function getMinRate()
	{
		return 0.00;
	}

	/**
	 * Returns a description of the rates applied by this rule;
	 * Zero will not make any changes.
	 *
	 * @return string
	 */
	public function getDescription()
	{
		return $this->_description;
	}
}