<?php

/* This file is part of testMaker.

testMaker is free software; you can redistribute it and/or modify
it under the terms of version 2 of the GNU General Public License as
published by the Free Software Foundation.

testMaker is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>. */


/**
 * @package Core
 */

libLoad("utilities::ExternalStateObject");

/**
 * Contains the logic needed to run adaptive tests
 *
 * Ported from the original testMaker.
 *
 * @package Core
 */
class AdaptiveTestSession extends ExternalStateObject
{
	/**
	 * Whether to randomize all items (<kbd>TRUE</kbd>) or only the first (<kbd>FALSE</kbd>)
	 * Adjust if necessary before calling {@link init()}
	 * @access public
	 */
	var $randomizeAll = TRUE;

	/**
	 * How many of the best items should be taken into account when randomizing
	 * Adjust if necessary before calling {@link init()}
	 * @access public
	 */
	var $randomizerLimit = 5;

	/**
	 * How many quadrature points should be used
	 * Adjust if necessary before calling {@link init()}
	 * @access public
	 */
	var $quadraturePoints = 41;

	/**
	 * Minimum Theta value
	 * Adjust if necessary before calling {@link init()}
	 * @access public
	 */
	var $thetaMin = -4.2;

	/**
	 * Theta width
	 * Adjust if necessary before calling {@link init()}
	 * @access public
	 */
	var $thetaWidth = 8.4;

	/**
	 * The minimum information limit per step
	 * Adjust if necessary before calling {@link init()}
	 * @access public
	 */
	var $minInfoLowerLimit = 0;

	/**
	 * How often the minimum information limit may be crossed before aborting
	 * Adjust if necessary before calling {@link init()}
	 * @access public
	 */
	var $maxMinInfoLimitCrossCount = 0;

	/**#@+
	 * @access private
	 */
	var $maxItemCount = 0;
	var $maxSem = 0.0;

	var $currentItemId = NULL;
	var $remainingItems = array();
	var $processedItems = array();

	var $stepNumber = 0;
	var $minInfoLimitCrossCount = 0;
	var $theta = 0;
	var $sem = 99.9;

	var $quadraturePointArray;
	/**#@-*/

	/**
	 * Initializes an adaptive test session
	 * Constructor replacement, see {@link ExternalStateObject}
	 * @param ItemBlock Adaptive item block
	 */
	function init($itemBlock)
	{
		$this->maxItemCount = $itemBlock->getMaxItems();
		if ($this->maxItemCount == 0)
			$this->maxItemCount = 1000000;
		$this->maxSem = $itemBlock->getMaxSem();

		$quadraturePointArray = new QuadraturePointArray();
		$quadraturePointArray->init($this->quadraturePoints, $this->thetaMin, $this->thetaWidth);
		$quadraturePointArray->saveState($this->quadraturePointArray);

		$adaptiveItem = new AdaptiveTestItem();
		foreach ($itemBlock->getTreeChildren() as $item) {
			$adaptiveItem->init($item->getId(), $item->getDiscrimination(), $item->getDifficulty(), $item->getGuessing());
			$adaptiveItem->saveState($this->remainingItems[$item->getId()]);
		}
	}

	/**
	 * Returns the theta value
	 * @return double
	 */
	function getTheta()
	{
		return $this->theta;
	}

	/**
	 * Returns the standard error of measurement
	 * @return double;
	 */
	function getSem()
	{
		return $this->sem;
	}

	/**
	 * Processes the answer to the current item
	 * Call {@link getCurrentItemId()} afterwards to get the ID of the new item
	 * @param boolean whether the current item was answered correctly (ignored for the first item)
	 * @param int The ID of the next item (should be NULL unless you are resuming a test run)
	 */
	function processAnswer($isCorrect, $nextItemId = NULL)
	{
		if ($this->stepNumber > 0)
		{
			$adaptiveItem = new AdaptiveTestItem();
			$adaptiveItem->loadState($this->remainingItems[$this->currentItemId]);
			$adaptiveItem->setAnswered($isCorrect);
			$adaptiveItem->saveState($this->processedItems[$this->currentItemId]);
			unset($this->remainingItems[$this->currentItemId]);
		}

		// Calculate L_n (Q_i) * W_i
		$productLW = array();
		$this->_calcProductLW($productLW);
		$this->_calcNewEstimate($productLW);
		$this->_calcSem($productLW);

		$this->_findNextItem($nextItemId);

		$this->stepNumber++;
	}

	/**
	 * Returns the ID of the current item
	 * @return int
	 */
	function getCurrentItemId()
	{
		return $this->currentItemId;
	}

	/**
	 * Checks whether the adaptive test session is considered finished
	 * @return boolean
	 */
	function isFinished()
	{
		
		$this->maxSem = $this->maxSem + 0.0;
		
		return $this->sem < $this->maxSem
			|| $this->stepNumber > $this->maxItemCount
			|| count($this->remainingItems) == 0
			|| $this->minInfoLimitCrossCount > $this->maxMinInfoLimitCrossCount;
	}


	/**
	 * @access private
	 */
	function _isLessMaxQuad($i){
		$quadraturePointArray = new QuadraturePointArray();
		$quadraturePointArray->loadState($this->quadraturePointArray);
		return $quadraturePointArray->isInArray($i);
	}

	/**
	 * @access private
	 */
	function _getQ($i){
		$quadraturePointArray = new QuadraturePointArray();
		$quadraturePointArray->loadState($this->quadraturePointArray);
		return $quadraturePointArray->getQ($i);
	}

	/**
	 * @access private
	 */
	function _getW($i){
		$quadraturePointArray = new QuadraturePointArray();
		$quadraturePointArray->loadState($this->quadraturePointArray);
		return $quadraturePointArray->getW($i);
	}

	/**
	 * @access private
	 */
	function _updateMinInfoLimitCrossCount($info)
	{
		if ($info > $this->minInfoLowerLimit) {
			$this->minInfoLimitCrossCount = 0;
		} else {
			$this->minInfoLimitCrossCount++;
		}
	}

	/**
	 * @access private
	 */
	function _findNextItem($nextItemId = NULL)
	{
		$adaptiveItem = new AdaptiveTestItem();

		if ($this->isFinished()) {
			$this->currentItemId = NULL;
			return;
		}

		if (isset($nextItemId))
		{
			$this->currentItemId = $nextItemId;

			$adaptiveItem->loadState($this->remainingItems[$this->currentItemId]);
			$this->_updateMinInfoLimitCrossCount($adaptiveItem->calcItemInformation($this->theta));

			return;
		}

		// Calculate item information for each item
		$itemInformation = array();
		foreach($this->remainingItems as $itemState) {
			$adaptiveItem->loadState($itemState);
			$itemInformation[$adaptiveItem->getId()] = $adaptiveItem->calcItemInformation($this->theta);
		}

		// Sort by item information, maintaining key-value association
		arsort($itemInformation);

		// Determine the index to use
		if ($this->randomizeAll || $this->stepNumber == 0) {
			$itemIndex = mt_rand(0, min($this->randomizerLimit, count($itemInformation))-1);
		} else {
			$itemIndex = 0;
		}

		// Reset and advance the pointer to the given index
		reset($itemInformation);
		for ($i = 0; $i < $itemIndex; $i++) {
			next($itemInformation);
		}

		// The array pointer is now set to the item to use
		$this->currentItemId = key($itemInformation);
		$this->_updateMinInfoLimitCrossCount(current($itemInformation));
	}


	/**
	 * @access private
	 */
	function _calcProductLW(&$productLW)
	{
		for ($i = 0; $this->_isLessMaxQuad($i); $i++) {
			$productLW[$i] = $this->_getW($i) * $this->calcProbabilityForQ($this->_getQ($i));
		}
	}

	/**
	 * @access private
	 */
	function _calcNewEstimate($productLW)
	{
		$result = 0;

		// Calculate numerator
		for ($i = 0; $this->_isLessMaxQuad($i); $i++) {
			$result += $this->_getQ($i) * $productLW[$i];
		}

		// Calculate demoninator and divide
		$result /= $this->_calcSumProductLW($productLW);

		$this->theta = $result;
	}

	/**
	 * @access private
	 */
	function _calcSem($productLW)
	{
		$result = 0.0;

		// Calculate sum in numerator
		for ($i = 0; $this->_isLessMaxQuad($i); $i++) {
			$result += pow($this->_getQ($i) - $this->theta, 2) * $productLW[$i];
		}

		// Calculate sum in denominator
		$result /= $this->_calcSumProductLW($productLW);

		// Take the square root
		$result = sqrt($result);

		$this->sem = $result;
	}

	/**
	 * @access private
	 */
	function _calcSumProductLW($productLW)
	{
		$result = 0.0;

		for ($i = 0; $this->_isLessMaxQuad($i); $i++) {
			$result += $productLW[$i];
		}

		return $result;
	}

	/**
	 * Calculate the propability that this test run took place with theta = Q
	 * @param double QuadraturePoint value
	 */
	function calcProbabilityForQ($q)
	{
		$result = 1.0;

		// Calculate the product of propabilities
		foreach ($this->processedItems as $itemState)
		{
			$adaptiveItem = new AdaptiveTestItem();
			$adaptiveItem->loadState($itemState);

			$p = $adaptiveItem->calcProbabilityOfCorrectness($q);

			if ($adaptiveItem->isCorrect()) {
				$result *= $p;
			} else {
				$result *= (1 - $p);
			}
		}

		return $result;
	}

	/**
	 * Debug function
	 */
	function display()
	{

		echo "<p><b>Processed:</b>";
		$adaptiveItem = new AdaptiveTestItem();

		foreach($this->processedItems as $itemState){
			$adaptiveItem->loadState($itemState);
			$adaptiveItem->display();
		}
		echo "\n<br/><b>Values</b>: Theta=",round($this->theta, 3)," SEM=",round($this->sem, 3)," StepNumber=",$this->stepNumber," CurrentItem=",$this->getCurrentItemId(),"\n";
	}

	/**
	 * Debug function
	 */
	function displayInit()
	{
		echo "MaxSem=",$this->maxSem," MaxItems=",$this->maxItemCount," Theta=",$this->theta," SEM=",$this->sem," StepNumber=",$this->stepNumber," CurrentItem=",$this->getCurrentItemId(),"\n";
		$quadraturePointArray = new QuadraturePointArray();
		$quadraturePointArray->loadState($this->quadraturePointArray);
		$quadraturePointArray->display();
		$this->display();
	}
}

/**
 * Manages the so-called quadrature points of the EAP model
 * @package Core
 */
class QuadraturePointArray extends ExternalStateObject
{
	/**#@+
	 * @access private
	 */
	var $points = array();
	/**#@-*/

	/**
	 * Initializes a quadrature point array
	 * Constructor replacement, see {@link ExternalStateObject}
	 * @param int Number of quadrature points to use
	 * @param double Minimum theta value
	 * @param double Theta width
	 */
	function init($pointCount, $thetaMin, $thetaWidth)
	{
		$step = $thetaWidth / ((double) $pointCount-1);

		$quadraturePoint = new QuadraturePoint();
		$this->points = array();
		for ($i = 0; $i < $pointCount; $i++)
		{
			$q = $thetaMin + ((double) $i) * $step;
			$quadraturePoint->init($q, $this->_calcNormalDistributionDensity($q));
			$quadraturePoint->saveState($this->points[$i]);
		}

		$this->_normalize();
	}

	/**
	 * Fits the weights of the QuadraturePoints to the discrete distribution
	 * @access private
	 */
	function _normalize()
	{
		$totalWeight = 0;
		for ($i = 0; $this->isInArray($i); $i++) {
			$totalWeight += $this->getW($i);
		}

		$quadraturePoint = new QuadraturePoint();
		for ($i = 0; $i < count($this->points); $i++) {
			$quadraturePoint->loadState($this->points[$i]);
			$quadraturePoint->normalizeQuadraturePointWeight($totalWeight);
		}
	}

	/**
	 * Returns whether $i is a valid array index
	 * @param int The index to check
	 * @return boolean
	 */
	function isInArray($i) {
		return $i < count($this->points);
	}

	/**
	 * Calls and returns {@link QuadraturePoint::getW()} of a certain quadrature point
	 * @param int The index of the quadrature point in question
	 */
	function getW($i)
	{
		$quadraturePoint = new QuadraturePoint();
		$quadraturePoint->loadState($this->points[$i]);
		return $quadraturePoint->getW();
	}

	/**
	 * Calls and returns {@link QuadraturePoint::getQ()} of a certain quadrature point
	 * @param int The index of the quadrature point in question
	 */
	function getQ($i)
	{
		$quadraturePoint = new QuadraturePoint();
		$quadraturePoint->loadState($this->points[$i]);
		return $quadraturePoint->getQ();
	}

	/**
	 * @access private
	 */
	function _calcNormalDistributionDensity($x, $mu = 0, $sigma = 1)
	{
		$result = 1 / ($sigma * sqrt(2*3.14));
		$exponent = pow($x - $mu, 2) / (2 * pow($sigma, 2));
		$result *= exp(-$exponent);

		return $result;
	}

	/**
	 * Debug function
	 */
	function display()
	{
		echo "<hr/><b>QuadraturePointArray Test with ",count($this->points)," points</b><br/>";

		$sum = 0;
		$quadraturePoint = new QuadraturePoint();
		for ($i = 0; $this->isInArray($i); $i++) {
			echo "<br>",$i," ";
			$quadraturePoint->loadState($this->points[$i]);
			$quadraturePoint->display();
			$sum = $sum + $this->getW($i);
		}
		echo "<br/><b>Gesamtsumme</b>=",$sum,"</p>";
	}
}

/**
 * Models a quadrature point
 * @package Core
 */
class QuadraturePoint extends ExternalStateObject
{
	/**
	 * Theta
	 * @var double
	 * @access private
	 */
	var $q;

	/**
	 * W
	 * @var double
	 * @access private
	 */
	var $w;

	/**
	 * Initializes an quadrature point
	 * Constructor replacement, see {@link ExternalStateObject}
	 * @param double Theta of the quadrature point
	 * @param double W of the quadrature point
	 */
	function init($q, $w){
		$this->q = $q;
		$this->w = $w;
	}

	/**
	 * Returns the Theta value
	 * @return double
	 */
	function getQ()
	{
		return $this->q;
	}

	/**
	 * Returns the W value
	 * @return double
	 */
	function getW()
	{
		return $this->w;
	}


	/**
	 * Normalizes W with the given divisor. Necessary because of discrete distribution
	 * @param double Divisor
	 */
	function normalizeQuadraturePointWeight($divisor)
	{
		$this->w /= $divisor;
	}

	/**
	 * Debug function
	 */
	function display()
	{
		echo "Q ",$this->q," W ",$this->getW();
	}
}

/**
 * Class to represent an item of an adaptive test
 * @package Core
 */
class AdaptiveTestItem extends ExternalStateObject
{
	/**#@+
	 * @access private
	 */
	var $id;

	var $a;
	var $b;
	var $c;

	var $isCorrect = NULL;
	var $isAnswered = FALSE;
	/**#@-*/

	/**
	 * Initializes an adaptive test item
	 * Constructor replacement, see {@link ExternalStateObject}
	 * @param double Discrimination
	 * @param double Difficulty
	 * @param double Guessing
	 */
	function init($id, $discrimination, $difficulty, $guessing)
	{
		$this->id = $id;

		$this->a = $discrimination;
		$this->b = $difficulty;
		$this->c = $guessing;
	}

	/**
	 * Returns the item ID
	 */
	function getId() {
		return $this->id;
	}

	/**
	 * Calculates the information of an item given theta
	 * @param double Theta
	 */
	function calcItemInformation($theta)
	{
		$p = $this->calcProbabilityOfCorrectness($theta);

		$quotient = pow(1 - $this->c, 2);
		$factor1 = pow($this->a, 2);
		$factor2 = (1-$p) / $p;
		$factor3 = pow($p - $this->c, 2);
		$result = pow (1.7, 2) * $factor1 * $factor2 * $factor3 / $quotient;

		return $result;
	}

	/**
	 * Calculates the probability that, given theta, the answer is correct
	 * @param double Theta
	 */
	function calcProbabilityOfCorrectness($theta)
	{
		// 3-PL model
		// Many statisticians are more familiar with the normal ogive, and prefer to work in probits.
		// The normal ogive and the logistic ogive are similar, and a conversion of 1.7 approximately aligns them.
		$quotient = 1 + exp(-1.7 * $this->a * ($theta - $this->b));
		$result = $this->c + (1 - $this->c) / $quotient;
		return $result;
	}

	/**
	 * Makes an unanswered item an answered item
	 */
	function setAnswered($isCorrect)
	{
		$this->isAnswered = TRUE;
		$this->isCorrect = $isCorrect;
	}

	/**
	 * Whether the answer was correct
	 */
	function isCorrect()
	{
		return $this->isCorrect;
	}

	/**
	 * Debug function
	 */
	function display()
	{
		echo " ItemID=",$this->getId()," ";
		if ($this->isAnswered) {
			echo " ",$this->isCorrect ? "true" : "false";
		}
	}
}

?>