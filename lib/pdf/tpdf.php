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


/* tpdf - Class for generating pdf documents in the testMaker */

define('FPDF_FONTPATH', EXTERNAL.'fpdf/font/');
require_once(EXTERNAL.'fpdf/fpdf.php');
require_once(EXTERNAL.'fpdf/fpdi.php');

define("TMP_DIRECTORY", $_SERVER['DOCUMENT_ROOT']."/tmp");


class tpdf {

	var $pdf;
	var $fontName = 'Helvetica';
	var $fontSize = 12.0;

	/**
	 * Constructor of TPDF
	 *
	 * Creates an new pdf file from a template file or creates a completely
	 * new one if no template filename is given.
	 */
	function tpdf($templateFile = '')
	{
		if($templateFile == '')
		{
			$this->pdf = new fpdf('p', 'pt', 'A4');
			$this->pdf->AddPage();
			$this->pdf->SetFont($this->fontName);
		} else
		{
			$this->pdf = new fpdi();
			$pageCount = $this->pdf->setSourceFile($templateFile);
			for($i = 1; $i<=$pageCount; ++$i)
			{
				$tpl = $this->pdf->ImportPage($i);
				$this->pdf->AddPage();
				$this->pdf->useTemplate($tpl);
			}
			$this->pdf->SetFont($this->fontName, '', $this->fontSize);
		}
	}

	/**
	 * Add a page to the pdf
	 */
	function addPage()
	{
		$this->pdf->AddPage();
	}

	/**
	 * Accept automatic page break, or not
	 */
	function acceptPageBreak()
	{
		$this->pdf->AcceptPageBreak();
	}

	/**
	 * Insert a line break
	 */
	function ln()
	{
		$this->pdf->Ln();
	}

	function setAutoPageBreak($auto, $margin)
	{
		$this->pdf->SetAutoPageBreak($auto, $margin);
	}

	function setMargins($left, $top)
	{
		$this->pdf->SetMargins($left, $top); 
	}

	/**
	 * Set the text color
	 *
	 * @param int Value for red: between 0 and 255
	 * @param int Value for green: between 0 and 255
	 * @param int Value for blue: between 0 and 255
	 */
	function setTextColor($r, $g, $b)
	{
		$this->pdf->setTextColor($r, $g, $b);
	}

	/**
	 * Set the fill color
	 *
	 * @param int Value for red: between 0 and 255
	 * @param int Value for green: between 0 and 255
	 * @param int Value for blue: between 0 and 255
	 */
	function setFillColor($r, $g, $b)
	{
		$this->pdf->setFillColor($r, $g, $b);
	}

	/**
	 * Draw an image to the pdf
	 */
	function drawImage($fileName, $x, $y, $w, $h, $type)
	{
		$this->pdf->Image($fileName, $x, $y, $w, $h, $type);
	}

	/**
	 * Write a textbox to the pdf.
	 *
	 * @param float X value of the upper left point of the box
	 * @param float Y value of the upper left point of the box
	 * @param float Width of the box
	 * @param String Text to print
	 * @param tinyInt 0 without border, 1 with border
	 * @param char Align of the text in the box (L=left,C=center,R=right,J=justification)
	 */
	function writeTextBox($x, $y, $width, $text, $border, $align = 'L')
	{
		$this->pdf->SetXY($x, $y);
		$this->pdf->MultiCell($width, 5, $text, $border, $align);
	}

	/**
	 * Write a textbox with a certain height to the pdf.
	 *
	 * @param float X value of the upper left point of the box
	 * @param float Y value of the upper left point of the box
	 * @param float Width of the box
	 * @param float Height of the box
	 * @param String Text to print
	 * @param tinyInt 0 without border, 1 with border
	 * @param char Align of the text in the box (L=left,C=center,R=right,J=justification)
	 */
	function writeExtendedTextBox($x, $y, $width, $height, $text, $border, $align = 'L')
	{
		$this->pdf->SetXY($x, $y);
		$this->pdf->MultiCell($width, $height, $text, $border, $align);
	}

	/**
	 * Set the font size
	 *
	 * @param float Size of the font
	 */
	function setFontSize($size)
	{
		$this->pdf->setFontSize($size);
	}

	/**
	 * Set font weight
	 *
	 * @param char Desired font weight
	 */
	function setFontWeight($weight)
	{
		if($weight == 'underline' || $weight == 'u' || $weight == 'U') $this->pdf->setFont($this->fontName, 'U', $this->fontSize);
		else if($weight == 'normal' || $weight == 'n' || $weight == 'N') $this->pdf->setFont($this->fontName, '', $this->fontSize);
		else if($weight == 'bold' || $weight == 'b' || $weight == 'B') $this->pdf->setFont($this->fontName, 'B', $this->fontSize);
		else if($weight == 'italic' || $weight == 'i' || $weight == 'I') $this->pdf->setFont($this->fontName, 'I', $this->fontSize);
	}

	/**
	 * Set the creator of the pdf
	 *
	 * @param String Name of the creator
	 */
	function setCreator($creator)
	{
		$this->pdf->SetCreator($creator);
	}

	/**
	 * Print a line
	 *
	 * @param float X value of the first point
	 * @param float Y value of the first point
	 * @param float X value of the second point
	 * @param float Y value or the second point
	 */
	function line($x1, $y1, $x2, $y2, $width, $col)
	{
		$this->pdf->SetLineWidth($width);
		$this->pdf->SetDrawColor($col[0], $col[1], $col[2]);
		$this->pdf->Line($x1, $y1, $x2, $y2);
	}
	
	function Code39($xpos, $ypos, $code, $baseline = 0.5, $height = 5){

		$wide = $baseline;
		$narrow = $baseline / 3 ; 
		$gap = $narrow;

		$barChar['0'] = 'nnnwwnwnn';
		$barChar['1'] = 'wnnwnnnnw';
		$barChar['2'] = 'nnwwnnnnw';
		$barChar['3'] = 'wnwwnnnnn';
		$barChar['4'] = 'nnnwwnnnw';
		$barChar['5'] = 'wnnwwnnnn';
		$barChar['6'] = 'nnwwwnnnn';
		$barChar['7'] = 'nnnwnnwnw';
		$barChar['8'] = 'wnnwnnwnn';
		$barChar['9'] = 'nnwwnnwnn';
		$barChar['A'] = 'wnnnnwnnw';
		$barChar['B'] = 'nnwnnwnnw';
		$barChar['C'] = 'wnwnnwnnn';
		$barChar['D'] = 'nnnnwwnnw';
		$barChar['E'] = 'wnnnwwnnn';
		$barChar['F'] = 'nnwnwwnnn';
		$barChar['G'] = 'nnnnnwwnw';
		$barChar['H'] = 'wnnnnwwnn';
		$barChar['I'] = 'nnwnnwwnn';
		$barChar['J'] = 'nnnnwwwnn';
		$barChar['K'] = 'wnnnnnnww';
		$barChar['L'] = 'nnwnnnnww';
		$barChar['M'] = 'wnwnnnnwn';
		$barChar['N'] = 'nnnnwnnww';
		$barChar['O'] = 'wnnnwnnwn'; 
		$barChar['P'] = 'nnwnwnnwn';
		$barChar['Q'] = 'nnnnnnwww';
		$barChar['R'] = 'wnnnnnwwn';
		$barChar['S'] = 'nnwnnnwwn';
		$barChar['T'] = 'nnnnwnwwn';
		$barChar['U'] = 'wwnnnnnnw';
		$barChar['V'] = 'nwwnnnnnw';
		$barChar['W'] = 'wwwnnnnnn';
		$barChar['X'] = 'nwnnwnnnw';
		$barChar['Y'] = 'wwnnwnnnn';
		$barChar['Z'] = 'nwwnwnnnn';
		$barChar['-'] = 'nwnnnnwnw';
		$barChar['.'] = 'wwnnnnwnn';
		$barChar[' '] = 'nwwnnnwnn';
		$barChar['*'] = 'nwnnwnwnn';
		$barChar['$'] = 'nwnwnwnnn';
		$barChar['/'] = 'nwnwnnnwn';
		$barChar['+'] = 'nwnnnwnwn';
		$barChar['%'] = 'nnnwnwnwn';

		$this->pdf->SetFont('Arial', '', 10);
		$this->pdf->Text($xpos, $ypos + $height + 4, $code);
		$this->pdf->SetFillColor(0);

		$code = '*'.strtoupper($code).'*';
		for($i=0; $i<strlen($code); $i++){
			$char = $code{$i};
			if(!isset($barChar[$char])){
				$this->Error('Invalid character in barcode: '.$char);
			}
			$seq = $barChar[$char];
			for($bar=0; $bar<9; $bar++){
				if($seq{$bar} == 'n'){
					$lineWidth = $narrow;
				}else{
					$lineWidth = $wide;
				}
				if($bar % 2 == 0){
					$this->pdf->Rect($xpos, $ypos, $lineWidth, $height, 'F');
				}
				$xpos += $lineWidth;
			}
			$xpos += $gap;
		}
    }

	/**
	 * Output the pdf file
	 */
	function output()
	{
		header('Expires: Mon, 14 Oct 2002 05:00:00 GMT'); // Date in the past
		header('Last-Modified: ' .gmdate("D, d M Y H:i:s") .' GMT'); // always modified
		header('Cache-Control: no-store, no-cache, must-revalidate'); // HTTP 1.1
		header('Cache-Control: post-check=0, pre-check=0', false);
		//header('Content-type: application/pdf'); // Mime-type pdf
		header('Pragma: no-cache'); // HTTP 1.0

		$this->pdf->Output("cert.pdf", "D");
		$this->pdf->closeParsers();
		$this->pdf->Close();
	}
	
	/**
	 * Return the pdf as a string.
	 */
	function outputString()
	{
		header('Expires: Mon, 14 Oct 2002 05:00:00 GMT'); // Date in the past
		header('Last-Modified: ' .gmdate("D, d M Y H:i:s") .' GMT'); // always modified
		header('Cache-Control: no-store, no-cache, must-revalidate'); // HTTP 1.1
		header('Cache-Control: post-check=0, pre-check=0', false);
		//header('Content-type: application/pdf'); // Mime-type pdf
		header('Pragma: no-cache'); // HTTP 1.0

		$stringPdf = $this->pdf->Output("", "S");
		
		return $stringPdf;
	}

	/**
	 * Close the pdf object
	 */
	function close()
	{
		$this->pdf->Close();
	}
}
